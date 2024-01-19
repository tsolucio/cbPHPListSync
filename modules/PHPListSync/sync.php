<?php
############################################################
# This is the sync script used by the vtigerphplistsync.   #
#                                                          #
# License GPL 3                                            #
# Author Giuseppe Rota    <g.rota@studiosynthesis.biz>     #
# Author Thomas Mathiesen <thomas@linprofs.com>            #
############################################################

################################ PHPLIST FUNCTIONS ##################################

// Add new user to newsletter
function AddNewUser($emailaddress, $listid) {
	$db = phplist_connectDB();
	mysqli_query($db, 'INSERT INTO phplist_user_user (email, confirmed, blacklisted, htmlemail) VALUES (\'' . $emailaddress . '\', \'1\', \'0\', \'1\')');
	logToFile("AddNewUser Email $emailaddress added to newsletter.");
	$result = mysqli_query($db, 'SELECT id,email FROM phplist_user_user WHERE (email=\'' . $emailaddress . '\')'); #Get new userid
	$row = mysqli_fetch_row($result); #Let us see what we got...
	$newuserid = $row[0];
	// syncAttributes($emailaddress,$newuserid,false,$db); // Only seems to do Contacts ? Don't really need this.
	logToFile("AddNewUser PHPList userid created for $emailaddress (userid is $newuserid)");
	mysqli_query($db, 'INSERT INTO phplist_listuser (userid, listid) VALUES (\'' . $newuserid . '\', \'' . $listid . '\')');
	logToFile("AddNewUser Email $emailaddress added to list $listid.");
}

// Add exiting user to newsletter
function AddExistingUser($emailaddress, $listid) {
	$db = phplist_connectDB();
	$result = mysqli_query($db, 'SELECT id,email FROM phplist_user_user WHERE (email=\'' . $emailaddress . '\')'); #Get userid
	$row = mysqli_fetch_row($result); #Let us see what we got...
	$newuserid = $row[0];
	syncAttributes($emailaddress, $newuserid, true, $db);
	logToFile("AddExistingUser Userid for $emailaddress is $newuserid");
	mysqli_query($db, 'INSERT INTO phplist_listuser (userid, listid) VALUES (\'' . $newuserid . '\', \'' . $listid . '\')');
	logToFile("AddExistingUser Email $emailaddress added to list $listid.");
}

//Only syncs Contacts ? Not really required for us
function syncAttributes($email, $phpluser, $update, $db) {
	global $configfilelinenumber, $cache_dir, $adb;
	$ctors = $adb->pquery(
		'SELECT salutation,firstname,lastname
			FROM vtiger_contactdetails INNER JOIN vtiger_contactscf on vtiger_contactdetails.contactid=vtiger_contactscf.contactid WHERE email=?',
		array($email)
	);
	logToFile('syncAttributes running');
	if ($adb->num_rows($ctors) == 1 && !empty($phpluser)) { // we have a contact
		$cto = $adb->fetch_array($ctors);
		$firstname = $cto['firstname'];
		$lastname = $cto['lastname'];
		$greeting = $cto['salutation'];
		if ($update) {
			mysqli_query($db, "UPDATE phplist_user_user_attribute set value='$firstname' WHERE attributeid='3' and userid=$phpluser");
			mysqli_query($db, "UPDATE phplist_user_user_attribute set value='$lastname' WHERE attributeid='4' and userid=$phpluser");
			mysqli_query($db, "UPDATE phplist_user_user_attribute set value='$greeting' WHERE attributeid='2' and userid=$phpluser");
			//mysqli_query("UPDATE phplist_user_user_attribute set value='$greeting_f' WHERE attributeid='1' and userid=$phpluser",$db);
			//mysqli_query("UPDATE phplist_user_user_attribute set value='$greeting_ger' WHERE attributeid='6' and userid=$phpluser",$db);
			//mysqli_query("UPDATE phplist_user_user_attribute set value='$greeting_ger_f' WHERE attributeid='5' and userid=$phpluser",$db);
		} else {
			mysqli_query($db, "INSERT INTO phplist_user_user_attribute (attributeid, userid, value) VALUES ('3', $phpluser, '$firstname')");
			mysqli_query($db, "INSERT INTO phplist_user_user_attribute (attributeid, userid, value) VALUES ('4', $phpluser, '$lastname')");
			mysqli_query($db, "INSERT INTO phplist_user_user_attribute (attributeid, userid, value) VALUES ('2', $phpluser, '$greeting')");
			//mysqli_query("INSERT INTO phplist_user_user_attribute (attributeid, userid, value) VALUES ('1', $phpluser, '$greeting_f')",$db);
			//mysqli_query("INSERT INTO phplist_user_user_attribute (attributeid, userid, value) VALUES ('6', $phpluser, '$greeting_ger')",$db);
			//mysqli_query("INSERT INTO phplist_user_user_attribute (attributeid, userid, value) VALUES ('5', $phpluser, '$greeting_ger_f')",$db);
		}
	}
}

// Sync phplist with the list of email addresses we found
function phplist_SyncWithvtigerSubscribingUsersFile($file, $listid) {
	$db = phplist_connectDB();
	$filetoread = @file($file);
	$entry = array_reverse($filetoread);
	logToFile("SyncWithvtigerSubscribingUsers Reading $file, to check if users are in newsletter.");
	$myCount = 0;
	foreach ($filetoread as $thisentry) { #For each entry in cache file
		$myCount++;
		$strippedstring = str_replace(array("\r\n", "\r", "\n"), '', $thisentry); #Strip line break in string

		// Is user already in newsletter and in the right list?
		$result = mysqli_query(
			$db,
			'SELECT phplist_user_user.id,phplist_user_user.email,phplist_listuser.userid,phplist_listuser.listid
				FROM phplist_user_user, phplist_listuser
				WHERE (phplist_user_user.email=\''.$strippedstring.'\') AND (phplist_user_user.id=phplist_listuser.userid) AND (phplist_listuser.listid='.$listid.')'
		);

		$row = mysqli_fetch_row($result); #Did the query return false?
		if ($row[0] == '') { #If not, the user is either not in the newsletter, or at least not in the right list
			logToFile("SyncWithvtigerSubscribingUsers Email address $strippedstring is not in newsletter and/or the right list.");

			// Is the user in the newsletter system at all?
			$result = mysqli_query(
				$db,
				'SELECT phplist_user_user.id,phplist_user_user.email FROM phplist_user_user WHERE (phplist_user_user.email=\'' . $strippedstring . '\')'
			);
			$row = mysqli_fetch_row($result); #Did the query return false?

			if ($row[0] == '') { #If nothing found, the user is not in the system at all.. let us add him/her..
				logToFile("SyncWithvtigerSubscribingUsers Email $strippedstring is not in newsletter at all. I am adding this email.");
				AddNewUser($strippedstring, $listid);
			} else { #If the query returned anything, a user is in system, but not the right list.. let us fix that
				AddExistingUser($strippedstring, $listid);
				logToFile("SyncWithvtigerSubscribingUsers Email $strippedstring is in the newsletter system, but not the right list ($listid). Correcting this now.");
			}
		} else {
			logToFile("SyncWithvtigerSubscribingUsers Email $strippedstring is in the right list ($listid),all good.");
		}
	}
	unlink($file); #Delete cache file
	logToFile("SyncWithvtigerSubscribingUsers Removed cache file $file");
	echo '<div style="padding-left:10px;"> Count :' . $myCount . '</div>';
}

//########################################### OTHER FUNCTIONS #########################################################

// Get phplist number and vtiger view number from config file
function GetConfigFromConfigFile($configfilelinenumber) {
	global $cache_dir;
	$filetoread = @file($cache_dir . '/sync.conf');
	$entry = array_reverse($filetoread);
	$i = 1;
	foreach ($filetoread as $thisentry) { #Count line number and find correct config line
		if ($i == $configfilelinenumber) {
			//$strippedstring = str_replace(array("\r\n", "\r", "\n"), '', $thisentry); #Strip line break in string
			return $thisentry;
		}
		$i++;
	}
}

// Explode and return the phplistid or vtigerviewid
function ExplodeConfig($configuration, $whatdoyouwant) {
	$config = explode(',', $configuration); #Explode the string, to look the two values (x,y)
	if ($whatdoyouwant == 'phplistid') { #Do we need the phplistid or vtigerviewid
		return $config[0];
	} else {
		return $config[1];
	}
}

// Remove all users from this specific list... so it adds all users every time it runs
function CleanPHPList_List($phplistid) {
	$db = phplist_connectDB();
	$result = mysqli_query($db, 'DELETE FROM phplist_listuser WHERE listid=\'' . $phplistid . '\'');
	if (!$result) {
		die(mysqli_error($db));
	}
}

// Get the actual data from vtiger
function vtiger_GetData($cvid) {
	global $configfilelinenumber, $cache_dir, $adb;
	$sqlstring = vtiger_GetSQLString($cvid); #Get the sqlstring using the function to parse some stuff
	if (!$sqlstring) {
		die('Error found');
	}
	$result = $adb->pquery($sqlstring, array());
	if ($adb->num_rows($result)) { #If the query returned anything..
		$myFile = $cache_dir . "/cache-$configfilelinenumber"; #Write cache file
		$fh = fopen($myFile, 'a');
		if (!$fh) {
			die('Error found');
		}
		while ($myrow = $adb->fetchByAssoc($result)) { #For each entry in mysql result
			foreach ($myrow as $key => $value) {
				$stringData = $value . "\n";
				if ($stringData != "\n") {
					fwrite($fh, $stringData);
					logToFile('Added ' . $value . ' as subscribing user to this text file.');
				}
			}
		}
	} else {
		echo 'Sorry, but there were no records found';
	}
}

// Get the SQL string that we need to find our data
function vtiger_GetSQLString($cvid) {
	global $adb;

	require_once 'modules/CustomView/CustomView.php';
	$etres = $adb->pquery('SELECT entitytype FROM vtiger_customview WHERE cvid=?', array($cvid));
	$entitytype = $adb->query_result($etres, 0, 0);
	if (!$entitytype) {
		return null;
	}
	$cv = new CustomView($entitytype);
	$listquery = getListQuery($entitytype);
	$listquery = $cv->getModifiedCvListQuery($cvid, $listquery, $entitytype);
	$listquery = substr($listquery, strpos($listquery, 'FROM'), strlen($listquery));
	// Here we decide which fields we want
	// Note that it relies on the query in vtiger checking for optout/removes so we only need the email fields
	// Vendors have no optout - doen't really matter as we rarely use this and it is only for notifications - say phones off
	if ($entitytype == 'Contacts') {
		$listquery = 'SELECT vtiger_contactdetails.email, vtiger_contactdetails.secondaryemail, vtiger_contactscf.cf_646, vtiger_contactscf.cf_884 ' . $listquery;
	} else if ($entitytype == 'Leads') {
		// cf_xxx is opt out
		$listquery = 'SELECT vtiger_leaddetails.email, vtiger_leaddetails.secondaryemail, vtiger_leadscf.cf_885 ' . $listquery;
	} else if ($entitytype == 'Accounts') {
		$listquery = 'SELECT vtiger_account.email1, vtiger_account.email2 ' . $listquery;
	} else if ($entitytype == 'Vendors') {
		$listquery = 'SELECT vtiger_vendor.email, vtiger_vendorcf.cf_672, vtiger_vendorcf.cf_674, vtiger_vendorcf.cf_801, vtiger_vendorcf.cf_882 ' . $listquery;
	} else {
		$listquery = 'SELECT email ' . $listquery;
	}
	return $listquery;
}

// Get list of email addresses from vtiger
function GetListofEmails($configfilelinenumber) {
	global $cache_dir;
	$configuration = GetConfigFromConfigFile($configfilelinenumber); #Get configuration (phplist number and vtiger view number)
	$phplistid = ExplodeConfig($configuration, 'phplistid'); #Get phplist id
	CleanPHPList_List($phplistid);
	$vtigerviewid = ExplodeConfig($configuration, 'vtigerviewid'); #Get vtiger view id
	vtiger_GetData($vtigerviewid); #Generate the cache file
	phplist_SyncWithvtigerSubscribingUsersFile($cache_dir . "/cache-$configfilelinenumber", $phplistid); #Sync phplist with cache file
}

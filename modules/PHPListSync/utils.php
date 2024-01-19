<?php
// Show help-page
function help() {
	global $mod_strings;
	echo '<div style="padding-left:10px;"><h2>' . $mod_strings['LBL_HELP'] . '</h2></div>';
	echo '<div style="padding-left:10px;"><h3>' . $mod_strings['LBL_ADDSYNC'] . '</h3>'
		. $mod_strings['LBL_HELPMESSAGE1'] . $mod_strings['LBL_RUNSYNCNOW'] . $mod_strings['LBL_HELPMESSAGE2'] .
		'<br><h3>' . $mod_strings['LBL_VIEWEDITSYNCSETUP'] . '</h3>'
		. $mod_strings['LBL_HELPMESSAGE3'] . $mod_strings['LBL_ADDSYNC'] . $mod_strings['LBL_HELPMESSAGE4'] .
		'<br><h3>' . $mod_strings['LBL_RUNSYNCNOW'] . '</h3>'
		. $mod_strings['LBL_HELPMESSAGE5'] . $mod_strings['LBL_ADDSYNC'] . $mod_strings['LBL_HELPMESSAGE6'] . '<br><br>'
		. $mod_strings['LBL_HELPMESSAGE7'];
	echo '</div>';
}

// Show edit form
function ShowEditForm() {
	ShowCurrentSync();
}

// Show a list of lists and views that are currently setup to sync.
function ShowCurrentSync() {
	global $mod_strings, $cache_dir;

	$lines = getSavedConfigs();
	$totalnumberoflines = count($lines);
	$i = 0;

	echo '<br><h2 style="padding-left:10px;">' . $mod_strings['LBL_FOLLOWINGLISTSETUPTOSYNC'] . ':</h2><br>'; #Print header of this page
	//    echo '<form method="post" action="index.php" name="removesync">'; #Form, so that we can delete entries
	echo '<table id="currentsyncs"><tr><td style="padding-left:10px;"><b>' . $mod_strings['LBL_VTIGERCRMVIEW'] . '</b></td><td></td><td><b>'
		.$mod_strings['LBL_PHPLISTLIST'] . '</b></td></tr>'; #Tables are handy

	while ($i < $totalnumberoflines) { #For each configuration set..
		debugthis("Line $i: " . $lines[$i] . '<br>');
		$infoweneed = explode(',', $lines[$i]); #Explode the string to find the phplistid and vtiger viewid
		$phplistname = phplist_GetListName($infoweneed[0]);
		$vtigerviewname = vtiger_GetListName($infoweneed[1]);
		echo '<tr><td style="padding-left:10px;">(' . $vtigerviewname[1] . ') - ' . $vtigerviewname[0]
			.'</td><td><img src="modules/PHPListSync/images/syncsmall.png"></td>
			<td>$phplistname</td><td><input name="removefilelinenumber" value="'.$i.'" type="radio"></td></tr>';
		$i++;
	}
	echo '</table>'; #Just closing the table
	//echo '<input name="com" value="removesync" type="hidden">'; #hidden field to send when we click "save"
	echo '<br><div style="padding-left:10px;"><input name="submit" value="' . $mod_strings['LBL_REMOVESELECTED'] . '" type="submit" id="removesyncbutton"></div>';
}

// Get phplist listname
function phplist_GetListName($listid) {
	global $table_prefix;
	$db = phplist_connectDB();
	$result = mysqli_query($db, 'SELECT * FROM ' . $table_prefix . "list WHERE id='" . $listid . "'");
	if (!$result) {
		die;
	}
	$myrow = mysqli_fetch_row($result);
	return $myrow[1] . ' (' . $myrow[2] . ')';
}

// Get vtiger view name
function vtiger_GetListName($listid) {
	global $adb;
	$r = $adb->pquery('SELECT viewname,entitytype FROM vtiger_customview WHERE cvid=?', array($listid));
	$myrow = array($adb->query_result($r, 0, 'viewname'), $adb->query_result($r, 0, 'entitytype'));
	return $myrow;
}

// Connect to phplist database
function phplist_connectDB() {
	global $database_host, $database_name, $database_user, $database_password;
	debugthis("\$db = mysqli_connect($database_host,$database_user,$database_password)");
	$db = mysqli_connect($database_host, $database_user, $database_password, $database_name);
	return $db;
}

function showSetup($currentvalue) {
	global $mod_strings;
	echo '<div style="padding-left:10px;"><h2>' . $mod_strings['LBL_SETUP'] . '</h2></div>';
	echo '<div style="padding-left:10px;">' . $mod_strings['LBL_SETUPLABEL1'] . '</div><br />';
	echo '<div style="padding-left:10px;"><input type="text" size="100" maxlength="350" id="phplist_config_file" value="' . $currentvalue . '"></div><br>';
	echo '<div style="padding-left:10px;"><input type="button" id="savesetupbutton" value="' . $mod_strings['LBL_SAVESETUP'] . '"></div>';
}

// Show the form to setup new sync
function ShowForm() {
	global $mod_strings;
	//    echo '<form method="post" action="index.php" name="setup">';
	echo '<div style="padding-left:10px;"><h2>' . $mod_strings['LBL_ADDSYNC'] . '</h2></div>';
	echo '<p style="padding-left:10px;">' . $mod_strings['LBL_SETUPPERMANENTSYNC'] . ': ';
	vtiger_showViews();
	echo '  ' . $mod_strings['LBL_WITHFOLLOWINGNSLIST'] . '  ';
	phplist_showLists(); #Show
	echo '</p>';
	//    echo '<input name="com" value="savesync" type="hidden">'; #hidden field to send when we click "save"
	echo '<br><div  style="padding-left:10px;"><input name="submit" value="Save sync" type="button" id="savesyncbutton"></div>'; //TODO login missing here
}

// Show phplist lists
function phplist_showLists() {
	global $table_prefix;
	$db = phplist_connectDB();
	$result = mysqli_query($db, 'SELECT * FROM ' . $table_prefix . 'list');
	if (!$result) {
		die;
	}
	echo '<select name="NewsletterListID" id="NewsletterListID">'; #Create dropdown
	while ($myrow = mysqli_fetch_row($result)) { #For each entry in mysql result
		echo '<option value="' . $myrow[0] . '">' . $myrow[1] . ' - ' . $myrow[2] . '</option>';
	}
	echo '</select>'; #Close dropdown
}

// Show views
function vtiger_showViews() {
	global $adb;
	$result = $adb->pquery(
		"SELECT vtiger_customview.cvid, viewname, entitytype FROM vtiger_customview WHERE entitytype in ('Contacts','Leads','Accounts','Vendors') ORDER BY entitytype",
		array()
	);
	echo '<select name="CustomViewID" id="CustomViewID">'; #Create dropdown
	while ($myrow = $adb->fetchByAssoc($result)) { #For each entry in mysql result
		echo '<option value="' . $myrow['cvid'] . '">(' . $myrow['entitytype'] . ') - ' . $myrow['viewname'] . '</option>';
	}
	echo '</select>'; #Close dropdown
}

// Add the two id's to the sync setup file (for permanent sync)
function AddToSyncList($newsletterid, $crmviewid) {
	global $mod_strings, $cache_dir;
	#A special thank you to www.tizag.com
	debugthis("Saving the file.. got this info: $newsletterid,$crmviewid");
	$myFile = $cache_dir . 'sync.conf';
	$fh = fopen($myFile, 'a') || die($mod_strings['LBL_ERRORMESSAGE1'] . ' ' . $myFile);
	$stringData = "$newsletterid,$crmviewid\n";
	fwrite($fh, $stringData);
	fclose($fh);
	debugthis("Saved file $myFile");
	echo '<div  style="padding-left:10px;">' . $mod_strings['LBL_SYNCISSAVED'] . '</div>';
}

// Removes a line in the sync config file
function removesyncconfigfilelinenumber($linenumber) {
	global $cache_dir;
	$fc = file($cache_dir . '/sync.conf'); #Open the file for reading
	$f  = fopen($cache_dir . '/sync.conf', 'w'); #Open the file for writing
	$i = 0; #Our counter
	foreach ($fc as $line) { #For each line in fc..
		if ($i != $linenumber) { #Check if it's the right line number
			fputs($f, $line); #If not, then write it back to the file.
		}
		$i++;
	}
}

// Link to sync
function ShowStartSyncPage() {
	global $mod_strings, $cache_dir;
	$lines = getSavedConfigs();
	$totalnumberoflines = count($lines);
	$i = 0;

	echo '<br><h2 style="padding-left:10px;">' . $mod_strings['LBL_PLEASECHOOSELIST'] . ':</h2><br>'; #Print header of this page
	echo '<table id="currentsyncs\"><tr><td style="padding-left:10px;"><b>View</b></td><td></td><td><b>PHPlist list</b></td></tr>'; #Tables are handy

	while ($i < $totalnumberoflines) { #For each configuration set..
		$infoweneed = explode(',', $lines[$i]); #Explode the string to find the phplistid and vtiger viewid
		$phplistname = phplist_GetListName($infoweneed[0]);
		$vtigerviewname = vtiger_GetListName($infoweneed[1]);
		$x = $i + 1; #Just correcting (bug)
		echo '<tr><td  style="padding-left:10px;">(' . $vtigerviewname[1] . ') - ' . $vtigerviewname[0]
			.'</td><td><img src="modules/PHPListSync/images/syncsmall.png"></td><td>'
			.$phplistname.'</td><td><input name="configid" value="'.$x.'" type="radio"></td></tr>';
		$i++;
	}
	echo '</table>'; #Just closing the table
	echo '<br><div  style="padding-left:10px;"> <input name="submit" value="' . $mod_strings['LBL_SYNCSELECTEDNOW'] . '" type="submit" id="syncnowbutton"> </div>';
}

###### phplist settings (vtiger table installed via module) ######
function get_phplist_settings() {
	global $adb;
	$result = $adb->pquery("SELECT option_value FROM vtiger_phplistoption WHERE option_name='phplist_config_file'", array());
	if ($adb->num_rows($result)) {
		return $adb->query_result($result, 0, 'option_value');
	}
	return false;
}

//only one settings for now, really
function save_phplist_settings($phplist_config_file) {
	global $adb;
	$adb->query("DELETE FROM vtiger_phplistoption WHERE option_name='phplist_config_file'");
	$adb->query("INSERT INTO vtiger_phplistoption (option_name,option_value) VALUES ('phplist_config_file','" . $phplist_config_file . "')");
}

function getSavedConfigs() {
	global $mod_strings, $cache_dir;
	$handle = @fopen($cache_dir . '/sync.conf', 'r');
	if ($handle) {
		while (!feof($handle)) {
			$lines[] = fgets($handle, 4096);
		}
		fclose($handle);
	} else {
		echo '<br><h2>' . $mod_strings['LBL_FILENOTFOUND'] . " $cache_dir/sync.conf:</h2><br>";
		return;
	}
	// Remove the end empty line
	$newLines = array_filter($lines);
	return $newLines;
}

// Log to file (great for debugging)
function logToFile($msg) {
	global $logfile, $cache_dir, $debugsync; #Get logfile location and name from index.php
	if ($debugsync == '1') {
		$fd = fopen($cache_dir . "/cache-$logfile", 'a'); #open file
		$str = '[' . date('Y/m/d h:i:s') . '] ' . $msg; #append to file
		fwrite($fd, $str . "\n");
		fclose($fd); #close file
	}
}

// Just printing some debug info
function debugthis($string) {
	global $debugsync;
	if ($debugsync == '1') {
		echo "<br><b>debug:</b> $string<br>";
	}
}

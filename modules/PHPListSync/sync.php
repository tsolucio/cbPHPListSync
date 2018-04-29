<?php
############################################################
# This is the sync script used by the vtigerphplistsync.   #
#                                                          #
# License GPL 3                                            #
# Author Giuseppe Rota    <g.rota@studiosynthesis.biz>     #
# Author Thomas Mathiesen <thomas@linprofs.com>            #
############################################################

# YOU ARE NOT SUPPOSED TO CHANGE ANYTHING IN THIS FILE TO HAVE THE SYNC WORK

################################ PHPLIST FUNCTIONS ##################################

function AddNewUser($emailaddress,$listid) { #Add new user to newsletter
    $db = phplist_connectDB();
    mysql_query('INSERT INTO phplist_user_user (email, confirmed, blacklisted, htmlemail) VALUES (\''.$emailaddress.'\', \'1\', \'0\', \'1\')',$db);
//    logToFile("Email $emailaddress added to newsletter.");
    $result = mysql_query('SELECT id,email FROM phplist_user_user WHERE (email=\''.$emailaddress.'\')',$db); #Get new userid
    $row = mysql_fetch_row($result); #Let us see what we got...
    $newuserid=$row[0];
//    logToFile("PHPList userid created for $emailaddress (userid is $newuserid)");
    mysql_query('INSERT INTO phplist_listuser (userid, listid) VALUES (\''.$newuserid.'\', \''.$listid.'\')',$db);
//    logToFile("Email $emailaddress added to list $listid.");
}

function AddExistingUser($emailaddress,$listid) { #Add exiting user to newsletter
    $db = phplist_connectDB();
    $result = mysql_query('SELECT id,email FROM phplist_user_user WHERE (email=\''.$emailaddress.'\')',$db); #Get userid
    $row = mysql_fetch_row($result); #Let us see what we got...
    $newuserid=$row[0];
//    logToFile("Userid for $emailaddress is $newuserid");
    mysql_query('INSERT INTO phplist_listuser (userid, listid) VALUES (\''.$newuserid.'\', \''.$listid.'\')',$db);
//    logToFile("Email $emailaddress added to list $listid.");
}

function phplist_SyncWithvtigerSubscribingUsersFile($file,$listid) { #Sync phplist with the list of email addresses we found
    $db = phplist_connectDB();
    $filetoread = @file($file);
    $entry = array_reverse($filetoread);
//    logToFile("Reading $file, to check if users are in newsletter.");

    foreach ($filetoread as $thisentry) { #For each entry in cache file...
        $strippedstring = str_replace(array("\r\n", "\r", "\n"), "", $thisentry); #Strip line break in string

        $result = mysql_query('SELECT phplist_user_user.id,phplist_user_user.email,phplist_listuser.userid,phplist_listuser.listid FROM phplist_user_user, phplist_listuser WHERE (phplist_user_user.email=\''.$strippedstring.'\') AND (phplist_user_user.id=phplist_listuser.userid) AND (phplist_listuser.listid='.$listid.')',$db); #Is user already in newsletter and in the right list?

        $row = mysql_fetch_row($result); #Did the query return false?
        if ( $row[0] == "" ) { #If not, the user is either not in the newsletter, or at least not in the right list
//            logToFile("Email address $strippedstring is not in newsletter and/or the right list.");

            $result = mysql_query('SELECT phplist_user_user.id,phplist_user_user.email FROM phplist_user_user WHERE (phplist_user_user.email=\''.$strippedstring.'\')'); #Is the user in the newsletter system at all?
            $row = mysql_fetch_row($result); #Did the query return false?

            if ( $row[0] == "" ) { #If nothing found, the user is not in the system at all.. let us add him/her..
//                logToFile("Email $strippedstring is not in newsletter at all. I am adding this email.");
                AddNewUser($strippedstring,$listid);

            } else { #If the query returned anything, a user is in system, but not the right list.. let us fix that
                AddExistingUser($strippedstring,$listid);
//                logToFile("Email $strippedstring is in the newsletter system, but not the right list ($listid). I am correcting this now.");
            }

        } else {
//            logToFile("Email $strippedstring is in the right list ($listid),all good.");
        }
    }
    unlink($file); #Delete cache file
//    logToFile("Removed cache file $file");

}

//########################################### OTHER FUNCTIONS #########################################################

function GetConfigFromConfigFile($configfilelinenumber) { #Get phplist number and vtiger view number from config file
    global $cache_dir;
    $filetoread = @file($cache_dir.'/sync.conf');
    $entry = array_reverse($filetoread);
//    debugthis("Reading sync/sync.conf, to find configuration entry.");

    $i=1;
    foreach ($filetoread as $thisentry) { #Count line number and find correct config line
        if ($i == $configfilelinenumber) {
        //$strippedstring = str_replace(array("\r\n", "\r", "\n"), "", $thisentry); #Strip line break in string
            return $thisentry;
        }
        $i++;
    }
}

function ExplodeConfig($configuration,$whatdoyouwant) { #Explode and return the phplistid or vtigerviewid
	$config = explode (",", $configuration); #Explode the string, to look the two values (x,y)
	if ($whatdoyouwant == "phplistid") { #Do we need the phplistid or vtigerviewid
		return $config[0];
	} else {
		return $config[1];
	}
}

function CleanPHPList_List($phplistid) { #Remove all users from this specific list... so it adds all users every time it runs
    $db = phplist_connectDB();	
	mysql_query('DELETE FROM phplist_listuser WHERE listid=\''.$phplistid.'\'',$db) or die(mysql_error());
}

function vtiger_GetData($cvid) { #Get the actual data from vtiger
    global $configfilelinenumber, $cache_dir, $adb;
    $sqlstring = vtiger_GetSQLString($cvid); #Get the sqlstring using the function to parse some stuff
    //echo $sqlstring;
    if (!$sqlstring) {
        die("Error found");
    }
    $result = $adb->pquery($sqlstring, array());
    if ($adb->num_rows($result)) { #If the query returned anything..
        $myFile = $cache_dir."/cache-$configfilelinenumber"; #Write cache file
        $fh = fopen($myFile, 'a') or die("can't open file");
        while ($myrow = $adb->fetchByAssoc($result)) { #For each entry in mysql result
		    $stringData = $myrow['email']."\n";
            if ( $stringData != "\n" ) {
                $stringData = $myrow['email']."\n";
                fwrite($fh, $stringData);
//                logToFile("Added $myrow['email'] as subscribing user to this text file.");
        	}
	    }
    } else {
        echo "Sorry, but there was no records found";
    }
}

function vtiger_GetSQLString($cvid) { #Get the SQL string that we need to find our data
    global $adb;

    require_once('modules/CustomView/CustomView.php');
    $etres = $adb->pquery("SELECT entitytype FROM vtiger_customview WHERE cvid=?",array($cvid));
    $entitytype = $adb->query_result($etres, 0, 0);
    if (!$entitytype)
        return null;

    $cv = new CustomView($entitytype);
    $listquery = getListQuery($entitytype);
    $listquery = $cv->getModifiedCvListQuery($cvid,$listquery,$entitytype);
    $listquery = substr($listquery, strpos($listquery,'FROM'),strlen($listquery));
    if ($entitytype == 'Contacts')
        $listquery = "SELECT vtiger_contactdetails.email ".$listquery;
    else
        $listquery = "SELECT email ".$listquery;
    return $listquery;
}


function GetListofEmails($configfilelinenumber) { #Get list of email addresses from vtiger
    global $cache_dir;
	$configuration=GetConfigFromConfigFile($configfilelinenumber); #Get configuration (phplist number and vtiger view number)
//    debugthis("Configuration found for $configfilelinenumber: $configuration");
	$phplistid=ExplodeConfig($configuration,"phplistid"); #Get phplist id
//    debugthis("Found phplistid in configuration: $phplistid");
	CleanPHPList_List($phplistid);
//    debugthis("Removing all users in PHPList from list id: $phplistid");	
	$vtigerviewid=ExplodeConfig($configuration,"vtigerviewid"); #Get vtiger view id
//    debugthis("Found vtigerviewid in configuration: $vtigerviewid");
	vtiger_GetData($vtigerviewid); #Generate the cache file
	phplist_SyncWithvtigerSubscribingUsersFile($cache_dir."/cache-$configfilelinenumber",$phplistid); #Sync phplist with cache file
}

?>

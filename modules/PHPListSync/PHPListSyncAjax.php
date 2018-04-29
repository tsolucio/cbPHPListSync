<?php
set_include_path(get_include_path() . PATH_SEPARATOR .'../../');
require_once('utils.php');

global $mod_strings,$logfile,$debugsync;

$debugsync = 0;
$logfile = "phplistsync.log";

$whattodo = $_REQUEST['com'];

#now there's only one settings, really.
$phplistconfigfile = get_phplist_settings();// "/var/www/lists/config/config.php";
if ((!$phplistconfigfile || !is_readable($phplistconfigfile) || empty($phplistconfigfile)) && $whattodo != 'savesetup') {
    showSetup('');
    return;
}

if ($whattodo != 'savesetup')
    require_once($phplistconfigfile); #Include phplist config file to pickup connection info. 

switch ($whattodo) { # If whattodo is..
        case "add": # Let's add a sync
            ShowForm();
            break;
        case "edit": # Let's edit the setup
            ShowEditForm();
            break;
        case "savesync": # Let's save our sync setup
            $newsletterid = $_REQUEST['NewsletterListID'] ;
            $crmviewid = $_REQUEST['CustomViewID'] ;
            AddToSyncList($newsletterid,$crmviewid);
            break;
       case "removesync": # Let's remove some syncs
            $removelinenumber = $_REQUEST['removefilelinenumber']; #which line are we to remove
            removesyncconfigfilelinenumber($removelinenumber);
            ShowEditForm();
            break;
        case "sync": # Show sync page
            ShowStartSyncPage();
            break;
        case "syncnow": # Actual syncing happens here
            require_once('sync.php');
            $configfilelinenumber = $_REQUEST['configid']; #Get config file line number which we are to sync (phplist + vtiger view)
            if ($configfilelinenumber) { #Check if list is sent to script (sync.php?listid=xx)
                GetListofEmails($configfilelinenumber);
            	echo '<div style="padding-left:10px;">' . $mod_strings['LBL_SYNCOK'] . '</div>';
            } else { # No listid sent to this script..
            	echo '<div style="padding-left:10px;">' . $mod_strings['LBL_SYNCERROR'] . '</div>';
            }
            break;
        case "setup":
            showSetup($phplistconfigfile);
            break;
        case "savesetup":
            $phplist_config_file = $_REQUEST['phplist_config_file'];
            save_phplist_settings($phplist_config_file);
            break;
        case "help": # Show help page
            help();
            break;
}

?>


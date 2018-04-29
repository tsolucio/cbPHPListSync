<script type="text/javascript" src="modules/PHPListSync/jquery-1.4.1.js"></script>
<script type="text/javascript">
$(document).ready(function () {
  //this == document
  var jqcontent = $('#Ajaxcontent');

  var removebutton = $('#removesyncbutton',this);
  removebutton.live('click',function(click_ev) {
    click_ev.preventDefault();
    var selected_sync = $('#currentsyncs input:checked').val();
    jqcontent.text('');
    jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=removesync',{"removefilelinenumber": selected_sync});
  });

  var syncnownbutton = $('#syncnowbutton',this);
  syncnownbutton.live('click',function(click_ev) {
    click_ev.preventDefault();
    var selected_sync = $('#currentsyncs input:checked').val();
    jqcontent.text('');
    jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=syncnow',{"configid":selected_sync});
  });

  var savesetupbutton = $('#savesetupbutton',this);
  savesetupbutton.live('click',function(click_ev) {
    click_ev.preventDefault();
    var phplist_config_file = $('#phplist_config_file').val();
    if (phplist_config_file !== '') {
      jqcontent.text('');
      jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=savesetup',{"phplist_config_file":phplist_config_file});
    }// else { console.log('empty value not accepted'); }
  });

  jqcontent.text('');

  $('#helplink').click(function(ev){
    jqcontent.text('');
    jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=help');
    ev.preventDefault();
  });


  $('#vieweditlink').click(function(ev){
    jqcontent.text('');
    jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=edit', function() {
    });
    ev.preventDefault();
  });


  $('#addsynclink').click(function(ev){
    jqcontent.text('');
    jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=add', function() {
//      console.log(this);
      var sb = $('#savesyncbutton',this);
  //    console.log(sb);
      sb.click(function(click_ev) {
        click_ev.preventDefault();
        var cvid = $('#CustomViewID option:selected').val();
	//console.log('cvid='+cvid);
        var nlid = $('#NewsletterListID option:selected').val();
	//console.log('nlid='+nlid);
        jqcontent.text('');
        jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=savesync', {"NewsletterListID": nlid, "CustomViewID":cvid}, function () {});
      });
    });
    ev.preventDefault();
  });

  $('#runnowlink').click( function(ev) {
    jqcontent.text('');
    jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=sync', function() {});
  });

  $('#setuplink').click( function(ev) {
    jqcontent.text('');
    jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=setup', function() {});
  });

});
</script>
<?php
global $log, $adb;

echo '<table><tr><td style="padding:10px;"><a href="http://forge.vtiger.com/projects/phplistsync/" target="_new"><img src="modules/PHPListSync/images/phpvtigersync.jpg" border=0></a>
        </td></tr>
        <tr><td style="padding:10px;">'.$mod_strings['LBL_PLEASECHOOSE'].' : 
        <a href="#" id="setuplink">'.$mod_strings['LBL_SETUP'].'</a> |
        <a href="#" id="addsynclink">'.$mod_strings['LBL_ADDSYNC'].'</a> | 
        <a href="#" id="vieweditlink">'.$mod_strings['LBL_VIEWEDITSYNCSETUP'].'</a> | 
        <a href="#" id="runnowlink">'.$mod_strings['LBL_RUNSYNCNOW'].'</a> |
        <a href="#" id="helplink">'.$mod_strings['LBL_HELP'].'</a>
        </td></tr></table>';
?>

<script type="text/javascript" src="include/jquery/jquery.js"></script>
<script type="text/javascript">
$(document).ready(function () {
	//this == document
	var jqcontent = $('#Ajaxcontent');

	var removebutton = $('#removesyncbutton', this);
	if (removebutton.length) {
		removebutton.live('click', function (click_ev) {
			click_ev.preventDefault();
			var selected_sync = $('#currentsyncs input:checked').val();
			jqcontent.text('');
			jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=removesync', {
				'removefilelinenumber': selected_sync
			});
		});
	}
	var syncnownbutton = $('#syncnowbutton', this);
	if (syncnownbutton.length) {
		syncnownbutton.live('click', function (click_ev) {
			click_ev.preventDefault();
			var selected_sync = $('#currentsyncs input:checked').val();
			jqcontent.text('');
			jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=syncnow', {
				'configid': selected_sync
			});
		});
	}
	var savesetupbutton = $('#savesetupbutton', this);
	if (savesetupbutton.length) {
		savesetupbutton.live('click', function (click_ev) {
			click_ev.preventDefault();
			var phplist_config_file = $('#phplist_config_file').val();
			if (phplist_config_file !== '') {
				jqcontent.text('');
				jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=savesetup', {
					'phplist_config_file': phplist_config_file
				});
			}
		});
	}
	jqcontent.text('');

	$('#helplink').click(function (ev) {
		jqcontent.text('');
		jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=help');
		ev.preventDefault();
	});

	$('#vieweditlink').click(function (ev) {
		jqcontent.text('');
		jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=edit', function() {});
		ev.preventDefault();
	});

	$('#addsynclink').click(function (ev) {
		jqcontent.text('');
		jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=add', function () {
			var sb = $('#savesyncbutton', this);
			sb.click(function(click_ev) {
				click_ev.preventDefault();
				var cvid = $('#CustomViewID option:selected').val();
				var nlid = $('#NewsletterListID option:selected').val();
				jqcontent.text('');
				jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=savesync', {
					'NewsletterListID': nlid,
					'CustomViewID': cvid
				}, function() {});
			});
		});
		ev.preventDefault();
	});

	$('#runnowlink').click(function (ev) {
		jqcontent.text('');
		jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=sync', function () {});
	});

	$('#setuplink').click(function (ev) {
		jqcontent.text('');
		jqcontent.load('index.php?module=PHPListSync&action=PHPListSyncAjax&com=setup', function () {});
	});
});
</script>
<?php
global $log, $adb;

echo '<table>
	<tr><td style="padding:10px;"><img src="modules/PHPListSync/images/phpvtigersync.jpg" border=0></td></tr>
	<tr><td style="padding:10px;">'
		.$mod_strings['LBL_PLEASECHOOSE'].' : <a href="#" id="setuplink">' . $mod_strings['LBL_SETUP']
		.'</a> | <a href="#" id="addsynclink">' . $mod_strings['LBL_ADDSYNC']
		.'</a> | <a href="#" id="vieweditlink">' . $mod_strings['LBL_VIEWEDITSYNCSETUP']
		.'</a> | <a href="#" id="runnowlink">' . $mod_strings['LBL_RUNSYNCNOW']
		.'</a> | <a href="#" id="helplink">' . $mod_strings['LBL_HELP']
		.'</a>
	</td></tr></table>';
?>
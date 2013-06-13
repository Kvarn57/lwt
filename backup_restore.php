<?php

/**************************************************************
"Learning with Texts" (LWT) is released into the Public Domain.
This applies worldwide.
In case this is not legally possible, any entity is granted the
right to use this work for any purpose, without any conditions, 
unless such conditions are required by law.

Developed by J. P. in 2011, 2012, 2013.
***************************************************************/

/**************************************************************
Call: backup_restore.php?....
      ... restore=xxx ... do restore 
      ... backup=xxx ... do backup 
      ... empty=xxx ... do truncate
Backup/Restore/Empty LWT Database
***************************************************************/

include "settings.inc.php";
include "connect.inc.php";
include "utilities.inc.php";

$message = '';

if ($tbpref == '') 
	$pref = "";
else
	$pref = substr($tbpref,0,-1) . "-";

// RESTORE

if (isset($_REQUEST['restore'])) {
	if ( isset($_FILES["thefile"]) && $_FILES["thefile"]["tmp_name"] != "" && $_FILES["thefile"]["error"] == 0 ) {
		$handle = gzopen ($_FILES["thefile"]["tmp_name"], "r");
		if ($handle === FALSE) {
			$message = "Error: Restore file could not be opened";
		} // $handle not OK
		else { // $handle OK
			$message = restore_file($handle, "Database");
		} // $handle OK
	} // restore file specified
	else {
		$message = "Error: No Restore file specified";
	}
} 

// BACKUP

elseif (isset($_REQUEST['backup'])) {
	$tables = array('archivedtexts', 'archtexttags', 'languages', 'sentences', 'settings', 'tags', 'tags2', 'textitems', 'texts', 'texttags', 'words', 'wordtags');
	$fname = "lwt-backup-" . $pref . date('Y-m-d-H-i-s') . ".sql.gz";
	$out = "-- " . $fname . "\n";
	foreach($tables as $table) { // foreach table
		$result = mysql_query('SELECT * FROM ' . $tbpref . $table);
		$num_fields = mysql_num_fields($result);
		$out .= "\nDROP TABLE IF EXISTS " . $table . ";\n";
		$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE ' . $tbpref . $table));
		$out .= str_replace($tbpref . $table, $table, str_replace("\n"," ",$row2[1])) . ";\n";
		if ($table !== 'sentences' && $table !== 'textitems') {
			while ($row = mysql_fetch_row($result)) { // foreach record
				$return = 'INSERT INTO ' . $table . ' VALUES(';
				for ($j=0; $j < $num_fields; $j++) { // foreach field
					if (isset($row[$j])) { 
						$return .= "'" . mysql_real_escape_string($row[$j]) . "'";
					} else { 
						$return .= 'NULL';
					}
					if ($j < ($num_fields-1)) $return .= ',';
				} // foreach field
				$out .= $return . ");\n";
			} // foreach record
		} // if
	} // foreach table
	header('Content-type: application/x-gzip');
	header("Content-disposition: attachment; filename=" . $fname);
	echo gzencode($out,9);
	exit();
}

// EMPTY

elseif (isset($_REQUEST['empty'])) {
	$dummy = runsql('TRUNCATE ' . $tbpref . 'archivedtexts','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'archtexttags','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'languages','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'sentences','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'tags','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'tags2','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'textitems','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'texts','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'texttags','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'words','');
	$dummy = runsql('TRUNCATE ' . $tbpref . 'wordtags', '');
	$dummy = runsql('DELETE FROM ' . $tbpref . 'settings where StKey = \'currenttext\'', '');
	optimizedb();
	$message = "Database content has been deleted (but settings have been kept)";
}
	
pagestart('Backup/Restore/Empty Database',true);

echo error_message_with_hide($message,1);

if ($tbpref == '') 
	$prefinfo = "(Default Table Set)";
else
	$prefinfo = "(Table Set: <i>" . tohtml(substr($tbpref,0,-1)) . "</i>)";

?>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure?');">
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 center">Backup</th>
<td class="td1">
<p class="smallgray2">
The database <i><?php echo tohtml($dbname); ?></i> <?php echo $prefinfo; ?> will be exported to a gzipped SQL file. Please keep this file in a safe place.<br />If necessary, you can recreate the database via the Restore function below.<br />Important: If the backup file is too large, the restore may not be possible (see limits below).</p>
<p class="right">&nbsp;<br /><input type="submit" name="backup" value="Download LWT Backup" /></p>
</td>
</tr>
<tr>
<th class="th1 center">Restore</th>
<td class="td1">
<p class="smallgray2">
The database <i><?php echo tohtml($dbname); ?></i> <?php echo $prefinfo; ?> will be replaced by the data in the specified backup file<br />(gzipped or normal SQL file, created above).<br /><b>Please be careful - the existent database will be overwritten!</b> <br />Important: If the backup file is too large, the restore may not be possible.<br />Upload limits (in bytes): <b>post_max_size = <?php echo ini_get('post_max_size'); ?> / upload_max_filesize = <?php echo ini_get('upload_max_filesize'); ?></b><br />
If needed, increase in "<?php echo tohtml(php_ini_loaded_file()); ?>" and restart server.</p>
<p><input name="thefile" type="file" /></p>
<p class="right">&nbsp;<br /><span class="red2">YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;</span> 
<input type="submit" name="restore" value="Restore from LWT Backup" /></p>
</td>
</tr>
<tr>
<th class="th1 center">Install<br />LWT<br />Demo</th>
<td class="td1">
<p class="smallgray2">
The database <i><?php echo tohtml($dbname); ?></i> <?php echo $prefinfo; ?> will be replaced by the LWT demo database.</p>
<p class="right">&nbsp;<br /> 
<input type="button" value="Install LWT Demo Database" onclick="location.href='install_demo.php';" />
</td>
</tr>
<tr>
<th class="th1 center">Empty<br />Database</th>
<td class="td1">
<p class="smallgray2">
Empty (= delete the contents of) all tables - except the Settings - of your database <i><?php echo tohtml($dbname); ?></i> <?php echo $prefinfo; ?>.</p>
<p class="right">&nbsp;<br /><span class="red2">YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;</span>
<input type="submit" name="empty" value="Empty LWT Database" />
</td>
</tr>
<tr>
<td class="td1 right" colspan="2"> 
<input type="button" value="&lt;&lt; Back" onclick="location.href='index.php';" /></td>
</tr>
</table>
</form>

<?php

pageend();

?>
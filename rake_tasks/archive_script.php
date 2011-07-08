<?php
echo "\n\n ENTERING: archive_script.php\n";

////
// cd $PHP_CODE/rake_tasks
// php archive_script.php

// my_time_stamp=`date +"%Y%m%d_%H%M%S"`
// php archive_script.php > /tmp/archive_script.log_$my_time_stamp

//include 'Archiver.php';



//////////////////////////////////////////////////////////////////////////////



////// Archiver

#$my_Archiver = new Archiver();
#$my_Archiver->get_database_connection_info();



//////////////////////////////////////////////////////////////////////////////


/***/
////// ArchiverHierarchyEntries

echo "\n\n REQUIRING: ArchiverHierarchyEntries.php\n";
require_once 'ArchiverHierarchyEntries.php';


echo "\n\n LAUNCHING: my_ArchiverHierarchyEntries\n";
$my_ArchiverHierarchyEntries = new ArchiverHierarchyEntries();
$my_ArchiverHierarchyEntries->get_database_connection_info();
$my_ArchiverHierarchyEntries->display_table_names_arr();

#$my_ArchiverHierarchyEntries->note_hierarchy_entries_rowcount_before();



echo "\n\n LEAVING: archive_script.php\n";
exit;
/***/


//////////////////////////////////////////////////////////////////////////////



////// ArchiverDataObjects

echo "\n\n REQUIRING: ArchiverDataObjects.php\n";
require_once 'ArchiverDataObjects.php';

echo "\n\n LAUNCHING: my_ArchiverDataObjects\n";
$my_ArchiverDataObjects = new ArchiverDataObjects();
$my_ArchiverDataObjects->get_database_connection_info();
$my_ArchiverDataObjects->display_table_names_arr();

#$my_ArchiverDataObjects->process_archivable_ids(20);
#$my_ArchiverDataObjects->get_database_connection_info();
#$my_ArchiverDataObjects->process_archivable_ids(3, FALSE);
#$my_ArchiverDataObjects->display_table_arr();


echo "\n\n LEAVING: archive_script.php\n";
exit;



//////////////////////////////////////////////////////////////////////////////



?>

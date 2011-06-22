<?php

////
// cd $PHP_CODE/rake_tasks
// php archive_script.php

// my_time_stamp=`date +"%Y%m%d_%H%M%S"`
// php archive_script.php > /tmp/archive_script.log_$my_time_stamp

include 'Archiver.php';
include 'ArchiverDataObjects.php';
include 'ArchiverHierarchyEntries.php';



////// Archiver

#$my_Archiver = new Archiver();
#$my_Archiver->get_database_connection_info();



////// ArchiverDataObjects

$my_ArchiverDataObjects = new ArchiverDataObjects();
$my_ArchiverDataObjects->init();
$my_ArchiverDataObjects->display_table_arr();
$my_ArchiverDataObjects->display_table_names_arr();

#$my_ArchiverDataObjects->get_database_connection_info();
#$my_ArchiverDataObjects->process_archivable_ids(3, FALSE);
#$my_ArchiverDataObjects->process_archivable_ids(200, TRUE);



////// ArchiverHierarchyEntries

#$my_ArchiverHierarchyEntries = new ArchiverHierarchyEntries();
#$my_ArchiverHierarchyEntries->init();


#$my_ArchiverHierarchyEntries->get_database_connection_info();

#$my_ArchiverHierarchyEntries->note_hierarchy_entries_rowcount_before();
#$my_ArchiverHierarchyEntries->process_archivable_ids(3, FALSE);
#$my_ArchiverHierarchyEntries->note_hierarchy_entries_rowcount_after();

?>

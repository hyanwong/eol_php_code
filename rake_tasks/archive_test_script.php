<?php

////
// cd $PHP_CODE/rake_tasks
// php archive_test_script.php
//
// my_time_stamp=`date +"%Y%m%d_%H%M%S"`
// php archive_test_script.php > /tmp/archive_script.log_$my_time_stamp

include 'Archiver.php';
include 'ArchiverDataObjects.php';
include 'ArchiverDataObjectsTester.php';
include 'ArchiverHierarchyEntries.php';
include 'ArchiverHierarchyEntriesTester.php';



////// ArchiverHierarchyEntriesTester

#$my_ArchiverHierarchyEntriesTester = new ArchiverHierarchyEntriesTester();
#$my_ArchiverHierarchyEntriesTester->initialize();
#$my_ArchiverHierarchyEntriesTester->test();



////// ArchiverDataObjectsTester

$my_ArchiverDataObjectsTester = new ArchiverDataObjectsTester();
$my_ArchiverDataObjectsTester->initialize();
$my_ArchiverDataObjectsTester->test();

exit;

#$my_ArchiverDataObjects->display_names_arr();
#$my_ArchiverDataObjects->display_table_names_arr();
#$my_ArchiverDataObjectsTester->test();



////// Archiver

#$my_Archiver = new Archiver();
#$my_Archiver->get_database_connection_info();



////// ArchiverDataObjects

#$my_ArchiverDataObjects = new ArchiverDataObjects();
#$my_ArchiverDataObjects->get_database_connection_info();
#$my_ArchiverDataObjects->process_archivable_ids(3, FALSE);
#$my_ArchiverDataObjects->process_archivable_ids(200, TRUE);




?>

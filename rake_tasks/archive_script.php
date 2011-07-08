<?php


#######################
# archive_script.php
# 
# This script is intended to run hourly on the production database master.
# It move rows from the primary tables: data_objects & hierarchy_entries tables
# into their archive tables: data_objects_archive & hierarchy_entries tables_archive
#
# In addition to archiving rows from the primary tables, this script also archives corresponding 
# rows in their Foreign Reference Tables (FRTs).
#
#######################



////// ArchiverDataObjects

require_once 'ArchiverDataObjects.php';


$my_ArchiverDataObjects = new ArchiverDataObjects(); // arg-TRUE to trace fn calls
# $my_ArchiverDataObjects->get_database_connection_info();
# $my_ArchiverDataObjects->display_table_names_arr();

echo "\n\n Archiving DataObjects\n";
// arg=number_of_rows_to_process. default=1000
$my_ArchiverDataObjects->process_archivable_ids(10000); 



//////////////////////////////////////////////////////////////////////////////



////// ArchiverHierarchyEntries

require_once 'ArchiverHierarchyEntries.php';


$my_ArchiverHierarchyEntries = new ArchiverHierarchyEntries(); // arg=TRUE to trace fn calls
# $my_ArchiverHierarchyEntries->get_database_connection_info();
# $my_ArchiverHierarchyEntries->display_table_names_arr();

echo "\n\n Archiving HierarchyEntries\n";
// arg=number_of_rows_to_process. default=1000
$my_ArchiverHierarchyEntries->process_archivable_ids(10000); 



//////////////////////////////////////////////////////////////////////////////
?>

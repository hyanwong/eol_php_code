<?php
// cd $PHP_CODE/rake_tasks/
// php -l ArchiverHierarchyEntries.php

include_once(dirname(__FILE__) . "/../config/environment.php");
include 'Archiver.php';



//////////////////////////////////////////////////////////////////////////////////////////
//
//   Primary Table: 
//   - hierarchy_entries
//
//    Foreign Reference tables:
//    - agents_hierarchy_entries
//    - curated_hierarchy_entry_relationships
//    - data_objects_hierarchy_entries
//    - harvest_events_hierarchy_entries
//    - hierarchy_entries_refs
//    - synonyms
//
// * random_hierarchy_images: delete rather than archive (this is a denormalized table)
//
//////////////////////////////////////////////////////////////////////////////////////////
//
// To launch the archive process:
//
//    // Instantiate an instance of ArchiverHierarchyEntries.
//    $this->my_archiver_hierarchy_entries = new ArchiverHierarchyEntries();
//
//    // Then invoke its process_archivable_ids() method.
//        $this->my_archiver_hierarchy_entries->process_archivable_ids(100);
//    where max_id_count is the maximum number of rows to archive in the primary table.
//
// 
//////////////////////////////////////////////////////////////////////////////////////////



class ArchiverHierarchyEntries extends Archiver implements populate_the_table_arr
{

private $primary_table   = "hierarchy_entries";



//////////////////////////////////////////////////////////////////////////////////////////



public function populate_table_arr()
{
if ($this->is_tracing) echo "\n\n ENTERING populate_table_arr()";

// Tables to be archived:
//   Primary Table: 
//   - hierarchy_entries
//
//    Foreign Reference tables:
//    - agents_hierarchy_entries
//    - curated_hierarchy_entry_relationships
//    - data_objects_hierarchy_entries
//    - harvest_events_hierarchy_entries
//    - hierarchy_entries_refs
//    - synonyms



    // Load info about the tables to be archived into the table_arr array:
    $idx=0;



////// Primary Table info:
    $col_name = "id";


    $this->table_arr[$idx++]=
    array("hierarchy_entries"                    , "hierarchy_entries_archive",
    $col_name);



////// Foreign Reference tables info:
    $col_name = "hierarchy_entry_id";


    $this->table_arr[$idx++]=
    array("agents_hierarchy_entries"             , "agents_hierarchy_entries_archive",
    $col_name);

    $this->table_arr[$idx++]=
    array("data_objects_hierarchy_entries"       , "data_objects_hierarchy_entries_archive",
    $col_name);

    $this->table_arr[$idx++]=
    array("harvest_events_hierarchy_entries"     , "harvest_events_hierarchy_entries_archive",
    $col_name);

    $this->table_arr[$idx++]=
    array("hierarchy_entries_refs"               , "hierarchy_entries_refs_archive",
    $col_name);

    $this->table_arr[$idx++]=
    array("synonyms"                             , "synonyms_archive",
    $col_name);



////// Table curated_hierarchy_entry_relationships doesn't fit the pattern.
////// It has two references to hierarchy_entries.id: hierarchy_entry_id_1 & hierarchy_entry_id_2

    $this->table_arr[$idx++]=
    array("curated_hierarchy_entry_relationships", "curated_hierarchy_entry_relationships_archive",
    "hierarchy_entry_id_1");


    $this->table_arr[$idx++]=
    array("curated_hierarchy_entry_relationships", "curated_hierarchy_entry_relationships_archive",
    "hierarchy_entry_id_2");



if ($this->is_tracing) echo "\n\n LEAVING populate_table_arr()";
} // end of populate_table_arr()



//////////////////////////////////////////////////////////////////////////////////////////



} // end of class ArchiverHierarchyEntries

?>

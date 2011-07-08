<?php

require_once 'Archiver.php';



//////////////////////////////////////////////////////////////////////////////////////////
//
// Archive these tables:
//
// PRIMARY TABLE: 
// - data_objects
//
// FOREIGN REFERENCE TABLES (FRTs):
// - agents_data_objects
// - audiences_data_objects
// - data_object_data_object_tags
// - data_objects_harvest_events
// - data_objects_hierarchy_entries
// - data_objects_info_items
// - data_objects_refs
// - data_objects_table_of_contents
// - data_objects_untrust_reasons
// - users_data_objects
// - users_data_objects_ratings
// - curator_data_object_logs
//
//////////////////////////////////////////////////////////////////////////////////////////
//
// To launch the archive process:
//
//    Instantiate an instance of ArchiverDataObjects.
//        $this->my_archiver_data_objects = new ArchiverDataObjects();
//
//    Then invoke its process_archivable_ids() method.
//        $this->my_archiver_data_objects->process_archivable_ids(max_id_count); // default=1000
//    where max_id_count is the maximum number of rows to archive in the primary table.
//
// 
//////////////////////////////////////////////////////////////////////////////////////////



class ArchiverDataObjects extends Archiver implements populate_the_table_arr
{



//////////////////////////////////////////////////////////////////////////////////////////



public function populate_table_arr()
{
if ($this->is_tracing) echo "\n\n ENTERING populate_table_arr()";

$this->primary_table   = "data_objects";

/*** Tables to be archived:

Base Table:
    data_objects

Foreign Reference Tables:
    agents_data_objects
    audiences_data_objects
    * data_object_data_object_tags
    data_objects_harvest_events
    data_objects_hierarchy_entries
    data_objects_info_items
    data_objects_refs
    data_objects_table_of_contents
    data_objects_untrust_reasons
    * users_data_objects
    * users_data_objects_ratings
    
    TBD: curator_data_object_logs

* NB These tables are in eol_production, not eol_data_production.
***/


    // Load info about the tables to be archived into the table_arr array:
    $idx=0;


    // Base table info:
    $col_name = "id";

    $this->table_arr[$idx++] = array("data_objects", "data_objects_archive", $col_name);



    // foreign reference tables info:
    $col_name = "data_object_id";

    $this->table_arr[$idx++]=array("agents_data_objects"           , "agents_data_objects_archive"           , $col_name);

    $this->table_arr[$idx++]=array("audiences_data_objects"        , "audiences_data_objects_archive"        , $col_name);

    //$this->table_arr[$idx++]=array("data_object_data_object_tags"  , "data_object_data_object_tags_archive"  , $col_name);

    $this->table_arr[$idx++]=array("data_objects_harvest_events"   , "data_objects_harvest_events_archive"   , $col_name);

    $this->table_arr[$idx++]=array("data_objects_hierarchy_entries", "data_objects_hierarchy_entries_archive", $col_name);

    $this->table_arr[$idx++]=array("data_objects_info_items"       , "data_objects_info_items_archive"       , $col_name);

    $this->table_arr[$idx++]=array("data_objects_refs"             , "data_objects_refs_archive"             , $col_name);

    $this->table_arr[$idx++]=array("data_objects_table_of_contents", "data_objects_table_of_contents_archive", $col_name);

    $this->table_arr[$idx++]=array("data_objects_untrust_reasons"  , "data_objects_untrust_reasons_archive"  , $col_name);

    //$this->table_arr[$idx++]=array("users_data_objects"            , "users_data_objects_archive"            , $col_name);

    //$this->table_arr[$idx++]=array("users_data_objects_ratings", "users_data_objects_ratings_archive"    , $col_name);



    // To Be Decided
    // $this->table_arr[$idx++]=array("curator_data_object_logs", "curator_data_object_logs_archive", $col_name);

//    $this->display_table_arr();

if ($this->is_tracing) echo "\n\n LEAVING populate_table_arr()";
} // end of populate_table_arr()



//////////////////////////////////////////////////////////////////////////////////////////



} // end of class ArchiverDataObjects

?>

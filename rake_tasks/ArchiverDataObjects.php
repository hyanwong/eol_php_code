<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
include 'Archiver.php';


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
//////
//
// To launch the archive process:
//
//    // Instantiate an instance of ArchiverDataObjects.
//    $this->my_archiver_data_objects = new ArchiverDataObjects();
//
//    // Initialize it, which populates some arrays.
//    $this->my_archiver_data_objects->initialize();
//
//    // Then invoke its process_archivable_ids() method.
//    $this->my_archiver_data_objects->process_archivable_ids(100);
//
// 
//////////////////////////////////////////////////////////////////////////////////////////



class ArchiverDataObjects extends Archiver
{

// arrays in which to store info about the tables to be archived:
private $table_arr       = array(); // TableName, its ArchiveTableName, the ColumnName of the DO ID.
private $table_names_arr = array(); // PrimaryTable name, FRT names, & all the Archive Table names.

private $arr_elements    = array(); // utility array used when iterating thru an array.



//////////////////////////////////////////////////////////////////////////////////////////



public function __construct()
{
    $this->mysqli =& $GLOBALS["db_connection"];

} // end of constructor



//////////////////////////////////////////////////////////////////////////////////////////



public function initialize()
{
    // Load info about the tables to be archived into the table_arr array
    $this->populate_table_arr();

    // Load the table names into the table_names_arr aray
    $this->populate_table_names_arr();

} // end of initialize()



//////////////////////////////////////////////////////////////////////////////////////////



public function get_table_names_arr()
{
    return ($this->table_names_arr);

} // end of get_table_names_arr()



//////////////////////////////////////////////////////////////////////////////////////////



public function display_table_names_arr()
{
    foreach($this->table_names_arr as $table_name)
    {
        echo "\n$table_name";
    }

    echo "\n\n";

} // end of display_table_names_arr()



//////////////////////////////////////////////////////////////////////////////////////////



public function display_table_arr()
{
    // $table_arr contains: sourceTableName, archiveTableName, column_name

    foreach($this->table_arr as $this->arr_elements)
    {
        $_source_table   = $this->arr_elements[0];
        $_archive_table  = $this->arr_elements[1];
        $_do_id          = $this->arr_elements[2];

        echo "\n\n\t $_source_table \t $_archive_table \t $_do_id";
    }

    echo "\n\n";

} // end of display_table_arr



//////////////////////////////////////////////////////////////////////////////////////////



public function get_table_arr()
{
    return ($this->table_arr);

}  // end of get_table_arr()



//////////////////////////////////////////////////////////////////////////////////////////



public function exit_on_sql_error($error_number, $error_description)
{
    if ($error_number)
    {
        exit("FAILED TO EXECUTE QUERY||$sql_statement||: ".$this->mysqli->error()."\n\n");
    }

}  // end of exit_on_sql_error()



//////////////////////////////////////////////////////////////////////////////////////////



public function rollback_and_exit()
{
    $this->mysqli->rollback();
    exit();

} // end of rollback_and_exit



//////////////////////////////////////////////////////////////////////////////////////////



public function populate_table_names_arr()
{
    // $table_arr contains: sourceTableName, archiveTableName, do_id
    // populate $table_names_arr with table_name, rowcount_before=0, rowcount_after=0

    $idx=0;
    foreach($this->table_arr as $this->arr_elements)
    {
        $table_name  = $this->arr_elements[0];
        $this->table_names_arr[$idx++] = $table_name;

        $table_name = $this->arr_elements[1];
        $this->table_names_arr[$idx++] = $table_name;
    }

//    $this->display_table_names_arr();

} // end of populate_table_names_arr



//////////////////////////////////////////////////////////////////////////////////////////



public function populate_table_arr()
{
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

} // end of populate_table_arr()



//////////////////////////////////////////////////////////////////////////////////////////



/////////////////////////////////
//
// archive_table_by_do_id()
// Move one or more rows* from the source table into its archive table.
//
// Parameter(s):
//   _do_id             : a data_objects.id (or reference_table.data_object_id);
//   _source_table_name : name of the table from which a row will be removed;
//   _archive_table_name: name of the archive table into which the row will be inserted;
//   _col_name          : name of column in the source table, which corresponds to the do.id.
//
// * When archiving the data_objects table, there should be only one row to archive,
//   but the foreign reference tables may contain multiple rows to archive per _do_id.
//
/////////////////////////////////
public function archive_table_by_do_id ($_do_id
                                       ,$_source_table_name
                                       ,$_archive_table_name
                                       ,$_column_name)
{
    // Copy row(s) from the source table to its archive table.
    $sql_statement =
    "REPLACE INTO $_archive_table_name
     SELECT * FROM $_source_table_name
     WHERE  $_column_name = $_do_id";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error() );



    // Remove row(s) from the source table.
    $sql_statement =
    "DELETE FROM $_source_table_name
     WHERE  $_column_name = $_do_id";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error() );

} // end of archive_table_by_do_id()



//////////////////////////////////////////////////////////////////////////////////////////



/////////////////////////////////
//
// archive_do_id()
//
// PARAMETER(S):
// $_do_id : the data_objects.id for the row(s) to be archived
//
// For a given data_objects.id:
//
//   1) Start Transaction;
//   2) archive the data_objects row, plus any rows in tables that reference it;
//   3) Commit.
//
/////////////////////////////////
public function archive_do_id($_do_id)
{
    /////////////////////////////// 
    // Archive each of the tables described in $table_arr
    //   1) Start Transaction;
    //   2) archive the data_objects row, plus all the rows in tables that reference it;
    //   3) Commit.

    $this->arr_elements  = array();
    foreach($this->table_arr as $this->arr_elements)
    {
        $source_table  = $this->arr_elements[0];
        $archive_table = $this->arr_elements[1];
        $column_name   = $this->arr_elements[2];

        $this->archive_table_by_do_id($_do_id, $source_table, $archive_table, $column_name);
    }

} // end of archive_do_id()



//////////////////////////////////////////////////////////////////////////////////////////



public function process_archivable_ids($max_id_count=1000)
{
    /// Fetch the IDs for all archivable data_objects rows.
    $sql_statement="SELECT id FROM data_objects WHERE archive=TRUE ORDER BY 1 LIMIT $max_id_count";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error());


    if ($result->num_rows == 0)
    {
        return;  // If no rows are archivable, return... there's no work to do.
    }



    /// Archive each do.id that was retrieved.
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM))
    {
        // START TRANSACTION
        $this->mysqli->autocommit(FALSE);

        $_row_id = $row[0];
            $this->archive_do_id($_row_id);

        // COMMIT
        $this->mysqli->commit();
    }

} // end of process_archivable_ids



//////////////////////////////////////////////////////////////////////////////////////////



public function get_database_connection_info()
{
    /// Fetch the archive table names in the default database.
    $sql_statement="SELECT DISTINCT table_name
                    FROM information_schema.columns
                    WHERE table_name LIKE '%data_objects%'
                    OR (column_name LIKE 'data_object_id' AND table_name LIKE '%_archive')
                    ORDER BY 1";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error() );

    echo "\n\nWhen Archiving data_objects, the following tables are involved:\n\n";
    while ($row = $result->fetch_array(MYSQLI_NUM))
    {
        echo "\t$row[0]\n";
    }

    echo "\n";

} // end of get_database_connection_info



//////////////////////////////////////////////////////////////////////////////////////////



} // end of class ArchiverDataObjects

?>

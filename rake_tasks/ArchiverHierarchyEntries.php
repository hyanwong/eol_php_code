<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
//include 'Archiver.php';


//////////////////////////////////////////////////////////////////////////////////////////
//
// Archive these tables:
// source table: 
// - hierarchy_entries
//
// Foreign Reference tables:
// - agents_hierarchy_entries
// - curated_hierarchy_entry_relationships
// - data_objects_hierarchy_entries
// - harvest_events_hierarchy_entries
// - hierarchy_entries_refs
// - synonyms
//
// * random_hierarchy_images => delete rather than archive (this is a denormalized table)
//
//////////////////////////////////////////////////////////////////////////////////////////



class ArchiverHierarchyEntries extends Archiver
{

// arrays in which to store info about the tables to be archived:
private $table_arr       = array(); // TableName, its ArchiveTableName, the ColumnName of the DO ID.
private $table_names_arr = array(); // PrimaryTable name, FRT names, & all the Archive Table names.

private $arr_elements    = array(); // utility array used when iterating thru an array.


// Do we want to report on the before & after row counts, in all the affected tables?
// NB This can be overwritten by a parameter sent to the process_archivable_ids method.
private $generate_report  = FALSE;


private $primary_table_name = "data_objects";


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
    //$this->display_table_arr();

    // Load the table names into the table_names_arr aray
    $this->populate_table_names_arr();
    //$this->display_table_names_arr();

} // end of initialize()



//////////////////////////////////////////////////////////////////////////////////////////



public function note_hierarchy_entries_rowcount_before()
{
    /// Fetch the number of rows in hierarchy_entries before the archiving process begins.
    $sql_statement="SELECT count(*)
                    FROM hierarchy_entries";

    $result = $this->mysqli->query($sql_statement);
    if (!$result) { exit("Could not run query ($sql_statement) from DB: ".mysql_error()."\n\n"); }

    $row = $result->fetch_array(MYSQLI_NUM);
    $this->hierarchy_entries_rows_before = $row[0];

} // end of note_hierarchy_entries_rowcount_before



//////////////////////////////////////////////////////////////////////////////////////////



public function note_hierarchy_entries_rowcount_after()
{
    /// Fetch the number of rows in hierarchy_entries before the archiving process begins.
    $sql_statement="SELECT count(*)
                    FROM hierarchy_entries";

    $result = $this->mysqli->query($sql_statement);
    if (!$result) { exit("Could not run query ($sql_statement) from DB: ".mysql_error()."\n\n"); }

    $row = $result->fetch_array(MYSQLI_NUM);
    $this->hierarchy_entries_rows_after = $row[0];
    $this->hierarchy_entries_rows_delta =
           $this->hierarchy_entries_rows_after - $this->hierarchy_entries_rows_before;


    echo "\nBEFORE: hierarchy_entries rows: ".number_format($this->hierarchy_entries_rows_before);
    echo "\nAFTER : hierarchy_entries rows: ".number_format($this->hierarchy_entries_rows_after);
    echo "\nDELTA : hierarchy_entries rows: ".number_format($this->hierarchy_entries_rows_delta);
    echo "\n";
    
} // end of note_hierarchy_entries_rowcount_after



//////////////////////////////////////////////////////////////////////////////////////////



public function get_database_connection_info()
{
    /// Fetch the archive table names in the default database.
    $sql_statement="SELECT DISTINCT table_name
                    FROM information_schema.columns
                    WHERE table_name like 'hierarchy_entries_archive'
                    OR (column_name LIKE 'hierarchy_entry_id' AND table_name LIKE '%_archive')
                    ORDER BY 1";

    $result = $this->mysqli->query($sql_statement);
    if (!$result) { exit("Could not run query ($sql_statement) from DB: ".mysql_error()."\n\n"); }

    echo "\n\nHierarchy Entries Archive Tables:\n\n";
    while ($row = $result->fetch_array(MYSQLI_NUM))
    {
        echo "\t$row[0]\n";
    }

} // end of get_database_connection_info



//////////////////////////////////////////////////////////////////////////////////////////



public function process_archivable_ids($max_id_count=1000)
{
    /// Retrieve the IDs for all archivable hierarchy_entries rows.
    $sql_statement="SELECT id FROM hierarchy_entries WHERE archive=TRUE LIMIT $max_id_count";

    if (!$result = $this->mysqli->query($sql_statement))
    { exit("Could not run query ($sql_statement) from DB: ".mysql_error()."\n\n"); }

    if ($result->num_rows == 0)
    { 
        echo "hierarchy_entries Rows Archived: 0\n\n";
        return;
    }



    /// Archive each he.id that was retrieved.
    while ($row = $result->fetch_object())
    {
        echo "\n".$row->id;
        echo "\n";


        // START TRANSACTION
        $this->mysqli->autocommit(FALSE);
    
        $this->archive_he_id($row->id);
        $this->delete_he_id($row->id);

        // COMMIT
        $this->mysqli->commit();
    }

} // end of process_archivable_ids


//////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////
//
// archive_he_id()
//
// PARAMETER(S):
// $_he_id : the hierarchy_entries.id for the row(s) to be archived
//
// For a given hierarchy_entries.id:
//
//   1) Start Transaction;
//   2) archive the hierarchy_entries row, plus any rows in tables that reference it;
//   3) Commit.
//
/////////////////////////////////
public function archive_he_id($_he_id)
{
/*** Tables to be archived:
Primary Table:
    hierarchy_entries
Foreign Reference Tables:
    agents_hierarchy_entries
    curated_hierarchy_entry_relationships
    data_objects_hierarchy_entries
    harvest_events_hierarchy_entries
    hierarchy_entries_refs
    synonyms
***/


    /////////////////////////////// 
    // Archive each of the tables described in $table_arr
    //   1) Start Transaction;
    //   2) archive the hierarchy_entries row, plus all the rows in tables that reference it;
    //   3) Commit.
    /////////////////////////////// 


    foreach($table_arr as $arr_elements)
    {
        $source_table  = $arr_elements[0];
        $archive_table = $arr_elements[1];
        $column_name   = $arr_elements[2];

    //  echo  "\n".$_he_id."\t".$source_table."\t".$archive_table."\t".$column_name."\n";
        $this->archive_table_by_he_id($_he_id, $source_table, $archive_table, $column_name);
    }

} // end of archive_he_id()


//////



public function populate_table_arr()
{
//echo "\n ENTERING ArchiverHierarchyEntries.populate_table_arr()\n";
/*** Tables to be archived:

Base Table:
    hierarchy_entries

Foreign Reference Tables:
    agents_hierarchy_entries
    curated_hierarchy_entry_relationships
    data_objects_hierarchy_entries
    harvest_events_hierarchy_entries
    hierarchy_entries_refs
    synonyms_archive
***/



    // Load info about the tables to be archived into the table_arr array:
    $idx=0;


    // Primary Table info:
    $col_name = "id";


    $this->table_arr[$idx++]=
    array("hierarchy_entries"                    , "hierarchy_entries_archive",
    $col_name);



    // Foreign Reference tables info:
    $col_name = "hierarchy_entry_id";

    $this->table_arr[$idx++]=
    array("agents_hierarchy_entries"             , "agents_hierarchy_entries_archive",
    $col_name);

    $this->table_arr[$idx++]=
    array("curated_hierarchy_entry_relationships", "curated_hierarchy_entry_relationships_archive",
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

} // end of populate_table_arr()



//////////////////////////////////////////////////////////////////////////////////////////



public function display_table_arr()
{
    // $table_arr contains: sourceTableName, archiveTableName, col_name

    foreach($this->table_arr as $this->arr_elements)
    {
        $_source_table   = $this->arr_elements[0];
        $_archive_table  = $this->arr_elements[1];
        $_do_id          = $this->arr_elements[2];

        echo "\n $_source_table \t $_archive_table \t $_do_id";
    }
    echo "\n";

} // end of display_table_arr



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

} // end of populate_table_names_arr



//////////////////////////////////////////////////////////////////////////////////////////


public function delete_he_id($_he_id)
{
    //delete from random_hierarchy_images where hierarchy_entry_id = $_he_id;
    // Remove row(s) from the source table.
    $sql_statement = "DELETE FROM random_hierarchy_images WHERE hierarchy_entry_id = $_he_id";

    //echo "\n\n>>>>>>>>>\n$sql_statement\n<<<<<<<<<";


    $result = $this->mysqli->query($sql_statement);
    if (!$result)
    {
        echo "FATAL ERROR: Failed to execute ($sql_statement) \n".mysql_error()."\n\n";
        exit;
    }

} // end of delete_he_id()


//////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////
//
// archive_table_by_he_id()
// Move one or more rows* from the source table into its archive table.
//
// Parameter(s):
//   _he_id             : a hierarchy_entries.id (or reference_table.hierarchy_entry_id);
//   _source_table_name : name of the table from which a row will be removed;
//   _archive_table_name: name of the archive table into which the row will be inserted;
//   _col_name          : name of column in the source table, which corresponds to the he.id.
//
// * When archiving the hierarchy_entries table, there should be only one row to archive,
//   but the foreign reference tables may contain multiple rows to archive per _he_id.
//
/////////////////////////////////
public function archive_table_by_he_id ($_he_id
                                       ,$_source_table_name
                                       ,$_archive_table_name
                                       ,$_column_name)
{
    // Copy row(s) from the source table to its archive table.
    $sql_statement =
    "REPLACE  INTO $_archive_table_name
     SELECT * FROM $_source_table_name
     WHERE  ".$_column_name." = $_he_id";

    $result = mysql_query($sql_statement);
    if (!$result)
    {
        echo "FATAL ERROR: Failed to execute ($sql_statement) \n".mysql_error()."\n\n";
        exit;
    }



    // Remove row(s) from the source table.
    $sql_statement =
    "DELETE FROM $_source_table_name
     WHERE  ".$_column_name." = $_he_id";

    $result = mysql_query($sql_statement);
    if (!$result)
    {
        echo "FATAL ERROR: Failed to execute ($sql_statement) \n".mysql_error()."\n\n";
        exit;
    }

} // end of archive_table_by_he_id()



//////////////////////////////////////////////////////////////////////////////////////////



public function get_table_names_arr()
{
    return ($this->table_names_arr);
}



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


public function rollback_and_exit()
{
    $this->mysqli->rollback();
    exit();

} // end of rollback_and_exit



} // end of class ArchiverHierarchyEntries


?>

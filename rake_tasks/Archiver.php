<?php

include_once(dirname(__FILE__) . "/../config/environment.php");



interface populate_the_table_arr
{
    public function populate_table_arr();
}



//////////////////////////////////////////////////////////////////////////////////////////
//
// Archiver is the parent class of (and contains all the functions common to) :
// - ArchiverDataObjects
// - ArchiverHierarchyEntries
//
//////////////////////////////////////////////////////////////////////////////////////////

class Archiver
{

// arrays in which to store info about the tables to be archived:
// TableName, its ArchiveTableName, the ColumnName of the DO ID.
protected $table_arr       = array(); 

protected $table_names_arr = array(); // PrimaryTable name, FRT names, & all the Archive Table names.

protected $arr_elements    = array(); // utility array used when iterating thru an array.

public $primary_table   = "";

protected $is_tracing = FALSE;



//////////////////////////////////////////////////////////////////////////////////////////



public function __construct($_is_tracing=FALSE)
{
    if ($_is_tracing) $this->is_tracing = TRUE;

    $this->mysqli =& $GLOBALS["db_connection"];
    $this->initialize();

} // end of constructor



//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////



public function archive_id($_archivable_id)
{
if ($this->is_tracing) echo "\n\n ENTERING archive_id($_archivable_id)";

    // Archive rows with the _archivable_id, in each of the tables described in $table_arr

    $this->arr_elements  = array();
    foreach($this->table_arr as $this->arr_elements)
    {
        $source_table  = $this->arr_elements[0];
        $archive_table = $this->arr_elements[1];
        $column_name   = $this->arr_elements[2];

        $this->archive_table_by_id($_archivable_id, $source_table, $archive_table, $column_name);
    }

if ($this->is_tracing) {echo "\n\n LEAVING archive_id()";}
} // end of archive_id()



//////////////////////////////////////////////////////////////////////////////////////////



/////////////////////////////////
//
// archive_table_by_id()
// Move one or more rows* from the source table into its archive table.
//
// Parameter(s):
//   _archivable_id     : primary table's PK (or reference table's foreign key)
//   _source_table_name : name of the table from which a row will be removed
//   _archive_table_name: name of the archive table into which the row will be inserted
//   _column_name       : column name in both tables: _source_table_name & _archive_table_name
//
// * When archiving a primary table, there should be only one row to archive per id,
//   but the foreign reference tables may contain multiple rows to archive per id.
//
/////////////////////////////////

public function archive_table_by_id ($_archivable_id
                                    ,$_source_table_name
                                    ,$_archive_table_name
                                    ,$_column_name)
{
if ($this->is_tracing) {echo "\n\n ENTERING archive_table_by_id ($_archivable_id, $_source_table_name, $_archive_table_name, $_column_name)";}

    // Copy row(s) from the source table to its archive table.
    $sql_statement =
    "REPLACE INTO $_archive_table_name
     SELECT * FROM $_source_table_name
     WHERE  $_column_name = $_archivable_id";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error() );



    // Remove row(s) from the source table.
    $sql_statement =
    "DELETE FROM $_source_table_name
     WHERE  $_column_name = $_archivable_id";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error() );

if ($this->is_tracing) {echo "\n\n LEAVING archive_table_by_id()";}
} // end of archive_table_by_id()



//////////////////////////////////////////////////////////////////////////////////////////



public function display_table_arr()
{
if ($this->is_tracing) {echo "\n\n ENTERING display_table_arr()";}

    // $table_arr contains: sourceTableName, archiveTableName, column_name

    foreach($this->table_arr as $this->arr_elements)
    {
        $_source_table   = $this->arr_elements[0];
        $_archive_table  = $this->arr_elements[1];
        $_column_name    = $this->arr_elements[2];

        echo "\n\n\t $_source_table \t $_archive_table \t $_column_name";
    }

    echo "\n\n";

if ($this->is_tracing) {echo "\n\n LEAVING display_table_arr()";}
} // end of display_table_arr



//////////////////////////////////////////////////////////////////////////////////////////



public function display_table_names_arr()
{
if ($this->is_tracing) {echo "\n\n ENTERING display_table_names_arr()";}

    foreach($this->table_names_arr as $table_name)
    {
        echo "\n$table_name";
    }

    echo "\n\n";

if ($this->is_tracing) {echo "\n\n LEAVING display_table_names_arr()";}
} // end of display_table_names_arr()



//////////////////////////////////////////////////////////////////////////////////////////



public function exit_on_sql_error($error_number, $error_description)
{
if ($this->is_tracing) {echo "\n\n ENTERING exit_on_sql_error($error_number, $error_description)";}

    if ($error_number)
    {
        exit("FAILED TO EXECUTE QUERY||$sql_statement||: ".$this->mysqli->error()."\n\n");
    }

if ($this->is_tracing) {echo "\n\n LEAVING exit_on_sql_error()";}
}  // end of exit_on_sql_error()



////////////////////////////////////////////////////////////////////////////////////////////////



public function get_database_connection_info()
{
if ($this->is_tracing) {echo "\n\n ENTERING get_database_connection_info()";}

    /// Fetch the server name.
    $sql_statement=" show variables like 'hostname'";
    $result = $this->mysqli->query($sql_statement);

    if (!$result) { exit("Could not run query ($sql_statement) from DB: ".mysql_error()."\n\n"); }

    $row = $result->fetch_array(MYSQLI_NUM);
    echo "\n\nServer Name: $row[1]";



    /// Fetch the default database name.
    $sql_statement="SELECT database()";
    $result = $this->mysqli->query($sql_statement);

    if (!$result) { exit("Could not run query ($sql_statement) from DB: ".mysql_error()."\n\n"); }

    $row = $result->fetch_array(MYSQLI_NUM);
    echo "\n\nDefault Databse: $row[0]\n\n";

if ($this->is_tracing) {echo "\n\n LEAVING get_database_connection_info()";}
} // end of get_database_connection_info



//////////////////////////////////////////////////////////////////////////////////////////



public function get_table_arr()
{
if ($this->is_tracing) {echo "\n\n ENTERING get_table_arr()";}

    return ($this->table_arr);

if ($this->is_tracing) {echo "\n\n LEAVING get_table_arr()";}
}  // end of get_table_arr()



//////////////////////////////////////////////////////////////////////////////////////////



public function get_table_names_arr()
{
if ($this->is_tracing) {echo "\n\n ENTERING get_table_names_arr()";}

    return ($this->table_names_arr);

if ($this->is_tracing) {echo "\n\n LEAVING get_table_names_arr()";}
} // end of get_table_names_arr()



//////////////////////////////////////////////////////////////////////////////////////////



public function initialize()
{
if ($this->is_tracing) {echo "\n\n ENTERING initialize()";}

    // Load info about the tables to be archived into the table_arr array
    $this->populate_table_arr();

    // Load the table names into the table_names_arr aray
    $this->populate_table_names_arr();

if ($this->is_tracing) {echo "\n\n LEAVING initialize()";}
} // end of initialize()



//////////////////////////////////////////////////////////////////////////////////////////


public function populate_table_arr()
{
echo "\n\n ENTERING Archiver->populate_table_arr()";

    // Any Class extending Archiver must define its own populate_table_arr() function.
    // It must, therefore, implement the populate_the_table_arr interface.
    // But in case it doesn't, this function is here to draw attention that oversight.

    echo "\n\n FATAL ERROR: EXITING Archiver->populate_table_arr()";
    exit;

} // end of populate_table_arr()


//////////////////////////////////////////////////////////////////////////////////////////



public function populate_table_names_arr()
{
if ($this->is_tracing) {echo "\n\n ENTERING Archiver->populate_table_names_arr()";}

    // $table_arr contains: sourceTableName, archiveTableName, column_name
    // populate $table_names_arr with sourceTableName & archiveTableName

    $idx=0;
    foreach($this->table_arr as $this->arr_elements)
    {
        $table_name  = $this->arr_elements[0];
        $this->table_names_arr[$idx++] = $table_name;

        $table_name = $this->arr_elements[1];
        $this->table_names_arr[$idx++] = $table_name;
    }

//    $this->display_table_names_arr();

if ($this->is_tracing) {echo "\n\n LEAVING populate_table_names_arr()";}
} // end of populate_table_names_arr



//////////////////////////////////////////////////////////////////////////////////////////



public function process_archivable_ids($max_id_count=1000)
{
if ($this->is_tracing) {echo "\n\n ENTERING process_archivable_ids($max_id_count)";}

    // Fetch the IDs for all archivable rows in the primary table.
    $sql_statement="SELECT id FROM $this->primary_table WHERE archive=TRUE ORDER BY 1 LIMIT $max_id_count";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error());



    if ($result->num_rows == 0)
    {
        echo "\t NO ARCHIVABLE ROWS FOUND\n";
        return;
    }



    /// Archive each do.id that was retrieved.
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM))
    {
        // START TRANSACTION
        $this->mysqli->autocommit(FALSE);

        $_row_id = $row[0];
            $this->archive_id($_row_id);

        // COMMIT
        $this->mysqli->commit();
    }

if ($this->is_tracing) {echo "\n\n LEAVING process_archivable_ids()";}
} // end of process_archivable_ids



////////////////////////////////////////////////////////////////////////////////////////////////



public function rollback_and_exit()
{
if ($this->is_tracing) {echo "\n\n ENTERING rollback_and_exit()";}

    $this->mysqli->rollback();
    exit();

if ($this->is_tracing) {echo "\n\n LEAVING rollback_and_exit()";}
} // end of rollback_and_exit



////////////////////////////////////////////////////////////////////////////////////////////////



} // end of class Archiver

?>

<?php

include_once(dirname(__FILE__) . "/../config/environment.php");



//////////////////////////////////////////////////////////////////////////////////////////
//
// Archiver is the parent class of (and contains some functions common to) :
// - ArchiverDataObjects
// - ArchiverHierarchyEntries
//
//////////////////////////////////////////////////////////////////////////////////////////



class Archiver
{


public function __construct()
{
    $this->mysqli =& $GLOBALS["db_connection"];

} // end of constructor



////////////////////////////////////////////////////////////////////////////////////////////////



public function get_database_connection_info()
{
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
    echo "\n\nDefault Databse: $row[0]";


} // end of get_database_connection_info



////////////////////////////////////////////////////////////////////////////////////////////////



/////////////////////////////////
//
// archive_table_by_id()
// Move one or more rows from the source table into its archive table.
//
// Parameter(s):
//   _id                : the value of the source table's primary key
//   _source_table_name : name of the table from which a row will be removed;
//   _archive_table_name: name of the archive table into which the row will be inserted;
//   _col_name          : column name of the source table's primary key.
//
/////////////////////////////////
public function archive_table_by_id ($_id
                                ,$_source_table_name
                                ,$_archive_table_name
                                ,$_column_name)
{

echo "\n\n\tArchiver->archive_table_by_id($_id, $_source_table_name, $_archive_table_name, $_column_name)\n\n";

    // Copy row(s) from the source table to its archive table.
    $sql_statement =
    "REPLACE  INTO $_archive_table_name
     SELECT * FROM $_source_table_name
     WHERE  ".$_column_name." = $_id";

    $result = mysql_query($sql_statement);
    if (!$result)
    {
        echo "FATAL ERROR: Failed to execute ($sql_statement) \n".mysql_error()."\n\n";
        exit;
    }



    // Remove row(s) from the source table.
    $sql_statement =
    "DELETE FROM $_source_table_name
     WHERE  ".$_column_name." = $_id";

    $result = mysql_query($sql_statement);
    if (!$result)
    {
        echo "FATAL ERROR: Failed to execute ($sql_statement) \n".mysql_error()."\n\n";
        exit;
    }



} // end of archive_table_by_id()



////////////////////////////////////////////////////////////////////////////////////////////////



public function rollback_and_exit()
{
    $this->mysqli->rollback();
    exit();

} // end of rollback_and_exit



////////////////////////////////////////////////////////////////////////////////////////////////



} // end of class Archiver

?>

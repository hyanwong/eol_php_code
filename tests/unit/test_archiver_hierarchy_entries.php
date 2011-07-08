<?php


////////////////////////////////////////////////////////////////////////////////////////////////
//
// This class tests the functionality of an ArchiverHierarchyEntries instance.
// It performs the following:
//
// * Instantiate an ArchiverHierarchyEntries instance
//
// * Ask the instance for an array (table_names_arr) containing the names
//   of the tables it intends to archive.
//   e.g.
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
// * Create a temporary table (archiver_temporary_table) in which to keep track of
//   all the table names, and the results of the tests. (rowcounts before and after each test,
//   as well as the expected and actual changes in rowcounts)
//   See the init_archiver_temporary_table() method for details.
//
// * It then causes the ArchiverHierarchyEntries instance to perform its archive operations.
//
// * Finally, in the Primary table, the FRTs and their Archive tables,
//   it compares the expected rowcounts to the actual rowcounts.
//   If they are the same, the test passes. Otherwise, it fails.
//
//
// Both ArchiverDataObjects & ArchiverHierarchyEntries inherit all their functions from Archiver,
// except for one: populate_table_arr()
// So, except for the tables they operate on, the two classes are identical.
// And because test_archiver_data_objects thoroughly tests ArchiverDataObjects, the only tests
// that need to be performed on ArchiverHierarchyEntries are those concerned with the differences
// in the tables those classes operate on. In particular, ArchiverHierarchyEntries operates on
// curated_hierarchy_entry_relationships, which contains two refreences to a hierarchy_entries.id:
// hierarchy_entry_id_1 & hierarchy_entry_id_2
//
// Therefore, the test_archiver_hierarchy_entries class will test proper functionality under
// the following conditions:
//
// a1) All the required tables are being archived.
//
// b1) Neither hierarchy_entry_id_1 nor hierarchy_entry_id_2 reference a hierarchy_entries.id that 
// needs to be archived.
//
// b2) Only hierarchy_entry_id_1 references a hierarchy_entries.id that needs to be archived.
//
// b3) Only hierarchy_entry_id_2 references a hierarchy_entries.id that needs to be archived.
//
// b4) Both hierarchy_entry_id_1 and hierarchy_entry_id_2 reference a hierarchy_entries.id that 
// needs to be archived.
//
////////////////////////////////////////////////////////////////////////////////////////////////


require_once(dirname(__FILE__) . "/../../config/environment.php");
require_once(DOC_ROOT . 'vendor/simpletest_extended/simpletest_unit_base.php');


include '../rake_tasks/ArchiverHierarchyEntries.php';



////////////////////////////////////////////////////////////////////////////////////////////////



class test_archiver_hierarchy_entries extends SimpletestUnitBase
{

    private $object_under_test;
    private $table_names_arr = array();
    private $arr_elements    = array();

    private $primary_table_name = "hierarchy_entries";


////////////////////////////////////////////////////////////////////////////////////////////////



public function __construct()
{
    $this->mysqli =& $GLOBALS["db_connection"];

    $this->initialize();

} // end of constructor



////////////////////////////////////////////////////////////////////////////////////////////////



public function setUp()
{
#echo "\n\n\t ENTERING setUp()";


    // truncate HE & FRT Tables
    $this->truncate_all_tables();


#echo "\n\n\t LEAVING  setUp()";
}



////////////////////////////////////////////////////////////////////////////////////////////////



/***
function tearDown()
{
echo "\n\n\t ENTERING tearDown()";

echo "\n\n\t LEAVING  tearDown()";
}
***/



////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST a1
All the required tables are being archived.
i.e.:
    - agents_hierarchy_entries
    - curated_hierarchy_entry_relationships
    - data_objects_hierarchy_entries
    - harvest_events_hierarchy_entries
    - hierarchy_entries
    - hierarchy_entries_refs
    - synonyms

SET-UP: 
 Truncate HE & FRTs
 Populate HE with 10 rows. update all archive=FALSE. Update 1 where archive-TRUE.
 Populate all FRTs with 2 rows, where 
 - row references an HE id being archived.
 - row references HE ids NOT being archived.
 
RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 HE   rowcount -2. 
 curated_hierarchy_entry_relationships   -1. 
*********/

public function test_a1()
{
#echo "\n\n ENTERING test_a1";



    $test_name = ">>> TEST HierarchyEntries a1";
    //echo "\n$test_name\n";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_hierarchy_entries($max_rowcount);
    $this->update_archive_column('hierarchy_entries', 'FALSE', $max_rowcount);
    $this->update_archive_column('hierarchy_entries', 'TRUE',   1);

    
    // Populate all FRTs with 2 rows, where
    // - 1 row corresponds to a HE row being archived
    // - 1 row corresponds to a HE row NOT being archived
    $this->populate_frts(1, 1, 0);  // ($row_limit, $_he_id, $_offset)
    $this->populate_frts(1, 11, 1);



//echo "\n\n ABOUT TO CALL update_rowcounts_before";
    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


//echo "\n\n ABOUT TO CALL object_under_test->process_archivable_ids(100)";
    // ARCHIVE, max 100 he_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // HE rowcount -1
    // HE_ARCHIVE  +1
    // curated_hierarchy_entry_relationships 0
    // curated_hierarchy_entry_relationships_archive 0
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables.
    $this->update_delta_expected('%', 0); // initialize all deltas to zero
    $this->update_delta_expected('hierarchy_entries',         -1);
    $this->update_delta_expected('hierarchy_entries_archive', +1);

    $this->update_delta_expected('agents_hierarchy_entries',         -1);
    $this->update_delta_expected('agents_hierarchy_entries_archive', +1);

    $this->update_delta_expected('curated_hierarchy_entry_relationships',         -1);
    $this->update_delta_expected('curated_hierarchy_entry_relationships_archive', +1);

    $this->update_delta_expected('data_objects_hierarchy_entries',         -1);
    $this->update_delta_expected('data_objects_hierarchy_entries_archive', +1);

    $this->update_delta_expected('harvest_events_hierarchy_entries',         -1);
    $this->update_delta_expected('harvest_events_hierarchy_entries_archive', +1);

    $this->update_delta_expected('hierarchy_entries_refs',         -1);
    $this->update_delta_expected('hierarchy_entries_refs_archive', +1);

    $this->update_delta_expected('synonyms',         -1);
    $this->update_delta_expected('synonyms_archive', +1);



    // DETERMINE ACTUAL RESULT:
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

//$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\t $test_name FAILED\n\n");

#echo "\n\n LEAVING test_a1";
}  // end of test_a1()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST b1
Neither hierarchy_entry_id_1 nor hierarchy_entry_id_2 reference a hierarchy_entries.id
that needs to be archived.

SET-UP: 
 Truncate HE & FRTs
 Populate HE with 10 rows. update all archive=FALSE. Update 2 where archive-TRUE.
 Populate curated_hierarchy_entry_relationships with 2 rows, where neither hierarchy_entry_id_1
 nor hierarchy_entry_id_2 contain values corresponding to the archivable rows in HE.
 
RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 HE   rowcount -2. 
 curated_hierarchy_entry_relationships   rowcount unchanged. 
*********/

public function test_b1()
{
#echo "\n\n ENTERING test_b1";


    $test_name = ">>> TEST HierarchyEntries b1";
    //echo"\n$test_name\n";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 2 rows.
    $this->insert_into_hierarchy_entries($max_rowcount);
    $this->update_archive_column('hierarchy_entries', 'FALSE', 10);
    $this->update_archive_column('hierarchy_entries', 'TRUE',   2);

    // Populate curated_hierarchy_entry_relationships with 2 rows, where
    // - hierarchy_entry_id_1 & hierarchy_entry_id_2 > 10.
    // - hierarchy_entry_id_1 & hierarchy_entry_id_2 > 10.
    $this->insert_1row_into_curated_hierarchy_entry_relationships(11, 12);
    $this->insert_1row_into_curated_hierarchy_entry_relationships(13, 14);

//echo "\n\n ABOUT TO CALL update_rowcounts_before";
    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


//echo "\n\n ABOUT TO CALL object_under_test->process_archivable_ids(100)";
    // ARCHIVE, max 100 he_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // HE rowcount -2
    // HE_ARCHIVE  +2
    // curated_hierarchy_entry_relationships 0
    // curated_hierarchy_entry_relationships_archive 0
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables.
    $this->update_delta_expected('%', 0); // initialize all deltas to zero
    $this->update_delta_expected('hierarchy_entries',         -2);
    $this->update_delta_expected('hierarchy_entries_archive', +2);



    // DETERMINE ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

//$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\t $test_name FAILED\n\n");

#echo "\n\n LEAVING test_b1";
}  // end of test_b1()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST b2
Only hierarchy_entry_id_1 references a hierarchy_entries.id that needs to be archived.

SET-UP: 
 Truncate HE & FRTs
 Populate HE with 10 rows. update all archive=FALSE. Update 2 where archive-TRUE.
 Populate curated_hierarchy_entry_relationships with 2 rows, where only hierarchy_entry_id_1
 contains a value corresponding to the archivable rows in HE.
 
RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 HE   rowcount -2. 
 curated_hierarchy_entry_relationships   -1. 
*********/

public function test_b2()
{
#echo "\n\n ENTERING test_b2";



    $test_name = ">>> TEST HierarchyEntries b2";
    //echo"\n$test_name\n";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 2 rows.
    $this->insert_into_hierarchy_entries($max_rowcount);
    $this->update_archive_column('hierarchy_entries', 'FALSE', 10);
    $this->update_archive_column('hierarchy_entries', 'TRUE',   2);

    // Populate curated_hierarchy_entry_relationships with 2 rows, where
    // - hierarchy_entry_id_1 = 1, hierarchy_entry_id_2 > 10.
    // - both hierarchy_entry_id_1 & hierarchy_entry_id_2 > 10.
    $this->insert_1row_into_curated_hierarchy_entry_relationships(1, 12);
    $this->insert_1row_into_curated_hierarchy_entry_relationships(13, 14);

//echo "\n\n ABOUT TO CALL update_rowcounts_before";
    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


//echo "\n\n ABOUT TO CALL object_under_test->process_archivable_ids(100)";
    // ARCHIVE, max 100 he_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // HE rowcount -2
    // HE_ARCHIVE  +2
    // curated_hierarchy_entry_relationships 0
    // curated_hierarchy_entry_relationships_archive 0
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables.
    $this->update_delta_expected('%', 0); // initialize all deltas to zero
    $this->update_delta_expected('hierarchy_entries',         -2);
    $this->update_delta_expected('hierarchy_entries_archive', +2);

    $this->update_delta_expected('curated_hierarchy_entry_relationships',         -1);
    $this->update_delta_expected('curated_hierarchy_entry_relationships_archive', +1);


    // DETERMINE ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

//$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\t $test_name FAILED\n\n");

#echo "\n\n LEAVING test_b2";
}  // end of test_b2()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST b3
Only hierarchy_entry_id_2 references a hierarchy_entries.id that needs to be archived.

SET-UP: 
 Truncate HE & FRTs
 Populate HE with 10 rows. update all archive=FALSE. Update 2 where archive-TRUE.
 Populate curated_hierarchy_entry_relationships with 2 rows, where only hierarchy_entry_id_2
 contains a value corresponding to the archivable rows in HE.
 
RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 HE   rowcount -2. 
 curated_hierarchy_entry_relationships   -1. 
*********/

public function test_b3()
{
#echo "\n\n ENTERING test_b3";



    $test_name = ">>> TEST HierarchyEntries b3";
    //echo"\n$test_name\n";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 2 rows.
    $this->insert_into_hierarchy_entries($max_rowcount);
    $this->update_archive_column('hierarchy_entries', 'FALSE', 10);
    $this->update_archive_column('hierarchy_entries', 'TRUE',   2);

    // Populate curated_hierarchy_entry_relationships with 2 rows, where
    // - hierarchy_entry_id_1 > 10, hierarchy_entry_id_2 = 1.
    // - hierarchy_entry_id_1 & hierarchy_entry_id_2 > 10.
    $this->insert_1row_into_curated_hierarchy_entry_relationships(11, 1);
    $this->insert_1row_into_curated_hierarchy_entry_relationships(13, 14);

//echo "\n\n ABOUT TO CALL update_rowcounts_before";
    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


//echo "\n\n ABOUT TO CALL object_under_test->process_archivable_ids(100)";
    // ARCHIVE, max 100 he_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // HE rowcount -2
    // HE_ARCHIVE  +2
    // curated_hierarchy_entry_relationships 0
    // curated_hierarchy_entry_relationships_archive 0
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables.
    $this->update_delta_expected('%', 0); // initialize all deltas to zero
    $this->update_delta_expected('hierarchy_entries',         -2);
    $this->update_delta_expected('hierarchy_entries_archive', +2);

    $this->update_delta_expected('curated_hierarchy_entry_relationships',         -1);
    $this->update_delta_expected('curated_hierarchy_entry_relationships_archive', +1);


    // DETERMINE ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

//$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\t $test_name FAILED\n\n");

#echo "\n\n LEAVING test_b3";
}  // end of test_b3()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST b4
Both hierarchy_entry_id_1 and hierarchy_entry_id_2 reference a hierarchy_entries.id
that needs to be archived.

SET-UP: 
 Truncate HE & FRTs
 Populate HE with 10 rows. update all archive=FALSE. Update 2 where archive-TRUE.
 Populate curated_hierarchy_entry_relationships with 2 rows, where 
 - both hierarchy_entry_id_2 & hierarchy_entry_id_2 reference HE ids needing to be archived
 - neigher hierarchy_entry_id_2 nor hierarchy_entry_id_2 reference HE ids needing to be archived
 
RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 HE   rowcount -2. 
 curated_hierarchy_entry_relationships   -1. 
*********/

public function test_b4()
{
#echo "\n\n ENTERING test_b4";



    $test_name = ">>> TEST HierarchyEntries b4";
    //echo"\n$test_name\n";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 2 rows.
    $this->insert_into_hierarchy_entries($max_rowcount);
    $this->update_archive_column('hierarchy_entries', 'FALSE', 10);
    $this->update_archive_column('hierarchy_entries', 'TRUE',   2);

    // Populate curated_hierarchy_entry_relationships with 2 rows, where
    // - hierarchy_entry_id_1 = 1, hierarchy_entry_id_2 = 2.
    // - both hierarchy_entry_id_1 & hierarchy_entry_id_2 > 10.
    $this->insert_1row_into_curated_hierarchy_entry_relationships(1, 2);
    $this->insert_1row_into_curated_hierarchy_entry_relationships(13, 14);

//echo "\n\n ABOUT TO CALL update_rowcounts_before";
    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


//echo "\n\n ABOUT TO CALL object_under_test->process_archivable_ids(100)";
    // ARCHIVE, max 100 he_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // HE rowcount -2
    // HE_ARCHIVE  +2
    // curated_hierarchy_entry_relationships 0
    // curated_hierarchy_entry_relationships_archive 0
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables.
    $this->update_delta_expected('%', 0); // initialize all deltas to zero
    $this->update_delta_expected('hierarchy_entries',         -2);
    $this->update_delta_expected('hierarchy_entries_archive', +2);

    $this->update_delta_expected('curated_hierarchy_entry_relationships',         -1);
    $this->update_delta_expected('curated_hierarchy_entry_relationships_archive', +1);


    // DETERMINE ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

//$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\t $test_name FAILED\n\n");

#echo "\n\n LEAVING test_b4";
}  // end of test_b4()



////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////



public function describe_archiver_temporary_table()
{
    // confirm creation of table archiver_temporary_table
    $sql_statement = "DESC archiver_temporary_table";
//    $result = $this->mysqli->query($sql_statement);
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

    echo "\n";
    while ($row = $result->fetch_row())
    {
        echo "\n$row[0]\t$row[1]\t$row[2]";
    }
    echo "\n\n";

} // end of describe_archiver_temporary_table()



////////////////////////////////////////////////////////////////////////////////////////////////



public function did_test_pass_or_fail($test_name)
{
#echo "\n\n\t ENTERING did_test_pass_or_fail($test_name)";

    $grade = 'PASSED';

    $sql_statement = "SELECT table_name
                            ,rowcount_before
                            ,rowcount_after
                            ,rowcount_delta_expected
                            ,rowcount_delta_actual
                      FROM   archiver_temporary_table
                      WHERE  rowcount_delta_expected != rowcount_delta_actual";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

    //echo "\n";
    while ($row = $result->fetch_row())
    {
        $grade = 'FAILED';
    }


return $grade;


#echo "\n\n\t LEAVING did_test_pass_or_fail()";
} // end of did_test_pass_or_fail()



////////////////////////////////////////////////////////////////////////////////////////////////



public function display_archiver_temporary_table()
{
    // Display the contents of the archiver_temporary_table table.
    $sql_statement = "SELECT
                       table_name
                      ,rowcount_before
                      ,rowcount_after
                      ,rowcount_delta_expected
                      ,rowcount_delta_actual
                      FROM archiver_temporary_table
                      ORDER BY table_name";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

    if ($result->num_rows == 0)
    {
        echo "\n\n\t!!!  archiver_temporary_table contains No Rows  !!!\n\n";
        //exit;
    }

    // Display the result of the above query on the screen:
    echo "\n\nrowcount_before\trowcount_after\trowcount_delta_expected\trowcount_delta_actual\ttable_name\n";
    while ($row = $result->fetch_row())
    {
        $table_name              = $row[0];
        $rowcount_before         = $row[1];
        $rowcount_after          = $row[2];
        $rowcount_delta_expected = $row[3];
        $rowcount_delta_actual   = $row[4];
        
        echo "\n$rowcount_before\t\t$rowcount_after\t\t$rowcount_delta_expected\t\t\t$rowcount_delta_actual\t\t\t$table_name";
    }
    echo "\n\n";

} // end of display_archiver_temporary_table()



////////////////////////////////////////////////////////////////////////////////////////////////



public function display_database_name()
{
echo "\n\n\t ENTERING display_database_name()";

    $sql_statement = "SELECT database()";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
    $row = $result->fetch_array(MYSQLI_NUM);
    echo "\n\n>>>>>>>>>>> $row[0]\n\n";

    return($rowcount);

echo "\n\n\t LEAVING display_database_name()";
} // end of display_database_name



////////////////////////////////////////////////////////////////////////////////////////////////



public function display_table_arr()
{

    // $table_arr contains: sourceTableName, archiveTableName, he_id

    foreach($this->table_names_arr as $this->arr_elements)
    {
        $_source_table   = $this->arr_elements[0];
        $_archive_table  = $this->arr_elements[1];
        $_he_id          = $this->arr_elements[2];

        echo "\n\n\t $_source_table \t $_archive_table \t $_he_id";
    }

    echo "\n\n";

} // end of display_table_arr



////////////////////////////////////////////////////////////////////////////////////////////////



public function display_table_names_arr()
{

    foreach($this->table_names_arr as $table_name)
    {
        echo "\n$table_name";
    }

    echo "\n\n";

} // end of display_table_names_arr()



////////////////////////////////////////////////////////////////////////////////////////////////



public function exit_on_sql_error($error_number, $error_description, $sql_statement)
{
#echo "\n\n\t ENTERING exit_on_sql_error()";

    if ($error_number)
    {
        echo "\n\ntest_archiver_hierarchy_entries.exit_on_sql_error()";
        //echo "\n\n$sql_statement";
        exit("\n\nFAILED TO EXECUTE QUERY||$sql_statement||: ".$this->mysqli->error()."\n\n");
    }

#echo "\n\n\t LEAVING exit_on_sql_error()";
}  // end of exit_on_sql_error()



////////////////////////////////////////////////////////////////////////////////////////



public function initialize()
{
#echo "\n\n\t ENTERING test_archiver_hierarchy_entries->initialize()";

    // Instantiate an ArchiverHierarchyEntries, and initialize it.
    // Initialization will cause it to create its table_names_arr array.
    // It is the table_names_arr array we need to access, to execute these tests.
    
//    $this->object_under_test = new ArchiverHierarchyEntries(TRUE); // TRUE to trace fn calls.
    $this->object_under_test = new ArchiverHierarchyEntries(FALSE);
//    $this->object_under_test->get_database_connection_info();



    $this->table_names_arr = $this->object_under_test->get_table_names_arr();
//    $this->display_table_names_arr();

//echo "\n\n\t\t RETURNING from table_names_arr = object_under_test->get_table_names_arr();";


    // Create a temporary table to hold intermediate results
    $this->initialize_archiver_temporary_table();


#echo "\n\n\t LEAVING  test_archiver_hierarchy_entries->initialize()";
} // end of initialize()



////////////////////////////////////////////////////////////////////////////////////////////////



public function initialize_archiver_temporary_table()
{
    // Create a temporary table to hold the test results.
    //
    // Table  : archiver_temporary_table
    //
    // Columns:
    //
    // table_names : the name of the primary table and all its foreign reference tables,
    //               as well as all their "..._archive" tables.
    // rowcount_before : after the set-up for each test, but before the test is actually
    //                   conducted we record the rowcounts.
    // rowcount_after  : after the test is conducted, we note the rowcounts again.
    //
    // expected_rowcount_delta : the expected difference between the before & after rowcounts.
    //
    // actual_rowcount_delta   : the actual difference between the before & after rowcounts.
    //
    // NB if the expected & actual rowcounts agree, the test passed. Otherwise, it failed.
   
   
    $sql_statement = "DROP TEMPORARY TABLE IF EXISTS archiver_temporary_table";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

    $sql_statement = "CREATE TEMPORARY TABLE archiver_temporary_table
                      (table_name              VARCHAR(64)
                      ,rowcount_before         INT DEFAULT 0
                      ,rowcount_after          INT DEFAULT 0
                      ,rowcount_delta_expected INT DEFAULT 0
                      ,rowcount_delta_actual   INT DEFAULT 0)";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );



    // $this->table_names_arr contains the names of the Primary Table, Foreign Reference Tables
    // and all their Archive Tables.
    // Populate archiver_temporary_table with these table names.
    foreach($this->table_names_arr as $table_name)
    {
        $sql_statement = "INSERT INTO archiver_temporary_table
                          (table_name
                          ,rowcount_before
                          ,rowcount_after
                          ,rowcount_delta_expected
                          ,rowcount_delta_actual)
                          VALUES('$table_name', 0, 0, 0, 0)";
        $result = $this->mysqli->query($sql_statement);
        $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
    }

//    $this->display_archiver_temporary_table();

} // end of initialize_archiver_temporary_table()



////////////////////////////////////////////////////////////////////////////////////////////////



/***
desc agents_hierarchy_entries;
+--------------------+---------------------+------+-----+---------+-------+
| Field              | Type                | Null | Key | Default | Extra |
+--------------------+---------------------+------+-----+---------+-------+
| hierarchy_entry_id | int(10) unsigned    | NO   | PRI | NULL    |       |
| agent_id           | int(10) unsigned    | NO   | PRI | NULL    |       |
| agent_role_id      | tinyint(3) unsigned | NO   | PRI | NULL    |       |
| view_order         | tinyint(3) unsigned | NO   |     | NULL    |       |
+--------------------+---------------------+------+-----+---------+-------+
4 rows in set (0.36 sec)
***/
public function insert_1row_into_agents_hierarchy_entries($_he_id, $_agent_id)
{
#echo "\n\n\t ENTERING insert_1row_into_agents_hierarchy_entries($_he_id, $_agent_id)";

    $_int = 9;
    $_tinyint = 1;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO agents_hierarchy_entries
                      (hierarchy_entry_id
                      ,agent_id
                      ,agent_role_id
                      ,view_order
                      )
                      VALUES
                      ($_he_id
                      ,$_agent_id
                      ,$_tinyint
                      ,$_tinyint )";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

#echo "\n\n\t LEAVING insert_1row_into_agents_hierarchy_entries($_he_id_1, $_he_id_2)";
}



////////////////////////////////////////////////////////////////////////////////////////////////



/***
 desc curated_hierarchy_entry_relationships;
+----------------------+---------------------+------+-----+---------+-------+
| Field                | Type                | Null | Key | Default | Extra |
+----------------------+---------------------+------+-----+---------+-------+
| hierarchy_entry_id_1 | int(10) unsigned    | NO   | PRI | NULL    |       |
| hierarchy_entry_id_2 | int(10) unsigned    | NO   | PRI | NULL    |       |
| user_id              | int(10) unsigned    | YES  |     | NULL    |       |
| equivalent           | tinyint(3) unsigned | NO   |     | NULL    |       |
+----------------------+---------------------+------+-----+---------+-------+
4 rows in set (0.00 sec)
***/
public function insert_1row_into_curated_hierarchy_entry_relationships($_he_id_1, $_he_id_2)
{
#echo "\n\n\t ENTERING insert_1row_into_curated_hierarchy_entry_relationships($_he_id_1, $_he_id_2)";

    $_int = 9;
    $_tinyint = 1;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO curated_hierarchy_entry_relationships
                      (hierarchy_entry_id_1
                      ,hierarchy_entry_id_2
                      ,user_id
                      ,equivalent
                      )
                      VALUES
                      ($_he_id_1
                      ,$_he_id_2
                      ,$_int
                      ,$_tinyint )";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

#echo "\n\n\t LEAVING insert_1row_into_curated_hierarchy_entry_relationships($_he_id_1, $_he_id_2)";
}



////////////////////////////////////////////////////////////////////////////////////////////////



/***
desc data_objects_hierarchy_entries;
+--------------------+------------------+------+-----+---------+-------+
| Field              | Type             | Null | Key | Default | Extra |
+--------------------+------------------+------+-----+---------+-------+
| hierarchy_entry_id | int(10) unsigned | NO   | PRI | NULL    |       |
| data_object_id     | int(10) unsigned | NO   | PRI | NULL    |       |
+--------------------+------------------+------+-----+---------+-------+
2 rows in set (0.00 sec)
***/
public function insert_1row_into_data_objects_hierarchy_entries($_he_id, $_data_object_id)
{
#echo "\n\n\t ENTERING insert_1row_into_data_objects_hierarchy_entries($_he_id, $_data_object_id)";

    $_int = 9;
    $_tinyint = 1;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO data_objects_hierarchy_entries
                      (hierarchy_entry_id
                      ,data_object_id
                      )
                      VALUES
                      ($_he_id
                      ,$_data_object_id )";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

#echo "\n\n\t LEAVING insert_1row_into_data_objects_hierarchy_entries()";
}



////////////////////////////////////////////////////////////////////////////////////////////////



/***
desc harvest_events_hierarchy_entries;
+--------------------+---------------------+------+-----+---------+-------+
| Field              | Type                | Null | Key | Default | Extra |
+--------------------+---------------------+------+-----+---------+-------+
| harvest_event_id   | int(10) unsigned    | NO   | PRI | NULL    |       |
| hierarchy_entry_id | int(10) unsigned    | NO   | PRI | NULL    |       |
| guid               | varchar(32)         | NO   | MUL | NULL    |       |
| status_id          | tinyint(3) unsigned | NO   |     | NULL    |       |
+--------------------+---------------------+------+-----+---------+-------+
4 rows in set (0.05 sec)
***/
public function insert_1row_into_harvest_events_hierarchy_entries($_he_id, $_harvest_event_id)
{
#echo "\n\n\t ENTERING insert_1row_into_harvest_events_hierarchy_entries($_he_id, $_harvest_event_id)";

    $_int = 9;
    $_tinyint = 1;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO harvest_events_hierarchy_entries
                      (harvest_event_id
                      ,hierarchy_entry_id
                      ,guid
                      ,status_id
                      )
                      VALUES
                      ($_harvest_event_id
                      ,$_he_id
                      ,'$_varchar'
                      ,$_tinyint )";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

#echo "\n\n\t LEAVING insert_1row_into_harvest_events_hierarchy_entries()";
}



////////////////////////////////////////////////////////////////////////////////////////////////



/***
desc hierarchy_entries;
+------------------+----------------------+------+-----+---------------------+----------------+
| Field            | Type                 | Null | Key | Default             | Extra          |
+------------------+----------------------+------+-----+---------------------+----------------+
| id               | int(10) unsigned     | NO   | PRI | NULL                | auto_increment |
| guid             | varchar(32)          | NO   |     | NULL                |                |
| identifier       | varchar(255)         | NO   | MUL | NULL                |                |
| source_url       | varchar(255)         | NO   |     | NULL                |                |
| name_id          | int(10) unsigned     | NO   | MUL | NULL                |                |

| parent_id        | int(10) unsigned     | NO   | MUL | NULL                |                |
| hierarchy_id     | smallint(5) unsigned | NO   | MUL | NULL                |                |
| rank_id          | smallint(5) unsigned | NO   |     | NULL                |                |
| ancestry         | varchar(500)         | NO   |     | NULL                |                |
| lft              | int(10) unsigned     | NO   | MUL | NULL                |                |

| rgt              | int(10) unsigned     | NO   |     | NULL                |                |
| depth            | tinyint(3) unsigned  | NO   |     | NULL                |                |
| taxon_concept_id | int(10) unsigned     | NO   | MUL | NULL                |                |
| vetted_id        | tinyint(3) unsigned  | NO   | MUL | 0                   |                |
| published        | tinyint(3) unsigned  | NO   | MUL | 0                   |                |

| visibility_id    | tinyint(3) unsigned  | NO   | MUL | 0                   |                |
| created_at       | timestamp            | NO   |     | CURRENT_TIMESTAMP   |                |
| updated_at       | timestamp            | NO   |     | 0000-00-00 00:00:00 |                |
| archive          | tinyint(1)           | YES  | MUL | 0                   |                |
+------------------+----------------------+------+-----+---------------------+----------------+
19 rows in set (0.09 sec)
***/
public function insert_1row_into_hierarchy_entries($_primary_id=0)
{
#echo "\n\n\t ENTERING insert_1row_into_hierarchy_entries($_primary_id)";


    $_int = 9;
    $_tinyint = 1;
    $_smallint = 2;
    $_bigint = 99;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement =
"insert into hierarchy_entries
(id
,guid
,identifier
,source_url
,name_id

,parent_id
,hierarchy_id
,rank_id
,ancestry
,lft

,rgt
,depth
,taxon_concept_id
,vetted_id
,published

,visibility_id
,created_at
,updated_at
,archive)

VALUES
($_primary_id
,'$_varchar'
,'$_varchar'
,'$_varchar'
,$_int

,$_int
,$_smallint
,$_smallint
,'$_varchar'
,$_int

,$_int
,$_tinyint
,$_int
,$_tinyint
,$_tinyint

,$_tinyint
,'$_time'
,'$_time'
,$_tinyint)";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

#echo "\n\n\t LEAVING insert_1row_into_hierarchy_entries($_primary_id)";
} // end of insert_1row_into_hierarchy_entries()



////////////////////////////////////////////////////////////////////////////////////////////////



/***
desc hierarchy_entries_refs;
+--------------------+------------------+------+-----+---------+-------+
| Field              | Type             | Null | Key | Default | Extra |
+--------------------+------------------+------+-----+---------+-------+
| hierarchy_entry_id | int(10) unsigned | NO   | PRI | NULL    |       |
| ref_id             | int(10) unsigned | NO   | PRI | NULL    |       |
+--------------------+------------------+------+-----+---------+-------+
2 rows in set (0.02 sec)
***/
public function insert_1row_into_hierarchy_entries_refs($_he_id, $_ref_id)
{
#echo "\n\n\t ENTERING insert_1row_into_hierarchy_entries_refs($_he_id, $_ref_id)";

    $_int = 9;
    $_tinyint = 1;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO hierarchy_entries_refs
                      (hierarchy_entry_id, ref_id
                      )
                      VALUES
                      ($_he_id, $_ref_id )";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

#echo "\n\n\t LEAVING insert_1row_into_hierarchy_entries_refs($_he_id, $_ref_id)";
}



////////////////////////////////////////////////////////////////////////////////////////////////



/***
desc synonyms;
+---------------------+----------------------+------+-----+---------+----------------+
| Field               | Type                 | Null | Key | Default | Extra          |
+---------------------+----------------------+------+-----+---------+----------------+
| id                  | int(10) unsigned     | NO   | PRI | NULL    | auto_increment |
| name_id             | int(10) unsigned     | NO   | MUL | NULL    |                |
| synonym_relation_id | tinyint(3) unsigned  | NO   |     | NULL    |                |
| language_id         | smallint(5) unsigned | NO   |     | NULL    |                |
| hierarchy_entry_id  | int(10) unsigned     | NO   | MUL | NULL    |                |
| preferred           | tinyint(3) unsigned  | NO   |     | NULL    |                |
| hierarchy_id        | smallint(5) unsigned | NO   |     | NULL    |                |
| vetted_id           | tinyint(3) unsigned  | NO   |     | 0       |                |
| published           | tinyint(3) unsigned  | NO   |     | 0       |                |
+---------------------+----------------------+------+-----+---------+----------------+
9 rows in set (0.05 sec)
***/
public function insert_1row_into_synonyms($_he_id, $_kp)
{
#echo "\n\n\t ENTERING insert_1row_into_synonyms($_he_id, $_kp)";

    $_int = 9;
    $_tinyint = 1;
    $_smallint = 2;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO synonyms
                      (id
                      ,name_id
                      ,synonym_relation_id
                      ,language_id
                      ,hierarchy_entry_id
                      ,preferred
                      ,hierarchy_id
                      ,vetted_id
                      ,published
                      )
                      VALUEs
                      ($_kp
                      ,$_kp
                      ,$_tinyint
                      ,$_smallint
                      ,$_he_id
                      ,$_tinyint
                      ,$_smallint
                      ,$_tinyint
                      ,$_tinyint )";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

#echo "\n\n\t LEAVING insert_1row_into_synonyms($_he_id, $_kp)\n";
}



////////////////////////////////////////////////////////////////////////////////////////////////



public function insert_into_hierarchy_entries($_max_inserts=0)
{
#echo "\n\n\t ENTERING insert_into_hierarchy_entries($_max_inserts)";

    for ($i=1; $i <= $_max_inserts; $i++)
    {
        $this->insert_1row_into_hierarchy_entries();
    }

#echo "\n\n\t LEAVING insert_into_hierarchy_entries($_max_inserts)";
} // end of insert_into_hierarchy_entries()



////////////////////////////////////////////////////////////////////////////////////////////////



// Foreign Reference tables:
// - agents_hierarchy_entries
// - curated_hierarchy_entry_relationships
// - data_objects_hierarchy_entries
// - harvest_events_hierarchy_entries
// - hierarchy_entries_refs
// - synonyms

public function populate_frts($row_limit, $_he_id, $_offset)
{
#echo "\n\n\t ENTERING populate_frts($row_limit, $_he_id)";

    for ($i=1; $i <= $row_limit; $i++)
    {
        $this->insert_1row_into_agents_hierarchy_entries(              $_he_id, $i + $_offset);
        $this->insert_1row_into_curated_hierarchy_entry_relationships( $_he_id, $i + $_offset);
        $this->insert_1row_into_data_objects_hierarchy_entries(        $_he_id, $i + $_offset);
        $this->insert_1row_into_harvest_events_hierarchy_entries(      $_he_id, $i + $_offset);
        $this->insert_1row_into_hierarchy_entries_refs(                $_he_id, $i + $_offset);
        $this->insert_1row_into_synonyms(                              $_he_id, $i + $_offset);
    }

#echo "\n\n\t LEAVING populate_frts($row_limit, $_he_id)";
} // end of populate_frts()



////////////////////////////////////////////////////////////////////////////////////////////////



public function table_isnot_empty($table_name)
{
    // If $table_name is not empty, return true. else return false.

    $sql_statement = "SELECT count(*) FROM $table_name";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
    $row = $result->fetch_array(MYSQLI_NUM);
    $rowcount = $row[0];

    return($rowcount);

} // end of table_isnot_empty



////////////////////////////////////////////////////////////////////////////////////////////////



public function truncate_all_tables()
{
#echo "\n\n\t ENTERING truncate_all_tables()";

    foreach($this->table_names_arr as $table_name)
    {
        $this->truncate_table($table_name);
    }

#echo "\n\n\t LEAVING truncate_all_tables()";
} // end of truncate_all_tables()



////////////////////////////////////////////////////////////////////////////////////////////////



public function truncate_table($table_name)
{

    $sql_statement = "TRUNCATE TABLE $table_name";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

} // end of truncate_table()



////////////////////////////////////////////////////////////////////////////////////////////////



public function update_archive_column($table_name, $true_false, $max_rowcount)
{
#echo "\n\n\t ENTERING update_archive_column($table_name, $true_false, $max_rowcount)";


    $sql_statement = "UPDATE $table_name SET archive=$true_false LIMIT $max_rowcount";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

#echo "\n\n\t LEAVING update_archive_column()";
} // end of update_archive_column()



////////////////////////////////////////////////////////////////////////////////////////////////



public function update_delta_actual($table_name, $calculation)
{
#echo "\n\n\t ENTERING update_delta_actual($table_name, $calculation)";

        $sql_statement = "UPDATE archiver_temporary_table
                          SET rowcount_delta_actual = $calculation
                          WHERE table_name LIKE '$table_name'";

        $result = $this->mysqli->query($sql_statement);
        $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

#echo "\n\n\t LEAVING update_delta_actual()";
} // end of update_delta_actual()



////////////////////////////////////////////////////////////////////////////////////////////////



public function update_delta_expected($table_name, $expected)
{
#echo "\n\n\t ENTERING update_delta_expected($table_name, $expected)";

        $sql_statement = "UPDATE archiver_temporary_table
                          SET rowcount_delta_expected = $expected
                          WHERE table_name LIKE '$table_name'";

        $result = $this->mysqli->query($sql_statement);
        $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );


#echo "\n\n\t LEAVING update_delta_expected()";
} // end of update_delta_expected()



////////////////////////////////////////////////////////////////////////////////////////////////



public function update_rowcounts_after()
{
#echo "\n\n\t ENTERING update_rowcounts_after()";

    // For each TableName in table_names_arr, determine that table's rowcount,
    // and set archiver_temporary_table.rowcount_after = the rowcount

    foreach($this->table_names_arr as $table_name)
    {
        //echo "\n\t\t >>>>>$table_name ";

        $sql_statement = "SELECT count(*) FROM $table_name";
        $result = $this->mysqli->query($sql_statement);
        $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
        $row = $result->fetch_array(MYSQLI_NUM);
        $rowcount_after = $row[0];



        $sql_statement = "UPDATE archiver_temporary_table
                          SET rowcount_after = $rowcount_after
                          WHERE table_name = '$table_name'";
        $result = $this->mysqli->query($sql_statement);
        $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
    }

#echo "\n\n\t LEAVING update_rowcounts_after()";
} // end of update_rowcounts_after



////////////////////////////////////////////////////////////////////////////////////////////////



public function update_rowcounts_before()
{
#echo "\n\n\t ENTERING update_rowcounts_before()";

    // For each Table Name in table_names_arr, set archiver_temporary_table.rowcount_before
    foreach($this->table_names_arr as $table_name)
    {
        $sql_statement = "SELECT count(*) FROM $table_name";
        $result = $this->mysqli->query($sql_statement);
        $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
        $row = $result->fetch_array(MYSQLI_NUM);
        $rowcount_before = $row[0];



        $sql_statement = "UPDATE archiver_temporary_table
                          SET rowcount_before = $rowcount_before
                          WHERE table_name = '$table_name'";
        $result = $this->mysqli->query($sql_statement);
        $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
    }

#echo "\n\n\t LEAVING update_rowcounts_before()";
} // end of update_rowcounts_before



////////////////////////////////////////////////////////////////////////////////////////////////



} // end of class test_archiver_hierarchy_entries

?>

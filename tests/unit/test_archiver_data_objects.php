<?php


////////////////////////////////////////////////////////////////////////////////////////////////
//
// This class tests the functionality of an ArchiverDataObjects instance.
// It performs the following:
//
// * Instantiate an ArchiverDataObjects instance
//
// * Ask the instance for an array (table_names_arr) containing the names
//   of the tables it intends to archive.
//   e.g.
//   Primary Table: 
//   - data_objects
//
//   Foreign Reference Table:
//   - agents_data_objects
//   - audiences_data_objects
//   - data_objects_harvest_events
//   - data_objects_hierarchy_entries
//   - data_objects_info_items
//   - data_objects_refs
//   - data_objects_table_of_contents
//   - data_objects_untrust_reasons
//
// * Create a temporary table (archiver_temporary_table) in which to keep track of
//   all the table names, and the results of the tests. (rowcounts before and after each test,
//   as well as the expected and actual changes in rowcounts)
//   See the init_archiver_temporary_table() method for details.
//
// * It then causes the ArchiverDataObjects instance to perform its archive operations.
//
// * Finally, it compares the expected rowcounts to the actual rowcounts. If they are the
//   same, the test passes. Otherwise, it fails.
//
//
// The boundary conditions tested are:
//  NONE: No DO or do_id rows, or No Foreign Reference Tables (FRTs) involved
//  ONE: 1 DO or do_id row, or 1 FRT involved
//  SOME: 2 DO or do_id rows, or 2 FRTs involved
//  ALL_BUT_1: All but 1 DO or do_id rows, or All but 1 FRTs involved
//  ALL: ALL DO or do_id rows, or ALL FRTs involved
// 
// Assuming that ALL=10 and SOME=2. Then in the following test descriptions:
// • None = 0
// • One = 1
// • Some = 2
// • ALL_BUT_1 = 9 
// • All = 10
// 
////////////////////////////////////////////////////////////////////////////////////////////////



require_once(dirname(__FILE__) . "/../../config/environment.php");
require_once(DOC_ROOT . 'vendor/simpletest_extended/simpletest_unit_base.php');


include '../rake_tasks/ArchiverDataObjects.php';



////////////////////////////////////////////////////////////////////////////////////////////////



class test_archiver_data_objects extends SimpletestUnitBase
{

    private $object_under_test;
    private $table_names_arr = array();
    private $arr_elements    = array();

    private $primary_table_name = "data_objects";



////////////////////////////////////////////////////////////////////////////////////////////////



public function __construct()
{
    $this->mysqli =& $GLOBALS["db_connection"];

    $this->initialize();

} // end of constructor



////////////////////////////////////////////////////////////////////////////////////////////////



public function setUp()
{
//echo "\n\n\t ENTERING setUp()";


    // truncate DO & FRT Tables
    $this->truncate_all_tables();


//echo "\n\n\t LEAVING  setUp()";
}



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
TEST a_1

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows. update all archive=FALSE.

RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 DO   rowcount unchanged. 
*********/

public function test_a1()
{

    $test_name = "DataObjecs A1";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows. UPDATE DataObjects.archive=FALSE in all rows.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', 10);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



    // ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables.
    $this->update_delta_expected('%', 0);



    // ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_a1 FAILED\n\n");

}  // end of test_a1()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST  a_2

DATA SET-UP:
 Truncate DO & FRTs
 Populate DO with 10 rows. update all archive=FALSE, update 1 row archive=TRUE;

RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 DO   rowcount -1. 
 DO_A rowcount +1. 
*********/

public function test_a2()
{

    $test_name = "DataObjecs A2";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', 10);
    $this->update_archive_column('data_objects', 'TRUE',   1);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



    // ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);



    // ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_a2 FAILED\n\n");

}  // end of test_a2()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST a_3

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows. update ALL archive=FALSE, update 2 row2 archive=TRUE;

RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 DO   rowcount -2. 
 DO_A rowcount +2. 
*********/

public function test_a3()
{

    $test_name = "DataObjecs A3";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE in 2 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', 10);
    $this->update_archive_column('data_objects', 'TRUE',   2);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



    // ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -2);
    $this->update_delta_expected('data_objects_archive', +2);



    // ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_a3 FAILED\n\n");

}  // end of test_a3()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST  a_4

DATA SET-UP:
 Truncate DO & FRTs
 Populate DO with 10 rows. update ALL archive=TRUE. update 1 archive=FALSE.

RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 DO   rowcount -9
 DO_A rowcount +9
*********/

public function test_a4()
{

    $test_name = "DataObjecs A4";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=TRUE  in all rows.
    // UPDATE DataObjects.archive=FALSE in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE', 10);
    $this->update_archive_column('data_objects', 'FALSE',   1);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



    // ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -9);
    $this->update_delta_expected('data_objects_archive', +9);



    // ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_a4 FAILED\n\n");

}  // end of test_a4()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST  a_5

DATA SET-UP:
 Truncate DO & FRTs
 Populate DO with 10 rows. update ALL archive=TRUE;

RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 DO   rowcount -10
 DO_A rowcount +10
*********/

public function test_a5()
{

    $test_name = "DataObjecs A5";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=TRUE  in all rows.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE', 10);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



    // ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();



    // EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -10);
    $this->update_delta_expected('data_objects_archive', +10);



    // ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_a5 FAILED\n\n");

}  // end of test_a5()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST  b_1: involving 0 FRTs

DATA SET-UP:
 Truncate DO & FRTs
 Populate DO with 10 rows - DO.id 1 to 10. update all archive=FALSE, update archive=TRUE limit 1
 Populate FRTs with 10 rows each, where do_id=11

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 FRT rowcount unchanged.
*********/

public function test_b1()
{

    $test_name = "DataObjecs B1";
    //echo"\n\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


    // ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();




    // EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);



    // ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_b1 FAILED\n\n");

}  // end of test_b1()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST  b_2: involving 1 FRT

DATA SET-UP:
 Truncate DO & FRTs
 Populate DO with 10 rows - DO.id 1 to 10. update all archive=FALSE, update archive=TRUE where DO.id=1
 Populate FRTs with 10 rows each, where do_id=11; insert into 1 FRT a row where do_id=1

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 1 FRT   table with rowcount -1
 1 FRT_A table with rowcount +1
*********/

public function test_b2()
{

    $test_name = "DataObjecs B2";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0);
    $this->insert_1row_into_audiences_data_objects(1, 1);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


    // ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();




    // EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);

    $this->update_delta_expected('audiences_data_objects', -1);
    $this->update_delta_expected('audiences_data_objects_archive', +1);



    // ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_b2 FAILED\n\n");

}  // end of test_b2()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST  b_3: involving 2 FRTs

DATA SET-UP:
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=FALSE, update archive=TRUE where DO.id=1
 Populate FRTs with 10 rows each, where do_id=11; update 2 FRTs do_id=1 LIMIT 1

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 2 FRT   tables with rowcount -1
 2 FRT_A tables with rowcount +1
*********/

public function test_b3()
{

    $test_name = "DataObjecs B3";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0);
    $this->insert_1row_into_audiences_data_objects(1, 1);
    $this->insert_1row_into_agents_data_objects(1, 1);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


    // ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();




    // EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);

    $this->update_delta_expected('audiences_data_objects', -1);
    $this->update_delta_expected('audiences_data_objects_archive', +1);

    $this->update_delta_expected('agents_data_objects', -1);
    $this->update_delta_expected('agents_data_objects_archive', +1);



    // ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_b3 FAILED\n\n");

}  // end of test_b3()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST  b_4: involving ALL_BUT_1 FRTs
DATA SET-UP:
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=FALSE, update archive=TRUE where DO.id=1
 Populate FRTs with 10 rows each, where do_id=1 LIMIT 1. update 1 FRT do_id=11

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 ALL_BUT_1 FRT   tables with rowcount -1
 ALL_BUT_1 FRT_A tables with rowcount +1
*********/

public function test_b4()
{

    $test_name = "DataObjecs B4";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0);
    
    // in all but one of the FRTs, add 1 rows where do_id=1
    $this->insert_1row_into_audiences_data_objects(        1, 1);
    $this->insert_1row_into_agents_data_objects(           1, 1);
    $this->insert_1row_into_data_objects_harvest_events(   1, 1);
    $this->insert_1row_into_data_objects_hierarchy_entries(1, 1);
    $this->insert_1row_into_data_objects_info_items(       1, 1);
    $this->insert_1row_into_data_objects_refs(             1, 1);
    $this->insert_1row_into_data_objects_table_of_contents(1, 1);

    // but not this FRT
    // $this->insert_1row_into_data_objects_untrust_reasons(  1, 1);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


    // ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.rowcount_before AFTER archiving
    $this->update_rowcounts_after();




    // EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);

    $this->update_delta_expected('audiences_data_objects', -1);
    $this->update_delta_expected('audiences_data_objects_archive', +1);

    $this->update_delta_expected('agents_data_objects', -1);
    $this->update_delta_expected('agents_data_objects_archive', +1);

    $this->update_delta_expected('data_objects_harvest_events', -1);
    $this->update_delta_expected('data_objects_harvest_events_archive', +1);

    $this->update_delta_expected('data_objects_hierarchy_entries', -1);
    $this->update_delta_expected('data_objects_hierarchy_entries_archive', +1);

    $this->update_delta_expected('data_objects_info_items', -1);
    $this->update_delta_expected('data_objects_info_items_archive', +1);

    $this->update_delta_expected('data_objects_refs', -1);
    $this->update_delta_expected('data_objects_refs_archive', +1);

    $this->update_delta_expected('data_objects_table_of_contents', -1);
    $this->update_delta_expected('data_objects_table_of_contents_archive', +1);

    // but not this FRT
    // $this->update_delta_expected('data_objects_untrust_reason's, -1);
    // $this->update_delta_expected('data_objects_untrust_reasons_archive', +1);



    // ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_b4 FAILED\n\n");

}  // end of test_b4()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST  b_5: involving ALL FRTs

DATA SET-UP:
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=FALSE, update archive=TRUE where DO.id=1
 Populate FRTs with 10 rows each, where do_id=1 LIMIT 1

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 ALL FRT   tables with rowcount -1
 ALL FRT_A tables with rowcount +1
*********/

public function test_b5()
{

    $test_name = "DataObjecs B5";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0); // populate_frts($row_limit, $do_id, $_offset)
    
    // in all FRTs, add 1 rows where do_id=1
    $this->insert_1row_into_audiences_data_objects(        1, 11); //($data_object_id,$_audience_id)
    $this->insert_1row_into_agents_data_objects(           1, 11);
    $this->insert_1row_into_data_objects_harvest_events(   1, 11);
    $this->insert_1row_into_data_objects_hierarchy_entries(1, 11);
    $this->insert_1row_into_data_objects_info_items(       1, 11);
    $this->insert_1row_into_data_objects_refs(             1, 11);
    $this->insert_1row_into_data_objects_table_of_contents(1, 11);
    $this->insert_1row_into_data_objects_untrust_reasons(  1, 11);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


// ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);

    $this->update_delta_expected('audiences_data_objects', -1);
    $this->update_delta_expected('audiences_data_objects_archive', +1);

    $this->update_delta_expected('agents_data_objects', -1);
    $this->update_delta_expected('agents_data_objects_archive', +1);

    $this->update_delta_expected('data_objects_harvest_events', -1);
    $this->update_delta_expected('data_objects_harvest_events_archive', +1);

    $this->update_delta_expected('data_objects_hierarchy_entries', -1);
    $this->update_delta_expected('data_objects_hierarchy_entries_archive', +1);

    $this->update_delta_expected('data_objects_info_items', -1);
    $this->update_delta_expected('data_objects_info_items_archive', +1);

    $this->update_delta_expected('data_objects_refs', -1);
    $this->update_delta_expected('data_objects_refs_archive', +1);

    $this->update_delta_expected('data_objects_table_of_contents', -1);
    $this->update_delta_expected('data_objects_table_of_contents_archive', +1);

    $this->update_delta_expected('data_objects_untrust_reasons', -1);
    $this->update_delta_expected('data_objects_untrust_reasons_archive', +1);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_b5 FAILED\n\n");

}  // end of test_b5()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST c_1: 0 FRT rows

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=FALSE, update archive=TRUE limit 1
 Populate FRTs with 10 rows each, where do_id=11

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 ALL FRT   tables with rowcount 0
 ALL FRT_A tables with rowcount 0
*********/

public function test_c1()
{

    $test_name = "DataObjecs C1";
    //echo"\n\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



// ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);

    $this->update_delta_expected('audiences_data_objects', 0);
    $this->update_delta_expected('audiences_data_objects_archive', 0);

    $this->update_delta_expected('agents_data_objects', 0);
    $this->update_delta_expected('agents_data_objects_archive', 0);

    $this->update_delta_expected('data_objects_harvest_events', 0);
    $this->update_delta_expected('data_objects_harvest_events_archive', 0);

    $this->update_delta_expected('data_objects_hierarchy_entries', 0);
    $this->update_delta_expected('data_objects_hierarchy_entries_archive', 0);

    $this->update_delta_expected('data_objects_info_items', 0);
    $this->update_delta_expected('data_objects_info_items_archive', 0);

    $this->update_delta_expected('data_objects_refs', 0);
    $this->update_delta_expected('data_objects_refs_archive', 0);

    $this->update_delta_expected('data_objects_table_of_contents', 0);
    $this->update_delta_expected('data_objects_table_of_contents_archive', 0);

    $this->update_delta_expected('data_objects_untrust_reasons', 0);
    $this->update_delta_expected('data_objects_untrust_reasons_archive', 0);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');
//$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_c1 FAILED\n\n");



}  // end of test_c1()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST c_2: 1 FRT with 1 row

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update archive, 9FALSE, 1 TRUE
 Populate FRTs with 10 rows each, where do_id=11. update 1 FRT archive=TRUE LIMIT 1

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 1 FRT   table with rowcount -1
 1 FRT_A table with rowcount +1
*********/

public function test_c2()
{

    $test_name = "DataObjecs C2";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0);

    // in 1 FRT, update 1 rows where do_id=1
    $this->update_data_objects_untrust_reasons(1, 1); //($max_rows, $_primary_id)


    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


// ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);

    $this->update_delta_expected('audiences_data_objects', 0);
    $this->update_delta_expected('audiences_data_objects_archive', 0);

    $this->update_delta_expected('agents_data_objects', 0);
    $this->update_delta_expected('agents_data_objects_archive', 0);

    $this->update_delta_expected('data_objects_harvest_events', 0);
    $this->update_delta_expected('data_objects_harvest_events_archive', 0);

    $this->update_delta_expected('data_objects_hierarchy_entries', 0);
    $this->update_delta_expected('data_objects_hierarchy_entries_archive', 0);

    $this->update_delta_expected('data_objects_info_items', 0);
    $this->update_delta_expected('data_objects_info_items_archive', 0);

    $this->update_delta_expected('data_objects_refs', 0);
    $this->update_delta_expected('data_objects_refs_archive', 0);

    $this->update_delta_expected('data_objects_table_of_contents', 0);
    $this->update_delta_expected('data_objects_table_of_contents_archive', 0);

    $this->update_delta_expected('data_objects_untrust_reasons', -1);
    $this->update_delta_expected('data_objects_untrust_reasons_archive', +1);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_c2 FAILED\n\n");

}  // end of test_c2()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST c_3: 1 FRT with 2 row

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update archive, 9FALSE, 1 TRUE
 Populate FRTs with 10 rows each, where do_id=11. update 2 FRTs: archive=TRUE LIMIT 2

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 1 FRT   table with rowcount -1
 1 FRT_A table with rowcount +1
*********/

public function test_c3()
{

    $test_name = "DataObjecs C3";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0);

    // in 1 FRT, update 1 rows where do_id=1
    $this->update_data_objects_untrust_reasons(2, 1); //($max_rows, $_primary_id)


    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


// ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);

    $this->update_delta_expected('audiences_data_objects', 0);
    $this->update_delta_expected('audiences_data_objects_archive', 0);

    $this->update_delta_expected('agents_data_objects', 0);
    $this->update_delta_expected('agents_data_objects_archive', 0);

    $this->update_delta_expected('data_objects_harvest_events', 0);
    $this->update_delta_expected('data_objects_harvest_events_archive', 0);

    $this->update_delta_expected('data_objects_hierarchy_entries', 0);
    $this->update_delta_expected('data_objects_hierarchy_entries_archive', 0);

    $this->update_delta_expected('data_objects_info_items', 0);
    $this->update_delta_expected('data_objects_info_items_archive', 0);

    $this->update_delta_expected('data_objects_refs', 0);
    $this->update_delta_expected('data_objects_refs_archive', 0);

    $this->update_delta_expected('data_objects_table_of_contents', 0);
    $this->update_delta_expected('data_objects_table_of_contents_archive', 0);

    $this->update_delta_expected('data_objects_untrust_reasons', -2);
    $this->update_delta_expected('data_objects_untrust_reasons_archive', +2);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_c3 FAILED\n\n");

}  // end of test_c3()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST c_4: 1 FRT with ALL_BUT_1 rows

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update archive, 9FALSE, 1 TRUE
 Populate FRTs with 10 rows each, where do_id=11. update 1 FRT archive=TRUE LIMIT 9

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 1 FRT   table with rowcount -9
 1 FRT_A table with rowcount +9
*********/

public function test_c4()
{

    $test_name = "DataObjecs C4";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0);

    // in 1 FRT, update 1 rows where do_id=1
    $this->update_data_objects_untrust_reasons(9, 1); //($max_rows, $_primary_id)


    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


// ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);

    $this->update_delta_expected('audiences_data_objects', 0);
    $this->update_delta_expected('audiences_data_objects_archive', 0);

    $this->update_delta_expected('agents_data_objects', 0);
    $this->update_delta_expected('agents_data_objects_archive', 0);

    $this->update_delta_expected('data_objects_harvest_events', 0);
    $this->update_delta_expected('data_objects_harvest_events_archive', 0);

    $this->update_delta_expected('data_objects_hierarchy_entries', 0);
    $this->update_delta_expected('data_objects_hierarchy_entries_archive', 0);

    $this->update_delta_expected('data_objects_info_items', 0);
    $this->update_delta_expected('data_objects_info_items_archive', 0);

    $this->update_delta_expected('data_objects_refs', 0);
    $this->update_delta_expected('data_objects_refs_archive', 0);

    $this->update_delta_expected('data_objects_table_of_contents', 0);
    $this->update_delta_expected('data_objects_table_of_contents_archive', 0);

    $this->update_delta_expected('data_objects_untrust_reasons', -9);
    $this->update_delta_expected('data_objects_untrust_reasons_archive', +9);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_c4 FAILED\n\n");

}  // end of test_c4()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST c_5: 1 FRT   table with ALL rows

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update archive, 10 TRUE
 Populate FRTs with 10 rows each, where do_id=11. update 1 FRT archive=TRUE

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
 1 FRT   table with rowcount -10
 1 FRT_A table with rowcount +10
*********/

public function test_c5()
{

    $test_name = "DataObjecs C5";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    //Populate FRTs with 10 rows each, where do_id=11
    $this->populate_frts(10,11,0);

    // in 1 FRT, update 1 rows where do_id=1
    $this->update_data_objects_untrust_reasons(10, 1); //($max_rows, $_primary_id)


    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();


// ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);

    $this->update_delta_expected('audiences_data_objects', 0);
    $this->update_delta_expected('audiences_data_objects_archive', 0);

    $this->update_delta_expected('agents_data_objects', 0);
    $this->update_delta_expected('agents_data_objects_archive', 0);

    $this->update_delta_expected('data_objects_harvest_events', 0);
    $this->update_delta_expected('data_objects_harvest_events_archive', 0);

    $this->update_delta_expected('data_objects_hierarchy_entries', 0);
    $this->update_delta_expected('data_objects_hierarchy_entries_archive', 0);

    $this->update_delta_expected('data_objects_info_items', 0);
    $this->update_delta_expected('data_objects_info_items_archive', 0);

    $this->update_delta_expected('data_objects_refs', 0);
    $this->update_delta_expected('data_objects_refs_archive', 0);

    $this->update_delta_expected('data_objects_table_of_contents', 0);
    $this->update_delta_expected('data_objects_table_of_contents_archive', 0);

    $this->update_delta_expected('data_objects_untrust_reasons', -10);
    $this->update_delta_expected('data_objects_untrust_reasons_archive', +10);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_c5 FAILED\n\n");

}  // end of test_c5()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST d_1: max rowcount = 0

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=TRUE

RUN TEST: max rowcount = 0

EXPECTED RESULT:
 DO   rowcount 0
 DO_A rowcount 0
*********/

public function test_d1()
{

    $test_name = "DataObjecs D1";
    //echo"\n\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=TRUE in all rows.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE', $max_rowcount);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



// ARCHIVE, max 0 do_ids
    $this->object_under_test->process_archivable_ids(0);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', 0);
    $this->update_delta_expected('data_objects_archive', 0);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');
//$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_d1 FAILED\n\n");

}  // end of test_d1()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST D.2: max rowcount = 1

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=TRUE

RUN TEST: max rowcount = 1

EXPECTED RESULT:
 DO   rowcount -1
 DO_A rowcount +1
*********/

public function test_d2()
{

    $test_name = "DataObjecs D2";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=TRUE in all rows.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE', $max_rowcount);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



// ARCHIVE, max 1 do_ids
    $this->object_under_test->process_archivable_ids(1);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -1);
    $this->update_delta_expected('data_objects_archive', +1);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_d2 FAILED\n\n");

}  // end of test_d2()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST d_3: max rowcount = 2

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=TRUE

RUN TEST: max rowcount = 2

EXPECTED RESULT:
 DO   rowcount -2
 DO_A rowcount +2
*********/

public function test_d3()
{

    $test_name = "DataObjecs D3";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=TRUE in all rows.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE', $max_rowcount);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



// ARCHIVE, max 2 do_ids
    $this->object_under_test->process_archivable_ids(2);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -2);
    $this->update_delta_expected('data_objects_archive', +2);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_d3 FAILED\n\n");

}  // end of test_d3()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST d_4: max rowcount = 9

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=TRUE

RUN TEST: max rowcount = 9

EXPECTED EXPECTED RESULT:
 DO   rowcount -9
 DO_A rowcount +9
*********/

public function test_d4()
{

    $test_name = "DataObjecs D4";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=TRUE in all rows.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE', $max_rowcount);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



// ARCHIVE, max 9 do_ids
    $this->object_under_test->process_archivable_ids(9);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -9);
    $this->update_delta_expected('data_objects_archive', +9);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_d4 FAILED\n\n");

}  // end of test_d4()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST d_5: max rowcount = 10

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=TRUE

RUN TEST: max rowcount = 10

EXPECTED RESULT:
 DO   rowcount -10
 DO_A rowcount +10
*********/

public function test_d5()
{

    $test_name = "DataObjecs D5";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=TRUE in all rows.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE', $max_rowcount);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



// ARCHIVE, max 10 do_ids
    $this->object_under_test->process_archivable_ids($max_rowcount);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -$max_rowcount);
    $this->update_delta_expected('data_objects_archive', +$max_rowcount);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_d5 FAILED\n\n");

}  // end of test_d5()



////////////////////////////////////////////////////////////////////////////////////////////////




/*********
TEST d_6: greater than max rowcount = 100

DATA SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows, with DO.id 1 to 10. update all archive=TRUE

RUN TEST: max rowcount = 100

EXPECTED RESULT:
 DO   rowcount -10
 DO_A rowcount +10
*********/

public function test_d6()
{

    $test_name = "DataObjecs D6";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=TRUE in all rows.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE', $max_rowcount);



    // Update archiver_temporary_table.rowcount_before BEFORE archiving
    $this->update_rowcounts_before();



// ARCHIVE, max 100 do_ids
    $this->object_under_test->process_archivable_ids(100);



    // Update archiver_temporary_table.update_rowcounts_after AFTER archiving
    $this->update_rowcounts_after();




// EXPECTED RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_expected=0 in all tables,
    //    except for data_objects & data_objects_archive which should be -1 & +1 respectively.
    $this->update_delta_expected('%', 0);
    $this->update_delta_expected('data_objects', -$max_rowcount);
    $this->update_delta_expected('data_objects_archive', +$max_rowcount);



// ACTUAL RESULT:
    // DO rowcount unchanged.
    // DO_ARCHIVE rowcount unchanged.
    //    Update archiver_temporary_table.rowcount_delta_actual=(rowcount_before-rowcount_after) in all tables.
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

    //$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\ttest_d6 FAILED\n\n");

}  // end of test_d6()



////////////////////////////////////////////////////////////////////////////////////////////////



/*********
TEST e1: 
All the required tables are being archived.
i.e.:
   - agents_data_objects
   - audiences_data_objects
   - data_objects
   - data_objects_harvest_events
   - data_objects_hierarchy_entries
   - data_objects_info_items
   - data_objects_refs
   - data_objects_table_of_contents
   - data_objects_untrust_reasons

SET-UP: 
 Truncate DO & FRTs
 Populate DO with 10 rows. update all archive=FALSE. Update 1 where archive-TRUE.
 Populate all FRTs with 2 rows, where 
 - row references a DO id being archived.
 - row references s DO id NOT being archived.
 
RUN TEST: max rowcount = 100

EXPECTED RESULT: 
 HE   rowcount -2. 
 curated_hierarchy_entry_relationships   -1. 
*********/

public function test_e1()
{

    $test_name = "DataObjecs e1";
    //echo"\t$test_name";
    $max_rowcount = 10;

    // ADDITIONAL SETUP
    // Populate DataObjects with 10 rows.
    // UPDATE DataObjects.archive=FALSE in all rows.
    // UPDATE DataObjects.archive=TRUE  in 1 row.
    $this->insert_into_data_objects($max_rowcount);
    $this->update_archive_column('data_objects', 'FALSE', $max_rowcount);
    $this->update_archive_column('data_objects', 'TRUE',   1);

    
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
    $this->update_delta_expected('data_objects',                           -1);
    $this->update_delta_expected('data_objects_archive',                   +1);

    $this->update_delta_expected('agents_data_objects',                    -1);
    $this->update_delta_expected('agents_data_objects_archive',            +1);

    $this->update_delta_expected('audiences_data_objects',                 -1);
    $this->update_delta_expected('audiences_data_objects_archive',         +1);

    $this->update_delta_expected('data_objects_harvest_events',            -1);
    $this->update_delta_expected('data_objects_harvest_events_archive',    +1);

    $this->update_delta_expected('data_objects_hierarchy_entries',         -1);
    $this->update_delta_expected('data_objects_hierarchy_entries_archive', +1);

    $this->update_delta_expected('data_objects_info_items',                -1);
    $this->update_delta_expected('data_objects_info_items_archive',        +1);

    $this->update_delta_expected('data_objects_refs',                      -1);
    $this->update_delta_expected('data_objects_refs_archive',              +1);

    $this->update_delta_expected('data_objects_table_of_contents',         -1);
    $this->update_delta_expected('data_objects_table_of_contents_archive', +1);

    $this->update_delta_expected('data_objects_untrust_reasons',           -1);
    $this->update_delta_expected('data_objects_untrust_reasons_archive',   +1);





    // DETERMINE ACTUAL RESULT:
    $this->update_delta_actual('%', '(rowcount_after - rowcount_before)');

//$this->display_archiver_temporary_table();



    // DID THE TEST PASS OR FAIL?
    $pass_or_fail = $this->did_test_pass_or_fail($test_name);
    $this->assertTrue($pass_or_fail == 'PASSED', "\n\n\t $test_name FAILED\n\n");

#echo "\n\n LEAVING test_e1";
}  // end of test_e1()
/***/


////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////



public function did_test_pass_or_fail($test_number)
{
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


} // end of did_test_pass_or_fail()



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
} // end of table_isnot_empty



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



public function display_table_arr()
{

    // $table_arr contains: sourceTableName, archiveTableName, do_id

    foreach($this->table_arr as $this->arr_elements)
    {
        $_source_table   = $this->arr_elements[0];
        $_archive_table  = $this->arr_elements[1];
        $_do_id          = $this->arr_elements[2];

        echo "\n\n\t $_source_table \t $_archive_table \t $_do_id";
    }

    echo "\n\n";

} // end of display_table_arr



////////////////////////////////////////////////////////////////////////////////////////////////



public function exit_on_sql_error($error_number, $error_description, $sql_statement)
{
    if ($error_number)
    {
        echo "\n\ntest_archiver_data_objects.exit_on_sql_error()";
        //echo "\n\n$sql_statement";
        exit("\n\nFAILED TO EXECUTE QUERY||$sql_statement||: ".$this->mysqli->error()."\n\n");
    }

}  // end of exit_on_sql_error()



////////////////////////////////////////////////////////////////////////////////////////



public function initialize()
{
//echo "\n\n\t ENTERING initialize()";

    // Instantiate an ArchiverDataObjects, and initialize it.
    // Initialization will cause it to create its table_names_arr array.
    // It is the table_names_arr array we need to access, to execute these tests.
    
    $this->object_under_test = new ArchiverDataObjects(FALSE); // arg=TRUE to trace fn calls
//    $this->object_under_test->get_database_connection_info();



    // copy the object-under-test's table_names_arr so we can create our temp table
    $this->table_names_arr = $this->object_under_test->get_table_names_arr();
//    $this->display_table_names_arr();


    // Create a temporary table to hold intermediate results
    $this->initialize_archiver_temporary_table();


//echo "\n\n\t LEAVING  initialize()";
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



/******
audiences_data_objects;
+----------------+---------------------+------+-----+---------+-------+
| Field          | Type                | Null | Key | Default | Extra |
+----------------+---------------------+------+-----+---------+-------+
| data_object_id | int(10) unsigned    | NO   | PRI | NULL    |       |
| audience_id    | tinyint(3) unsigned | NO   | PRI | NULL    |       |
+----------------+---------------------+------+-----+---------+-------+
******/
public function insert_1row_into_audiences_data_objects($data_object_id, $_audience_id)
{

    $sql_statement = "INSERT INTO audiences_data_objects
                      (data_object_id
                      ,audience_id)
                      VALUES
                      ($data_object_id
                      ,$_audience_id)";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

}



////////////////////////////////////////////////////////////////////////////////////////////////



/******
agents_data_objects;
+----------------+---------------------+------+-----+---------+-------+
| Field          | Type                | Null | Key | Default | Extra |
+----------------+---------------------+------+-----+---------+-------+
| data_object_id | int(10) unsigned    | NO   | PRI | NULL    |       |
| agent_id       | int(10) unsigned    | NO   | PRI | NULL    |       |
| agent_role_id  | tinyint(3) unsigned | NO   | PRI | NULL    |       |
| view_order     | tinyint(3) unsigned | NO   |     | NULL    |       |
+----------------+---------------------+------+-----+---------+-------+
******/
public function insert_1row_into_agents_data_objects($_primary_id, $_agent_id)
{
    $_tinyint = 1;

    $sql_statement = "INSERT INTO agents_data_objects
                      (data_object_id
                      ,agent_id
                      ,agent_role_id
                      ,view_order)
                      VALUES
                      ($_primary_id
                      ,$_agent_id
                      ,$_tinyint
                      ,$_tinyint)";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

}



////////////////////////////////////////////////////////////////////////////////////////////////



/******
data_objects_harvest_events;
+------------------+---------------------+------+-----+---------+-------+
| Field            | Type                | Null | Key | Default | Extra |
+------------------+---------------------+------+-----+---------+-------+
| harvest_event_id | int(10) unsigned    | NO   | PRI | NULL    |       |
| data_object_id   | int(10) unsigned    | NO   | PRI | NULL    |       |
| guid             | varchar(32)         | NO   | MUL | NULL    |       |
| status_id        | tinyint(3) unsigned | NO   |     | NULL    |       |
+------------------+---------------------+------+-----+---------+-------+
******/
public function insert_1row_into_data_objects_harvest_events($_primary_id, $_harvest_event_id)
{
    $_tinyint = 1;
    $_int = 9;
    $_bigint = 99;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';

    $sql_statement = "INSERT INTO data_objects_harvest_events
                      (harvest_event_id
                      ,data_object_id
                      ,guid
                      ,status_id )
                      VALUES
                      ($_harvest_event_id
                      ,$_primary_id
                      ,'$_varchar'
                      ,$_tinyint )";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
}



////////////////////////////////////////////////////////////////////////////////////////////////


/******
data_objects_hierarchy_entries;
+--------------------+------------------+------+-----+---------+-------+
| Field              | Type             | Null | Key | Default | Extra |
+--------------------+------------------+------+-----+---------+-------+
| hierarchy_entry_id | int(10) unsigned | NO   | PRI | NULL    |       |
| data_object_id     | int(10) unsigned | NO   | PRI | NULL    |       |
+--------------------+------------------+------+-----+---------+-------+
******/
public function insert_1row_into_data_objects_hierarchy_entries($_primary_id, $_he_id)
{
    $_int = 9;
    $_bigint = 99;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO data_objects_hierarchy_entries
                      (hierarchy_entry_id
                      ,data_object_id )
                      VALUES
                      ($_he_id
                      ,$_primary_id )";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
}



////////////////////////////////////////////////////////////////////////////////////////////////



/******
data_objects_info_items;
+----------------+----------------------+------+-----+---------+-------+
| Field          | Type                 | Null | Key | Default | Extra |
+----------------+----------------------+------+-----+---------+-------+
| data_object_id | int(10) unsigned     | NO   | PRI | NULL    |       |
| info_item_id   | smallint(5) unsigned | NO   | PRI | NULL    |       |
+----------------+----------------------+------+-----+---------+-------+
******/
public function insert_1row_into_data_objects_info_items($_primary_id, $_info_item_id)
{
    $_int = 9;
    $_bigint = 99;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO data_objects_info_items
                      (data_object_id
                      ,info_item_id )
                      VALUES
                      ($_primary_id
                      ,$_info_item_id )";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
}



////////////////////////////////////////////////////////////////////////////////////////////////



/******
desc  data_objects_refs;
+----------------+------------------+------+-----+---------+-------+
| Field          | Type             | Null | Key | Default | Extra |
+----------------+------------------+------+-----+---------+-------+
| data_object_id | int(10) unsigned | NO   | PRI | NULL    |       |
| ref_id         | int(10) unsigned | NO   | PRI | NULL    |       |
+----------------+------------------+------+-----+---------+-------+
******/
public function insert_1row_into_data_objects_refs($_primary_id, $_ref_if)
{
    $_int = 9;
    $_bigint = 99;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO data_objects_refs
                      (data_object_id
                      ,ref_id )
                      VALUES
                      ($_primary_id
                      ,$_ref_if )";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
}



////////////////////////////////////////////////////////////////////////////////////////////////



/******
data_objects_table_of_contents;
+----------------+----------------------+------+-----+---------+-------+
| Field          | Type                 | Null | Key | Default | Extra |
+----------------+----------------------+------+-----+---------+-------+
| data_object_id | int(10) unsigned     | NO   | PRI | NULL    |       |
| toc_id         | smallint(5) unsigned | NO   | PRI | NULL    |       |
+----------------+----------------------+------+-----+---------+-------+
******/
public function insert_1row_into_data_objects_table_of_contents($_primary_id, $_toc_id)
{
    $_int = 9;
    $_bigint = 99;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO data_objects_table_of_contents
                      (data_object_id
                      ,toc_id )
                      VALUES
                      ($_primary_id
                      ,$_toc_id )";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
}



////////////////////////////////////////////////////////////////////////////////////////////////



/******
data_objects_untrust_reasons;
+-------------------+---------+------+-----+---------+----------------+
| Field             | Type    | Null | Key | Default | Extra          |
+-------------------+---------+------+-----+---------+----------------+
| id                | int(11) | NO   | PRI | NULL    | auto_increment |
| data_object_id    | int(11) | YES  | MUL | NULL    |                |
| untrust_reason_id | int(11) | YES  |     | NULL    |                |
+-------------------+---------+------+-----+---------+----------------+
******/
public function insert_1row_into_data_objects_untrust_reasons($_primary_id, $_id)
{
    $_int = 9;
    $_bigint = 99;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement = "INSERT INTO data_objects_untrust_reasons
                      (id
                      ,data_object_id
                      ,untrust_reason_id)
                      VALUES
                      ($_id
                      ,$_primary_id
                      ,$_int)";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );
}



////////////////////////////////////////////////////////////////////////////////////////////////



/******
desc data_objects;
+------------------------+----------------------+------+-----+---------------------+----------------+
| Field                  | Type                 | Null | Key | Default             | Extra          |
+------------------------+----------------------+------+-----+---------------------+----------------+
| id                     | int(10) unsigned     | NO   | PRI | NULL                | auto_increment |
| guid                   | varchar(32)          | NO   | MUL | NULL                |                |
| identifier             | varchar(255)         | YES  | MUL | NULL                |                |
| data_type_id           | smallint(5) unsigned | NO   | MUL | NULL                |                |
| mime_type_id           | smallint(5) unsigned | NO   |     | NULL                |                |
| object_title           | varchar(255)         | NO   |     | NULL                |                |
| language_id            | smallint(5) unsigned | NO   |     | NULL                |                |
| license_id             | tinyint(3) unsigned  | NO   |     | NULL                |                |
| rights_statement       | varchar(300)         | NO   |     | NULL                |                |
| rights_holder          | text                 | NO   |     | NULL                |                |
| bibliographic_citation | text                 | NO   |     | NULL                |                |
| source_url             | varchar(255)         | NO   |     | NULL                |                |
| description            | mediumtext           | NO   | MUL | NULL                |                |
| description_linked     | mediumtext           | YES  |     | NULL                |                |
| object_url             | varchar(255)         | NO   | MUL | NULL                |                |
| object_cache_url       | bigint(20) unsigned  | YES  |     | NULL                |                |
| thumbnail_url          | varchar(255)         | NO   |     | NULL                |                |
| thumbnail_cache_url    | bigint(20) unsigned  | YES  |     | NULL                |                |
| location               | varchar(255)         | NO   |     | NULL                |                |
| latitude               | double               | NO   |     | NULL                |                |
| longitude              | double               | NO   |     | NULL                |                |
| altitude               | double               | NO   |     | NULL                |                |
| object_created_at      | timestamp            | NO   |     | 0000-00-00 00:00:00 |                |
| object_modified_at     | timestamp            | NO   |     | 0000-00-00 00:00:00 |                |
| created_at             | timestamp            | NO   | MUL | CURRENT_TIMESTAMP   |                |
| updated_at             | timestamp            | NO   |     | 0000-00-00 00:00:00 |                |
| data_rating            | float                | NO   |     | 2.5                 |                |
| vetted_id              | tinyint(3) unsigned  | NO   |     | NULL                |                |
| visibility_id          | int(11)              | YES  | MUL | NULL                |                |
| published              | tinyint(1)           | NO   | MUL | 0                   |                |
| curated                | tinyint(1)           | NO   |     | 0                   |                |
| archive                | tinyint(1)           | YES  | MUL | 0                   |                |
+------------------------+----------------------+------+-----+---------------------+----------------+
32 rows in set (0.01 sec)
******/

public function insert_1row_into_data_objects($_primary_id=0)
{
    $_int = 9;
    $_bigint = 99;
    $_double = 1.0;

    $_varchar = 'abc';
    $_text = 'ABC';

    $_time = '2011-06-18 18:43:35';



    $sql_statement =
"insert into data_objects
(id                     
,guid                   
,identifier             
,data_type_id           
,mime_type_id           
,object_title           
,language_id            
,license_id             
,rights_statement       
,rights_holder          
,bibliographic_citation 
,source_url             
,description            
,description_linked     
,object_url             
,object_cache_url       
,thumbnail_url          
,thumbnail_cache_url    
,location               
,latitude               
,longitude              
,altitude               
,object_created_at      
,object_modified_at     
,created_at             
,updated_at             
,data_rating            
,vetted_id              
,visibility_id          
,published              
,curated                
,archive )

VALUES
($_primary_id
,'$_varchar'
,'$_varchar'
,$_int
,$_int
,'$_varchar'
,$_int
,$_int
,'$_varchar'
,'$_text'

,'$_text'
,'$_varchar'
,'$_text'
,'$_text'
,'$_varchar'
,$_int
,'$_varchar'
,$_int
,'$_varchar'
,$_double

,$_double
,$_double
,'$_time'
,'$_time'
,'$_time'
,'$_time'
,$_double
,$_int
,$_int
,$_int

,$_int
,$_int)";

    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

} // end of insert_1row_into_data_objects()



////////////////////////////////////////////////////////////////////////////////////////////////



public function insert_into_data_objects($_max_inserts=0)
{
    for ($i=1; $i <= $_max_inserts; $i++)
    {
        $this->insert_1row_into_data_objects();
    }

} // end of insert_into_data_objects()



////////////////////////////////////////////////////////////////////////////////////////////////



public function populate_frts($row_limit, $do_id, $_offset)
{
    for ($i=1; $i <= $row_limit; $i++)
    {
        $this->insert_1row_into_audiences_data_objects(        $do_id, $i + $_offset);
        $this->insert_1row_into_agents_data_objects(           $do_id, $i + $_offset);
        $this->insert_1row_into_data_objects_harvest_events(   $do_id, $i + $_offset);
        $this->insert_1row_into_data_objects_hierarchy_entries($do_id, $i + $_offset);
        $this->insert_1row_into_data_objects_info_items(       $do_id, $i + $_offset);
        $this->insert_1row_into_data_objects_refs(             $do_id, $i + $_offset);
        $this->insert_1row_into_data_objects_table_of_contents($do_id, $i + $_offset);
        $this->insert_1row_into_data_objects_untrust_reasons(  $do_id, $i + $_offset);
    }

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

    foreach($this->table_names_arr as $table_name)
    {
        $this->truncate_table($table_name);
    }

} // end of truncate_table()



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

    $sql_statement = "UPDATE $table_name SET archive=$true_false LIMIT $max_rowcount";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

} // end of update_archive_column()



////////////////////////////////////////////////////////////////////////////////////////////////



public function update_delta_actual($table_name, $calculation)
{
        $sql_statement = "UPDATE archiver_temporary_table
                          SET rowcount_delta_actual = $calculation
                          WHERE table_name LIKE '$table_name'";

        $result = $this->mysqli->query($sql_statement);
        $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

} // end of update_delta_actual()



////////////////////////////////////////////////////////////////////////////////////////////////



public function update_delta_expected($table_name, $expected)
{
        $sql_statement = "UPDATE archiver_temporary_table
                          SET rowcount_delta_expected = $expected
                          WHERE table_name LIKE '$table_name'";

        $result = $this->mysqli->query($sql_statement);
        $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

} // end of update_delta_expected()



////////////////////////////////////////////////////////////////////////////////////////////////



public function update_rowcounts_after()
{
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

} // end of update_rowcounts_after



////////////////////////////////////////////////////////////////////////////////////////////////



public function update_rowcounts_before()
{
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

} // end of update_rowcounts_before



////////////////////////////////////////////////////////////////////////////////////////////////



/******
data_objects_untrust_reasons;
+-------------------+---------+------+-----+---------+----------------+
| Field             | Type    | Null | Key | Default | Extra          |
+-------------------+---------+------+-----+---------+----------------+
| id                | int(11) | NO   | PRI | NULL    | auto_increment |
| data_object_id    | int(11) | YES  | MUL | NULL    |                |
| untrust_reason_id | int(11) | YES  |     | NULL    |                |
+-------------------+---------+------+-----+---------+----------------+
******/
public function update_data_objects_untrust_reasons($max_rows, $_primary_id)
{
    $sql_statement = "UPDATE data_objects_untrust_reasons
                      SET data_object_id = $_primary_id
                      LIMIT $max_rows";
    $result = $this->mysqli->query($sql_statement);
    $this->exit_on_sql_error($this->mysqli->errno(), $this->mysqli->error(), $sql_statement );

}



////////////////////////////////////////////////////////////////////////////////////////////////



} // end of class test_archiver_data_objects

?>

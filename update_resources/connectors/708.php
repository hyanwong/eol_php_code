<?php
namespace php_active_record;
/*
execution time: 11 hours when API calls are already cached
Environments EOL
https://jira.eol.org/browse/DATA-1487
taxa:               230,808
measurementorfact:  1,052,641
*/
exit;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EnvironmentsEOLDataConnector');
$timestart = time_elapsed();
$resource_id = 708;
$func = new EnvironmentsEOLDataConnector($resource_id);
$func->generate_EnvEOL_data();
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/taxon.tab");
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/occurrence.tab");
    Functions::count_rows_from_text_file(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/measurement_or_fact.tab");
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
// $func->list_folders_with_corrupt_files(); //utility
?>
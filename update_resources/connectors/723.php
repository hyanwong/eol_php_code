<?php
namespace php_active_record;
/*
NCBI, GGBN, GBIF, BHL, BOLDS database coverages
estimated execution time: ~3 days
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NCBIGGIqueryAPI');
$timestart = time_elapsed();
$resource_id = 723;
$func = new NCBIGGIqueryAPI($resource_id);

$func->get_all_taxa();
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
}

/* not yet implemented
sleep(60);
$func->generate_spreadsheet($resource_id);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function count_subfamily_per_database() // call this function above to run the report
{
    $databases = array("bhl", "ncbi", "gbif", "bolds"); // nothing for ggbn
    foreach($databases as $database)
    {
        $func->count_subfamily_per_database(DOC_ROOT . "/tmp/dir_" . $database . "/" . $database . ".txt", $database);
    }
}

?>
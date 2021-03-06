<?php
namespace php_active_record;
/*
execution time: 3 hours when HTTP request is already cached
Connector processes a CSV file exported from the IUCN portal (www.iucnredlist.org). 
The exported CSV file is requested and is generated by the portal a couple of days afterwards.
The completion is confirmed via email to the person who requested it.

To be harvestd quarterly: https://jira.eol.org/browse/WEB-5427
#==== 8 PM, 25th of the month, quarterly (Feb, May, Aug, Nov) => IUCN Structured Data
00 20 25 2,5,8,11 * /usr/bin/php /opt/eol_php_code/update_resources/connectors/737.php > /dev/null

            taxon   measurementorfact
2014 05 27  73,465  533,549
2014 08 14  76,022  554,047
increase    2,557   20,498
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IUCNRedlistDataConnector');
$timestart = time_elapsed();
$resource_id = 737;
$func = new IUCNRedlistDataConnector($resource_id);
$func->generate_IUCN_data();

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

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
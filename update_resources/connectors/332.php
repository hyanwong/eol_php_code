<?php
namespace php_active_record;
/*
3I Interactive archive
Partner provides/hosts a DWC-A file. Connector also converts Distribution & Occurrece extensions into structured data.
estimated execution time: 

Cicadellinae                                                without occurrence
http://eol.org/schema/reference/reference:                  57
http://rs.tdwg.org/dwc/terms/taxon:                         3788
http://rs.tdwg.org/dwc/terms/measurementorfact:             516
http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution:  443
http://rs.gbif.org/terms/1.0/vernacularname:                9
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/I3InteractiveAPI');
$timestart = time_elapsed();
$params["resource_id"] = 332;

$params["process occurrence"] = false;
$params["dwca_file"] = "http://localhost/~eolit/cp/3IInteractive/DwCArchive_Cicadellinae.zip";
$params["dwca_file"] = "http://dmitriev.speciesfile.org/Export/DwCArchive_Cicadellinae.zip";

$func = new I3InteractiveAPI($params);
$func->get_all_taxa();
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"]))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"], CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_working", CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"]);
    rename(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($params["resource_id"]);
    Functions::count_resource_tab_files($params["resource_id"]);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
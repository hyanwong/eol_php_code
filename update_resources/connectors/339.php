<?php
namespace php_active_record;
/* connector for Royal Botanic Garden Edinburgh: Rhododendron Images from Curtis Botanical Magazine
estimated execution time: 
There is already a published data for this resource that is set to 'import once'. 
The connector modifies the 339.xml in Beast.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

$resource_id = 339;

//--------------
/* set rating to 2 */
require_library('ResourceDataObjectElementsSetting');
$resource_path = "http://data.rbge.org.uk/service/static/Rhododendron_curtis_images_eol_transfer.xml";
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path, 'http://purl.org/dc/dcmitype/StillImage', 2);
$xml = $func->set_data_object_rating_on_xml_document();
$func->save_resource_document($xml);
//--------------

Functions::set_resource_status_to_force_harvest($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>
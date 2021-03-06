<?php
namespace php_active_record;
// connector: [lifedesk_eol_export]
class LifeDeskToEOLAPI
{
    function __construct()
    {
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 2); // 15mins timeout
        $this->text_path = array();
    }

    function export_lifedesk_to_eol($params)
    {
        require_library('connectors/LifeDeskToScratchpadAPI');
        $func = new LifeDeskToScratchpadAPI();
        if($this->text_path = $func->load_zip_contents($params["lifedesk"]))
        {
            self::update_eol_xml("LD_".$params["name"]);
        }
        // remove temp dir
        $parts = pathinfo($this->text_path["eol_xml"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }
    
    private function update_eol_xml($lifedesk_name)
    {
        /*
        taxon = 434
        dwc:ScientificName = 434
        reference = 614
        synonym = 68
        commonName = 2
        dataObjects = 1705
        reference = 0
        texts = 1146
        images = 559
        videos = 0
        sounds = 0
        */
        require_library('ResourceDataObjectElementsSetting');
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $lifedesk_name . ".xml";
        $func = new ResourceDataObjectElementsSetting($lifedesk_name, $resource_path);
        $xml = file_get_contents($this->text_path["eol_xml"]);
        $xml = $func->replace_taxon_element_value("dc:source", "replace any existing value", "", $xml, false);
        $xml = $func->replace_data_object_element_value("dc:source", "replace any existing value", "", $xml, false);
        $xml = self::remove_tags_in_references($xml);
        $func->save_resource_document($xml);
        // zip the xml
        $command_line = "gzip -c " . CONTENT_RESOURCE_LOCAL_PATH . $lifedesk_name . ".xml >" . CONTENT_RESOURCE_LOCAL_PATH . $lifedesk_name . ".xml.gz";
        $output = shell_exec($command_line);
    }

    private function remove_tags_in_references($xml_string)
    {
        $field = "reference";
        $xml = simplexml_load_string($xml_string);
        debug("remove_tags_in_references " . count($xml->taxon) . "-- please wait...");
        foreach($xml->taxon as $taxon)
        {
            $i = 0;
            foreach($taxon->reference as $ref)
            {
                $taxon->reference[$i] = strip_tags($ref);
                $i++;
            }
            // foreach($taxon->dataObject as $dataObject){}
        }
        debug("remove_tags_in_references -- done.");
        return $xml->asXML();
    }

}
?>
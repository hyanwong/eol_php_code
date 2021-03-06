<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$specified_id = @$argv[1];
if(!is_numeric($specified_id)) $specified_id = null;
$fast_for_testing = @$argv[2];
if($fast_for_testing && $fast_for_testing == "--fast") $fast_for_testing = true;
else $fast_for_testing = false;

// this checks to make sure we only have one instance of this script running
// if there are more than one then it means we're still harvesting something from yesterday
if(Functions::grep_processlist('harvest_resources') > 2)
{
    $to      = EOL_DEV_EMAIL_ADDRESS;
    $subject = 'Skipped Harvest';
    $message = 'We just skipped a scheduled harvest due to a previous one running long.';
    $headers = 'From: no-reply@eol.org' . "\r\n" .
        'Reply-To: no-reply@eol.org' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
    exit;
}

$log = HarvestProcessLog::create(array('process_name' => 'Harvesting'));
$resources = Resource::ready_for_harvesting();
foreach($resources as $resource)
{
    // IMPORTANT!
    // We skip a few hard-coded resource IDs, here.
    // 201 is Harvard's MCZ.
    // 224 is 3I Interactive Keys and Taxonomic Databases' "Typhlocybinae" DB.
    // TODO - it would be preferable if this flag were in the DB. ...It looks like using a ResourceStatus could achieve the effect.
    // TODO - output a warning if a resource gets skipped.
    if(in_array($resource->id, array(77, 224, 710, 752))) continue;
    // NOTE that a specified id will get SKIPPED if it's not "ready" for harvesting.
    if($specified_id && $resource->id != $specified_id) continue;
    // if(!in_array($resource->id, array(324))) continue;
    // if($resource->id < 197) continue;
    if($GLOBALS['ENV_DEBUG']) echo $resource->id."\n";
    
    $validate = true;
    if($GLOBALS['ENV_NAME'] == 'test') $validate = false;
    $resource->harvest($validate, false, $fast_for_testing);
}
$log->finished();


if(!$fast_for_testing)
{
    // publish all pending resources
    shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/publish_resources.php ENV_NAME=". $GLOBALS['ENV_NAME']);
    
    // setting appropriate TaxonConcept publish flag
    Hierarchy::fix_published_flags_for_taxon_concepts();
    Hierarchy::fix_improperly_trusted_concepts();
    
    // update collection items which reference superceded concepts
    shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/remove_superceded_collection_items.php ENV_NAME=". $GLOBALS['ENV_NAME']);
    
    if(!$specified_id)
    {
        // denormalize tables
        shell_exec(PHP_BIN_PATH . dirname(__FILE__)."/denormalize_tables.php ENV_NAME=". $GLOBALS['ENV_NAME']);
        
        if(defined('SOLR_SERVER'))
        {
            if($GLOBALS["ENV_NAME"] == 'test' || date('w') == 6)
            {
                if(SolrAPI::ping(SOLR_SERVER, 'site_search'))
                {
                    $search_indexer = new SiteSearchIndexer();
                    $search_indexer->recreate_index_for_class('DataObject');
                    $search_indexer->recreate_index_for_class('TaxonConcept');
                    $solr = new SolrAPI(SOLR_SERVER, 'site_search');
                    $solr->optimize();
                }
                
                if(SolrAPI::ping(SOLR_SERVER, 'data_objects'))
                {
                    $solr = new SolrAPI(SOLR_SERVER, 'data_objects');
                    $solr->optimize();
                }
                
                if(SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries'))
                {
                    $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
                    $solr->optimize();
                }
                
                if(SolrAPI::ping(SOLR_SERVER, 'hierarchy_entry_relationship'))
                {
                    $solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entry_relationship');
                    $solr->optimize();
                }
                
                if(SolrAPI::ping(SOLR_SERVER, 'collection_items'))
                {
                    $solr = new SolrAPI(SOLR_SERVER, 'collection_items');
                    $solr->optimize();
                }
            }
        }
    }
}

?>

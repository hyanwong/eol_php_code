<?php
define("CP_DOMAIN", "http://turbellaria.umaine.edu");
define("TAXON_URL", "http://turbellaria.umaine.edu/turb2.php?action=1&code=");
class TurbellarianAPI
{
    public static function get_all_taxa()
    {
        $urls = self::compile_taxon_urls();
        $all_taxa = array();
        $used_collection_ids = array();                
        
        $i=1; $total=sizeof($urls);
        foreach($urls as $url)
        {
            print"\n $i of $total";$i++;
            $arr = self::get_Turbellarian_taxa($url,$used_collection_ids);                             
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];            
            $all_taxa = array_merge($all_taxa,$page_taxa);                                    
        }
        return $all_taxa;
    }    
    
    public static function get_Turbellarian_taxa($url,$used_collection_ids)
    {        
        $response = self::search_collections($url);//this will output the raw (but structured) output from the external service
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;            
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;            
            $used_collection_ids[$rec["sciname"]] = true;
        }        
        return array($page_taxa,$used_collection_ids);
    }    

    function compile_taxon_urls()
    {
        $limit=13998; 
        $limit=6; 
        $urls=array();
        for ($i=2; $i<=$limit; $i++){$urls[] = TAXON_URL . $i;}        

        $final=array();            
        foreach($urls as $url)
        {
            $html = self::clean_str(Functions::get_remote_file_fake_browser($url));                
            $html = utf8_decode($html);            
            $html = trim(str_ireplace('<td> </td>' , "", $html));                                                
            $html2 = trim(substr($html,stripos($html,'<table alt="table of subtaxa">'),strlen($html)));
            
            //process first table
            if(preg_match("/xxx(.*?)<ul>/ims", "xxx".$html2, $matches))
            {            
                $temp=$matches[1];                
                $temp = trim(str_ireplace('&' , "|", $temp));                                        
                $temp = trim(str_ireplace('<th>' , "<td>", $temp));                        
                $temp = trim(str_ireplace('</th>' , "</td>", $temp));                                                        
                //get columns per taxon
                $temp2 = str_ireplace("<td>" , "&arr[]=", $temp);	
                $temp2 = strip_tags($temp2,"<a><th>");                
                $arr = array(); parse_str($temp2);	     
                $arr[]=$url;                   
                $tbl1_arr=$arr;
                //end get columns per taxon                 
            }
            //end process first table
            
            //process 2nd table
            $html2 = trim(substr($html,stripos($html,'<table alt="table of taxa">'),strlen($html)));
            if(preg_match("/xxx(.*?)<\/ul>/ims", "xxx".$html2, $matches))
            {
                $temp = $matches[1];                
                $temp = str_ireplace('<tr bgcolor="#ddffff">' , '<tr>', $temp);	
                
                $temp = trim(str_ireplace('&' , "|", $temp));                                        
                $temp = str_ireplace("<tr>" , "&arr[]=", $temp);	
                $arr = array(); parse_str($temp);	                        
                
                $tbl2_arr=array();
                foreach($arr as $r)
                {
                    $temp2 = str_ireplace("<td>" , "&arr2[]=", $r);	
                    $arr2 = array(); parse_str($temp2);	                           
                    $arr2[]=$url;
                    $tbl2_arr[]=$arr2;
                }        
            }            
            //end process 2nd table
            
            //combine genus + species if applicable
            $i=0;
            foreach($tbl2_arr as $r)
            {
                if(!is_numeric(stripos($r[0],"href")))$tbl2_arr[$i][0]=$tbl1_arr[0] . " " . $r[0];
                $i++;
            }
            //end combine genus + species if applicable
            
            //add taxon in tbl1 to tbl2            
            $tbl2_arr[]=$tbl1_arr;
            
            //loop to get only taxon with: diagnosis and image        
            foreach($tbl2_arr as $row)
            {
                foreach($row as $r)
                {
                    if( is_numeric(stripos($r,"diagnosis")) || is_numeric(stripos($r,"fig. avail.")) )
                    {$final[]=$row;}                        
                }    
            }        

        }//end for loop
        return $final;                
    }    

    function search_collections($url)//this will output the raw (but structured) array
    {        
        $response = self::scrape_species_page($url);        
        return $response;
    }               
    
    function prepare_access($arr)
    {   
        $taxon=""; $author=""; $diagnosis_href=""; $images_href=""; $literature_href="";   
        $i=0;
        $records = sizeof($arr);
        foreach($arr as $r)
        {
            $r = str_ireplace("|","&",$r);
            if($i==0)$taxon  = trim(strip_tags($r));
            if($i==1)$author = trim(strip_tags($r));
            if( is_numeric(stripos($r,"diagnosis")) )
            {
                $temp = strip_tags($r,"<a>");$href="";
                if(preg_match("/href=\"(.*?)\"/ims", "xxx".$r, $matches))$diagnosis_href = CP_DOMAIN . $matches[1];
            }            
            if( is_numeric(stripos($r,"fig. avail.")) )
            {
                $temp = strip_tags($r,"<a>");$href="";
                if(preg_match("/href=\"(.*?)\"/ims", "xxx".$r, $matches))$images_href = CP_DOMAIN . $matches[1];
            }
            if( is_numeric(stripos($r,"literature")) )
            {
                $temp = strip_tags($r,"<a>");$href="";
                if(preg_match("/href=\"(.*?)\"/ims", "xxx".$r, $matches))$literature_href = CP_DOMAIN . $matches[1];
            }            

            $i++;
        }
        return array("taxon"        =>$taxon, 
                     "taxon_author" =>$taxon . " " . $author,
                     "diagnosis"    =>$diagnosis_href,
                     "images"       =>$images_href,
                     "literature"   =>$literature_href,
                     "source_url"   =>$arr[$records-1]
                    );                
                    
    }    
    
    function scrape_species_page($url)
    {           
        $arr_scraped=array();
        $arr_photos=array();
        $arr_sciname=array();                
        
        $rights_holder = "National Science Foundation - Turbellarian Taxonomic Database";
        
        $agent=array();
        $agent[]=array("role" => "compiler" , "homepage" => "http://turbellaria.umaine.edu/" , "name" => "Seth Tyler");
        $agent[]=array("role" => "compiler" , "homepage" => "http://turbellaria.umaine.edu/" , "name" => "Steve Schilling");
        $agent[]=array("role" => "compiler" , "homepage" => "http://turbellaria.umaine.edu/" , "name" => "Matt Hooge");
        $agent[]=array("role" => "compiler" , "homepage" => "http://turbellaria.umaine.edu/" , "name" => "Louise Bush");        
        
        $taxa_arr=self::prepare_access($url);
        $sciname = $taxa_arr["taxon_author"];
        $species_page_url = $taxa_arr["source_url"];        

        //photos start =================================================================
        if($taxa_arr["images"])
        {
            $html = Functions::get_remote_file_fake_browser($taxa_arr["images"]);
            $html = utf8_decode($html);            
            $html = strip_tags($html,"<img>");                            
            $html = str_ireplace('<img src="' , "&arr[]=", $html);	            
            $arr=array();parse_str($html);	                        
            foreach($arr as $r)
            {
                /*
                from: http://turbellaria.umaine.edu/thb/12223a_thb.gif
                to  : http://turbellaria.umaine.edu/gif/12223a.gif                  
                
                from: http://turbellaria.umaine.edu/thb/12223b_thb.jpg
                to  : http://turbellaria.umaine.edu/img/12223b.jpg  
                */
                
                if(is_numeric(stripos($r,'/icons/')))continue;
                $img = substr($r,0,stripos($r,'"'));

                $path_info = pathinfo($img);
                $extension = strtolower($path_info['extension']);          

                if    ($extension=="jpg")$str="img/";
                elseif($extension=="gif")$str="gif/";

                $img = str_ireplace('thb/',$str,$img);
                $img = str_ireplace('_thb','',$img);
                $img = CP_DOMAIN . "/" . $img;
                
                $mimeType   = self::get_mimetype($extension);                
                $dataType   = "http://purl.org/dc/dcmitype/StillImage";
                $dc_source  = $taxa_arr["images"];
                $license    = "http://creativecommons.org/licenses/by-nc-sa/2.0/";
                $mediaURL   = $img;                                
                $description = ''; $subject="";
                $arr_photos["$sciname"][] = self::prepare_array($mediaURL,$mimeType,$rights_holder,$dataType,$dc_source,$description,$subject,$license,$agent);
            }            
        }        
        //photos end =================================================================
        
        //diagnosis start =================================================================        
        
        if($taxa_arr["diagnosis"])
        {
            $html = Functions::get_remote_file_fake_browser($taxa_arr["diagnosis"]);
            $html = utf8_decode($html);
            
            $diagnosis="";
            if(preg_match("/<pre>(.*?)<\/pre>/ims", $html, $matches))$diagnosis = self::clean_str(str_ireplace(";",";<br>",$matches[1]));                
            else
            {if(preg_match("/<hr>(.*?)<hr>/ims", $html, $matches))   $diagnosis = self::clean_str(str_ireplace(";",";<br>",$matches[1]));}
            
            $diagnosis = strip_tags($diagnosis,"<br>");
            
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $dc_source  = $taxa_arr["diagnosis"];
            $license    = "http://creativecommons.org/licenses/by-nc-sa/2.0/";
            $mediaURL   = "";
            $description = $diagnosis;
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription";
            if($diagnosis)
            {
                $arr_texts["$sciname"][] = self::prepare_array($mediaURL,$mimeType,$rights_holder,$dataType,$dc_source,$description,$subject,$license,$agent);
            }            
        }        
        //diagnosis end =================================================================
        
        //text start references =================================================================
        $arr_ref=array();
        if($taxa_arr["literature"])
        {
            $html = Functions::get_remote_file_fake_browser($taxa_arr["literature"]);
            $html = utf8_decode($html);            
            $arr_ref = self::prepare_reference($html);
        }
        //text end references =================================================================        
                
        $arr_sciname["$sciname"]=$species_page_url;                       
        foreach(array_keys($arr_sciname) as $sci)
        {
            $arr_scraped[]=array("id"=>"",
                                 "kingdom"=>"",
                                 "phylum"=>"",
                                 "class"=>"",
                                 "order"=>"",
                                 "family"=>"",
                                 "sciname"=>utf8_encode($sci),
                                 "dc_source"=>$species_page_url,   
                                 "photos"=>@$arr_photos["$sci"],
                                 "texts"=>@$arr_texts["$sci"],
                                 "references"=>$arr_ref
                                );
        }                
        return $arr_scraped;        
    }

    function prepare_array($mediaURL,$mimeType,$rights_holder,$dataType,$dc_source,$description,$subject,$license,$agent)
    {
        return array(
                    "identifier"    =>"",
                    "mediaURL"      =>$mediaURL,
                    "mimeType"      =>$mimeType,                        
                    "rights"        =>"",
                    "rights_holder" =>$rights_holder,
                    "dataType"      =>$dataType,
                    "description"   =>$description,
                    "title"         =>"",
                    "location"      =>"",
                    "dc_source"     =>$dc_source,
                    "agent"         =>$agent,
                    "subject"       =>$subject,
                    "license"       =>$license
                    );                                        
    }
    
    function get_mimetype($ext)
    {
        $mimetype="";
        $mpg=array("mpg","mpeg");        
        if      ($ext == "wmv")         $mimetype="video/x-ms-wmv";
        elseif  ($ext == "avi")         $mimetype="video/x-msvideo";        
        elseif  ($ext == "mp4")         $mimetype="video/mp4";
        elseif  ($ext == "mov")         $mimetype="video/quicktime";
        elseif  (in_array($ext, $mpg))  $mimetype="video/mpeg";
        elseif  ($ext == "flv")         $mimetype="video/x-flv";        
        return $mimetype;
    }    
    
    function prepare_reference($html)
    {
        $html = self::clean_str($html);
        $html = str_ireplace(array("<td><b>Primary authority:</b></td>","<td>other taxonomic work:</td>","<td>latest authority:</td>"), "", $html);			        
        $refs=array();
        if(preg_match("/<table border alt=\"table of references\">(.*?)<\/table>/ims", $html, $matches))
        {
            $html = $matches[1];
            $html = str_ireplace('</td>' , ', ',$html);            
            
            $html = strip_tags($html,"<tr>");
            $html = str_ireplace("<tr>" , "&arr[]=", $html);	
            $arr = array(); parse_str($html);	                        
            foreach($arr as $r)
            {                          
                $ref = str_ireplace('</tr>' , '', $r);	
                $ref = strip_tags($ref);
                $ref = str_ireplace(", ," , "."   ,$ref);
                $ref = trim(str_ireplace(".," , "."   ,$ref));                
                if(substr($ref,strlen($ref)-1,1)==",")$ref=substr($ref,0,strlen($ref)-1) . ".";//replace last char if "," to "."                
                $refs[]=array("url"=>"", "ref"=>$ref);                        
            }              
        }
        return $refs;
    }        
    
    function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $taxon["identifier"] = "";
        $taxon["source"] = $rec["dc_source"];                
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));        
        $taxon["kingdom"] = ucfirst(trim($rec["kingdom"]));
        $taxon["phylum"] = ucfirst(trim($rec["phylum"]));       
        $taxon["class"] = ucfirst(trim($rec["class"]));
        $taxon["order"] = ucfirst(trim($rec["order"]));
        $taxon["family"] = ucfirst(trim($rec["family"]));        
        if(@$rec["photos"]) $taxon["dataObjects"] = self::prepare_objects($rec["photos"],@$taxon["dataObjects"],array());
        if(@$rec["texts"])  $taxon["dataObjects"] = self::prepare_objects($rec["texts"],@$taxon["dataObjects"],$rec["references"]);        
        $taxon_object = new SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    function prepare_objects($arr,$taxon_dataObjects,$references)
    {
        $arr_SchemaDataObject=array();        
        if($arr)
        {
            $arr_ref=array();
            $length = sizeof($arr);
            $i=0;
            foreach($arr as $rec)
            {
                $i++;
                if($length == $i)$arr_ref = $references;
                $data_object = self::get_data_object($rec,$arr_ref);
                if(!$data_object) return false;
                $taxon_dataObjects[]= new SchemaDataObject($data_object);                     
            }
        }        
        return $taxon_dataObjects;
    }
    
    function get_data_object($rec,$references)
    {
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = $rec["identifier"];        
        $data_object_parameters["source"] = $rec["dc_source"];        
        $data_object_parameters["dataType"] = $rec["dataType"];
        $data_object_parameters["mimeType"] = @$rec["mimeType"];
        $data_object_parameters["mediaURL"] = @$rec["mediaURL"];        
        $data_object_parameters["rights"] = @$rec["rights"];
        $data_object_parameters["rightsHolder"] = @$rec["rights_holder"];        
        $data_object_parameters["title"] = @$rec["title"];
        $data_object_parameters["description"] = utf8_encode($rec["description"]);
        $data_object_parameters["location"] = utf8_encode($rec["location"]);        
        $data_object_parameters["license"] = @$rec["license"];

        //start reference
        $data_object_parameters["references"] = array();        
        $ref=array();
        foreach($references as $r)
        {
            if(!$r["ref"])continue;
            $referenceParameters = array();
            $referenceParameters["fullReference"] = trim(utf8_encode($r["ref"]));           
            if($r["url"])$referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => trim($r["url"])));      
            $ref[] = new SchemaReference($referenceParameters);
        }        
        $data_object_parameters["references"] = $ref;
        //end reference
        
        if(@$rec["subject"])
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = @$rec["subject"];
            $data_object_parameters["subjects"][] = new SchemaSubject($subjectParameters);
        }        
        
        if(@$rec["agent"])
        {               
            $agents = array();
            foreach($rec["agent"] as $a)
            {  
                $agentParameters = array();
                $agentParameters["role"]     = $a["role"];
                $agentParameters["homepage"] = $a["homepage"];
                $agentParameters["logoURL"]  = "";        
                $agentParameters["fullName"] = $a["name"];
                $agents[] = new SchemaAgent($agentParameters);
            }
            $data_object_parameters["agents"] = $agents;
        }
        return $data_object_parameters;
    }    

    function clean_str($str)
    {    
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB","\xA0", "\xAO","\xB0", "\xa0", chr(13), chr(10), "\xaO", "0xC3", "0x20", "0x70", "0x6C", "\xc2", "\x1a"), "", $str);			
        return $str;
    }    
}
?>
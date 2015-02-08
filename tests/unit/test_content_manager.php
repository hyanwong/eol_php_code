<?php
namespace php_active_record;

class test_content_manager extends SimpletestUnitBase
{
    static $large_width_image = 'http://content62.eol.org/content/2013/06/25/16/25942_orig.jpg'; //jpeg with width>580px and asp. ratio > 580/360
    static $small_png_image = 'http://eol.org/assets/v2/icon_taxon.png'; //a permanently accessible small png file
    static $unused_data_object_id = 0; //used to temporarily test the databases, then deleted, so must have an unused id 
    
    function setUp()
    {
        parent::setUp();
        $this->content_manager = new ContentManager();
    }

    function tearDown()
    {
        unset($this->content_manager);
        parent::tearDown();
    }

    function testWebServerAvailability()
    {
        $file = Functions::get_remote_file(WEB_ROOT . 'tests/fixtures/files/test_resource.xml');
        $this->assertPattern('/dataObject/', $file, 'YOUR WEB SERVER MAY NOT BE RUNNING');
    }

    function testGrabResource()
    {
        $file = $this->content_manager->grab_file(WEB_ROOT . 'tests/fixtures/files/test_resource.xml', 'resource', array('resource_id' => 101010101));
        $this->assertTrue($file == '101010101.xml', 'File name should be same as resource id');
        $this->assertTrue(file_exists(CONTENT_RESOURCE_LOCAL_PATH . '101010101.xml'), 'File should exist');
        unlink(CONTENT_RESOURCE_LOCAL_PATH . '101010101.xml');
    }
    
    //TODO: To properly test resources, we should test grab a zipped resource file here too.

    function testGrabImage()
    {
        $w = 72; //dimensions assumed to be less than ContentManager::small_image_dimensions()
        $h = 60;
        $cache_num = $this->content_manager->grab_file(self::$small_png_image, 'image');
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $this->assertTrue(file_exists($cache_path .'.png'), 'Should upload the image');
        $size = getimagesize($cache_path .'.png');
        $this->assertTrue($size[0] === $w && $size[1] === $h, "Should be $w pixels wide and $h pixels high");

        $this->assertTrue(file_exists($cache_path .'_orig.jpg'), 'Should be an original size converted to jpeg');
        $size = getimagesize($cache_path .'_orig.jpg');
        $this->assertTrue($size[0] === $w && $size[1] === $h, "Should be $w pixels wide and $h pixels high");

        $this->assertTrue(file_exists($cache_path .'_580_360.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_580_360.jpg');
        $this->assertTrue($size[0] === $w && $size[1] === $h, "Should be $w pixels wide and $h pixels high");

        $this->assertTrue(file_exists($cache_path .'_260_190.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_260_190.jpg');
        $this->assertTrue($size[0] === $w && $size[1] === $h, "Should be $w pixels wide and $h pixels high");

        $this->assertTrue(file_exists($cache_path .'_98_68.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_98_68.jpg');
        $this->assertTrue($size[0] === $w && $size[1] === $h, "Should be $w pixels wide and $h pixels high");

        $this->assertTrue(file_exists($cache_path .'_130_130.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_130_130.jpg');
        $this->assertTrue($size[0] === 130 && $size[1] === 130, 'Thumbnail should be 130 pixels wide and 130 pixels high');

        $this->assertTrue(file_exists($cache_path .'_88_88.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_88_88.jpg');
        $this->assertTrue($size[0] === 88 && $size[1] === 88, 'Thumbnail should be 88 pixels wide and 88 pixels high');

        self::delete_content($cache_path);
    }

    function testGrabImageSizesInDatabase()
    {
        $this->assertTrue($GLOBALS['db_connection']->delete('DELETE FROM image_sizes WHERE data_object_id='.self::$unused_data_object_id), 'Should delete id '.self::$unused_data_object_id.' from the database, if it exists');
        $cache_num = $this->content_manager->grab_file(self::$small_png_image, 'image', array('data_object_id' => self::$unused_data_object_id));
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $local_file = $cache_path .'.png';
        $this->assertTrue(is_file($local_file), 'Should upload the image');
        $resp = $GLOBALS['db_connection']->query('SELECT width, height, crop_x_pct FROM image_sizes WHERE data_object_id='.self::$unused_data_object_id.' LIMIT 1');
        $this->assertTrue($resp, 'Should query from the image_sizes table in the database');
        if ($resp) {
            $this->assertTrue($resp->num_rows == 1, 'Should find data for a newly inserted image in the database');
            if ($resp->num_rows == 1) {
                $size = getimagesize($local_file);
                $details = $resp->fetch_row();
                $this->assertTrue($details[0] == $size[0], 'Should be the image width');
                $this->assertTrue($details[1] == $size[1], 'Should be the image height');
                $this->assertTrue(!isset($details[2]), 'Should be the custom crop left percentage, which is NULL if no custom crop');
            }
        }
        $GLOBALS['db_connection']->delete('DELETE FROM image_sizes WHERE data_object_id='.self::$unused_data_object_id);
        self::delete_content($cache_path);

        /* TODO: add unit test to check retrieval of previous crop values from a different data object that has the same guid
           but this is complex as it requires us to use a guid correspondind to a known crop of an image stored in the data_objects table */
    }

    function testGrabImageWithNoExtension()
    {
        $file = 'http://eolspecies.lifedesks.org/image/view/793'; //a permanently accessible medium-sized file with no extension
        $w = 233;
        $h = 345;

        $cache_num = $this->content_manager->grab_file($file, 'image');
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $this->assertTrue(file_exists($cache_path . '.jpg'), 'Should upload the image');
        $size = getimagesize($cache_path .'.jpg');
        $this->assertTrue($size[0] === $w && $size[1] === $h, "Should be $w pixels wide and $h pixels high");

        $this->assertTrue(file_exists($cache_path . '_orig.jpg'), 'Should be an original size converted to jpeg');
        $size = getimagesize($cache_path .'_orig.jpg');
        $this->assertTrue($size[0] === $w && $size[1] === $h, "Should be $w pixels wide and $h pixels high");

        $this->assertTrue(file_exists($cache_path . '_580_360.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_580_360.jpg');
        $this->assertTrue($size[0] === $w && $size[1] === $h, "Should be $w pixels wide and $h pixels high");

        $this->assertTrue(file_exists($cache_path . '_260_190.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_260_190.jpg');
        $this->assertTrue((abs($size[1]*$w/$h - $size[0]) <= 1) && $size[1] === 190, "Should retain approximate aspect ratio, but be 190 pixels high");

        $this->assertTrue(file_exists($cache_path . '_98_68.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_98_68.jpg');
        $this->assertTrue((abs($size[1]*$w/$h - $size[0]) <= 1) && $size[1] === 68, "Should retain approximate aspect ratio, but be 68 pixels high");

        $this->assertTrue(file_exists($cache_path .'_130_130.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_130_130.jpg');
        $this->assertTrue($size[0] === 130 && $size[1] === 130, 'Thumbnail should be 130 pixels wide and 130 pixels high');

        $this->assertTrue(file_exists($cache_path .'_88_88.jpg'), 'Should create thumbnail');
        $size = getimagesize($cache_path .'_88_88.jpg');
        $this->assertTrue($size[0] === 88 && $size[1] === 88, 'Thumbnail should be 88 pixels wide and 88 pixels high');

        self::delete_content($cache_path);
    }

    function testGrabContentVideo()
    {
        $cache_num = $this->content_manager->grab_file('http://www.crowcastle.net/preston/linux-av/legodrgn.avi', 'video');
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $this->assertTrue(file_exists($cache_path .'.avi'), 'Should be an avi file');
        self::delete_content($cache_path);
    }

    function testGrabContentAudio()
    {
        $cache_num = $this->content_manager->grab_file('http://www.nch.com.au/acm/8kmp38.wav', 'audio');
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $this->assertTrue(file_exists($cache_path .'.wav'), 'Should be a wav file');
        if (defined('SOX_BIN_PATH')) {
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_orig.png'), 'Should be a spectrogram of the audio, ');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_580_360.png'), 'Should create thumbnail');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_260_190.png'), 'Should create thumbnail');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_98_68.png'), 'Should create thumbnail');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_130_130.png'), 'Should create thumbnail');
            $this->assertTrue(file_exists(CONTENT_LOCAL_PATH . $cache_path .'_88_88.png'), 'Should create thumbnail');
        }
        self::delete_content($cache_path);
    }

    function testGrabContentAudioWithNoExtension()
    {
        $cache_num = $this->content_manager->grab_file('https://api.soundcloud.com/tracks/50714448/download?client_id=ac6cdf58548a238e00b7892c031378ce', 'audio');
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $this->assertTrue(file_exists($cache_path .'.wav'), 'Should be a wav file');
        self::delete_content($cache_path);
    }

    function testImageRotating()
    {
        $cache_num = $this->content_manager->grab_file(self::$large_width_image, 'image');
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $sizes = getimagesize($cache_path .'_580_360.jpg');
        // this will max out the width
        $this->assertTrue($sizes[0] == 580);
        $this->assertTrue($sizes[1] < 360);
        self::delete_content($cache_path);

        $cache_num = $this->content_manager->grab_file(self::$large_width_image, 'image', array('rotation' => 90));
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $sizes = getimagesize($cache_path .'_580_360.jpg');
        // now it will max out the height
        $this->assertTrue($sizes[0] < 360);
        $this->assertTrue($sizes[1] == 360);
        self::delete_content($cache_path);
    }

    function testCustomLargeSize()
    {
        $cache_num = $this->content_manager->grab_file(self::$large_width_image, 'image');
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $sizes = getimagesize($cache_path .'_580_360.jpg');
        // this will max out the width
        $this->assertTrue($sizes[0] == 580);
        $this->assertTrue($sizes[1] < 360);
        self::delete_content($cache_path);

        $cache_num = $this->content_manager->grab_file(self::$large_width_image, 'image', array('large_image_dimensions' => array(300, 300)));
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $sizes = getimagesize($cache_path .'_580_360.jpg');
        // now it will max out the height
        $this->assertTrue($sizes[0] == 300);
        $this->assertTrue($sizes[1] < 300);
        self::delete_content($cache_path);
    }

    function testCustomCroppingWithHardLinks()
    {
        $crop_percentages = array(12.3,45.6,7.8, null);
        $this->assertTrue($GLOBALS['db_connection']->delete('DELETE FROM image_sizes WHERE data_object_id='.self::$unused_data_object_id), 'Should delete id '.self::$unused_data_object_id.' from the database, if it exists');
        $cache_num = $this->content_manager->grab_file(self::$small_png_image, 'image', array('data_object_id' => self::$unused_data_object_id));
        $cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num);
        $local_file = $cache_path .'.png';
        $this->assertTrue(is_file($local_file), 'Should upload the image');
        $md5_thumb = md5_file($cache_path .'_130_130.jpg');
        if (is_file($local_file)) {
            $this->assertTrue(self::number_of_hard_links($local_file) == 1, 'Should be a file with only one hard link');
            $new_cache_num = $this->content_manager->grab_file($local_file, 'image', array('data_object_id' => self::$unused_data_object_id, 'crop_pct'=>$crop_percentages));
            $new_cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($new_cache_num);
            
            $new_local_file = $new_cache_path .'.png';
            $this->assertTrue(is_file($new_local_file), 'Should create a new image which is a hard link to the old one');
            $this->assertTrue(self::number_of_hard_links($new_local_file) == 2, 'Should have two hard links for the new file');
            $this->assertTrue(self::number_of_hard_links($local_file) == 2, 'Should also have two hard links for the original file');
            $this->assertTrue(self::number_of_hard_links($new_cache_path .'_orig.jpg') == 2, 'Should have two hard links for new _orig cache');
            $this->assertTrue(self::number_of_hard_links($new_cache_path .'_580_360.jpg') == 2, 'Should have two hard links for new _580_360 file');
            $this->assertTrue(self::number_of_hard_links($new_cache_path .'_260_190.jpg') == 2, 'Should have two hard links for new _260_190 file');
            $this->assertTrue(self::number_of_hard_links($new_cache_path .'_98_68.jpg') == 2, 'Should have two hard links for new _98_68 file');
            $this->assertTrue(self::number_of_hard_links($new_cache_path .'_88_88.jpg') == 1, 'Should have a single hard link for new _88_88 file');
            $this->assertTrue(self::number_of_hard_links($new_cache_path .'_130_130.jpg') == 1, 'Should have a single hard link for new _130_130 file');
            self::delete_content($cache_path);
            $this->assertTrue(!file_exists($local_file), 'Should have deleted the old images');
            $this->assertTrue(self::number_of_hard_links($new_local_file) == 1, 'Should have a single remaining links to the new file');
            
            $resp = $GLOBALS['db_connection']->query('SELECT crop_x_pct, crop_y_pct, crop_width_pct, crop_height_pct, height, width FROM image_sizes WHERE data_object_id='.self::$unused_data_object_id.' LIMIT 1');
            $this->assertTrue($resp, 'Should query from the image_sizes table in the database');
            if ($resp) {
                $this->assertTrue($resp->num_rows == 1, 'Should find data for a newly inserted image in the database');
                if ($resp->num_rows == 1) {
                    $details = $resp->fetch_row();
                    $this->assertTrue(abs($details[0] - $crop_percentages[0]) < 0.01, 'Should have stored the new image left crop position');
                    $this->assertTrue(abs($details[1] - $crop_percentages[1]) < 0.01, 'Should have stored the new image top crop position');
                    $this->assertTrue(abs($details[2] - $crop_percentages[2]) < 0.01, 'Should have stored the new image width crop value');
                    $this->assertTrue(is_null($details[3]), 'Should have stored the new image height crop value as null');
                }
            }
            //check file types
            $new_md5_thumb = md5_file($new_cache_path .'_130_130.jpg');
            $this->assertTrue($md5_thumb != $new_md5_thumb, 'Should have a different crop image after custom cropping');
            //try grabbing again.
            $redownloaded_cache_num = $this->content_manager->grab_file(self::$small_png_image, 'image', array('data_object_id' => self::$unused_data_object_id));
            $redownloaded_cache_path = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($redownloaded_cache_num);
            $redownloaded_md5_thumb = md5_file($redownloaded_cache_path .'_130_130.jpg');
            $this->assertTrue($cache_path !== $new_cache_path, 'Should have redownloaded to a new cache location');
            $this->assertTrue($redownloaded_md5_thumb == $new_md5_thumb, 'Should have identical thumbnails between the initial custom crop and redownloaded version');
        }

        $GLOBALS['db_connection']->delete('DELETE FROM image_sizes WHERE data_object_id='.self::$unused_data_object_id);
        self::delete_content($new_cache_path);
        self::delete_content($redownloaded_cache_path);
    }

    function testCustomCroppingWithFileOnDifferentFilesystem()
    {
        $crop_percentages = array(12.3,45.6,7.8, null);
        $this->assertTrue($GLOBALS['db_connection']->delete('DELETE FROM image_sizes WHERE data_object_id='.self::$unused_data_object_id), 'Should delete id '.self::$unused_data_object_id.' from the database, if it exists');

        $cache_num1 = $this->content_manager->grab_file(self::$large_width_image, 'image', array('data_object_id' => self::$unused_data_object_id));
        $cache_path1 = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num1);
        $local_file1 = $cache_path1 .'.jpg';
        $local_crop1 = $cache_path1 .'_130_130.jpg';
        $md5crop1 = md5(file_get_contents($local_crop1));
        $this->assertTrue(is_file($local_file1), 'Should upload the image');

        //crop, but use url rather than local file
        $cache_num2 = $this->content_manager->grab_file(self::$large_width_image, 'image', array('data_object_id' => self::$unused_data_object_id, 'crop_pct'=>$crop_percentages));
        $cache_path2 = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num2);
        $local_file2 = $cache_path2 .'.jpg';
        $local_crop2 = $cache_path2 .'_130_130.jpg';
        $md5crop2 = md5(file_get_contents($local_crop2));
        $this->assertTrue(is_file($local_file2), 'Should upload the image');
        $this->assertTrue($local_file1 !== $local_file2, 'Should upload new image');
        $this->assertTrue(self::number_of_hard_links($local_file1) == 1, 'Should have only a single hard link for each file');
        $this->assertTrue(self::number_of_hard_links($local_file2) == 1, 'Should have only a single hard link for each file');
        $this->assertTrue($md5crop1 !== $md5crop2, 'Should have different thumbnails between the initial and custom crop version');

        //reharvest
        $cache_num3 = $this->content_manager->grab_file(self::$large_width_image, 'image', array('data_object_id' => self::$unused_data_object_id));
        $cache_path3 = CONTENT_LOCAL_PATH . ContentManager::cache_num2path($cache_num3);
        $local_file3 = $cache_path3 .'.jpg';
        $local_crop3 = $cache_path3 .'_130_130.jpg';
        $md5crop3 = md5(file_get_contents($local_crop3));        
        $this->assertTrue(is_file($local_file3), 'Should upload the image');
        $this->assertTrue($local_file2 !== $local_file3, 'Should upload new image');
        $this->assertTrue($md5crop2 === $md5crop3, 'Should have identical thumbnails between the custom crop and reharvested version');
        
        self::delete_content($cache_path1);
        self::delete_content($cache_path2);
        self::delete_content($cache_path3);
        $GLOBALS['db_connection']->delete('DELETE FROM image_sizes WHERE data_object_id='.self::$unused_data_object_id);

    }

    function testEnforcingExtensions()
    {
        $file = $this->content_manager->grab_file('http://www.wikipedia.org/', 'image');
        $this->assertEqual($file, false);
        if ($file) self::delete_content(CONTENT_LOCAL_PATH . ContentManager::cache_num2path($file));
        $file = $this->content_manager->grab_file('http://www.wikipedia.org/', 'upload');
        $this->assertNotEqual($file, false);
        if ($file) self::delete_content(CONTENT_LOCAL_PATH . ContentManager::cache_num2path($file));
    }

    private static function number_of_hard_links($file)
    {
        $file_stat = stat($file);
        return $file_stat['nlink'];
    }

    private static function delete_content($prefix)
    {
        $prefix = trim($prefix);
        if(!$prefix || $prefix == '/') return;
        foreach(glob($prefix. '*') as $filename) unlink($filename);
    }
}

?>
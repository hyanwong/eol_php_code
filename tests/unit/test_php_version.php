<?php

class test_php_version extends SimpletestUnitBase
{
    function testPHPVersion()
    {
        $this->assertTrue(PHP_MAJOR_VERSION >= 5, "You must be running PHP version 5.3 or greater");
        if(PHP_MAJOR_VERSION == 5) $this->assertTrue(PHP_MINOR_VERSION >= 3, "You must be running PHP version 5.3 or greater");
    }
}

?>
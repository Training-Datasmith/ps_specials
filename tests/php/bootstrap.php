<?php

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '8.1.7');
}

define('_PS_MODULE_DIR_', dirname(dirname(__DIR__)) . '/');
define('_PS_ROOT_DIR_', dirname(dirname(__DIR__)) . '/');

require dirname(__FILE__) . '/TestState.php';
require dirname(__FILE__) . '/fakes.php';
require dirname(__FILE__) . '/fakes_global.php';

require dirname(dirname(__DIR__)) . '/ps_specials.php';

class TestablePs_Specials extends Ps_Specials
{
    public function exposeGetCacheId($name = null)
    {
        return $this->getCacheId($name);
    }

    public function getModuleSmartyData()
    {
        return $this->smarty;
    }
}

require dirname(__FILE__) . '/PsSpecialsTestCase.php';

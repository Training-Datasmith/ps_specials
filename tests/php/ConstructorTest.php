<?php

class ConstructorTest extends PsSpecialsTestCase
{
    public function testIdentityCompliancyAndFlags()
    {
        $module = $this->newModule();
        $this->assertSame('ps_specials', $module->name);
        $this->assertSame('front_office_features', $module->tab);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertSame(0, $module->need_instance);
        $this->assertTrue($module->bootstrap);
        $this->assertSame('8.1.0', $module->ps_versions_compliancy['min']);
        $this->assertSame(_PS_VERSION_, $module->ps_versions_compliancy['max']);
    }

    public function testVersionMetadataMakesUpgrade104Reachable()
    {
        $module = $this->newModule();
        $this->assertSame('1.0.4', $module->version);
        $xml = file_get_contents(dirname(dirname(__DIR__)) . '/config.xml');
        $this->assertInternalType('string', $xml);
        $this->assertNotSame('', $xml);
        $this->assertSame(1, preg_match('#<name>(?:<!\\[CDATA\\[([^\\]]+)\\]\\]>|([^<]+)</name>)#', $xml, $nameMatch));
        $this->assertSame(1, preg_match('#<version><!\\[CDATA\\[([^\\]]+)\\]\\]></version>#', $xml, $versionMatch));
        $nameValue = !empty($nameMatch[1]) ? $nameMatch[1] : $nameMatch[2];
        $this->assertInternalType('string', $nameValue);
        $this->assertInternalType('string', $versionMatch[1]);
        $this->assertSame('ps_specials', $nameValue);
        $this->assertSame('1.0.4', $versionMatch[1]);
        $this->assertTrue(version_compare($module->version, '1.0.4', '>='));
        $this->assertFileExists(dirname(dirname(__DIR__)) . '/upgrade/upgrade-1.0.4.php');
    }

    public function testTranslatedDisplayNameAndDescription()
    {
        $module = $this->newModule();
        $this->assertContains('Specials block', $module->displayName);
        $this->assertContains(
            'Provide information on your special offers in a specific block displayed on your homepage.',
            $module->description
        );
    }

    public function testConstructorCreatesModuleSmartyData()
    {
        $module = $this->newModule();
        $ctx = Context::getContext();
        $this->assertNotSame($ctx->smarty, $module->getModuleSmartyData());
        $this->assertSame($ctx->smarty->moduleData, $module->getModuleSmartyData());
    }

    public function testModuleFileExitsWithoutPsVersion()
    {
        $path = dirname(dirname(__DIR__)) . '/ps_specials.php';
        $result = $this->runChildInclude($path);
        $this->assertSame(0, $result['exit_code']);
        $this->assertSame('running', $result['sentinel']);
        $this->assertSame('', $result['stdout']);
        $this->assertSame('', $result['stderr']);
    }

    public function testWidgetInterfaceContract()
    {
        $module = $this->newModule();
        $implements = class_implements($module);
        $this->assertArrayHasKey('PrestaShop\\PrestaShop\\Core\\Module\\WidgetInterface', $implements);
        $ref = new ReflectionClass('Ps_Specials');
        foreach (array('renderWidget', 'getWidgetVariables') as $methodName) {
            $method = $ref->getMethod($methodName);
            $this->assertTrue($method->isPublic());
            $params = $method->getParameters();
            $this->assertCount(2, $params);
            $this->assertTrue($params[1]->isArray());
            $this->assertTrue($params[0]->isOptional());
            $this->assertTrue($params[1]->isOptional());
        }
    }
}

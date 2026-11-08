<?php

class ConfigurationFormTest extends PsSpecialsTestCase
{
    private $invalidMessage = 'The number of products is invalid. Please enter a positive number.';
    private $successMessage = 'The settings have been updated.';

    /**
     * @dataProvider invalidLimitProvider
     */
    public function testRejectsInvalidLimit($posted)
    {
        $_POST['submitSpecials'] = '1';
        $_POST['BLOCKSPECIALS_SPECIALS_NBR'] = $posted;
        $module = $this->newModule();
        $clearsBefore = count(TestState::$parentClearCacheCalls);
        $writesBefore = count(TestState::$configurationUpdates);
        $output = $module->getContent();
        $this->assertSame($writesBefore, count(TestState::$configurationUpdates));
        $this->assertSame($clearsBefore, count(TestState::$parentClearCacheCalls));
        $this->assertContains($this->invalidMessage, $output);
        $this->assertNotContains($this->successMessage, $output);
        $capture = TestState::$helperFormCapture;
        $this->assertSame($posted, $capture['tpl_vars']['fields_value']['BLOCKSPECIALS_SPECIALS_NBR']);
    }

    public function invalidLimitProvider()
    {
        return array(
            array('0'),
            array('-2'),
            array('1.5'),
            array('abc'),
            array(''),
            array('8abc'),
            array(' 4'),
            array('4 '),
            array('08'),
        );
    }

    public function testMissingLimitIsRejectedAndStoredValueRedisplayed()
    {
        $_POST['submitSpecials'] = '1';
        $module = $this->newModule();
        $output = $module->getContent();
        $this->assertEmpty($this->configurationWritesForKey('BLOCKSPECIALS_SPECIALS_NBR'));
        $this->assertContains($this->invalidMessage, $output);
        $this->assertSame('8', TestState::$helperFormCapture['tpl_vars']['fields_value']['BLOCKSPECIALS_SPECIALS_NBR']);
    }

    /**
     * @dataProvider positiveLimitProvider
     */
    public function testSavesPositiveInteger($posted, $expectedInt)
    {
        $_POST['submitSpecials'] = '1';
        $_POST['BLOCKSPECIALS_SPECIALS_NBR'] = $posted;
        $module = $this->newModule();
        $output = $module->getContent();
        $writes = $this->configurationWritesForKey('BLOCKSPECIALS_SPECIALS_NBR');
        $this->assertNotEmpty($writes);
        $this->assertSame($expectedInt, $writes[count($writes) - 1]);
        $this->assertGreaterThan(0, count(TestState::$parentClearCacheCalls));
        $this->assertContains($this->successMessage, $output);
        $this->assertNotContains($this->invalidMessage, $output);
        $this->assertSame($posted, TestState::$helperFormCapture['tpl_vars']['fields_value']['BLOCKSPECIALS_SPECIALS_NBR']);
    }

    public function positiveLimitProvider()
    {
        return array(
            array('1', 1),
            array('8', 8),
            array('15', 15),
        );
    }

    public function testGetSubmissionIsAccepted()
    {
        $_GET['submitSpecials'] = '1';
        $_GET['BLOCKSPECIALS_SPECIALS_NBR'] = '6';
        $module = $this->newModule();
        $module->getContent();
        $writes = $this->configurationWritesForKey('BLOCKSPECIALS_SPECIALS_NBR');
        $this->assertSame(6, $writes[count($writes) - 1]);
    }

    public function testOpeningConfigurationPageDoesNotMutateState()
    {
        $module = $this->newModule();
        $writesBefore = count(TestState::$configurationUpdates);
        $clearsBefore = count(TestState::$parentClearCacheCalls);
        $output = $module->getContent();
        $this->assertSame($writesBefore, count(TestState::$configurationUpdates));
        $this->assertSame($clearsBefore, count(TestState::$parentClearCacheCalls));
        $this->assertNotContains($this->invalidMessage, $output);
        $this->assertNotContains($this->successMessage, $output);
        $this->assertSame('8', TestState::$helperFormCapture['tpl_vars']['fields_value']['BLOCKSPECIALS_SPECIALS_NBR']);
    }

    public function testRenderFormWiresHelper()
    {
        TestState::$configuration['PS_LANG_DEFAULT'] = '3';
        $module = $this->newModule();
        Context::getContext()->language->id = 7;
        $module->getContent();
        $adminLinkCalls = Context::getContext()->link->adminLinkCalls;
        $this->assertCount(1, $adminLinkCalls);
        $this->assertSame(array('AdminModules', false), $adminLinkCalls[0]);
        $capture = TestState::$helperFormCapture;
        $this->assertFalse($capture['show_toolbar']);
        $this->assertSame('module', $capture['table']);
        $this->assertSame('id_module', $capture['identifier']);
        $this->assertSame('submitSpecials', $capture['submit_action']);
        $this->assertSame(3, $capture['default_form_language']);
        $expectedIndex = TestState::$adminLinkReturn
            . '&configure=ps_specials&tab_module=front_office_features&module_name=ps_specials';
        $this->assertSame($expectedIndex, $capture['currentIndex']);
        $this->assertSame(array('AdminModules'), TestState::$getAdminTokenLiteTabs);
        $this->assertSame(TestState::$adminTokenReturn, $capture['token']);
        $this->assertSame(7, $capture['tpl_vars']['id_language']);
        $this->assertSame(Context::getContext()->controller->getLanguages(), $capture['tpl_vars']['languages']);
        $input = $capture['fields_form'][0]['form']['input'][0];
        $this->assertSame('BLOCKSPECIALS_SPECIALS_NBR', $input['name']);
        $this->assertSame('text', $input['type']);
        $this->assertSame('Settings', $capture['fields_form'][0]['form']['legend']['title']);
    }

    public function testFalseEmployeeLanguageSettingBecomesIntegerZero()
    {
        unset(TestState::$configuration['PS_BO_ALLOW_EMPLOYEE_FORM_LANG']);
        $module = $this->newModule();
        $module->getContent();
        $this->assertSame(0, TestState::$helperFormCapture['allow_employee_form_lang']);
    }

    public function testTruthyEmployeeLanguageSettingIsPreserved()
    {
        TestState::$configuration['PS_BO_ALLOW_EMPLOYEE_FORM_LANG'] = '2';
        $module = $this->newModule();
        $module->getContent();
        $this->assertSame('2', TestState::$helperFormCapture['allow_employee_form_lang']);
    }
}

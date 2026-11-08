<?php

class InstallUninstallTest extends PsSpecialsTestCase
{
    private $expectedHooks = array(
        'actionProductAdd',
        'actionProductUpdate',
        'actionProductDelete',
        'actionObjectSpecificPriceDeleteAfter',
        'actionObjectSpecificPriceAddAfter',
        'actionObjectSpecificPriceUpdateAfter',
        'displayHome',
    );

    public function testSuccessfulInstallConfiguresAndRegistersHooks()
    {
        $module = $this->newModule();
        $this->assertTrue($module->install());
        $writes = $this->configurationWritesForKey('BLOCKSPECIALS_SPECIALS_NBR');
        $this->assertNotEmpty($writes);
        $this->assertSame(8, $writes[0]);
        $this->assertTrue(is_numeric($module->id));
        $this->assertNotEmpty(TestState::$parentClearCacheCalls);
        $this->assertSame($this->expectedHooks, TestState::$registeredHooks);
    }

    public function testParentInstallFailureSkipsHookRegistration()
    {
        TestState::$parentInstallLateFailure = true;
        $module = $this->newModule();
        $this->assertFalse($module->install());
        $this->assertSame(array(), TestState::$registeredHooks);
    }

    public function testRegistrationFailureShortCircuitsLaterHooks()
    {
        TestState::$registerHookFailOn = 'actionProductUpdate';
        $module = $this->newModule();
        $this->assertFalse($module->install());
        $this->assertContains('actionProductAdd', TestState::$registeredHooks);
        $this->assertNotContains('actionProductDelete', TestState::$registeredHooks);
        $this->assertNotContains('displayHome', TestState::$registeredHooks);
    }

    public function testSuccessfulUninstallDelegatesAndClears()
    {
        $module = $this->newModule();
        TestState::$parentClearCacheCalls = array();
        $this->assertTrue($module->uninstall());
        $this->assertCount(1, TestState::$parentClearCacheCalls);
    }

    public function testFailedUninstallReturnsFalseAfterClearAttempt()
    {
        TestState::$parentUninstallResult = false;
        $module = $this->newModule();
        TestState::$parentClearCacheCalls = array();
        $this->assertFalse($module->uninstall());
        $this->assertCount(1, TestState::$parentClearCacheCalls);
    }
}

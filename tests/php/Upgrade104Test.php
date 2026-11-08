<?php

class Upgrade104Test extends PsSpecialsTestCase
{
    protected function setUp()
    {
        parent::setUp();
        require_once dirname(dirname(__DIR__)) . '/upgrade/upgrade-1.0.4.php';
    }

    public function testMigratesLegacyNamesToRealSpecificPriceHooks()
    {
        TestState::$hookIdsByName['actionobjectspecificpricecoreaddafter'] = 11;
        TestState::$hookIdsByName['actionobjectspecificpricecoreupdateafter'] = 12;
        TestState::$hookIdsByName['actionobjectspecificpricecoredeleteafter'] = 13;
        foreach (array(11, 12, 13) as $hid) {
            TestState::$preloadedHookIds[$hid] = true;
        }
        $module = $this->newModule();
        $module->id = 99;
        $this->assertTrue(upgrade_module_1_0_4($module));
        $this->assertSame(
            array(
                'actionObjectSpecificPriceCoreAddAfter',
                'actionObjectSpecificPriceCoreUpdateAfter',
                'actionObjectSpecificPriceCoreDeleteAfter',
            ),
            TestState::$unregisterHookCalls
        );
        $this->assertSame(
            array(
                'actionObjectSpecificPriceAddAfter',
                'actionObjectSpecificPriceUpdateAfter',
                'actionObjectSpecificPriceDeleteAfter',
            ),
            TestState::$registeredHooks
        );
    }

    public function testRegistrationFailureMakesUpgradeFail()
    {
        TestState::$registerHookResult = false;
        $module = $this->newModule();
        $module->id = 99;
        $this->assertFalse(upgrade_module_1_0_4($module));
    }

    public function testMissingLegacyHooksDoNotFailSuccessfulRegistration()
    {
        TestState::$unregisterHookCalls = array();
        $module = $this->newModule();
        $module->id = 99;
        $this->assertTrue(upgrade_module_1_0_4($module));
    }
}

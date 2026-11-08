<?php

class CacheIdAndClearTest extends PsSpecialsTestCase
{
    public function testClearCacheDelegatesAndReturnsParentResult()
    {
        TestState::$parentClearCacheReturn = 7;
        $module = $this->newModule();
        $result = $module->_clearCache('*');
        $this->assertSame(7, $result);
        $this->assertCount(1, TestState::$parentClearCacheCalls);
    }

    /**
     * @dataProvider customerIdProvider
     */
    public function testCustomerIdIsAppendedAsIntegerSegment($customerId, $pattern)
    {
        $module = $this->newModule();
        Context::getContext()->customer->id = $customerId;
        $cacheId = $module->exposeGetCacheId('ps_specials');
        $this->assertRegExp($pattern, $cacheId);
    }

    public function customerIdProvider()
    {
        return array(
            array('15', '/^ps_specials\\|15(?:\\||$)/'),
            array('15abc', '/^ps_specials\\|15(?:\\||$)/'),
        );
    }

    /**
     * @dataProvider emptyCustomerIdProvider
     */
    public function testEmptyCustomerIdDoesNotAppendZeroSegment($customerId)
    {
        $module = $this->newModule();
        Context::getContext()->customer->id = $customerId;
        $cacheId = $module->exposeGetCacheId('ps_specials');
        $this->assertStringStartsWith('ps_specials', $cacheId);
        $this->assertNotRegExp('/^ps_specials\\|0(?:\\||$)/', $cacheId);
    }

    public function emptyCustomerIdProvider()
    {
        return array(
            array(0),
            array('0'),
            array(''),
            array(null),
        );
    }

    /**
     * @dataProvider invalidationHookProvider
     */
    public function testInvalidationHookDelegatesCacheClear($method)
    {
        $module = $this->newModule();
        $before = count(TestState::$parentClearCacheCalls);
        $module->{$method}(array('id_product' => 99));
        $this->assertSame($before + 1, count(TestState::$parentClearCacheCalls));
    }

    public function invalidationHookProvider()
    {
        return array(
            array('hookActionProductAdd'),
            array('hookActionProductUpdate'),
            array('hookActionProductDelete'),
            array('hookActionObjectSpecificPriceDeleteAfter'),
            array('hookActionObjectSpecificPriceAddAfter'),
            array('hookActionObjectSpecificPriceUpdateAfter'),
        );
    }
}

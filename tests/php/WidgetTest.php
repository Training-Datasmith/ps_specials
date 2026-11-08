<?php

class WidgetTest extends PsSpecialsTestCase
{
    public function testWidgetVariablesBulkAssemblePresentAndLinkProducts()
    {
        $module = $this->newModule();
        $vars = $module->getWidgetVariables('displayHome', array());
        $this->assertCount(1, TestState::$assembleProductsLog);
        $this->assertCount(2, TestState::$presentCalls);
        $this->assertInstanceOf(
            'PrestaShop\\PrestaShop\\Adapter\\Presenter\\Product\\ProductListingLazyArray',
            $vars['products'][0]
        );
        $this->assertInstanceOf(
            'PrestaShop\\PrestaShop\\Adapter\\Presenter\\Product\\ProductListingLazyArray',
            $vars['products'][1]
        );
        $this->assertSame(4, $vars['products'][0]->id_product);
        $this->assertSame(9, $vars['products'][1]->id_product);
        $this->assertSame(TestState::$pageLinkReturn, $vars['allSpecialProductsLink']);
        $ctx = Context::getContext();
        $this->assertSame(array('prices-drop'), $ctx->link->pageLinkCalls);
        $this->assertSame($ctx->link, TestState::$imageRetrieverLinks[0]);
        $deps = TestState::$presenterConstructDeps[0];
        $this->assertSame($ctx->link, $deps['link']);
        $this->assertSame($ctx->getTranslator(), $deps['translator']);
        $this->assertInstanceOf('PrestaShop\\PrestaShop\\Adapter\\Product\\PriceFormatter', $deps['priceFormatter']);
        $this->assertInstanceOf('PrestaShop\\PrestaShop\\Adapter\\Product\\ProductColorsRetriever', $deps['colorsRetriever']);
        $this->assertSame(TestState::$presenterSettings, TestState::$presentCalls[0]['settings']);
        $this->assertSame($ctx->language, TestState::$presentCalls[0]['language']);
    }

    public function testPricesDropReceivesLanguagePageAndConfiguredLimit()
    {
        TestState::$configuration['BLOCKSPECIALS_SPECIALS_NBR'] = '5';
        $module = $this->newModule();
        $module->getWidgetVariables(null, array());
        $this->assertCount(1, TestState::$pricesDropCalls);
        $call = TestState::$pricesDropCalls[0];
        $this->assertSame(3, $call['id_lang']);
        $this->assertSame(0, $call['page_number']);
        $this->assertSame(5, $call['nb_products']);
    }

    public function testEmptyProductArrayReturnsExactFalseVariables()
    {
        TestState::$pricesDropReturn = array();
        $module = $this->newModule();
        $vars = $module->getWidgetVariables(null, array());
        $this->assertSame(false, $vars);
        $this->assertSame(0, count(TestState::$presentCalls));
        $this->assertSame(0, count(Context::getContext()->link->pageLinkCalls));
    }

    public function testRenderWidgetWithoutProductsDoesNotAssignOrFetch()
    {
        TestState::$pricesDropReturn = array();
        $module = $this->newModule();
        $result = $module->renderWidget(null, array());
        $this->assertSame(false, $result);
        $this->assertSame(0, count(TestState::$smartyAssignLog));
        $this->assertSame(0, count(TestState::$fetchCalls));
    }

    public function testFalseProductResultReturnsExactFalseVariables()
    {
        TestState::$pricesDropReturn = false;
        $module = $this->newModule();
        $vars = $module->getWidgetVariables(null, array());
        $this->assertSame(false, $vars);
        $this->assertSame(0, count(TestState::$presentCalls));
        $this->assertSame(0, count(Context::getContext()->link->pageLinkCalls));
    }

    public function testStringProductResultIsRejectedWithoutDiagnostics()
    {
        TestState::$pricesDropReturn = 'nope';
        $module = $this->newModule();
        $vars = $module->getWidgetVariables(null, array());
        $this->assertSame(false, $vars);
        $this->assertSame(0, count(TestState::$presentCalls));
    }

    public function testIntegerProductResultIsRejectedWithoutDiagnostics()
    {
        TestState::$pricesDropReturn = 2;
        $module = $this->newModule();
        $vars = $module->getWidgetVariables(null, array());
        $this->assertSame(false, $vars);
        $this->assertSame(0, count(TestState::$presentCalls));
    }

    public function testRenderWidgetAssignsModuleSmartyDataAndReturnsFetchResult()
    {
        TestState::$pricesDropReturn = array(array('id_product' => 4));
        $module = $this->newModule();
        $result = $module->renderWidget(null, array());
        $this->assertSame(TestState::$fetchReturn, $result);
        $data = $module->getModuleSmartyData();
        $this->assertArrayHasKey('products', $data->assigned);
        $this->assertArrayHasKey('allSpecialProductsLink', $data->assigned);
        $this->assertCount(1, TestState::$fetchCalls);
        $this->assertSame($this->moduleTemplateResource(), TestState::$fetchCalls[0]['template']);
    }
}

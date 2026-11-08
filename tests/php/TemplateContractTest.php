<?php

class TemplateContractTest extends PsSpecialsTestCase
{
    public function testTemplateConsumesPublishedWidgetVariables()
    {
        $path = dirname(dirname(__DIR__)) . '/views/templates/hook/ps_specials.tpl';
        $contents = file_get_contents($path);
        $this->assertInternalType('string', $contents);
        $this->assertNotSame('', $contents);
        $this->assertContains('$products', $contents);
        $this->assertContains('{$allSpecialProductsLink}', $contents);
        $this->assertContains('catalog/_partials/miniatures/product.tpl', $contents);
    }
}

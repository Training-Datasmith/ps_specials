<?php

namespace PrestaShop\PrestaShop\Core\Module;

interface WidgetInterface
{
    public function renderWidget($hookName, array $configuration);

    public function getWidgetVariables($hookName, array $configuration);
}

namespace PrestaShop\PrestaShop\Core\Product;

class ProductPresentationSettings
{
}

namespace PrestaShop\PrestaShop\Adapter\Presenter\Product;

class ProductListingLazyArray
{
    public $id_product;

    public function __construct($idProduct)
    {
        $this->id_product = $idProduct;
    }
}

namespace PrestaShop\PrestaShop\Adapter\Presenter\Product;

class ProductListingPresenter
{
    public $imageRetriever;
    public $link;
    public $priceFormatter;
    public $colorsRetriever;
    public $translator;

    public function __construct(
        $imageRetriever,
        $link,
        $priceFormatter,
        $productColorsRetriever,
        $translator
    ) {
        $this->imageRetriever = $imageRetriever;
        $this->link = $link;
        $this->priceFormatter = $priceFormatter;
        $this->colorsRetriever = $productColorsRetriever;
        $this->translator = $translator;
        \TestState::$presenterConstructDeps[] = array(
            'imageRetriever' => $imageRetriever,
            'link' => $link,
            'priceFormatter' => $priceFormatter,
            'colorsRetriever' => $productColorsRetriever,
            'translator' => $translator,
        );
    }

    public function present($settings, array $product, \Language $language)
    {
        \TestState::$presentCalls[] = array(
            'settings' => $settings,
            'product' => $product,
            'language' => $language,
        );

        return new ProductListingLazyArray(isset($product['id_product']) ? $product['id_product'] : 0);
    }
}

namespace PrestaShop\PrestaShop\Adapter\Image;

class ImageRetriever
{
    public $link;

    public function __construct(\Link $link)
    {
        $this->link = $link;
        \TestState::$imageRetrieverLinks[] = $link;
    }
}

namespace PrestaShop\PrestaShop\Adapter\Product;

class PriceFormatter
{
}

class ProductColorsRetriever
{
}


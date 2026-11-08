<?php

/**
 * Process-wide mutable state for PHPUnit fakes; reset in PsSpecialsTestCase::setUp().
 */
class TestState
{
    public static $configuration = array();
    public static $configurationUpdates = array();
    public static $configurationUpdateResult = true;
    public static $registeredHooks = array();
    public static $unregisterHookCalls = array();
    public static $registerHookResult = true;
    public static $registerHookFailOn = null;
    public static $parentInstallResult = true;
    public static $parentInstallLateFailure = false;
    public static $parentUninstallResult = true;
    public static $parentClearCacheCalls = array();
    public static $parentClearCacheReturn = 0;
    public static $isCached = false;
    public static $fetchReturn = 'FETCH_SENTINEL_HTML';
    public static $isCachedCalls = array();
    public static $fetchCalls = array();
    public static $smartyAssignLog = array();
    public static $pricesDropReturn = array();
    public static $pricesDropCalls = array();
    public static $assembleProductsLog = array();
    public static $presentCalls = array();
    public static $presenterSettings;
    public static $pageLinkReturn = 'PRICES_DROP_LINK';
    public static $adminLinkReturn = 'admin-modules-link';
    public static $adminTokenReturn = 'ADMIN_TOKEN_SENTINEL';
    public static $helperFormCapture = null;
    public static $hookIdsByName = array();
    public static $hookConstructCount = 0;
    public static $hookDeleteCalls = array();
    public static $preloadedHookIds = array();
    public static $imageRetrieverLinks = array();
    public static $presenterConstructDeps = array();
    public static $getAdminTokenLiteTabs = array();

    public static function reset()
    {
        self::$configuration = array(
            'BLOCKSPECIALS_SPECIALS_NBR' => '8',
        );
        self::$configurationUpdates = array();
        self::$configurationUpdateResult = true;
        self::$registeredHooks = array();
        self::$unregisterHookCalls = array();
        self::$registerHookResult = true;
        self::$registerHookFailOn = null;
        self::$parentInstallResult = true;
        self::$parentInstallLateFailure = false;
        self::$parentUninstallResult = true;
        self::$parentClearCacheCalls = array();
        self::$parentClearCacheReturn = 7;
        self::$isCached = false;
        self::$fetchReturn = 'FETCH_SENTINEL_HTML';
        self::$isCachedCalls = array();
        self::$fetchCalls = array();
        self::$smartyAssignLog = array();
        self::$pricesDropReturn = array(
            array('id_product' => 4),
            array('id_product' => 9),
        );
        self::$pricesDropCalls = array();
        self::$assembleProductsLog = array();
        self::$presentCalls = array();
        self::$presenterSettings = new \PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings();
        self::$pageLinkReturn = 'PRICES_DROP_LINK';
        self::$adminLinkReturn = 'admin-modules-link';
        self::$adminTokenReturn = 'ADMIN_TOKEN_SENTINEL';
        self::$helperFormCapture = null;
        self::$hookIdsByName = array();
        self::$hookConstructCount = 0;
        self::$hookDeleteCalls = array();
        self::$preloadedHookIds = array();
        self::$imageRetrieverLinks = array();
        self::$presenterConstructDeps = array();
        self::$getAdminTokenLiteTabs = array();
        $_POST = array();
        $_GET = array();
        Context::resetSingleton();
    }
}

<?php

class Context
{
    /** @var Context */
    private static $instance;

    public $language;
    public $link;
    public $smarty;
    public $controller;
    public $customer;
    public $shop;
    public $employee;
    public $currency;
    public $country;
    private $translator;

    public static function getContext()
    {
        if (self::$instance === null) {
            self::resetSingleton();
        }

        return self::$instance;
    }

    public static function resetSingleton()
    {
        $ctx = new Context();
        $ctx->language = new Language(3);
        $ctx->link = new Link();
        $ctx->smarty = new FakeSmarty();
        $ctx->controller = new FakeAdminController();
        $ctx->customer = new stdClass();
        $ctx->customer->id = 0;
        $ctx->shop = new stdClass();
        $ctx->shop->id = 1;
        $ctx->employee = new stdClass();
        $ctx->employee->id = 1;
        $ctx->currency = new stdClass();
        $ctx->currency->id = 1;
        $ctx->country = new stdClass();
        $ctx->country->id = 1;
        $ctx->translator = new FakeTranslator();
        self::$instance = $ctx;
    }

    public function getTranslator()
    {
        return $this->translator;
    }
}

class FakeTranslator
{
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return $id;
    }
}

class FakeSmarty
{
    public $moduleData;

    public function createData($parent = null)
    {
        $data = new FakeSmartyData($this);
        $this->moduleData = $data;

        return $data;
    }

    public function createTemplate($template, $cacheId = null, $compileId = null, $data = null)
    {
        return new FakeSmartyTemplate();
    }
}

class FakeSmartyData
{
    public $parent;
    public $assigned = array();

    public function __construct($parent)
    {
        $this->parent = $parent;
    }

    public function assign($tplVar, $value = null)
    {
        if (is_array($tplVar)) {
            foreach ($tplVar as $k => $v) {
                $this->assigned[$k] = $v;
                TestState::$smartyAssignLog[] = array('key' => $k, 'value' => $v, 'target' => 'module_data');
            }
        } else {
            $this->assigned[$tplVar] = $value;
            TestState::$smartyAssignLog[] = array('key' => $tplVar, 'value' => $value, 'target' => 'module_data');
        }
    }
}

class FakeSmartyTemplate
{
    public function fetch()
    {
        return TestState::$fetchReturn;
    }
}

class FakeAdminController
{
    public function getLanguages()
    {
        return array(
            array('id_lang' => 7, 'name' => 'English'),
        );
    }
}

class Language
{
    public $id;

    public function __construct($id)
    {
        $this->id = (int) $id;
    }
}

class Link
{
    public $pageLinkCalls = array();
    public $adminLinkCalls = array();

    public function getPageLink($page)
    {
        $this->pageLinkCalls[] = $page;

        return TestState::$pageLinkReturn;
    }

    public function getAdminLink($controller, $withToken = true)
    {
        $this->adminLinkCalls[] = array($controller, $withToken);

        return TestState::$adminLinkReturn;
    }
}

class Configuration
{
    public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null, $default = false)
    {
        if (array_key_exists($key, TestState::$configuration)) {
            return TestState::$configuration[$key];
        }

        return $default;
    }

    public static function updateValue($key, $values, $html = false, $idShopGroup = null, $idShop = null)
    {
        if (!Validate::isConfigName($key)) {
            return false;
        }
        TestState::$configurationUpdates[] = array('key' => $key, 'value' => $values);
        if (TestState::$configurationUpdateResult) {
            TestState::$configuration[$key] = $values;
        }

        return (bool) TestState::$configurationUpdateResult;
    }
}

class Validate
{
    public static function isInt($value)
    {
        return (string) (int) $value === (string) $value || $value === false;
    }

    public static function isLoadedObject($object)
    {
        return is_object($object) && !empty($object->id);
    }

    public static function isConfigName($config_name)
    {
        return (bool) preg_match('/^[a-zA-Z_0-9-]+$/', $config_name);
    }

    public static function isHookName($hook)
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $hook);
    }
}

class Tools
{
    public static function isSubmit($submit)
    {
        return isset($_POST[$submit]) || isset($_POST[$submit . '_x']) || isset($_POST[$submit . '_y'])
            || isset($_GET[$submit]) || isset($_GET[$submit . '_x']) || isset($_GET[$submit . '_y']);
    }

    public static function getValue($key, $default_value = false)
    {
        if (empty($key) || !is_string($key)) {
            return false;
        }
        if (isset($_POST[$key]) || isset($_GET[$key])) {
            $value = isset($_POST[$key]) ? $_POST[$key] : $_GET[$key];
        } else {
            $value = $default_value;
        }
        if (is_string($value)) {
            return urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($value)));
        }

        return $value;
    }

    public static function getAdminTokenLite($tab, Context $context = null)
    {
        TestState::$getAdminTokenLiteTabs[] = $tab;

        return TestState::$adminTokenReturn;
    }
}

class Product
{
    public static function getPricesDrop(
        $id_lang,
        $page_number = 0,
        $nb_products = 10,
        $count = false,
        $order_by = null,
        $order_way = null,
        $beginning = false,
        $ending = false,
        $context = null
    ) {
        TestState::$pricesDropCalls[] = array(
            'id_lang' => $id_lang,
            'page_number' => $page_number,
            'nb_products' => $nb_products,
            'count' => $count,
            'order_by' => $order_by,
            'order_way' => $order_way,
        );

        return TestState::$pricesDropReturn;
    }
}

class ProductAssembler
{
    public $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function assembleProducts(array $rawProducts)
    {
        TestState::$assembleProductsLog[] = $rawProducts;
        $out = array();
        foreach ($rawProducts as $row) {
            $row['assembled_by'] = 'bulk';
            $out[] = $row;
        }

        return $out;
    }
}

class ProductPresenterFactory
{
    public $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getPresentationSettings()
    {
        return TestState::$presenterSettings;
    }
}

class HelperForm
{
    public $show_toolbar;
    public $table;
    public $default_form_language;
    public $allow_employee_form_lang;
    public $identifier;
    public $submit_action;
    public $currentIndex;
    public $token;
    public $tpl_vars;
    public $fields_form;

    public function generateForm($fields_form)
    {
        TestState::$helperFormCapture = array(
            'show_toolbar' => $this->show_toolbar,
            'table' => $this->table,
            'default_form_language' => $this->default_form_language,
            'allow_employee_form_lang' => $this->allow_employee_form_lang,
            'identifier' => $this->identifier,
            'submit_action' => $this->submit_action,
            'currentIndex' => $this->currentIndex,
            'token' => $this->token,
            'tpl_vars' => $this->tpl_vars,
            'fields_form' => $fields_form,
        );

        return 'FORM_MARK';
    }
}

class Hook
{
    public $id;

    public function __construct($id = null)
    {
        ++TestState::$hookConstructCount;
        if ($id !== null && isset(TestState::$preloadedHookIds[(int) $id])) {
            $this->id = (int) $id;
        }
    }

    public function delete()
    {
        TestState::$hookDeleteCalls[] = $this->id;
    }

    public static function getIdByName($hookName, $withAliases = true)
    {
        $hookName = strtolower($hookName);
        if (!Validate::isHookName($hookName)) {
            return false;
        }
        if (isset(TestState::$hookIdsByName[$hookName])) {
            return TestState::$hookIdsByName[$hookName];
        }

        return false;
    }

    public static function registerHook($module_instance, $hook_name, $shop_list = null)
    {
        if (!($module_instance instanceof Module) || !isset($module_instance->id) || !is_numeric($module_instance->id)) {
            return false;
        }
        $hook_names = is_array($hook_name) ? $hook_name : array($hook_name);
        foreach ($hook_names as $name) {
            if (!Validate::isHookName($name)) {
                return false;
            }
            if (TestState::$registerHookFailOn === $name) {
                return false;
            }
            TestState::$registeredHooks[] = $name;
            if (TestState::$registerHookResult === false) {
                return false;
            }
        }

        return true;
    }

    public static function unregisterHook($module_instance, $hook_identifier, $shop_list = null)
    {
        if (is_string($hook_identifier)) {
            $hook_id = self::getIdByName($hook_identifier, false);
            TestState::$unregisterHookCalls[] = $hook_identifier;
            if (empty($hook_id)) {
                return false;
            }

            return true;
        }

        return true;
    }
}

abstract class Module
{
    public $id;
    public $name;
    public $tab;
    public $author;
    public $version;
    public $need_instance;
    public $bootstrap;
    public $ps_versions_compliancy = array();
    public $displayName;
    public $description;
    protected $table = 'module';
    protected $identifier = 'id_module';
    protected $context;
    protected $smarty;
    private $templateFileForTests = 'module:ps_specials/views/templates/hook/ps_specials.tpl';

    public function __construct()
    {
        $this->context = Context::getContext();
        if (is_object($this->context->smarty)) {
            $this->smarty = $this->context->smarty->createData($this->context->smarty);
        }
    }

    public function install()
    {
        if (!TestState::$parentInstallResult) {
            return false;
        }
        $this->id = 42;

        return true;
    }

    public function uninstall()
    {
        return (bool) TestState::$parentUninstallResult;
    }

    public function registerHook($hook_name, $shop_list = null)
    {
        return Hook::registerHook($this, $hook_name, $shop_list);
    }

    public function unregisterHook($hook_id, $shop_list = null)
    {
        return Hook::unregisterHook($this, $hook_id, $shop_list);
    }

    protected function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        TestState::$parentClearCacheCalls[] = array(
            'template' => $template,
            'cache_id' => $cache_id,
            'compile_id' => $compile_id,
        );

        return (int) TestState::$parentClearCacheReturn;
    }

    protected function getCacheId($name = null)
    {
        return $name !== null ? $name : $this->name;
    }

    public function isCached($template, $cache_id = null, $compile_id = null)
    {
        TestState::$isCachedCalls[] = array(
            'template' => $template,
            'cache_id' => $cache_id,
            'compile_id' => $compile_id,
        );

        return (bool) TestState::$isCached;
    }

    public function fetch($templatePath, $cache_id = null, $compile_id = null)
    {
        TestState::$fetchCalls[] = array(
            'template' => $templatePath,
            'cache_id' => $cache_id,
            'compile_id' => $compile_id,
        );

        return TestState::$fetchReturn;
    }

    protected function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return htmlspecialchars($this->context->getTranslator()->trans($id, $parameters, $domain, $locale), ENT_NOQUOTES);
    }

    public function displayError($error)
    {
        $output = '
        <div class="bootstrap">
        <div class="module_error alert alert-danger" >
            <button type="button" class="close" data-dismiss="alert">&times;</button>';
        if (is_array($error)) {
            $output .= '<ul>';
            foreach ($error as $msg) {
                $output .= '<li>' . $msg . '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= $error;
        }
        $output .= '</div></div>';

        return $output;
    }

    public function displayConfirmation($string)
    {
        return '
        <div class="bootstrap">
        <div class="module_confirmation conf confirm alert alert-success">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            ' . $string . '
        </div>
        </div>';
    }
}

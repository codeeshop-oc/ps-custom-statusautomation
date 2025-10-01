<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use Codeeshop\PsModuleLogger\Log;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;

// if (class_exists(Codeeshop\PsModuleLogger\Log::class)) {
//     echo "Logger class loaded ✅";
// } else {
//     echo "Logger class NOT loaded ❌";
// }

class Statusautomation extends Module
{
    // for calling only 1 time
    public static $IS_FUNC_CALLED = false;
    public const MY_DEBUG = 'WITH';
    // public const MY_DEBUG = 'WITHOUT';
    // public const MY_DEBUG = 'WITHOUT';
    // protected $MY_DEBUG = 'WITH';
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'statusautomation';
        $this->tab = 'others';
        $this->version = '1.1.0';
        $this->author = 'Anant Fiverr';
        $this->need_instance = 0;

        /*
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Status Automation');
        $this->description = $this->l('Status Automation Status Automation');

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '9.0'];
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY', false);
        Configuration::updateValue('STATUSAUTOMATION_PHASE_1_BATCH_SIZE', 100);

        include dirname(__FILE__) . '/sql/install.php';

        return parent::install()
            && $this->registerHook([
                'header',
                'actionObjectOrderUpdateAfter',
                'displayFreeShippingHandlingMessage',
                'actionOrderStatusPostUpdate',
                'actionObjectCustomerUpdateBefore',
                'displayBackOfficeHeader',
                'moduleRoutes',
                'displayOrderConfirmation',
                'actionCustomerGridQueryBuilderModifier',
                'additionalCustomerFormFields',
                'actionCustomerGridDefinitionModifier',
            ]);
    }

    public function uninstall()
    {
        Configuration::deleteByName('STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY');

        include dirname(__FILE__) . '/sql/uninstall.php';

        return parent::uninstall();
    }

    private function test()
    {
        // $this->registerHook('displayFreeShippingHandlingMessage');
        // die;
        return;
        // $newOrderStatusObj = new stdClass();
        // // $newOrderStatusObj->id = 16;
        // // $id_order = 10;
        // $newOrderStatusObj->id = 11;
        // $id_order = 25;
        // $this->hookActionOrderStatusPostUpdate([
        //     'newOrderStatus' => $newOrderStatusObj,
        //     'id_order' => $id_order,
        // ]);
        // exit('test done ' . $id_order);

        // include dirname(__FILE__) . '/sql/uninstall.php';
        // include dirname(__FILE__) . '/sql/install.php';
        // $this->unregisterHook('actionObjectCustomerUpdateAfter');
        // $this->registerHook('actionObjectOrderUpdateAfter');

        // $id_carrier_temp = false;
        // $statusautomationModule = Module::getInstanceByName('statusautomation');
        // if (Validate::isLoadedObject($statusautomationModule)) {
        //     $id_carrier_temp = $statusautomationModule->getCarrierIdByCity($city, $id_country);
        // }
        // if ($id_carrier_temp) {
        //     $this->context->cart->id_carrier = $id_carrier_temp;
        // } else {
        //     $this->context->cart->id_carrier = Tools::getValue('carrier', (int)Configuration::get("WL_OOW_CARRIER"));
        // }
        // $this->getFreeShippingThreshold(151, 99);
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->test();
        $output = '';
        /*
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitStatusautomationModule')) == true) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->trans('Successfully Saved', [], 'Modules.Statusautomation.Statusautomation.php'));
        }

        $sfContainer = SymfonyContainer::getInstance();
        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'register_url' => $this->context->link->getModuleLink('statusautomation', 'login', ['create_account' => '1']),
            'login_url' => $this->context->link->getModuleLink('statusautomation', 'login', []),
            'STATUSAUTOMATION_DOWNLOAD_URL' => $sfContainer->get('router')->generate('statusautomation_download'),
        ]);

        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStatusautomationModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $field_values = $this->getConfigFormValues();

        $field_values['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS[]'] = [];

        foreach (['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS', 'STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES'] as $key) {
            if (!empty($field_values[$key]) && is_string($field_values[$key])) {
                $field_values[$key . '[]'] = json_decode($field_values[$key]);
            } else {
                $field_values[$key . '[]'] = $field_values[$key];
            }
        }

        $helper->tpl_vars = [
            'fields_value' => $field_values, /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm(), $this->getUploadForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $inputs = [];

        $this->phase1Inputs($inputs);

        // phase 2
        $this->phase2Inputs($inputs);

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'tabs' => [
                    'phase_1_inputs' => $this->l('Order Confirmation & VIP Offer Popup'),
                    'phase_2_inputs' => $this->l('Phase 2'),
                    'phase_3_inputs' => $this->l('Phase 3'),
                    'phase_4_inputs' => $this->l('Phase 4'),
                ],
                'input' => $inputs,
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $configs = [];

        foreach ([
            'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_GUEST',
            'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_GUEST',
            'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_UNDER_VERIFICATION',
            'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_UNDER_VERIFICATION',
            'STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY',
            'PSVIPFLOW_CUSTOMER_GROUP_ID',
            'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_VIP',
            'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_PHONE_VERIFY',
            'STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST',
            'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST',
            'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_CONVERT_GROUP_TO_BLACKLIST',
            'STATUSAUTOMATION_PHASE_1_BATCH_SIZE',
            'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_BLACKLIST',
            'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_REGISTER_VERIFY',
            'STATUSAUTOMATION_PHASE_2_ORDER_STATUS_CONFIRMED',
            'STATUSAUTOMATION_PHASE_2_ORDER_STATUS_CASABLANCA',
            'STATUSAUTOMATION_PHASE_2_ORDER_STATUS_NOT_CASABLANCA',
            'STATUSAUTOMATION_PHASE_2_ID_CARRIER_CASABLANCA',
            'STATUSAUTOMATION_PHASE_2_ID_CARRIER_NOT_CASABLANCA',
            'STATUSAUTOMATION_PHASE_2_STATUS',
        ] as $key) {
            $configs[$key] = Tools::getValue($key, Configuration::get($key));
        }

        for ($i = 0; $i < 3; ++$i) {
            $configs['STATUSAUTOMATION_PHASE_1_BUTTON_CUSTOM_URL_TYPE_' . $i] = Tools::getValue('STATUSAUTOMATION_PHASE_1_BUTTON_CUSTOM_URL_TYPE_' . $i, Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_CUSTOM_URL_TYPE_' . $i));
            $configs['STATUSAUTOMATION_PHASE_1_BUTTON_URL_' . $i] = Tools::getValue('STATUSAUTOMATION_PHASE_1_BUTTON_URL_' . $i, Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_URL_' . $i));
            $tmp = [];

            foreach (Language::getIDs(true) as $id_lang) {
                $tmp[$id_lang] = Tools::getValue('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i . '_' . $id_lang, Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i, $id_lang));
            }

            $configs['STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i] = $tmp;
        }

        $configs['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS'] = Tools::getValue('STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS', Configuration::get('STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS'));

        $configs['STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES'] = Tools::getValue('STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES', Configuration::get('STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES'));

        return $configs;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach ($form_values as $key => $value) {
            if (in_array($key, ['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS', 'STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES']) && is_array($value)) {
                // Convert all cities to lowercase and trim
                if ($key === 'STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES') {
                    $value = array_map('trim', array_map('strtolower', $value));
                    // update in post so it shows updated data
                    $_POST['STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES'] = json_encode($value);
                }
                Configuration::updateValue($key, json_encode($value));
            } else {
                Configuration::updateValue($key, $value);
            }
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $sfContainer = SymfonyContainer::getInstance();

            Media::addJsDef([
                'STATUSAUTOMATION_UPLOAD_URL' => $sfContainer->get('router')->generate('statusautomation_upload'),
                'STATUSAUTOMATION_UPLOAD_IMPORT_URL' => $sfContainer->get('router')->generate('statusautomation_upload_import'),
            ]);

            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
            $this->context->controller->addJS($this->_path . 'views/js/copy.js');
        }

        $controller = Tools::getValue('controller');
        if ($controller == 'AdminCustomers') {
            $this->context->controller->addJS($this->_path . 'views/js/back_customer.js');
        }
    }

    public function hookActionObjectOrderUpdateAfter($params)
    {
        $CesLog = new Log('statusautomation');
        if (Module::isEnabled($this->name) && Configuration::get('STATUSAUTOMATION_PHASE_2_STATUS')) {
            if (!self::$IS_FUNC_CALLED) {
                $this->processPendingStatuses();
            }
        }
    }

    /**
     * Create the structure of your form.
     */
    protected function getUploadForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Upload BlackList CSV File'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'file',
                        'label' => $this->trans('File', [], 'Modules.Statusautomation.Admin'),
                        'name' => 'STATUSAUTOMATION_FILE',
                    ],
                ],
                'buttons' => [
                    [
                        'type' => 'button',
                        'icon' => 'process-icon-upload',
                        'class' => 'btn-primary pull-right',
                        'title' => $this->l('Upload'),
                        'id' => 'submitOptionsmodule',
                    ],
                ],
            ],
        ];
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $loginURL = $this->context->link->getModuleLink('statusautomation', 'login', ['create_account' => '1']);
        $ORDER_PAGE_RESEND_OTP_URL = $this->context->link->getModuleLink('statusautomation', 'login', ['submitLoginResendVerificationCode' => '1']);
        $ORDER_PAGE_VALIDATE_OTP_URL = $this->context->link->getModuleLink('statusautomation', 'login', ['validate_verify' => '1']);

        $buttons = [];
        for ($i = 0; $i < 3; ++$i) {
            $buttons[] = [
                'status' => (bool) Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i, Context::getContext()->language->id),
                'text' => Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i, Context::getContext()->language->id),
                'type' => Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_CUSTOM_URL_TYPE_' . $i),
                'url' => $this->getRedirectURL(Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_CUSTOM_URL_TYPE_' . $i), Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_URL_' . $i)),
            ];
        }

        Media::addJsDef([
            'STATUSAUTOMATION_PHASE_2_STATUS' => Configuration::get('STATUSAUTOMATION_PHASE_2_STATUS'),
            // 'STATUSAUTOMATION_CASABLANCA_CITIES' => $this->getCitiesArray(),
            'STATUSAUTOMATION_LOGIN_URL' => $loginURL,
            'STATUSAUTOMATION_SIGN_IN_LOGIN' => $this->trans('WhatsApp', [], 'Modules.Statusautomation.Statusautomation.php'),
            'STATUSAUTOMATION_ADD_EMAIL_TEXT' => $this->trans('+ Add Email', [], 'Modules.Statusautomation.Statusautomation.php'),
            'STATUSAUTOMATION_HIDE_EMAIL_TEXT' => $this->trans('+ Hide Email', [], 'Modules.Statusautomation.Statusautomation.php'),
            'PSVIPFLOW_VERIFICATION_FORM_TITLE' => $this->trans('Verification Form Title', [], 'Modules.Statusautomation.Statusautomation.php'),
            'PSVIPFLOW_VERIFICATION_FORM_CONFIRM_BUTTON_TEXT' => $this->trans('Verify', [], 'Modules.Statusautomation.Statusautomation.php'),
            'PSVIPFLOW_VERIFICATION_FORM_ENTER_CODE_HERE_TEXT' => $this->trans('Enter code you received here', [], 'Modules.Statusautomation.Statusautomation.php'),
            'PSVIPFLOW_VERIFICATION_FORM_NOT_RECEIVED_TEXT' => $this->trans('Not received ?', [], 'Modules.Statusautomation.Statusautomation.php'),
            'PSVIPFLOW_VERIFICATION_FORM_RESEND_CODE_TEXT' => $this->trans('Resend code', [], 'Modules.Statusautomation.Statusautomation.php'),
            'PSVIPFLOW_VERIFICATION_FORM_ORDER_PAGE_RESEND_OTP_URL' => $ORDER_PAGE_RESEND_OTP_URL,
            'ORDER_PAGE_VALIDATE_OTP_URL' => $ORDER_PAGE_VALIDATE_OTP_URL,
            'whatsapp_buttons' => $buttons,
            'TEXT_RELOAD' => $this->trans('Reload', [], 'Modules.Statusautomation.Login.php'),
        ]);

        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');

        if (in_array(Tools::getValue('controller'), ['login']) && Tools::getValue('module') == 'statusautomation') {
            if (Tools::getValue('create_account') == '1') {
                $this->context->controller->addJS($this->_path . 'views/js/signup.js');
            } else {
                // login js is dependent on front.js
                $this->context->controller->addJS($this->_path . 'views/js/login.js');
            }
        }

        $this->saveLastVisitedProductId();
    }

    private function processPendingStatuses()
    {
        $rows = StatusautomationPendingStatusChanges::getAll();

        if (!empty($rows)) {
            self::$IS_FUNC_CALLED = true;
            $CesLog = new Log('statusautomation');
            $CesLog->write(json_encode(['rows' => $rows]));
            foreach ($rows as $row) {
                self::putPaymentStatus(new Order((int) $row['id_order']), $row['target_id_order_state']);
                StatusautomationPendingStatusChanges::delete($row['id_order'], $row['target_id_order_state']);
            }
        }
    }

    // save last visited product id
    private function saveLastVisitedProductId()
    {
        if (Tools::getValue('controller') == 'product') {
            $productId = (int) Tools::getValue('id_product');
            if ($productId) {
                Media::addJsDef([
                    'STATUSAUTOMATION_IS_PRODUCT_PAGE' => true,
                ]);
                // Context::getContext()->cookie->__set('statusautomation_last_viewed_product', $productId);
            }
        }
    }

    // get last visited product id
    public function getLastVisitedProductId()
    {
        return Context::getContext()->cookie->__get('statusautomation_last_viewed_product') ?? false;
    }

    public function hookModuleRoutes($params)
    {
        if (!Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY') && !Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST')) {
            return [];
        }

        return [
            'module-statusautomation-login' => [
                'rule' => 'mobile-login',
                'controller' => 'Login',
                'params' => [
                    'fc' => 'module',
                    'module' => 'statusautomation',
                ],
            ],
        ];
    }

    public function hookActionObjectCustomerUpdateBefore($params)
    {
        $customer = $params['object'];
        $old_customer = new Customer($customer->id);
        $new_customer_group = (int) $customer->id_default_group;
        $old_customer_group = (int) $old_customer->id_default_group;

        if ($old_customer_group != $new_customer_group) {
            if ($new_customer_group == Configuration::get('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_BLACKLIST')) {
                // update to blacklist
                $whatsapp_row = StatusautomationWhatsappVerify::getWhatsappRow($customer->id);

                if (StatusautomationBlacklist::isBlacklisted($whatsapp_row['whatsapp_number']) != 1) {
                    StatusautomationBlacklist::saveBlacklist($whatsapp_row['whatsapp_number']);
                    StatusautomationBlacklist::changeCustomerGroup($whatsapp_row['whatsapp_number']);
                }
            }
        }
    }

    // also changing to is guest 0
    public static function updateCustomerGroup($id_order, $id_customer_group)
    {
        $order = new Order($id_order);
        $customer = new Customer($order->id_customer);

        if ($customer->id_default_group != $id_customer_group) {
            $customer->cleanGroups();
            $customer->id_default_group = $id_customer_group;
            $customer->is_guest = 0;
            $customer->update();
            $customer->addGroups([(int) $id_customer_group]);
        }
    }

    private function checkIfCasablanca($id_order = null)
    {
        if ($id_order) {
            $sql = 'SELECT a.city
                FROM ' . _DB_PREFIX_ . 'orders o
                INNER JOIN ' . _DB_PREFIX_ . 'address a
                ON o.id_address_delivery = a.id_address
                WHERE o.id_order = ' . (int) $id_order;

            $city = Db::getInstance()->getValue($sql);

            return $this->checkIfCasablancaCity($city);
        }

        return false;
    }

    private function checkIfCasablancaCity($city = null)
    {
        $cities = $this->getCitiesArray();

        if ($city !== false && in_array(strtolower($city), $cities)) {
            return true;
        }

        return false;
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if (!Module::isEnabled($this->name)) {
            return;
        }

        try {
            $id_order = (int) $params['id_order'];
            if ($params['newOrderStatus']->id == Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_CONVERT_GROUP_TO_BLACKLIST')) {
                self::updateCustomerGroup($id_order, Configuration::get('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_BLACKLIST'));
            }

            if (Configuration::get('STATUSAUTOMATION_PHASE_2_STATUS') && $params['newOrderStatus']->id == Configuration::get('STATUSAUTOMATION_PHASE_2_ORDER_STATUS_CONFIRMED')) {
                // check if city is casablanca
                if ($this->checkIfCasablanca($id_order)) {
                    $this->saveForNextUpdate(new Order($id_order), Configuration::get('STATUSAUTOMATION_PHASE_2_ORDER_STATUS_CASABLANCA'));
                } else {
                    $this->saveForNextUpdate(new Order($id_order), Configuration::get('STATUSAUTOMATION_PHASE_2_ORDER_STATUS_NOT_CASABLANCA'));
                }
            }
        } catch (Exception $err) {
            $CesLog = new Log('statusautomation', 'error.log');
            $CesLog->write($err->getMessage());
        }
    }

    public function saveForNextUpdate($order, $order_status)
    {
        if (Validate::isLoadedObject($order) && $order->current_state != $order_status) {
            StatusautomationPendingStatusChanges::save($order->id, $order_status);
        }
    }

    public function hookActionCustomerGridDefinitionModifier($params)
    {
        if (!Module::isEnabled($this->name)) {
            return;
        }

        $definition = $params['definition'];
        $filters = $definition->getFilters();

        if (Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY')) {
            $definition
                ->getColumns()
                ->addBefore(
                    'email',
                    (new ToggleColumn('is_verified_field'))
                        ->setName($this->trans('Is Verified', [], 'Admin.Global'))
                        ->setOptions([
                            'field' => 'is_verified',
                            'route' => 'statusautomation_update_is_verified_toggle',
                            'primary_field' => 'id_customer',
                            'route_param_name' => 'customerId',
                        ])
                );

            $filters->add(
                (new Filter('is_verified_field', YesAndNoChoiceType::class))
                    ->setAssociatedColumn('is_verified_field')
            );
        }

        if (Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST')) {
            $definition
                ->getColumns()
                ->addBefore(
                    'email',
                    (new ToggleColumn('is_blacklist_field'))
                        ->setName($this->trans('Is Blacklist', [], 'Admin.Global'))
                        ->setOptions([
                            'field' => 'is_blacklist_field',
                            'route' => 'statusautomation_update_is_blacklist_toggle',
                            'primary_field' => 'id_customer',
                            'route_param_name' => 'customerId',
                        ])
                )
                ->addBefore(
                    'email',
                    (new DataColumn('is_blacklist_order_field'))
                        ->setName($this->trans('Is Blacklist (Orders)', [], 'Admin.Global'))
                        ->setOptions([
                            'clickable' => true,
                            'sortable' => false,
                            'field' => 'is_blacklist_order_field',
                        ])
                );

            $filters->add(
                (new Filter('is_blacklist_field', YesAndNoChoiceType::class))
                    ->setAssociatedColumn('is_blacklist_field')
            );
        }
    }

    /**
     * should be below ts_whatsapp module
     *
     * @param array $params
     *
     * @return void
     */
    public function hookActionCustomerGridQueryBuilderModifier(array $params)
    {
        if (!Module::isEnabled($this->name)) {
            return;
        }

        $searchQueryBuilder = $params['search_query_builder'];
        $searchCriteria = $params['search_criteria'];
        $check_condition = false;
        if (Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY')) {
            $searchQueryBuilder->addSelect(
                'IF(whatsapp.`is_verified` = "1", 1, 0) as `is_verified_field`'
            );

            if (!Module::isEnabled('ts_whatsapp')) {
                $searchQueryBuilder->leftJoin(
                    'c',
                    '`' . pSQL(_DB_PREFIX_) . 'ts_whatsapp`',
                    'whatsapp',
                    'whatsapp.`id_customer` = c.`id_customer`'
                );
            }

            $check_condition = true;
        }

        if (Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST')) {
            $searchQueryBuilder->addSelect(
                'IF(blist.`is_blacklisted` = "YES", 1, 0) as `is_blacklist_field`'
            );

            $searchQueryBuilder->addSelect(
                '(SELECT COUNT(inner_o.id_order) FROM `' . pSQL(_DB_PREFIX_) . 'orders` inner_o WHERE inner_o.id_customer = whatsapp.id_customer AND inner_o.current_state IN (' . Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST') . ',' . Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_CONVERT_GROUP_TO_BLACKLIST') . ')) as `is_blacklist_order_field`'
            );

            $searchQueryBuilder->leftJoin(
                'c',
                '`' . pSQL(_DB_PREFIX_) . 'statusautomation_blacklist`',
                'blist',
                'blist.`phone_number` = whatsapp.`whatsapp_number`'
            );

            $check_condition = true;
        }

        if ($check_condition) {
            foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
                if ('is_verified_field' === $filterName) {
                    $searchQueryBuilder->setParameter('is_verified_field', $filterValue);
                    $condition = '';
                    if ($filterValue == 0) {
                        $condition = 'whatsapp.is_verified is null OR ';
                    }

                    $searchQueryBuilder->andWhere('(' . $condition . ' whatsapp.is_verified = :is_verified_field)');
                } elseif ('is_blacklist_field' === $filterName) {
                    $searchQueryBuilder->setParameter('is_blacklist_field', $filterValue == 1 ? 'YES' : 'NO');
                    $condition = '';
                    if ($filterValue == 0) {
                        $condition = 'blist.is_blacklisted is null OR ';
                    }

                    $searchQueryBuilder->andWhere('(' . $condition . ' blist.is_blacklisted = :is_blacklist_field)');
                }
            }
        }
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public static function saveProportion($id_product, $proportion_value)
    {
        Db::getInstance()->delete('pproductsize', 'id_product = "' . (int) $id_product . '"');

        Db::getInstance()->insert('pproductsize', [
            'proportion_value' => $proportion_value,
            'id_product' => $id_product,
        ]);

        return true;
    }

    public static function initUploadProcess($csv, $xlsx)
    {
        // Db::getInstance()->execute('TRUNCATE `' . _DB_PREFIX_ . 'bulkorderstatusupdate`');

        @unlink($csv);
        @unlink($xlsx);
    }

    public function hookAdditionalCustomerFormFields($params)
    {
        if (!Module::isEnabled($this->name)) {
            return;
        }

        if (in_array(Tools::getValue('controller'), ['login']) && Tools::getValue('module') == 'statusautomation') {
            if (Tools::getValue('create_account') == '1') {
                foreach (array_keys($params['fields']) as $key) {
                    if (in_array($key, ['password', 'birthday', 'id_gender'])) {
                        unset($params['fields'][$key]);

                    // remove
                    // array_splice($params['fields'], 0, 0, [
                    //     (new FormField())
                    //     ->setName($key)
                    //     ->setType('hidden')
                    //         ->setValue('')
                    //         ->setRequired(false)
                    //         ->setLabel($key),
                    // ]);
                    } elseif ($key == 'email') {
                        // array_splice($params['fields'], 0, 0, [
                        //     (new FormField())
                        //     ->setName($key)
                        //     ->setType('hidden')
                        //         ->setValue('')
                        //         ->setRequired(false)
                        //         ->setLabel($key),
                        // ]);
                    }
                }
            } else {
            }
        }

        return [];
    }

    public static function encryptData($id_order)
    {
        return base64_encode($id_order);
    }

    public static function decryptData($id_order)
    {
        return base64_decode($id_order);
    }

    public static function statusUpdateCondition($id_order, $id_override_order_status = 0)
    {
        $order = new Order((int) $id_order);

        // if payment method valid
        if (Validate::isLoadedObject($order) && (empty(Configuration::get('STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS')) || (!empty(Configuration::get('STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS')) && in_array($order->module, json_decode(Configuration::get('STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS')))))) {
            // $orders_count = StatusautomationVerify::getOrderCount($order->id_customer);
            // if ($orders_count > 1) {

            $customer = new Customer($order->id_customer);
            $address = new Address($order->id_address_delivery);

            $is_blacklisted_number = false;

            // is a blacklisted number
            if (Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST')) {
                if (!empty($address->phone)) {
                    $is_blacklisted_number = StatusautomationBlacklist::isBlacklisted($address->phone);
                } elseif (!empty($address->phone_mobile)) {
                    $is_blacklisted_number = StatusautomationBlacklist::isBlacklisted($address->phone_mobile);
                }

                if ($is_blacklisted_number) {
                    self::putPaymentStatus($order, Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST'));
                }
            }

            if (!$is_blacklisted_number) {
                if (!$id_override_order_status) {
                    $id_override_order_status = (int) $customer->id_default_group;
                }
                switch ((int) $id_override_order_status) {
                    case Configuration::get('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_GUEST'):
                        self::putPaymentStatus($order, Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_GUEST'));

                        break;
                    case Configuration::get('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_UNDER_VERIFICATION'):
                        self::putPaymentStatus($order, Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_UNDER_VERIFICATION'));
                        break;
                    case (int) Configuration::get('PSVIPFLOW_CUSTOMER_GROUP_ID'):
                    case Configuration::get('PSVIPFLOW_CUSTOMER_GROUP_ID'):
                        // vip group
                        self::putPaymentStatus($order, Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_VIP'));
                        break;

                    default:
                        if (Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY')) {
                            // is a verified number
                            $is_verified_number = false;
                            if (!empty($address->phone)) {
                                $is_verified_number = StatusautomationVerify::isVerified($address->phone);
                            } elseif (!empty($address->phone_mobile)) {
                                $is_verified_number = StatusautomationVerify::isVerified($address->phone_mobile);
                            }

                            if ($is_verified_number) {
                                self::putPaymentStatus($order, Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_PHONE_VERIFY'));
                            }
                        }
                        break;
                }
            }
        }
    }

    // added this so its came after the module: prestavipflow, as order is reassigned for customer so we have to check number of orders
    public function hookDisplayOrderConfirmation($params)
    {
        if (!Module::isEnabled($this->name)) {
            return;
        }

        self::statusUpdateCondition($params['order']->id);
    }

    private static function putPaymentStatus($order, $order_status)
    {
        $CesLog = new Log('statusautomation');
        if (Validate::isLoadedObject($order) && $order->current_state != $order_status) {
            $order->setCurrentState((int) $order_status);
            $CesLog->write(sprintf('Updated: Order Id - %s, Status: %s', $order->id, $order_status));
        }
    }

    private function getOrderStates()
    {
        $orderStates = OrderState::getOrderStates($this->context->language->id);
        $data = [];

        $data[] = [
            'id' => '',
            'val' => '',
            'name' => $this->trans('--Select--', [], 'Modules.Statusautomation.Statusautomation.php'),
        ];

        foreach ($orderStates as $order_state) {
            $data[] = [
                'id' => $order_state['id_order_state'],
                'val' => $order_state['id_order_state'],
                'name' => $order_state['name'],
            ];
        }

        return $data;
    }

    private function getPaymentMethods()
    {
        $payment_methods = [];
        foreach (PaymentModule::getInstalledPaymentModules() as $payment) {
            $module = Module::getInstanceByName($payment['name']);
            if (Validate::isLoadedObject($module) && $module->active) {
                $payment_methods[] = [
                    'name' => $module->displayName,
                    'id' => $module->name,
                    'val' => $module->name,
                ];
            }
        }

        return $payment_methods;
    }

    private function getCustomerGroups()
    {
        $data = [];
        $id_lang = Context::getContext()->language->id;
        $sql = '
            SELECT a.id_group as id, gl.name
            FROM `' . _DB_PREFIX_ . 'group` a
            LEFT JOIN `' . _DB_PREFIX_ . 'group_lang` gl ON(gl.id_group = a.id_group)
            WHERE id_lang = "' . $id_lang . '"';

        foreach (Db::getInstance()->executeS($sql) as $row) {
            $data[] = [
                'id' => $row['id'],
                'val' => $row['id'],
                'name' => $row['name'],
            ];
        }

        return $data;
    }

    private function getRedirectURL($type, $url)
    {
        $link = $this->context->link;
        switch ($type) {
            case 'MY_HOMEPAGE':
                $url = $link->getPageLink('index', true);
                break;

            case 'CONTACT_US':
                $url = $link->getPageLink('contact', true);
                break;
            case 'LAST_PRODUCT_SEEN':
                // if ($id_product = $this->module->getLastVisitedProductId()) {
                //     $url = $link->getProductLink($id_product);
                // }
                break;
            case 'MY_ACCOUNT':
                $url = $link->getPageLink('my-account', true);
                break;

            case 'CUSTOM':
            default:
                // code...
                break;
        }

        // if url is empty goto myaccount page
        if (empty($url)) {
            $url = $link->getPageLink('my-account', true);
        }

        return $url;
    }

    private function phase1Inputs(&$inputs)
    {
        $inputs = [
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->l('Customer Group (Guest)'),
                'desc' => $this->l('if this status exist than it can change to `Customer Group (Under Verification)`'),
                'name' => 'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_GUEST',
                'options' => [
                    'query' => $this->getCustomerGroups(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->trans('Order Status (Guest)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_GUEST',
                'desc' => $this->l('if `Customer Group (Guest)` than change order status to this'),
                'options' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->l('Customer Group (Under Verification)'),
                'desc' => [
                    $this->l('if verification code is sent but not accepted'),
                    $this->l('and customer group exist is `Customer Group (Under Verification)`'),
                ],
                'name' => 'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_UNDER_VERIFICATION',
                'options' => [
                    'query' => $this->getCustomerGroups(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->trans('Order Status (Under Verification)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_UNDER_VERIFICATION',
                'desc' => $this->l('if `Customer Group (Under Verification)` than change order status to this'),
                'options' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->l('Customer Group (VIP)'),
                'name' => 'PSVIPFLOW_CUSTOMER_GROUP_ID',
                'options' => [
                    'query' => $this->getCustomerGroups(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->trans('Order Status (VIP)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_VIP',
                // 'desc' => $this->l('if `Customer Group (VIP)` than change order status to this'),
                'options' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'switch',
                'label' => $this->trans('Enable (Phone Verify)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled'),
                    ],
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->trans('Order Status (Phone Verify)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_PHONE_VERIFY',
                'desc' => $this->trans('If other order statuses are not valid and customer is veriried than use this', [], 'Modules.Statusautomation.Statusautomation.php'),
                'options' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'switch',
                'label' => $this->trans('Enable (BlackList)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled'),
                    ],
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->l('Customer Group (BlackList)'),
                'name' => 'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_BLACKLIST',
                'options' => [
                    'query' => $this->getCustomerGroups(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->trans('Order Status (BlackList)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST',
                'desc' => $this->l('Customer Group (BlackList)'),
                'options' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->trans('Order Status (Convert Customer Group to BlackList)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_CONVERT_GROUP_TO_BLACKLIST',
                'desc' => $this->l('If the order status changed to this customer group than customer will be marked as blacklist'),
                'options' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'select',
                'multiple' => true,
                'label' => $this->trans('Payment Method', [], 'Modules.Statusautomation.Statusautomation.php'),
                'class' => 'chosen',
                'name' => 'STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS',
                'required' => false,
                'options' => [
                    'query' => $this->getPaymentMethods(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ],
            [
                'type' => 'text',
                'name' => 'STATUSAUTOMATION_PHASE_1_BATCH_SIZE',
                'label' => $this->trans('Batch Import Size', [], 'Modules.Statusautomation.Statusautomation.php'),
                'tab' => 'phase_1_inputs',
            ],
        ];

        for ($i = 0; $i < 3; ++$i) {
            // Configuration::deleteByName('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i);
            $inputs[] = [
                'type' => 'text',
                'lang' => true,
                'name' => 'STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i,
                'label' => $this->trans('Button %d', [$i + 1], 'Modules.Statusautomation.Statusautomation.php'),
                'tab' => 'phase_1_inputs',
            ];

            $inputs[] = [
                'type' => 'select',
                'class' => 'chosen custom_url_type',
                'name' => 'STATUSAUTOMATION_PHASE_1_BUTTON_CUSTOM_URL_TYPE_' . $i,
                'label' => $this->trans('URL Type %d', [$i + 1], 'Modules.Statusautomation.Statusautomation.php'),
                'options' => [
                    'query' => [
                        [
                            'id' => 'MY_ACCOUNT',
                            'name' => $this->trans('My Account', [], 'Modules.Statusautomation.Statusautomation.php'),
                        ],
                        [
                            'id' => 'MY_HOMEPAGE',
                            'name' => $this->trans('Homepage', [], 'Modules.Statusautomation.Statusautomation.php'),
                        ],
                        [
                            'id' => 'CONTACT_US',
                            'name' => $this->trans('Contact Us', [], 'Modules.Statusautomation.Statusautomation.php'),
                        ],
                        [
                            'id' => 'LAST_PRODUCT_SEEN',
                            'name' => $this->trans('Last Product Seen', [], 'Modules.Statusautomation.Statusautomation.php'),
                        ],
                        [
                            'id' => 'CUSTOM',
                            'name' => $this->trans('Custom', [], 'Modules.Statusautomation.Statusautomation.php'),
                        ],
                    ],
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'phase_1_inputs',
            ];

            $inputs[] = [
                'type' => 'text',
                'name' => 'STATUSAUTOMATION_PHASE_1_BUTTON_URL_' . $i,
                'label' => $this->trans('Button %d (Redirect URL)', [$i + 1], 'Modules.Statusautomation.Statusautomation.php'),
                'tab' => 'phase_1_inputs',
            ];
        }
    }

    private function phase2Inputs(&$inputs)
    {
        $this->context->controller->addJS($this->_path . 'views/js/select2.js');
        $this->context->controller->addCSS($this->_path . 'views/css/select2.css');

        $STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES = Tools::getValue('STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES', Configuration::get('STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES'));

        $casablanca_cities_array = [];
        if (gettype($STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES) == 'string') {
            $STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES = json_decode($STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES, true);
        }

        foreach ($STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES as $value) {
            $casablanca_cities_array[] = [
                'id' => $value,
                'name' => $value,
            ];
        }

        $inputs[] = [
            'type' => 'switch',
            'label' => $this->trans('Live Status', [], 'Modules.Statusautomation.Statusautomation.php'),
            'name' => 'STATUSAUTOMATION_PHASE_2_STATUS',
            'is_bool' => true,
            'values' => [
                [
                    'id' => 'active_on',
                    'value' => true,
                    'label' => $this->l('Enabled'),
                ],
                [
                    'id' => 'active_off',
                    'value' => false,
                    'label' => $this->l('Disabled'),
                ],
            ],
            'tab' => 'phase_2_inputs',
        ];

        $inputs[] = [
            'type' => 'select',
            'class' => 'casablanca_cities',
            'multiple' => true,
            'name' => 'STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES',
            'label' => $this->trans('Casablanca Cities', [], 'Modules.Statusautomation.Statusautomation.php'),
            'options' => [
                'query' => $casablanca_cities_array,
                'id' => 'id',
                'name' => 'name',
            ],
            'tab' => 'phase_2_inputs',
        ];

        $inputs[] = [
            'type' => 'select',
            'class' => 'chosen',
            'label' => $this->trans('Order Status (Phase 1 Confirmed)', [], 'Modules.Statusautomation.Statusautomation.php'),
            'name' => 'STATUSAUTOMATION_PHASE_2_ORDER_STATUS_CONFIRMED',
            'desc' => $this->l('phase 2 order status is checked if this is the last order status of order'),
            'options' => [
                'query' => $this->getOrderStates(),
                'id' => 'id',
                'name' => 'name',
            ],
            'tab' => 'phase_2_inputs',
        ];

        $inputs[] = [
            'type' => 'select',
            'class' => 'chosen',
            'label' => $this->trans('Order Status (Casablanca)', [], 'Modules.Statusautomation.Statusautomation.php'),
            'name' => 'STATUSAUTOMATION_PHASE_2_ORDER_STATUS_CASABLANCA',
            'desc' => $this->l('if ordered city is `Casablanca`'),
            'options' => [
                'query' => $this->getOrderStates(),
                'id' => 'id',
                'name' => 'name',
            ],
            'tab' => 'phase_2_inputs',
        ];

        $inputs[] = [
            'type' => 'select',
            'class' => 'chosen',
            'label' => $this->trans('Carrier (Casablanca)', [], 'Modules.Statusautomation.Statusautomation.php'),
            'name' => 'STATUSAUTOMATION_PHASE_2_ID_CARRIER_CASABLANCA',
            'desc' => $this->l('if ordered city is `not Casablanca` this carrier is auto selected'),
            'options' => [
                'query' => $this->getCarriers(),
                'id' => 'id_carrier',
                'name' => 'name',
            ],
            'tab' => 'phase_2_inputs',
        ];

        $inputs[] = [
            'type' => 'select',
            'class' => 'chosen',
            'label' => $this->trans('Order Status (not Casablanca)', [], 'Modules.Statusautomation.Statusautomation.php'),
            'name' => 'STATUSAUTOMATION_PHASE_2_ORDER_STATUS_NOT_CASABLANCA',
            'desc' => $this->l('if ordered city is `not Casablanca`'),
            'options' => [
                'query' => $this->getOrderStates(),
                'id' => 'id',
                'name' => 'name',
            ],
            'tab' => 'phase_2_inputs',
        ];

        $inputs[] = [
            'type' => 'select',
            'class' => 'chosen',
            'label' => $this->trans('Carrier (not Casablanca)', [], 'Modules.Statusautomation.Statusautomation.php'),
            'name' => 'STATUSAUTOMATION_PHASE_2_ID_CARRIER_NOT_CASABLANCA',
            'desc' => $this->l('if ordered city is `not Casablanca` this carrier is auto selected'),
            'options' => [
                'query' => $this->getCarriers(),
                'id' => 'id_carrier',
                'name' => 'name',
            ],
            'tab' => 'phase_2_inputs',
        ];
    }

    private function getCarriers()
    {
        $orderStates = Carrier::getCarriers($this->context->language->id);

        return $orderStates;
    }

    private function getCitiesArray()
    {
        return json_decode(Configuration::get('STATUSAUTOMATION_PHASE_2_CASABLANCA_CITIES'), true);
    }

    public function hookDisplayFreeShippingHandlingMessage($params)
    {
        $output = '';
        if (Module::isEnabled($this->name) && Configuration::get('STATUSAUTOMATION_PHASE_2_STATUS')) {
            $free_remaining_amount = StatusautomationShippingHelper::getRemainingForCarrierFreeShipping($params['cart'], $params['id_carrier'], $params['id_country'], $params['cart_total']);
            $this->context->smarty->assign([
                // 'free_remaining_amount' => $free_remaining_amount,
                'free_remaining_amount' => $free_remaining_amount ? Tools::displayPrice($free_remaining_amount) : false,
            ]);

            $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/hook/displayFreeShippingHandlingMessage.tpl');
        }

        return $output;
    }

    // from Orderonwhatsapp > ajax.php, update > id_carrier
    public function getCarrierIdByCity($city, $id_country, $hook = '')
    {
        $id_carrier = null;

        if (Module::isEnabled($this->name) && Configuration::get('STATUSAUTOMATION_PHASE_2_STATUS')) {
            if ($city && $this->checkIfCasablancaCity($city)) {
                $id_carrier = Configuration::get('STATUSAUTOMATION_PHASE_2_ID_CARRIER_CASABLANCA');
            } else {
                $id_carrier = Configuration::get('STATUSAUTOMATION_PHASE_2_ID_CARRIER_NOT_CASABLANCA');
            }
        }

        return $id_carrier;
    }
}

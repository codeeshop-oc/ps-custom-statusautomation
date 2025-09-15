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

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;

class Statusautomation extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'statusautomation';
        $this->tab = 'others';
        $this->version = '1.0.0';
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

        // include dirname(__FILE__) . '/sql/uninstall.php';
        // include dirname(__FILE__) . '/sql/install.php';
        // $this->unregisterHook('actionValidateOrder');
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

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';
        /*
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitStatusautomationModule')) == true) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->trans('Successfully Saved', [], 'Modules.Statusautomation.Statusautomation.php'));
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $sfContainer = SymfonyContainer::getInstance();

        $this->context->smarty->assign([
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
        if (!empty($field_values['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS']) && is_string($field_values['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS'])) {
            $field_values['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS[]'] = json_decode($field_values['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS']);
        } else {
            $field_values['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS[]'] = $field_values['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS'];
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
        $inputs = [
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->l('Customer Group (Register Verify)'),
                'name' => 'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_REGISTER_VERIFY',
                'options' => [
                    'query' => $this->getCustomerGroups(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'general',
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
                'tab' => 'general',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->trans('Order Status (Phone Verify)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_PHONE_VERIFY',
                'options' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'general',
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
                'tab' => 'general',
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
                'tab' => 'general',
            ],
            [
                'type' => 'select',
                'class' => 'chosen',
                'label' => $this->trans('Order Status (BlackList)', [], 'Modules.Statusautomation.Statusautomation.php'),
                'name' => 'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST',
                'options' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name',
                ],
                'tab' => 'general',
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
                'tab' => 'general',
            ],
            [
                'type' => 'text',
                'name' => 'STATUSAUTOMATION_PHASE_1_BATCH_SIZE',
                'label' => $this->trans('Batch Import Size', [], 'Modules.Statusautomation.Statusautomation.php'),
                'tab' => 'general',
            ],
        ];

        for ($i = 0; $i < 3; ++$i) {
            // Configuration::deleteByName('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i);
            $inputs[] = [
                'type' => 'text',
                'lang' => true,
                'name' => 'STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i,
                'label' => $this->trans('Button %d', [$i + 1], 'Modules.Statusautomation.Statusautomation.php'),
                'tab' => 'general',
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
                'tab' => 'general',
            ];

            $inputs[] = [
                'type' => 'text',
                'name' => 'STATUSAUTOMATION_PHASE_1_BUTTON_URL_' . $i,
                'label' => $this->trans('Button %d (Redirect URL)', [$i + 1], 'Modules.Statusautomation.Statusautomation.php'),
                'tab' => 'general',
            ];
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'tabs' => [
                    'general' => $this->l('Order Confirmation & VIP Offer Popup'),
                    'product_import' => $this->l('Phase 2'),
                    'product_export' => $this->l('Phase 3'),
                    'order_import' => $this->l('Phase 4'),
                    // 'advanced' => $this->l('Advanced Settings'),
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
        $configs = [
            'STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY' => Tools::getValue('STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY', Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY')),
            'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_PHONE_VERIFY' => Tools::getValue('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_PHONE_VERIFY', Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_PHONE_VERIFY')),
            'STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST' => Tools::getValue('STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST', Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST')),
            'STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST' => Tools::getValue('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST', Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST')),
            'STATUSAUTOMATION_PHASE_1_BATCH_SIZE' => Tools::getValue('STATUSAUTOMATION_PHASE_1_BATCH_SIZE', Configuration::get('STATUSAUTOMATION_PHASE_1_BATCH_SIZE')),
            'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_BLACKLIST' => Tools::getValue('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_BLACKLIST', Configuration::get('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_BLACKLIST')),
            'STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_REGISTER_VERIFY' => Tools::getValue('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_REGISTER_VERIFY', Configuration::get('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_REGISTER_VERIFY')),
        ];

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

        return $configs;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach ($form_values as $key => $value) {
            if (in_array($key, ['STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS']) && is_array($value)) {
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
        }

        if (Tools::getValue('controller') == 'AdminCustomers') {
            $this->context->controller->addJS($this->_path . 'views/js/back_customer.js');
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
        Media::addJsDef([
            'STATUSAUTOMATION_LOGIN_URL' => $loginURL,
            'STATUSAUTOMATION_SIGN_IN_LOGIN' => $this->trans('WhatsApp', [], 'Modules.Statusautomation.Statusautomation.php'),
            'STATUSAUTOMATION_ADD_EMAIL_TEXT' => $this->trans('+ Add Email', [], 'Modules.Statusautomation.Statusautomation.php'),
            'STATUSAUTOMATION_HIDE_EMAIL_TEXT' => $this->trans('+ Hide Email', [], 'Modules.Statusautomation.Statusautomation.php'),
        ]);

        if (in_array(Tools::getValue('controller'), ['login']) && Tools::getValue('module') == 'statusautomation') {
            if (Tools::getValue('create_account') == '1') {
                $this->context->controller->addJS($this->_path . 'views/js/signup.js');
            } else {
                $this->context->controller->addJS($this->_path . 'views/js/login.js');
            }
        }
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');

        $this->saveLastVisitedProductId();
        // dump($this->context->customer->isLogged());die;
    }

    // save last visited product id
    private function saveLastVisitedProductId()
    {
        if (Tools::getValue('controller') == 'product') {
            $productId = (int) Tools::getValue('id_product');
            if ($productId) {
                Context::getContext()->cookie->__set('statusautomation_last_viewed_product', $productId);
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
                '(SELECT COUNT(inner_o.id_order) FROM `' . pSQL(_DB_PREFIX_) . 'orders` inner_o WHERE inner_o.id_customer = whatsapp.id_customer AND inner_o.current_state = ' . Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST') . ') as `is_blacklist_order_field`'
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

    // added this so its came after the module: prestavipflow, as order is reassigned for customer so we have to check number of orders
    public function hookDisplayOrderConfirmation($params)
    {
        if (!Module::isEnabled($this->name)) {
            return;
        }

        // if payment method valid
        if (empty(Configuration::get('STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS')) || (!empty(Configuration::get('STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS')) && in_array($params['order']->module, json_decode(Configuration::get('STATUSAUTOMATION_PHASE_1_PAYMENT_METHODS'))))) {
            // $orders_count = StatusautomationVerify::getOrderCount($params['order']->id_customer);
            // if ($orders_count > 1) {

            $address = new Address($params['order']->id_address_delivery);

            if (Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_PHONE_VERIFY')) {
                // is a verified number
                $is_verified_number = false;
                if (!empty($address->phone)) {
                    $is_verified_number = StatusautomationVerify::isVerified($address->phone);
                } elseif (!empty($address->phone_mobile)) {
                    $is_verified_number = StatusautomationVerify::isVerified($address->phone_mobile);
                }

                if ($is_verified_number) {
                    $this->putPaymentStatus($params['order'], Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_PHONE_VERIFY'));
                }
            }

            // is a blacklisted number
            if (Configuration::get('STATUSAUTOMATION_PHASE_1_STATUS_BLACKLIST')) {
                $is_blacklisted_number = false;
                if (!empty($address->phone)) {
                    $is_blacklisted_number = StatusautomationBlacklist::isBlacklisted($address->phone);
                } elseif (!empty($address->phone_mobile)) {
                    $is_blacklisted_number = StatusautomationBlacklist::isBlacklisted($address->phone_mobile);
                }

                if ($is_blacklisted_number) {
                    $this->putPaymentStatus($params['order'], Configuration::get('STATUSAUTOMATION_PHASE_1_ORDER_STATUS_BLACKLIST'));
                }
            }
        }
    }

    private function putPaymentStatus($order, $order_status)
    {
        if (Validate::isLoadedObject($order) && $order->current_state != $order_status) {
            $order->setCurrentState((int) $order_status);
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
}

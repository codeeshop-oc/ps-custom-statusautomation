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
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('SHOPIFYPSC_PHASE_1_STATUS', false);

        include dirname(__FILE__) . '/sql/install.php';

        return parent::install()
            && $this->registerHook([
                'header',
                'displayBackOfficeHeader',
                'moduleRoutes',
                'actionCustomerGridQueryBuilderModifier',
                'actionCustomerGridDefinitionModifier',
            ]);
    }

    public function uninstall()
    {
        Configuration::deleteByName('SHOPIFYPSC_PHASE_1_STATUS');

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

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getUploadForm(), $this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $inputs = [
            [
                'type' => 'switch',
                'label' => $this->l('Live mode'),
                'name' => 'SHOPIFYPSC_PHASE_1_STATUS',
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
        ];

        for ($i = 0; $i < 3; ++$i) {
            $inputs[] = [
                'type' => 'text',
                'name' => 'SHOPIFYPSC_PHASE_1_BUTTON_' . $i,
                'label' => $this->trans('Button %d', [$i + 1], 'Modules.Statusautomation.Statusautomation.php'),
                'tab' => 'general',
            ];

            $inputs[] = [
                'type' => 'text',
                'name' => 'SHOPIFYPSC_PHASE_1_BUTTON_URL_' . $i,
                'label' => $this->trans('Button URL %d', [$i + 1], 'Modules.Statusautomation.Statusautomation.php'),
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

                // [

                //     // array(
                //     //     'col' => 3,
                //     //     'type' => 'text',
                //     //     'prefix' => '<i class="icon icon-envelope"></i>',
                //     //     'desc' => $this->l('Enter a valid email address'),
                //     //     'name' => 'STATUSAUTOMATION_ACCOUNT_EMAIL',
                //     //     'label' => $this->l('Email'),
                //     // ),
                //     // array(
                //     //     'type' => 'password',
                //     //     'name' => 'STATUSAUTOMATION_ACCOUNT_PASSWORD',
                //     //     'label' => $this->l('Password'),
                //     // ),
                // ],
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
            'SHOPIFYPSC_PHASE_1_STATUS' => Tools::getValue('SHOPIFYPSC_PHASE_1_STATUS', Configuration::get('SHOPIFYPSC_PHASE_1_STATUS')),
        ];

        for ($i = 0; $i < 3; ++$i) {
            $configs['SHOPIFYPSC_PHASE_1_BUTTON_' . $i] = Tools::getValue('SHOPIFYPSC_PHASE_1_BUTTON_' . $i, Configuration::get('SHOPIFYPSC_PHASE_1_BUTTON_' . $i));
            $configs['SHOPIFYPSC_PHASE_1_BUTTON_URL_' . $i] = Tools::getValue('SHOPIFYPSC_PHASE_1_BUTTON_URL_' . $i, Configuration::get('SHOPIFYPSC_PHASE_1_BUTTON_URL_' . $i));
        }

        return $configs;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
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
    }

    /**
     * Create the structure of your form.
     */
    protected function getUploadForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Upload CSV File'),
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
                        'class' => 'btn btn-primary pull-right',
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
            'statusautomation_login_url' => $loginURL,
        ]);

        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookModuleRoutes($params)
    {
        if (!Configuration::get('SHOPIFYPSC_PHASE_1_STATUS')) {
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
        if (Module::isEnabled($this->name) && Configuration::get('SHOPIFYPSC_PHASE_1_STATUS')) {
            $definition = $params['definition'];
            $filters = $definition->getFilters();
            $columns = $definition->getColumns();

            foreach (['country_id', 'country'] as $key) {
                $columns->remove($key);
                $filters->remove($key);
            }

            $definition
                ->getColumns()
                ->addAfter(
                    'osname',
                    (new DataColumn('shipping_status'))
                        ->setName('Shipping Status')
                        ->setOptions([
                            'field' => 'shipping_status_field', // the database field
                        ])
                );

            $filters = $definition->getFilters();
            $filters->add(
                (new Filter('shipping_status', ChoiceType::class))
                    ->setTypeOptions([
                        'choices' => $this->getCustomerGroupChoices(),
                        'required' => false,
                        // 'placeholder' => $this->l('All groups'),
                        'translation_domain' => false,
                    ])
                    ->setAssociatedColumn('shipping_status')
            );
        }
    }

    /**
     * hook to get listing query
     *
     * @param array $params
     *
     * @return void
     */
    public function hookActionCustomerGridQueryBuilderModifier(array $params)
    {
        if (Module::isEnabled($this->name) && Configuration::get('SHOPIFYPSC_PHASE_1_STATUS')) {
            $searchQueryBuilder = $params['search_query_builder'];
            $searchCriteria = $params['search_criteria'];
            $searchQueryBuilder->addSelect(
                'IF(wdto.`shipping_status` IS NULL, "", wdto.`shipping_status`) as `shipping_status_field`'
            );
            $searchQueryBuilder->leftJoin(
                'o',
                '`' . pSQL(_DB_PREFIX_) . 'ordericonsandfilters`',
                'wdto',
                'wdto.`id_order` = o.`id_order`'
            );
            foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
                if ('shipping_status' === $filterName) {
                    if ($filterValue == 'FULLY_SHIPPED') {
                        $searchQueryBuilder->setParameter('shipping_status', $filterValue);
                        $searchQueryBuilder->andWhere('(shipping_status = :shipping_status)');
                    } elseif ($filterValue == 'PARTIALLY_SHIPPED') {
                        $searchQueryBuilder->setParameter('shipping_status', $filterValue);
                        $searchQueryBuilder->andWhere('(shipping_status = :shipping_status)');
                    }
                }
            }
        }
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }
}

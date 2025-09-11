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
class StatusautomationLoginModuleFrontController extends ModuleFrontController
{
    private const PREFIX_WHATSAPP_NUMBER = '+212';
    public $ssl = true;
    // public $php_self = 'login';
    public $auth = false;

    // .page-authentication, .page-customer-account

    public function checkAccess()
    {
        if ($this->context->customer->isLogged() && !$this->ajax) {
            $this->redirect_after = ($this->authRedirection) ? urlencode($this->authRedirection) : 'my-account';
            $this->redirect();
        }

        return parent::checkAccess();
    }

    public function initContent()
    {
        $should_redirect = false;

        if (Tools::isSubmit('submitCreate') || Tools::isSubmit('create_account')) {
            // fixed added the email
            if (empty($_POST['email'])) {
                $_POST['email'] = sprintf('%s@secure.boutique', Tools::getValue('whatsapp', ''));
            }

            foreach (['password', 'birthday', 'id_gender'] as $key) {
                $_POST[$key] = '';
            }

            $register_form = $this
                ->makeCustomerForm()
                ->setGuestAllowed(false)
                ->fillWith(Tools::getAllValues());

            // dump((Tools::getAllValues()));die;

            if (Tools::isSubmit('submitCreate')) {
                $hookResult = array_reduce(
                    Hook::exec('actionSubmitAccountBefore', [], null, true),
                    function ($carry, $item) {
                        return $carry && $item;
                    },
                    true
                );
                if ($hookResult && $register_form->submit()) {
                    $should_redirect = true;
                }
            }

            $this->context->smarty->assign([
                'authentication_url' => $this->context->link->getModuleLink('statusautomation', 'login', []),
                'register_form' => $register_form->getProxy(),
                'hook_create_account_top' => Hook::exec('displayCustomerAccountFormTop'),
            ]);

            $this->setTemplate('module:statusautomation/views/templates/front/customer/registration.tpl');
        } elseif (Tools::isSubmit('submitVerify')) {
            // Tools::getValue('whatsapp')
            // dump(Tools::getValue('phone_verify_code'));
            // api to send code

            $this->ajaxRender(json_encode([
                'status' => true,
                'values' => Tools::getAllValues(),
            ]));
            exit;
        } elseif (Tools::isSubmit('w_validate')) {
            $login_form = $this->makeLoginForm()->fillWith(
                Tools::getAllValues()
            );

            $whatsapp_number = $this->getWhatsappNumber($this->context->customer->id);
            $URL = $this->context->link->getModuleLink('statusautomation', 'login', ['validate_verify' => '1']);

            $this->context->smarty->assign([
                'action' => $URL,
                'whatsapp_number' => $whatsapp_number,
                'prefix_whatsapp_number' => self::PREFIX_WHATSAPP_NUMBER,
                // 'login_form' => $login_form->getProxy(),
            ]);

            $this->setTemplate('module:statusautomation/views/templates/front/customer/whatsapp_validate.tpl');
        } else {
            $login_form = $this->makeLoginForm()->fillWith(
                Tools::getAllValues()
            );

            if (Tools::isSubmit('submitLogin')) {
                if ($login_form->submit()) {
                    $should_redirect = true;
                }
            }

            $this->context->smarty->assign([
                'login_form' => $login_form->getProxy(),
            ]);

            $this->setTemplate('module:statusautomation/views/templates/front/customer/authentication.tpl');
        }

        parent::initContent();

        if ($should_redirect && !$this->ajax) {
            $back = urldecode(Tools::getValue('back'));

            $new_url = str_replace('create_account=1', 'w_validate=1', $this->urls['current_url']);

            return $this->redirectWithNotifications($new_url);

            // dump($back);die;
            // if (Tools::urlBelongsToShop($back)) {
            //     // Checks to see if "back" is a fully qualified
            //     // URL that is on OUR domain, with the right protocol
            //     return $this->redirectWithNotifications($back);
            // }

            // // Well we're not redirecting to a URL,
            // // so...
            // if ($this->authRedirection) {
            //     // We may need to go there if defined
            //     return $this->redirectWithNotifications($this->authRedirection);
            // }

            // // go home
            // return $this->redirectWithNotifications(__PS_BASE_URI__);
        }
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        if (Tools::isSubmit('submitCreate') || Tools::isSubmit('create_account')) {
            $breadcrumb['links'][] = [
                'title' => $this->trans('Create an account', [], 'Shop.Theme.Customeraccount'),
                'url' => $this->context->link->getModuleLink('statusautomation', 'login', []),
            ];
        } else {
            $breadcrumb['links'][] = [
                'title' => $this->trans('Log in to your account', [], 'Shop.Theme.Customeraccount'),
                'url' => $this->context->link->getModuleLink('statusautomation', 'login', []),
            ];
        }

        return $breadcrumb;
    }

    public static function getWhatsappNumber($id_customer = null)
    {
        $query = new DbQuery();
        $query->select('s.whatsapp_number');
        $query->from('ts_whatsapp', 's');
        $query->where('s.`id_customer` = ' . (int) $id_customer);

        $whatsapp_number = Db::getInstance()->getValue($query);

        return $whatsapp_number ?? false;
    }

    public function getTemplateVarPage()
    {
        $params = parent::getTemplateVarPage();

        $params['body_classes']['page-customer-account'] = true;
        $params['body_classes']['page-authentication'] = true;

        return $params;
    }

    public function getRedirectURL()
    {
        $homeUrl = $this->context->link->getPageLink('index', true);
        $contactUsUrl = $this->context->link->getPageLink('contact', true);
        $myAccountUrl = $this->context->link->getPageLink('my-account', true);
        dump($homeUrl);
        dump($myAccountUrl);
        dump($contactUsUrl);
        exit;
    }
}

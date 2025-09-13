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
        $should_redirect = $should_register_redirect = false;

        if (Tools::isSubmit('order_to_login')) {
            $_POST['whatsapp'] = Tools::getValue('t_number');
            $this->context->cookie->__set('statusautomation_validate_login_whatsapp', Tools::getValue('whatsapp', false));

            $status = true;
            $message = '';
            // $should_redirect = true;
            $current_customer_id = StatusautomationBlacklist::getOneCustomerIdByWhatsappNumber(Tools::getValue('whatsapp', false));

            $whatsapp_number_row = StatusautomationWhatsappVerify::getWhatsappRow($current_customer_id);

            $id_whatsapp = $whatsapp_number_row ? $whatsapp_number_row['id_whatsapp'] : 0;
            $whatsapp_number = $whatsapp_number_row ? $whatsapp_number_row['whatsapp_number'] : '';

            $this->sendOTPReq($id_whatsapp, $whatsapp_number);

            $new_url = $this->context->link->getModuleLink('statusautomation', 'login', ['phone_number_validate' => 1, 'whatsapp' => $whatsapp_number]);

            $this->ajaxRender(json_encode([
                'status' => $status,
                'message' => $message,
                'new_url' => $new_url,
                'values' => Tools::getAllValues(),
            ]));
            exit;
        } elseif (Tools::isSubmit('submitCreate') || Tools::isSubmit('create_account')) {
            // fixed added the email
            if (empty($_POST['email'])) {
                $_POST['email'] = sprintf('%s@secure.boutique', Tools::getValue('whatsapp', ''));
            }

            foreach (['password', 'birthday', 'id_gender'] as $key) {
                $_POST[$key] = '';
            }

            $old_customer_exist = false;
            if (Validate::isEmail($_POST['email'])) {
                $old_customer_exist = (new Customer())->getByEmail($_POST['email']);
            }

            // dump($_POST['email']);die;

            $register_form = $this
                ->makeCustomerForm()
                ->setGuestAllowed(false)
                ->fillWith(Tools::getAllValues());

            if (Tools::isSubmit('submitCreate')) {
                $hookResult = array_reduce(
                    Hook::exec('actionSubmitAccountBefore', [], null, true),
                    function ($carry, $item) {
                        return $carry && $item;
                    },
                    true
                );
                if ($hookResult && $register_form->submit()) {
                    $should_register_redirect = true;
                }
            }

            $phone_number_already_exist = false;
            if (!empty($old_customer_exist) && !empty($old_customer_exist->id) && $this->context->customer->id != $old_customer_exist->id) {
                $phone_number_already_exist = $this->trans('Phone number already registered. Please login.', [], 'Modules.Statusautomation.Login.php');
            }

            $this->context->smarty->assign([
                'phone_number_already_exist' => $phone_number_already_exist,
                'authentication_url' => $this->context->link->getModuleLink('statusautomation', 'login', []),
                'register_form' => $register_form->getProxy(),
                'hook_create_account_top' => Hook::exec('displayCustomerAccountFormTop'),
            ]);

            $this->setTemplate('module:statusautomation/views/templates/front/customer/registration.tpl');
        } elseif (Tools::isSubmit('submitLoginResendVerificationCode')) {
            $ts_whatsappModule = Module::getInstanceByName('ts_whatsapp');
            $resp = $ts_whatsappModule->checkPhoneNumberIsValid(Tools::getValue('whatsapp'));
            $status = $resp['status'] ?? false;
            $message = $resp['message'] ?? false;
            $new_otp = '';

            if ($status) {
                // send otp before redirect

                $current_customer_id = StatusautomationBlacklist::getOneCustomerIdByWhatsappNumber(Tools::getValue('whatsapp', false));

                $whatsapp_number_row = StatusautomationWhatsappVerify::getWhatsappRow($current_customer_id);

                $id_whatsapp = $whatsapp_number_row ? $whatsapp_number_row['id_whatsapp'] : 0;
                $whatsapp_number = $whatsapp_number_row ? $whatsapp_number_row['whatsapp_number'] : '';

                $resp = $this->sendOTPReq($id_whatsapp, $whatsapp_number);

                if ($resp['status']) {
                    $new_otp = $resp['otp'];
                    $message = $this->trans('Successfully Sent', [], 'Modules.Statusautomation.Login.php');
                }
            }

            $this->ajaxRender(json_encode([
                'status' => $status,
                'message' => $message,
                // 'new_otp' => $new_otp,
                'values' => Tools::getAllValues(),
            ]));
            exit;
        } elseif (Tools::isSubmit('submitResendVerificationCode')) {
            $ts_whatsappModule = Module::getInstanceByName('ts_whatsapp');
            $resp = $ts_whatsappModule->checkPhoneNumberIsValid(Tools::getValue('whatsapp'));
            $status = $resp['status'] ?? false;
            $message = $resp['message'] ?? false;
            $new_otp = '';

            if ($status) {
                // send otp before redirect
                $whatsapp_number_row = StatusautomationWhatsappVerify::getWhatsappRow($this->context->customer->id);
                $id_whatsapp = $whatsapp_number_row ? $whatsapp_number_row['id_whatsapp'] : 0;
                $whatsapp_number = $whatsapp_number_row ? $whatsapp_number_row['whatsapp_number'] : '';

                $resp = $this->sendOTPReq($id_whatsapp, $whatsapp_number);

                if ($resp['status']) {
                    $new_otp = $resp['otp'];
                    $message = $this->trans('Successfully Sent', [], 'Modules.Statusautomation.Login.php');
                }
            }

            $this->ajaxRender(json_encode([
                'status' => $status,
                'message' => $message,
                'new_otp' => $new_otp,
                'values' => Tools::getAllValues(),
            ]));
            exit;
        } elseif (Tools::isSubmit('submitVerifyLogin')) {
            // api to send code

            $current_customer_id = StatusautomationBlacklist::getOneCustomerIdByWhatsappNumber(Tools::getValue('whatsapp', false));

            $whatsapp_number_row = StatusautomationWhatsappVerify::getWhatsappRow($current_customer_id);

            $status = false;
            if (StatusautomationWhatsappVerify::checkOTP($whatsapp_number_row ? $whatsapp_number_row['id_whatsapp'] : '', Tools::getValue('whatsapp'), Tools::getValue('phone_verify_code', false))) {
                $customer = new Customer($current_customer_id);
                $this->doOnlyLogin($customer->email);

                if (empty($this->errors)) {
                    $status = true;
                    $message = $this->trans('Successfully Login', [], 'Modules.Statusautomation.Login.php');
                } else {
                    $message = implode('<br/>', $this->errors);
                }
            } else {
                $message = $this->trans('Invalid OTP. Please try again.', [], 'Modules.Statusautomation.Login.php');
            }

            $this->ajaxRender(json_encode([
                'status' => $status,
                'message' => $message,
                'values' => Tools::getAllValues(),
            ]));
            exit;
        } elseif (Tools::isSubmit('submitVerify')) {
            // api to send code

            if (!empty($this->context->customer->id)) {
                $current_customer_id = $this->context->customer->id;
            } else {
                $current_customer_id = StatusautomationBlacklist::getOneCustomerIdByWhatsappNumber(Tools::getValue('whatsapp', false));
            }

            $whatsapp_number_row = StatusautomationWhatsappVerify::getWhatsappRow($current_customer_id);

            $status = false;
            if (StatusautomationWhatsappVerify::checkOTP($whatsapp_number_row ? $whatsapp_number_row['id_whatsapp'] : '', Tools::getValue('whatsapp'), Tools::getValue('phone_verify_code', false))) {
                $curr_customer = new Customer($current_customer_id);
                $this->doLoginFromRegister($curr_customer->email);

                if (empty($this->errors)) {
                    $status = true;
                    $message = $this->trans('Successfully Verified', [], 'Modules.Statusautomation.Login.php');
                } else {
                    $message = implode('<br/>', $this->errors);
                }
            } else {
                $message = $this->trans('Invalid OTP. Please try again.', [], 'Modules.Statusautomation.Login.php');
            }

            $this->ajaxRender(json_encode([
                'status' => $status,
                'message' => $message,
                'values' => Tools::getAllValues(),
            ]));
            exit;
        } elseif (Tools::isSubmit('login_whatsapp_validate')) {
            $this->context->controller->registerJavascript(
                'module-sweetalert-lib', // A unique ID for the script
                'modules/' . $this->module->name . '/views/js/sweetalert.js', // The file path
                ['position' => 'bottom', 'priority' => 200] // An array of options
            );

            if (!empty($this->context->cookie->__get('statusautomation_validate_login_whatsapp'))) {
                $_POST['whatsapp'] = $this->context->cookie->__get('statusautomation_validate_login_whatsapp');
            }

            $login_form = $this->makeLoginForm()->fillWith(
                Tools::getAllValues()
            );

            $current_customer_id = StatusautomationBlacklist::getOneCustomerIdByWhatsappNumber(Tools::getValue('whatsapp', false));

            $whatsapp_number_row = StatusautomationWhatsappVerify::getWhatsappRow($current_customer_id);

            $id_whatsapp = $whatsapp_number_row ? $whatsapp_number_row['id_whatsapp'] : 0;
            $whatsapp_number = $whatsapp_number_row ? $whatsapp_number_row['whatsapp_number'] : '';
            $URL = $this->context->link->getModuleLink('statusautomation', 'login', ['validate_verify' => '1']);
            $resend_verification_code = $this->context->link->getModuleLink('statusautomation', 'login', ['submitLoginResendVerificationCode' => '1']);

            $buttons = [];
            for ($i = 0; $i < 3; ++$i) {
                $buttons[] = [
                    'status' => (bool) Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i, Context::getContext()->language->id),
                    'text' => Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i, Context::getContext()->language->id),
                    'url' => $this->getRedirectURL(Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_CUSTOM_URL_TYPE_' . $i), Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_URL_' . $i)),
                ];
            }

            Media::addJsDef([
                'whatsapp_buttons' => $buttons,
            ]);

            $this->context->smarty->assign([
                'whatsapp_verify_url' => $URL,
                'whatsapp_resend_verification_code_url' => $resend_verification_code,
                'whatsapp_number' => $whatsapp_number,
                'submitName' => 'submitVerifyLogin',
                'prefix_whatsapp_number' => self::PREFIX_WHATSAPP_NUMBER,
            ]);

            $this->setTemplate('module:statusautomation/views/templates/front/customer/whatsapp_validate.tpl');
        } elseif (Tools::isSubmit('phone_number_validate')) {
            $this->context->controller->registerJavascript(
                'module-sweetalert-lib', // A unique ID for the script
                'modules/' . $this->module->name . '/views/js/sweetalert.js', // The file path
                ['position' => 'bottom', 'priority' => 200] // An array of options
            );

            $login_form = $this->makeLoginForm()->fillWith(
                Tools::getAllValues()
            );

            if (!empty($this->context->customer->id)) {
                $current_customer_id = $this->context->customer->id;
            } else {
                $current_customer_id = StatusautomationBlacklist::getOneCustomerIdByWhatsappNumber(Tools::getValue('whatsapp', false));
            }

            $whatsapp_number_row = StatusautomationWhatsappVerify::getWhatsappRow($current_customer_id);

            $id_whatsapp = $whatsapp_number_row ? $whatsapp_number_row['id_whatsapp'] : 0;
            $whatsapp_number = $whatsapp_number_row ? $whatsapp_number_row['whatsapp_number'] : '';
            $URL = $this->context->link->getModuleLink('statusautomation', 'login', ['validate_verify' => '1']);
            $resend_verification_code = $this->context->link->getModuleLink('statusautomation', 'login', ['submitResendVerificationCode' => '1']);

            $buttons = [];
            for ($i = 0; $i < 3; ++$i) {
                $buttons[] = [
                    'status' => (bool) Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i, Context::getContext()->language->id),
                    'text' => Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_TEXT_' . $i, Context::getContext()->language->id),
                    'url' => $this->getRedirectURL(Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_CUSTOM_URL_TYPE_' . $i), Configuration::get('STATUSAUTOMATION_PHASE_1_BUTTON_URL_' . $i)),
                ];
            }

            Media::addJsDef([
                'whatsapp_buttons' => $buttons,
            ]);

            // dump($buttons);die;

            $this->context->smarty->assign([
                'whatsapp_verify_url' => $URL,
                'whatsapp_resend_verification_code_url' => $resend_verification_code,
                'whatsapp_number' => $whatsapp_number,
                'submitName' => 'submitVerify',
                'prefix_whatsapp_number' => self::PREFIX_WHATSAPP_NUMBER,
                // 'whatsapp_buttons' => $buttons,
                // 'login_form' => $login_form->getProxy(),
            ]);

            $this->setTemplate('module:statusautomation/views/templates/front/customer/whatsapp_validate.tpl');
        } else {
            $login_form = $this->makeLoginForm()->fillWith(
                Tools::getAllValues()
            );

            if (Tools::isSubmit('submitLoginOTP')) {
                if ($login_form->submitForm()) {
                    $this->context->cookie->__set('statusautomation_validate_login_whatsapp', Tools::getValue('whatsapp', false));
                    // $should_redirect = true;
                    $current_customer_id = StatusautomationBlacklist::getOneCustomerIdByWhatsappNumber(Tools::getValue('whatsapp', false));

                    $whatsapp_number_row = StatusautomationWhatsappVerify::getWhatsappRow($current_customer_id);

                    $id_whatsapp = $whatsapp_number_row ? $whatsapp_number_row['id_whatsapp'] : 0;
                    $whatsapp_number = $whatsapp_number_row ? $whatsapp_number_row['whatsapp_number'] : '';

                    $this->sendOTPReq($id_whatsapp, $whatsapp_number);

                    $new_url = $this->context->link->getModuleLink('statusautomation', 'login', ['login_whatsapp_validate' => 1]);

                    return $this->redirectWithNotifications($new_url);
                }
            }

            $this->context->smarty->assign([
                'login_form' => $login_form->getProxy(),
            ]);

            $this->setTemplate('module:statusautomation/views/templates/front/customer/authentication.tpl');
        }

        parent::initContent();

        if ($should_register_redirect) {
            // $back = urldecode(Tools::getValue('back'));

            // send otp before redirect
            $whatsapp_number_row = StatusautomationWhatsappVerify::getWhatsappRow($this->context->customer->id);
            $id_whatsapp = $whatsapp_number_row ? $whatsapp_number_row['id_whatsapp'] : 0;
            $whatsapp_number = $whatsapp_number_row ? $whatsapp_number_row['whatsapp_number'] : '';

            $new_url = str_replace('create_account=1', 'phone_number_validate=1', $this->urls['current_url']);

            $this->sendOTPReq($id_whatsapp, $whatsapp_number);

            return $this->redirectWithNotifications($new_url);
        }
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        if (Tools::isSubmit('submitCreate') || Tools::isSubmit('create_account')) {
            $breadcrumb['links'][] = [
                'title' => $this->trans('Create an account', [], 'Modules.Statusautomation.Login.php'),
                'url' => $this->context->link->getModuleLink('statusautomation', 'login', []),
            ];
        } else {
            $breadcrumb['links'][] = [
                'title' => $this->trans('Log in to your account', [], 'Modules.Statusautomation.Login.php'),
                'url' => $this->context->link->getModuleLink('statusautomation', 'login', []),
            ];
        }

        return $breadcrumb;
    }

    private function sendOTPReq($id_whatsapp, $whatsapp_number)
    {
        // save and send otp
        $verifyObj = new StatusautomationWhatsappVerify();
        $verifyObj->id_whatsapp = (int) $id_whatsapp;
        $verifyObj->phone_number = $whatsapp_number;
        $verifyObj->code = StatusautomationWhatsappVerify::generateOtpCode(6);
        // delete all before save new
        $verifyObj->deleteAllOTP($verifyObj->phone_number);
        $verifyObj->save();

        $status = StatusautomationOTPApi::send($whatsapp_number, $verifyObj->code);

        return [
            'status' => $status,
            'otp' => $verifyObj->code,
        ];
    }

    public function getTemplateVarPage()
    {
        $params = parent::getTemplateVarPage();

        $params['body_classes']['page-customer-account'] = true;
        $params['body_classes']['page-authentication'] = true;

        return $params;
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
                if ($id_product = $this->module->getLastVisitedProductId()) {
                    $url = $link->getProductLink($id_product);
                }
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

    public function doOnlyLogin($email)
    {
        Hook::exec('actionAuthenticationBefore');

        $customer = new StatusautomationCustomerExtend();
        $authentication = $customer->getByEmailLogin($email, false);

        if (isset($authentication->active) && !$authentication->active) {
            $this->errors[] = $this->translator->trans('Your account isn\'t available at this time, please contact us', [], 'Shop.Notifications.Error');
        } elseif (!$authentication || !$authentication->id && $authentication->is_guest == 1) {
            $this->errors[] = $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error');
        } else {
            $this->context->updateCustomer($authentication);
            Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

            // Login information have changed, so we check if the cart rules still apply
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
        }

        return true;
    }

    public function doLoginFromRegister($email)
    {
        Hook::exec('actionAuthenticationBefore');

        $customer = new StatusautomationCustomerExtend();
        $authentication = $customer->getByEmailLogin($email, false);

        if (isset($authentication->active) && !$authentication->active) {
            $this->errors[] = $this->translator->trans('Your account isn\'t available at this time, please contact us', [], 'Shop.Notifications.Error');
        } elseif ($authentication->is_guest == 1) {
            // set customer group to customer
            StatusautomationCustomerExtend::updateCustomerGroup($this->context->customer->id, Configuration::get('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_REGISTER_VERIFY'));
            $authentication = new Customer($authentication->id);
            // updated it to customer group
            $authentication->is_guest = 0;
            $authentication->update();
        }

        if (!$authentication || !$authentication->id) {
            $this->errors[] = $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error');
        } else {
            $this->context->updateCustomer($authentication);
            Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);
            // dump($authentication);die;

            // Login information have changed, so we check if the cart rules still apply
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
            // $this->context->cookie->write();
        }

        return true;
    }

    protected function makeLoginForm()
    {
        $form = new StatusautomationCustomerLoginForm(
            $this->context->smarty,
            $this->context,
            $this->getTranslator(),
            new StatusautomationCustomerLoginFormatter($this->getTranslator()),
            $this->getTemplateVarUrls()
        );

        $form->setAction($this->getCurrentURL());

        return $form;
    }

    // private function getErrorPageRedirect()
    // {
    //     if (!Tools::getIsset('secret') && Tools::getIsset('user') && Configuration::get('LOGINWITHCUSTOMERIDOREMAIL_QUICK_LOGIN_ERROR_REDIRECT_TO_PAGE')) {
    //         exit($this->redirectWithNotifications(Configuration::get('LOGINWITHCUSTOMERIDOREMAIL_QUICK_LOGIN_ERROR_REDIRECT_TO_PAGE')));
    //     } else {
    //         exit($this->redirectWithNotifications('index'));
    //     }
    // }
}

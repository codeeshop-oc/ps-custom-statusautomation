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
class StatusautomationOTPApi
{
    private const VERBOSE = true;
    public const API_VERSION = 'v22.0';
    private const OTP_TEMPLATE = 'ps_statusautomation_otp';
    private const OTP_TEMPLATE_LANGUAGE = 'en_US';

    public static function log($message)
    {
        $log = new StatusautomationLog();
        $log->write($message);
    }

    public static function send($phone)
    {
        $template_parameters = [];
        $template_parameters[] = $phone['message'];

        // remove
        // return ['status' => true, 'message' => 'sent'];

        // remove
        // $phone['prefix_whatsapp_number'] = '+91';
        // $phone['phone_number'] = '7838659995';

        $response = self::sendWhatsAppTemplateMessage(self::formatMobilePhoneForWhatsapp($phone['phone_number'], $phone['prefix_whatsapp_number']), self::OTP_TEMPLATE, self::OTP_TEMPLATE_LANGUAGE, $template_parameters);

        return $response;

        // self::sendAndSaveMessage(
        //     self::formatMobilePhoneForWhatsapp($phone['phone_number'], $phone['prefix_whatsapp_number']),
        //     $phone['message'],
        //     false,
        // );

        // return [
        //     'status' => true,
        //     'message' => 'Success',
        // ];
    }

    private function sendWhatsAppTemplateMessage($phone_number, $template_name, $language_code, $template_parameters = [])
    {
        $module = Module::getInstanceByName('statusautomation');
        try {
            // WhatsApp API v22.0 credentials
            $access_token = Configuration::get('WHATSAPPAPI_ACCESS_TOKEN');
            $phone_number_id = Configuration::get('WHATSAPPAPI_PHONE_NUMBER_ID');

            $url = sprintf('https://graph.facebook.com/%s/%s/messages', self::API_VERSION, $phone_number_id);

            self::log([
                'url' => $url,
            ]);

            // Build the message payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phone_number,
                'type' => 'template',
                'template' => [
                    'name' => $template_name,
                    'language' => ['code' => $language_code],
                    'components' => [],
                ],
            ];

            if (!empty($template_parameters)) {
                $payload['template']['components'][] = [
                    'type' => 'body',
                    'parameters' => [],
                ];

                foreach ($template_parameters as $param) {
                    $payload['template']['components'][0]['parameters'][] = [
                        'type' => 'text',
                        'text' => $param,
                    ];
                }
            }

            $payload['template']['components'][] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => 0,
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => 'order/12345',
                    ],
                ],
            ];

            $headers = [
                "Authorization: Bearer {$access_token}",
                'Content-Type: application/json',
            ];

            // echo '<pre>';
            self::log([
                'payload' => $payload,
            ]);
            // Initialize cURL
            $ch = curl_init($url);

            if (self::VERBOSE) {
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                $verbose = fopen('php://temp', 'w+');
                curl_setopt($ch, CURLOPT_STDERR, $verbose);
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            $error = curl_error($ch);

            if (self::VERBOSE) {
                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);
                self::log([
                    'method' => 'Curl verbose information',
                    'logs' => htmlspecialchars($verboseLog),
                ]);
            }

            curl_close($ch);

            self::log([
                '$result' => $result,
                '$error' => $error,
            ]);

            $json_decoded_data = json_decode($result, true);

            if ($error) {
                return ['status' => false, 'message' => implode(',', $error)];
            }

            if (!empty($json_decoded_data['error'])) {
                if (isset($json_decoded_data['error']['message'])) {
                    return ['status' => false, 'message' => $json_decoded_data['error']['message']];
                } else {
                    return ['status' => false, 'message' => implode(',', $json_decoded_data['error'])];
                }
            }

            return ['status' => true, 'message' => $module->l('Successfully sent.'), 'data' => json_decode($result, true)];
        } catch (Exception $err) {
            return ['status' => false, 'message' => $module->l('Something went wrong.')];
        }
    }

    public static function formatMobilePhoneForWhatsapp($phone, $prefix_whatsapp_number = '-')
    {
        $phone = preg_replace('/[^0-9]+/', '', $phone);

        return $prefix_whatsapp_number . $phone;
        // if (!$id_country) {
        //     return $phone;
        // } else {
        //     $country = new Country((int)$id_country);
        //     $phone_number_utils = PhoneNumberUtil::getInstance();
        //     $phone_number = $phone_number_utils->parse($phone, $country->iso_code);
        //     return $phone_number->getCountryCode().$phone_number->getNationalNumber();
        // }
    }

    // public static function sendAndSaveMessage(
    //     $number,
    //     $message_txt,
    //     $id_customer = false,
    //     $from_number = false,
    //     $order = false,
    //     $from_conversation = false,
    //     $phone_id = false,
    //     $business_id = false
    // ) {
    //     $success = true;
    //     $context = Context::getContext();
    //     $from = Configuration::get('WHATSAPPAPI_PHONE_NUMBER');
    //     $phone_number_id = Configuration::get('WHATSAPPAPI_PHONE_NUMBER_ID');
    //     $business_account_id = Configuration::get('WHATSAPPAPI_BUSINESS_ACCOUNT_ID');
    //     $access_token = Configuration::get('WHATSAPPAPI_ACCESS_TOKEN');
    //     $template = self::OTP_TEMPLATE;

    //     $language_code = self::getMessageTemplates($business_account_id, $access_token, false, $template, $context->language->iso_code);

    //     dump($language_code);
    //     $message_txt = self::sanitizeMessageTxt($message_txt);
    //     $message_txt = Tools::str_replace_once('{shop_name}', Configuration::get('PS_SHOP_NAME'), $message_txt);

    //     $id_country = false;
    //     if ($id_customer) {
    //         $address = new Address((int) Address::getFirstCustomerAddressId((int) $id_customer));
    //         $id_country = $address->id_country;
    //     }
    //     if (isset($conf) && is_object($conf) && $conf->event_type === 'admin_on_new_order') {
    //         $id_country = false;
    //     }
    //     $message_txt = str_replace(["\r", "\n"], ' ', preg_replace('/\s+/', ' ', $message_txt));
    //     $post_fields = [
    //         'messaging_product' => 'whatsapp',
    //         'recipient_type' => 'individual',
    //         'to' => $id_country ? self::formatMobilePhoneForWhatsapp($number, $id_country) : $number,
    //         'type' => $template == '' ? 'text' : 'template',
    //     ];
    //     if ($template != '') {
    //         $post_fields['template'] = [
    //             'name' => $template,
    //             'language' => [
    //                 'code' => $language_code,
    //             ],
    //             'components' => [
    //                 [
    //                     'type' => 'body',
    //                     'parameters' => [
    //                         [
    //                             'type' => 'text',
    //                             'text' => $message_txt,
    //                         ],
    //                     ],
    //                 ],
    //             ],
    //         ];
    //     } else {
    //         $post_fields['text'] = [
    //             'preview_url' => 'true',
    //             'body' => $message_txt,
    //         ];
    //     }

    //     dump($post_fields);die;

    //     // $curl = curl_init();
    //     // curl_setopt_array($curl, [
    //     //     CURLOPT_URL => 'https://graph.facebook.com/' . self::$api_version . '/' . $phone_number_id . '/messages',
    //     //     CURLOPT_RETURNTRANSFER => true,
    //     //     CURLOPT_ENCODING => '',
    //     //     CURLOPT_MAXREDIRS => 10,
    //     //     CURLOPT_TIMEOUT => 0,
    //     //     CURLOPT_FOLLOWLOCATION => true,
    //     //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //     //     CURLOPT_CUSTOMREQUEST => 'POST',
    //     //     CURLOPT_POSTFIELDS => json_encode($post_fields),
    //     //     CURLOPT_HTTPHEADER => [
    //     //         'Authorization: Bearer ' . $access_token,
    //     //         'Content-Type: application/json',
    //     //     ],
    //     // ]);
    //     // $response = json_decode(curl_exec($curl));

    //     // $module = Module::getInstanceByName('statusautomation');
    //     // if (curl_errno($curl)) {
    //     //     $success = false;
    //     //     $msg = 'Statusautomation::StatusautomationOTPApi::send::Curl call error - ' . curl_error($curl) . ' - WhatsApp recipient: ' . $number;
    //     //     PrestaShopLogger::addLog($msg, 4, null, null, null, false);

    //     //     return [
    //     //         'success' => $success,
    //     //         'msg' => sprintf($module->l('There was an error sending the message to %s. Please, try again later.'), $number),
    //     //     ];
    //     // }

    //     // $wa_message = new WhatsappapiMessages();
    //     // if ($id_conf) {
    //     //     $wa_message->id_whatsappapi_conf = $id_conf;
    //     // }
    //     // $wa_message->business_account_id = $business_account_id;
    //     // $wa_message->phone_number_id = $phone_number_id;
    //     // $wa_message->id_customer = (int) $id_customer;
    //     // $wa_message->id_order = $order ? $order->id : 0;
    //     // $wa_message->id_cart = $order ? $order->id_cart : 0;
    //     // $wa_message->from_number = $from;
    //     // $wa_message->rcpt_number = $number;
    //     // $wa_message->type = $template == '' ? 'text' : 'template';
    //     // $wa_message->template = $template;
    //     // $wa_message->language_code = $language_code;
    //     // $wa_message->message = $message_txt;

    //     // $wa_message->read = 0;
    //     // if (isset($context->employee)) {
    //     //     $wa_message->employee = $context->employee->firstname . ' ' . $context->employee->lastname . ' (' . $context->employee->email . ')';
    //     // }
    //     // $wa_message->date_sent = date('Y-m-d H:i:s');
    //     // $wa_message->id_shop = $context->shop->id;
    //     // if (isset($response->error)) {
    //     //     $success = false;
    //     //     if (isset($response->error->error_user_title)) {
    //     //         $msg = $response->error->error_user_title . ' - ' . $response->error->error_user_msg . ' ' . $from;
    //     //     } else {
    //     //         $msg = $response->error->message;
    //     //         if (isset($response->error->error_data->details)) {
    //     //             $msg .= ' - ' . $response->error->error_data->details;
    //     //         }
    //     //         $msg .= ' ' . $number;
    //     //     }
    //     //     PrestaShopLogger::addLog('WhatsAppApi::WhatsappapiMessages::sendAndSaveMessage::' . $msg, 3, $response->error->code, null, null, true);
    //     //     $wa_message->status = 'failed';
    //     //     $wa_message->success = 0;
    //     //     $wa_message->error = $msg;
    //     // } else {
    //     //     $wa_message->status = 'sent';
    //     //     $wa_message->success = 1;
    //     //     $wa_message->wa_id = isset($response->contacts[0]->wa_id) ? $response->contacts[0]->wa_id : '';
    //     //     $wa_message->message_id = isset($response->messages[0]->id) ? $response->messages[0]->id : '';
    //     //     $msg = sprintf($module->l('Message successfully sent to %s.'), $number);
    //     // }
    //     // $wa_message->save();

    //     return [
    //         'success' => $success,
    //         'msg' => $msg,
    //         // 'message_id' => $wa_message->message_id,
    //     ];
    // }

    // public static function sanitizeMessageTxt($message_txt)
    // {
    //     return Tools::str_replace_once('\r\n', ' ', preg_replace('/\s+/S', ' ', $message_txt));
    // }

    // public static function getMessageTemplates($business_account_id, $access_token, $get_approved_lang = false, $get_specified_template = false, $lang = false)
    // {
    //     $curl = curl_init();
    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => 'https://graph.facebook.com/'.self::$api_version.'/'.$business_account_id.'/message_templates?fields=name,category,content,language,name_or_content,status',
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 0,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'GET',
    //         CURLOPT_HTTPHEADER => array(
    //             'Authorization: Bearer '.$access_token
    //         ),
    //     ));
    //     $response = json_decode(curl_exec($curl), true);
    //     curl_close($curl);
    //     $templates = array();
    //     if (isset($response['data'])) {
    //         if ($get_approved_lang === true) {
    //             foreach ($response['data'] as $template) {
    //                 if ($template['name'] === 'ps_whatsappapi_module_template' && $template['status'] === 'APPROVED') {
    //                     return $template['language'];
    //                 }
    //             }
    //             return 'en';
    //         }
    //         foreach ($response['data'] as $template) {
    //             if (Tools::strpos($template['name'], 'sample_') !== false || $template['name'] === 'hello_world') {
    //                 continue;
    //             }
    //             if ($get_specified_template !== false && $lang !== false) {
    //                 if ($template['name'] === $get_specified_template && $template['status'] === 'APPROVED' && Tools::strpos($lang, $template['language']) >= 0) {
    //                     return $template['language'];
    //                 }
    //             }
    //             $templates[$template['name']] = array (
    //                 'id_template' => $template['name'],
    //                 'name'        => $template['name'].' - '.$template['language'].' ('.$template['status'].')'
    //             );
    //         }
    //         return $templates;
    //     } else {
    //         return array();
    //     }
    // }
}

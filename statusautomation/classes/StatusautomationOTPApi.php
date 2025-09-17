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
        $phone['prefix_whatsapp_number'] = '+91';
        $phone['phone_number'] = '7838659995';

        $response = self::sendWhatsAppTemplateMessage(self::formatMobilePhoneForWhatsapp($phone['phone_number'], $phone['prefix_whatsapp_number']), self::OTP_TEMPLATE, self::OTP_TEMPLATE_LANGUAGE, $template_parameters);

        return $response;         
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

                // foreach ($template_parameters as $param) {
                // }
                $payload['template']['components'][0]['parameters'][] = [
                    'type' => 'text',
                    'text' => $template_parameters[0],
                ];
                
                $payload['template']['components'][] = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => 0,
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $template_parameters[0],
                        ],
                    ],
                ];
            }


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
}

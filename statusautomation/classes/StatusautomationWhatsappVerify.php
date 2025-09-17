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
class StatusautomationWhatsappVerify extends ObjectModel
{
    public static $debug = false;

    public $id_whatsapp;
    public $phone_number;
    public $code;
    public $date_add;
    public $date_expire;

    public static $definition = [
        'table' => 'ts_whatsapp_verify',
        'primary' => 'id_ts_whatsapp_verify',
        'fields' => [
            'id_whatsapp' => ['type' => self::TYPE_INT],
            'phone_number' => ['type' => self::TYPE_STRING],
            'code' => ['type' => self::TYPE_STRING],
            'date_add' => ['type' => self::TYPE_DATE],
            'date_expire' => ['type' => self::TYPE_DATE],
        ],
    ];

    public static function getWhatsappRow($id_customer = null)
    {
        $query = new DbQuery();
        $query->select('s.id_whatsapp, s.whatsapp_number');
        $query->from('ts_whatsapp', 's');
        $query->where('s.`id_customer` = ' . (int) $id_customer);

        $whatsapp_number = Db::getInstance()->getRow($query);

        return $whatsapp_number ?? false;
    }

    public static function generateOtpCode($length = 6)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $otp = '';

        for ($i = 0; $i < $length; ++$i) {
            $otp .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $otp;
    }

    public static function checkOTP($id_whatsapp, $phone_number, $otp)
    {
        $query = new DbQuery();
        $query->select('code');
        $query->from(self::$definition['table'], 's');
        $query->where('s.`phone_number` = "' . (string) $phone_number . '" OR s.`phone_number` = "0' . (string) $phone_number . '"');
        $query->where('s.`id_whatsapp` = "' . $id_whatsapp . '"');        
        $query->orderBy('s.`id_ts_whatsapp_verify` DESC');

        if (self::$debug) {
            print_r($query->__toString());
            echo ';<br/>';
        }

        $query_otp_code = Db::getInstance()->getValue($query);
        $otp = trim($otp);

        if (($otp && $otp == $query_otp_code) ?? false) {
            self::deleteOne($id_whatsapp);
            return true;
        } else {
            return false;
        }
    }

    public static function deleteOne($id_whatsapp)
    {
        return Db::getInstance()->delete(self::$definition['table'], '`id_whatsapp` = "' . (string) $id_whatsapp . '"');
    }

    public function deleteAllOTP($phone_number)
    {
        return Db::getInstance()->delete(self::$definition['table'], '`phone_number` = "' . (string) $phone_number . '" OR `phone_number` = "0' . (string) $phone_number . '"');
    }
}

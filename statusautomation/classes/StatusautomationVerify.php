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
class StatusautomationVerify extends ObjectModel
{
    public $id_customer
    public $whatsapp_number;
    public $is_verified;

    public static $definition = [
        'table' => 'ts_whatsapp',
        'primary' => 'id_ts_whatsapp',
        'multilang' => true,
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT],
            'whatsapp_number' => ['type' => self::TYPE_STRING],
            'is_verified' => ['type' => self::TYPE_INT],
            'is_verified_date' => ['type' => self::TYPE_DATE],
        ],
    ];

    // public static function saveBlacklist($phone_number)
    // {
    //     Db::getInstance()->execute('
	// 	    INSERT INTO `' . _DB_PREFIX_ . self::$definition['table'] . '`
	// 	    (`phone_number`, `is_blacklisted`)
	// 	    VALUES ("' . pSQL($phone_number) . '", "YES")
	// 	    ON DUPLICATE KEY UPDATE
	// 	    is_blacklisted = "YES"
	// 	');
    // }

    public static function isVerified($delivery_phone_number, $is_verified = '1')
    {
        $query = new DbQuery();
        $query->select('count(s.`whatsapp_number)');
        $query->from(self::$definition['table'], 's');
        $query->where('s.`whatsapp_number` = "' . (string) $delivery_phone_number . '" OR s.`whatsapp_number` = "0' . (string) $delivery_phone_number . '"');
        $query->where('s.`is_verified` = "' . $is_verified . '"');

        $whatsapp_number = Db::getInstance()->getValue($query);

        return $whatsapp_number ?? false;
    }

    public static function getOrderCount($id_customer)
    {
        $query = new DbQuery();
        $query->select('count(s.`id_order`)');
        $query->from('orders', 's');
        $query->where('s.`id_customer` = "' . $id_customer . '"');

        $whatsapp_number = Db::getInstance()->getValue($query);

        return $whatsapp_number ?? false;
    }
}

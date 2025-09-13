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
class StatusautomationBlacklist extends ObjectModel
{
    public $id_statusautomation_blacklist;
    public $phone_number;
    public $is_blacklisted;
    public static $debug = false;

    public static $definition = [
        'table' => 'statusautomation_blacklist',
        'primary' => 'id_statusautomation_blacklist',
        'multilang' => true,
        'fields' => [
            'phone_number' => ['type' => self::TYPE_STRING],
            'is_blacklisted' => ['type' => self::TYPE_STRING],
            'date_add' => ['type' => self::TYPE_DATE],
            'date_upd' => ['type' => self::TYPE_DATE],
            /* LANG FIELD */
            'name' => [
                'type' => self::TYPE_STRING,
                'lang' => true, 'validate' => 'isGenericName',
                'size' => 190,
            ],
            'description' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
            ],
        ],
    ];

    public static function saveBlacklist($phone_number)
    {
        Db::getInstance()->execute('
		    INSERT INTO `' . _DB_PREFIX_ . self::$definition['table'] . '`
		    (`phone_number`, `is_blacklisted`)
		    VALUES ("' . pSQL($phone_number) . '", "YES")
		    ON DUPLICATE KEY UPDATE
		    is_blacklisted = "YES"
		');
    }

    /**
     * Find duplicate customers by WhatsApp number
     */
    public static function getCustomerIdsByWhatsappNumber($whatsapp_number)
    {
        $query = new DbQuery();
        $query->select('pw.id_customer');
        $query->from('ts_whatsapp', 'pw');
        $query->where('(pw.whatsapp_number = "' . $whatsapp_number . '" OR pw.whatsapp_number = "0' . $whatsapp_number . '" )');
        $query->groupBy('pw.id_customer');
        if (self::$debug) {
            print_r($query->__toString());
            echo ';<br/>';
        }

        return array_column(Db::getInstance()->executeS($query), 'id_customer') ?? [];
    }

    /**
     * Find duplicate customers by WhatsApp number
     */
    public static function getOneCustomerIdByWhatsappNumber($whatsapp_number)
    {
        $query = new DbQuery();
        $query->select('pw.id_customer');
        $query->from('ts_whatsapp', 'pw');
        $query->where('(pw.whatsapp_number = "' . $whatsapp_number . '" OR pw.whatsapp_number = "0' . $whatsapp_number . '" )');
        $query->groupBy('pw.id_customer');
        if (self::$debug) {
            print_r($query->__toString());
            echo ';<br/>';
        }

        $rows = array_column(Db::getInstance()->executeS($query), 'id_customer');
        $best_id_customer = false;
        foreach ($rows as $id_customer) {
            $customer = new Customer($id_customer);
            if ($customer->id_default_group == Configuration::get('PSVIPFLOW_CUSTOMER_GROUP_ID')) {
                $best_id_customer = $id_customer;
                break;
            }

            if (StatusautomationVerify::getOrderCount($id_customer)) {
                $best_id_customer = $id_customer;
                break;
            }

            if ($customer->id_default_group != Configuration::get('PS_GUEST_GROUP')) {
                $best_id_customer = $id_customer;
            }
        }

        if ($best_id_customer == false && !empty($rows)) {
            $best_id_customer = $rows[0];
        }

        return (int) $best_id_customer;
    }

    public static function changeCustomerGroup($phone_number)
    {
        // get customers with phone_number
        $customers = self::getCustomerIdsByWhatsappNumber($phone_number);
        if ($customers) {
            foreach ($customers as $id_customer) {
                StatusautomationCustomerExtend::updateCustomerGroup($id_customer, Configuration::get('STATUSAUTOMATION_PHASE_1_CUSTOMER_GROUP_ID_BLACKLIST'));
            }
        }
    }

    public static function isBlacklisted($delivery_phone_number, $is_blacklisted = 'YES')
    {
        $query = new DbQuery();
        $query->select('count(s.`phone_number`)');
        $query->from(self::$definition['table'], 's');
        $query->where('s.`phone_number` = "' . (string) $delivery_phone_number . '" OR s.`phone_number` = "0' . (string) $delivery_phone_number . '"');
        $query->where('s.`is_blacklisted` = "' . $is_blacklisted . '"');

        $whatsapp_number = Db::getInstance()->getValue($query);

        return $whatsapp_number ?? false;
    }
}

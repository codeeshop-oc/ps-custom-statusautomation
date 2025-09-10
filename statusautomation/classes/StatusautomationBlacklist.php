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
}

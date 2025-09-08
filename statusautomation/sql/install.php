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
$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'statusautomation_blacklist` (
    `id_statusautomation_blacklist` int(11) NOT NULL AUTO_INCREMENT,
    `phone_number` VARCHAR(15),
    `is_blacklisted` ENUM("YES", "NO") DEFAULT "NO",
    PRIMARY KEY  (`id_statusautomation_blacklist`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ts_whatsapp_verify` (
    `id_ts_whatsapp_verify` int(11) NOT NULL AUTO_INCREMENT,
    `id_whatsapp` int(11) NOT NULL,
    `phone_number` VARCHAR(15),
    `code` VARCHAR(6),
    `date_add` datetime DEFAULT CURRENT_TIMESTAMP,
    `date_expire` datetime DEFAULT NULL,
    PRIMARY KEY (`id_ts_whatsapp_verify`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = '
CREATE TRIGGER `' . _DB_PREFIX_ . 'ts_whatsapp_verify_before_insert`
BEFORE INSERT ON `' . _DB_PREFIX_ . 'ts_whatsapp_verify`
FOR EACH ROW
BEGIN
    IF NEW.date_expire IS NULL THEN
        SET NEW.date_expire = DATE_ADD(NOW(), INTERVAL 10 MINUTE);
    END IF;
END;';

$sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'ts_whatsapp` ADD COLUMN is_verified TINYINT(1) DEFAULT 0;';
$sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'ts_whatsapp` ADD COLUMN is_verified_date datetime DEFAULT "0000:00:00 00:00:00"';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

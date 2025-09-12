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

// `id_shop` int(11),

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'statusautomation_blacklist` (
    `id_statusautomation_blacklist` int(11) NOT NULL AUTO_INCREMENT,
    `phone_number` VARCHAR(15),
    `is_blacklisted` ENUM("YES", "NO") DEFAULT "YES",
    `date_add` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (`id_statusautomation_blacklist`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// `id_shop` int(11),
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'statusautomation_error` (
    `id_statusautomation_error` int(11) NOT NULL AUTO_INCREMENT,
    `message` VARCHAR(200),
    `date_add` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (`id_statusautomation_error`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// `id_shop` int(11),
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ts_whatsapp_verify` (
    `id_ts_whatsapp_verify` int(11) NOT NULL AUTO_INCREMENT,
    `id_whatsapp` int(11) NOT NULL,
    `phone_number` VARCHAR(15),
    `code` VARCHAR(6),
    `date_add` datetime DEFAULT CURRENT_TIMESTAMP,
    `date_expire` datetime DEFAULT NULL,
    PRIMARY KEY (`id_ts_whatsapp_verify`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// 2. Drop trigger if it already exists
$sql[] = 'DROP TRIGGER IF EXISTS `' . _DB_PREFIX_ . 'ts_whatsapp_verify_before_insert`;';

$sql[] = '
CREATE TRIGGER `' . _DB_PREFIX_ . 'ts_whatsapp_verify_before_insert`
BEFORE INSERT ON `' . _DB_PREFIX_ . 'ts_whatsapp_verify`
FOR EACH ROW
BEGIN
    IF NEW.date_expire = '0000-00-00 00:00:00' THEN
        SET NEW.date_expire = DATE_ADD(NOW(), INTERVAL 10 MINUTE);
    END IF;
END;';

$columnExists = (bool) Db::getInstance()->getValue('
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = "' . _DB_NAME_ . '"
      AND TABLE_NAME = "' . _DB_PREFIX_ . 'ts_whatsapp"
      AND COLUMN_NAME = "is_verified"
');

if (!$columnExists) {
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'ts_whatsapp` ADD COLUMN is_verified TINYINT(1) DEFAULT 0;';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'ts_whatsapp` ADD COLUMN is_verified_date datetime DEFAULT "0000:00:00 00:00:00"';
}

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

// 2. Add UNIQUE constraint only if not exists
$uniqueExists = (bool) Db::getInstance()->getValue('
    SELECT COUNT(1)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
    ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
    WHERE tc.TABLE_SCHEMA = "' . _DB_NAME_ . '"
      AND tc.TABLE_NAME = "' . _DB_PREFIX_ . 'statusautomation_blacklist"
      AND tc.CONSTRAINT_TYPE = "UNIQUE"
      AND kcu.COLUMN_NAME = "phone_number"
');

if (!$uniqueExists) {
    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'statusautomation_blacklist`
        ADD UNIQUE KEY `unique_phone_number` (`phone_number`)');
}

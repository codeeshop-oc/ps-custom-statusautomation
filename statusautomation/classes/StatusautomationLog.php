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
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Log class
 */
class StatusautomationLog
{
    private $handle;
    private $debugEnabled;

    /**
     * Constructor
     *
     * @param string $filename
     */
    public function __construct($filename = 'info.log')
    {
        $this->debugEnabled = Configuration::get('STATUSAUTOMATION_DEBUG_STATUS', null, null, null, true);
        if ($this->debugEnabled) {
            $this->handle = fopen(_PS_MODULE_DIR_ . 'statusautomation/logs/' . date('Y-m-d') . '_' . $filename, 'a');
        }
    }

    /**
     * @param string $message
     */
    public function write($message)
    {
        if ($this->handle) {
            fwrite($this->handle, date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n");
        }
    }

    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}

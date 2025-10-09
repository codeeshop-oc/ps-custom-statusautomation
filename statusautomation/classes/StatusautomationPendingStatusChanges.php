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
class StatusautomationPendingStatusChanges
{
    public const TABLE = 'statusautomation_pending_status_changes';
    public const DEBUG = false;

    public static function save($id_order, $order_status)
    {
        Db::getInstance()->insert(self::TABLE, [
            'id_order' => (int) $id_order,
            'target_id_order_state' => $order_status,
            'date_add' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Find duplicate customers by WhatsApp number
     */
    public static function getAll()
    {
        $query = new DbQuery();
        $query->select('id_order, target_id_order_state');
        $query->from(self::TABLE, 'pw');
        // $phone = Ts_WhatsApp::normalizeWhatsappNumber($phone);
        // $query->where('(pw.whatsapp_number = "' . $whatsapp_number . '" OR pw.whatsapp_number = "0' . $whatsapp_number . '" )');
        // $query->groupBy('pw.id_customer');

        if (self::DEBUG) {
            print_r($query->__toString());
            echo ';<br/>';
        }

        return Db::getInstance()->executeS($query) ?? false;
    }

    /**
     * Find duplicate customers by WhatsApp number
     */
    public static function delete($id_order, $target_id_order_state)
    {
        Db::getInstance()->delete(self::TABLE, sprintf('id_order = %d AND target_id_order_state = %d', $id_order, $target_id_order_state));
    }
}

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

class StatusautomationCustomerExtend extends CustomerCore
{
    /**
     * Return customer instance from its e-mail (optionally check password).
     *
     * @param string $email e-mail
     * @param string $plaintextPassword Password is also checked if specified
     * @param bool $ignoreGuest
     *
     * @return bool|Customer|CustomerCore Customer instance
     */
    public function getByEmailLogin($email, $ignoreGuest = true)
    {
        $status = false;
        $error = '';
        if (!Validate::isEmail($email)) {
            $error = Tools::displayError();
        }

        $shopGroup = Shop::getGroupFromShop(Shop::getContextShopID(), false);

        $sql = new DbQuery();
        $sql->select('c.*');
        $sql->from('customer', 'c');
        $sql->where('c.`email` = \'' . pSQL($email) . '\'');
        if (Shop::getContext() == Shop::CONTEXT_SHOP && $shopGroup['share_customer']) {
            $sql->where('c.`id_shop_group` = ' . (int) Shop::getContextShopGroupID());
        } else {
            $sql->where('c.`id_shop` IN (' . implode(', ', Shop::getContextListShopID(Shop::SHARE_CUSTOMER)) . ')');
        }
        if ($ignoreGuest) {
            $sql->where('c.`is_guest` = 0');
        }
        $sql->where('c.`deleted` = 0');

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);

        if (!$result) {
            return false;
        }

        $customer = new Customer();
        $customer->id = (int) $result['id_customer'];
        foreach ($result as $key => $value) {
            if (property_exists($this, $key)) {
                $customer->{$key} = $value;
            }
        }

        $customer->update();

        return $customer;
    }

    public static function getCurrentCustomerGroup($id_customer)
    {
        $sql = new DbQuery();
        $sql->select('c.id_default_group');
        $sql->from('customer', 'c');
        $sql->where('c.`id_customer` = \'' . pSQL($id_customer) . '\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    public static function updateCustomerGroup($id_customer, $customer_group_status)
    {
        if (self::getCurrentCustomerGroup((int) $id_customer) != $customer_group_status) {
            Db::getInstance()->update('customer', [
                'id_default_group' => pSQL($customer_group_status),
            ], 'id_customer = "' . (int) $id_customer . '"');

            Db::getInstance()->delete('customer_group', 'id_customer = ' . (int) $id_customer);

            $row = ['id_customer' => (int) $id_customer, 'id_group' => (int) $customer_group_status];
            Db::getInstance()->insert('customer_group', $row, false, true, Db::INSERT_IGNORE);
        }
    }
}

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

class StatusautomationShippingHelper
{
    /**
     * Get remaining amount for free shipping for a specific carrier
     *
     * @param Cart $cart
     * @param int $id_carrier
     *
     * @return float|bool Remaining amount needed or false if not available
     */
    public static function getRemainingForCarrierFreeShipping(Cart $cart, $id_carrier, $id_country, $cart_total)
    {
        $carrier = new Carrier($id_carrier);
        $customer = new Customer($cart->id_customer);

        // || !$carrier->is_free
        if (!$carrier->active) {
            return false; // Carrier not active or does not offer free shipping
        }

        // Check customer group restriction
        $carrierGroups = array_column($carrier->getGroups(), 'id_group');
        if (!empty($carrierGroups) && !in_array($customer->id_default_group, $carrierGroups)) {
            return false; // Customer not eligible for this carrier
        }

        // $cart_total
        $lower_price_range = self::getFreeShippingThreshold($id_country, $id_carrier, $cart->id_currency, 0);
        // dump($lower_price_range);die;

        if ($lower_price_range && $cart_total < $lower_price_range) {
            return max(0, $lower_price_range - $cart_total);
        }

        // Already qualifies for free shipping
        return 0;
    }

    public static function getFreeShippingThreshold($id_country, $id_carrier = null, $id_currency = null, $price = 0)
    {
        $threshold = false;

        if (!$id_carrier || !$id_country || !$id_currency) {
            return false;
        }

        $cache_id = 'Statusautomation::getFreeShippingThreshold_' . (int) $id_carrier . '_' . (int) $id_country . '_' . (int) $id_currency;

        if (!Cache::isStored($cache_id)) {
            $query = new DbQuery();
            $query->select('prp.delimiter1');
            // $query->select('pd.id_zone, prp.*, pd.price, (select pcl.name from `' . _DB_PREFIX_ . 'country_lang` `pcl` WHERE pcl.id_country = pc.id_country LIMIT 1) country_name');
            $query->from('country', 'pc');
            $query->leftJoin('delivery', 'pd', 'pd.id_zone = pc.id_zone');
            $query->leftJoin('range_price', 'prp', 'prp.id_carrier = pd.id_carrier AND prp.id_range_price = pd.id_range_price');
            $query->where('pc.id_country = ' . (int) $id_country);
            if ($id_carrier) {
                $query->where('prp.id_carrier = ' . (int) $id_carrier);
            }
            $query->where('pd.price = ' . (int) $price);

            // $rows = Db::getInstance()->executeS($query);
            // print_r($query->__toString());
            // dump($rows);
            // exit;

            $threshold = (float) Db::getInstance()->getValue($query);

            $id_default_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');

            if ($id_default_currency != $id_currency) { // If cart currency is not default one, convert threshold amount
                $threshold = Tools::convertPrice($threshold, Currency::getCurrencyInstance((int) $id_currency));
            }

            Cache::store($cache_id, $threshold);
        }

        return $threshold;
    }
}

{*
* 2007-2023 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<p>
  <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#showDocumentation" aria-expanded="false" aria-controls="showDocumentation">
    {l s='Documentation' mod='__MY_MODULE_NAME__'}
  </button>
</p>

<div class="collapse" id="showDocumentation">
    <div class="panel">
        <h3><i class="icon icon-tags"></i> {l s='Documentation' mod='__MY_MODULE_NAME__'}</h3>
        <p>
            &raquo; {l s='You can get a PDF documentation to configure this module' mod='__MY_MODULE_NAME__'} :
            <ul>
                <li><a href="{$module_dir|escape:'javascript':'UTF-8'}docs/readme_en.pdf" target="_blank">{l s='English' mod='__MY_MODULE_NAME__'}</a></li>
            </ul>
        </p>
    </div>
</div>
 
<div class="ces_info">
    <div class="ces_info-item ces_info-item-contact">
        <span>Contact Us</span>        
        <p><a class="ces-link" href="https://addons.prestashop.com/contact-form.php?id_product={$id_seller_product|escape:'javascript':'UTF-8'}" target="_blank">Contact us</a> on any question or problem with the module</p>
    </div>
    <div class="ces_info-item ces_info-item-rate">
        <span>{l s="Rate \"$module_display_name\"" mod='__MY_MODULE_NAME__'}</span>        
        <br/>
        <a href="https://addons.prestashop.com/en/ratings.php?id_product={$id_seller_product|escape:'javascript':'UTF-8'}" target="_blank" id="stars">
            <div
              class="grade-stars"
              data-grade="5"
              data-input="criterion[1]">
            </div>
        </a>
    </div>
    <div class="ces_info-item ces_info-item-docs">
        <span><a class="ces-link" href="https://addons.prestashop.com/en/2_community-developer?contributor=1228629" target="_blank">{l s="What's next?" mod='__MY_MODULE_NAME__'}</a></span>
        <p>{l s='Find out how to improve your shop with' mod='__MY_MODULE_NAME__'} <a href="https://addons.prestashop.com/en/2_community-developer?contributor=1228629" class="suggestions-link" target="_blank">{l s='other modules and themes' mod='__MY_MODULE_NAME__'}</a> {l s='made by us.' mod='__MY_MODULE_NAME__'}<br/></p>
    </div>
</div>
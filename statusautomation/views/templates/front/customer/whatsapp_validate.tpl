{**
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
 *}
{extends file='page.tpl'}

{block name='page_title'}
	{l s='Validate account' d='Modules.Statusautomation.Whatsapp_validate'}
{/block}

{block name='page_content'}
	{block name='login_form_container'}
		<form id="verify_phone-form" action="{block name='login_form_actionurl'}{$whatsapp_verify_url}{/block}" method="post">

			<section>
				<br />
				<div class="form-group row has-error1">
					<label class="col-md-3 form-control-label">
					</label>
					<div class="col-md-6">
						<div class="my-sm-2">{l s='WhatsApp Number' d='Modules.Statusautomation.Whatsapp_validate'}</div>
						<input class="form-control" name="whatsapp" type="text" value="{$whatsapp_number}" required="">

						{*
						<span class="form-control-comment hidden">{l s='Please enter a valid WhatsApp number without country code or 0 at start (9 digits only).' d='Modules.Statusautomation.Whatsapp_validate'}
						</span>

						<div class="whatsapp_help_block help-block hidden">
							<ul>
								<li class="alert alert-danger">{l s='Please enter a valid WhatsApp number without country code or 0 at start (9 digits only).' d='Modules.Statusautomation.Whatsapp_validate'}</li>
							</ul>
						</div>
						*}
						<div class="whatsapp_help_block help-block">
							<ul></ul>
						</div>
					</div> {* col-md-6 *}
				</div> {* form-group *}
				<div class="form-group row">
					<label class="col-md-3 form-control-label">
					</label>
					<div class="col-md-6">
						<div class="my-sm-2">{l s='Enter code you received here' d='Modules.Statusautomation.Whatsapp_validate'}</div>
						<div>
							<input class="form-control" name="phone_verify_code" type="text" value="" required="">
						</div>
						<div>{l s='Not received ?' d='Modules.Statusautomation.Whatsapp_validate'}
					 		<button type="button" id="submit-resend_verification_code" data-url="{$whatsapp_resend_verification_code_url}" class="btn btn-link" href="">{l s='resend code' d='Modules.Statusautomation.Whatsapp_validate'}</button>
					 	</div>
					</div>
				</div>
			</section>

			<footer class="form-footer text-sm-center clearfix">
		        <input type="hidden" name="{$submitName}" value="1">

		        <button id="submit-verify" class="btn btn-primary" data-link-action="sign-in" type="button">
		            Verify
	          	</button>

		    </footer>
		</form>
	{/block}
{/block}
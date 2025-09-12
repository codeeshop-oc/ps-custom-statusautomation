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


$(document).ready(function () {

	// remove required
	$('[name="email"]').removeAttr('required')
	$('[name="email"]').val('')

	const emailOptionFormEl = $('[name="email"]').parents('.form-group')
	emailOptionFormEl.hide()
	emailOptionFormEl.before(`
		<div class="form-group row" id="show_email_option_form">
			<label class="col-md-3 form-control-label"></label>
			<div class="col-md-6">
				<button type="button" class="btn btn-default show_email_option">${STATUSAUTOMATION_ADD_EMAIL_TEXT}</button>
			</div>
		</div>
	`)
	$('#show_email_option_form .show_email_option').text(STATUSAUTOMATION_ADD_EMAIL_TEXT)

	$('.show_email_option').click(function () {
		if (emailOptionFormEl.is(':visible')) {
			emailOptionFormEl.hide(250)
			$('#show_email_option_form .show_email_option').text(STATUSAUTOMATION_ADD_EMAIL_TEXT)
		} else {
			emailOptionFormEl.show(250)
			$('#show_email_option_form .show_email_option').text(STATUSAUTOMATION_HIDE_EMAIL_TEXT)
		}
	})
})
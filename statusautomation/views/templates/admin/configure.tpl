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

<div class="form-group">
	<a href="{$STATUSAUTOMATION_DOWNLOAD_URL}" class="btn btn-primary js-download-file-btn" target="_blank"><i class="process-icon-download"></i> {l s="Download BlackList File" d='Modules.Statusautomation.Configure'}</a>
</div>

<div class="moduleconfig-content panel">
	<h3><i class="icon icon-tags"></i> {l s='URL\'s' d='Modules.Statusautomation.Configure'}</h3>
	<p>
		<input type="hidden" id="data-copy-message"
		data-copy-success-message="{l s='Successfully Copied' d='Modules.Statusautomation.Configure'}"
		data-copy-error-message="{l s='Something went wrong.<br/>Please try again.' d='Modules.Statusautomation.Configure'}" />
		<span>&raquo; {l s='Login URL' d='Modules.Statusautomation.Configure'} :
			<div class="input-group">
				<input id="generated_cron_url_login" readonly type="text" value="{$login_url}" class="form-control" />
				<span class="input-group-addon copy_url c-pointer">
					<i id="generated_cron_url_login_copy" class="icon icon-copy data_copy">
						{l s='Copy URL' d='Modules.Statusautomation.Configure'}</i>
				</span>
			</div>
		</span>
	</p>
	<p>
		<span>&raquo; {l s='Register URL' d='Modules.Statusautomation.Configure'} :
			<div class="input-group">
				<input id="generated_cron_url_register" readonly type="text" value="{$register_url}" class="form-control" />
				<span class="input-group-addon copy_url c-pointer">
					<i id="generated_cron_url_register_copy" class="icon icon-copy data_copy">
						{l s='Copy URL' d='Modules.Statusautomation.Configure'}</i>
				</span>
			</div>
		</span>
	</p>
</div>

{*
	<div class="panel">
		<h3><i class="icon icon-credit-card"></i> {l s='Status Automation' d='Modules.Statusautomation.Configure'}</h3>
		<p>
			<strong>{l s='Here is my new generic module!' d='Modules.Statusautomation.Configure'}</strong><br />
			{l s='Thanks to PrestaShop, now I have a great module.' d='Modules.Statusautomation.Configure'}<br />
			{l s='I can configure it using the following configuration form.' d='Modules.Statusautomation.Configure'}
		</p>
		<br />
		<p>
			{l s='This module will boost your sales!' d='Modules.Statusautomation.Configure'}
		</p>
	</div>

	<div class="panel">
		<h3><i class="icon icon-tags"></i> {l s='Documentation' d='Modules.Statusautomation.Configure'}</h3>
		<p>
			&raquo; {l s='You can get a PDF documentation to configure this module' d='Modules.Statusautomation.Configure'} :
			<ul>
				<li><a href="#" target="_blank">{l s='English' d='Modules.Statusautomation.Configure'}</a></li>
				<li><a href="#" target="_blank">{l s='French' d='Modules.Statusautomation.Configure'}</a></li>
			</ul>
		</p>
	</div>
*}
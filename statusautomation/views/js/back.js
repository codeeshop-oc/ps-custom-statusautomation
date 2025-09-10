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

// $(document).ready(function () {
	// remove panel heading
	// const panel = $('[name="submitStatusautomationModule"]').parents('.panel')
	// panel.find('.panel-heading').remove()
// })

$(document).ready(function () {

	$('.custom_url_type').each((i, ele) => {
		$(ele).trigger('change')
	});

	// rearranging form to top
	(function () {
		const ele = $('#submitOptionsmodule').parents('.panel')
		const html = ele.prop('outerHTML')
		ele.remove()
		$('form#module_form').prepend(html)
	})()

	progressBar()
	progressBarHide()
}).on('change', '.custom_url_type', function () {
	if ($(this).val() != 'CUSTOM') {
		$(this).parents('.form-group').next().hide(250)
	} else {
		$(this).parents('.form-group').next().show(250)
	}
}).on('change', '[name="STATUSAUTOMATION_FILE"]', function () {
	progressBarHide()
}).on('click', '[name="submitOptionsmodule"]', function () {
	progressBarHide()
	// const url = $(this).parents('form').attr('action')
	const url = STATUSAUTOMATION_UPLOAD_URL
	console.log('here')
	updateFile(url)
})

function progressBarShow() {
	$('.progress.active').show()
}

function progressBarHide() {
	$('.progress.active').hide()
}

function progressBar() {
	const html = `<div class="progress active progress-striped" style="display: none; width: 100%">
      <div class="progress-bar progress-bar-success" role="progressbar" style="width: 0%" id="uploaded_progressbar_done">
        <span><span id="uploaded_progression_done">0</span>%
        </span>
      </div>
    </div>`

	const form = $('[name="submitOptionsmodule"]').parents('form')
	form.prepend(html)
}

function enableForm(){
	$('#module_form, [name="STATUSAUTOMATION_FILE"], [name="submitAddAttachments"], [name="submitOptionsmodule"]').removeAttr('disabled', 'disabled').removeClass('disabled')
}

function disableForm(){
	$('#module_form, [name="STATUSAUTOMATION_FILE"], [name="submitAddAttachments"], [name="submitOptionsmodule"]').attr('disabled', 'disabled').addClass('disabled')
}

function updateFile(url) {
	const $input = $('[name="STATUSAUTOMATION_FILE"]');
    const uploadedFile = $input.prop('files')[0];

	const data = new FormData();
    data.append('file', uploadedFile);

	$.ajax({
       type: 'POST',
       url: url,
       cache: false,
       contentType: false,
       processData: false,
       enctype: 'multipart/form-data',
       data: data,
       success: function(jsonData)
       {
			if(jsonData.status) {
				showSuccessMessage(jsonData.message)
				startProcessing(0, jsonData.total_count)
			} else {
				showErrorMessage(jsonData.message)
			}
       },
       error: function(XMLHttpRequest, textStatus, errorThrown)
       {
			showErrorMessage(errorThrown)
       }
    });
}

function pollProgressBar(offset, total_count) {
	let percent = Math.round((offset * 100) / total_count);
	if (percent > 100) {
		percent = 100
	}

	$('#uploaded_progression_done').text(percent)
	$('#uploaded_progressbar_done').css('width', percent + '%')
}

function startProcessing(offset, total_count) {
	disableForm()
	console.log(STATUSAUTOMATION_UPLOAD_IMPORT_URL, 'STATUSAUTOMATION_UPLOAD_IMPORT_URL')
	$.ajax({
       type: 'POST',
       url: STATUSAUTOMATION_UPLOAD_IMPORT_URL,
       data: {
       		offset: offset,
       		total_count: total_count
       },
       success: function(jsonData)
       {
			if(jsonData.status) {
       			pollProgressBar(jsonData.offset, total_count)
       			progressBarShow()
				if (jsonData.done) {
					showSuccessMessage(jsonData.message)
					enableForm()
				} else {
					startProcessing(jsonData.offset, total_count)
					disableForm()
				}
			} else {
				showErrorMessage(jsonData.message)
				enableForm()

			}
       },
       error: function(XMLHttpRequest, textStatus, errorThrown)
       {
			showErrorMessage(errorThrown)
			enableForm()
       }
    });
}
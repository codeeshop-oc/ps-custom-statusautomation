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

$(document).on("click", ".copy_url", function () {
  const attr_id = $(this).parent().find('input').attr('id')
  copyURL(attr_id, $("#" + attr_id).val());
});

async function copyURL(attr_id, url = "") {
  if (url && (await copyToClipboard(url))) {
    $("#" + attr_id + '_copy').removeClass("icon-copy").addClass("icon-check");
    const t = setTimeout(function () {
      $("#" + attr_id + '_copy').removeClass("icon-check").addClass("icon-copy");
      clearTimeout(t);
    }, 800);
    $.growl.notice({
      title: "",
      message: $("#data-copy-message").data("copy-success-message"),
    });
  } else {
    $.growl.error({
      title: "",
      message: $("#data-copy-message").data("copy-error-message"),
    });
  }
}

async function copyToClipboard(value) {
  // Try using the execCommand method
  try {
    let done = false;
    await navigator.clipboard.writeText(value).then(
      function () {
        done = true;
      },
      function (err) {
        done = false;
      }
    );

    return true;
  } catch (e) {
    // If execCommand fails, try creating a temporary textarea
    let textarea = document.createElement("textarea");
    textarea.value = value;
    textarea.style.position = "absolute";
    textarea.style.left = "-9999px";
    document.body.appendChild(textarea);
    textarea.select();
    try {
      document.execCommand("copy");
      setTimeout(() => document.body.removeChild(textarea), 100);
      return true;
    } catch (e) {
      // If creating a textarea fails, use fallback alert
      alert("Unable to copy text to clipboard. Please copy manually.");
      return false;
    }
  }
}
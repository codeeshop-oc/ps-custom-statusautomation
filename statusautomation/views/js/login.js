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

var Statusautomation = function () {};
$(document)
  .ready(function () {
    Statusautomation.prototype.verifyPhone = function (button_id, CURRENT_URL) {
      $(".whatsapp_help_block ul li").remove();
      $(button_id).attr("disabled", "disabled");

      $.ajax({
        type: "POST",
        url: CURRENT_URL,
        dataType: "JSON",
        data: $("#verify_phone-form input"),
        success: function (jsonData) {
          $(button_id).removeAttr("disabled");

          if (jsonData.status) {
            Swal.fire({
              title: jsonData.message,
              icon: "success",
              showDenyButton: whatsapp_buttons[1].status,
              showCancelButton: whatsapp_buttons[2].status,
              showConfirmButton: whatsapp_buttons[0].status,
              confirmButtonText: whatsapp_buttons[0].text,
              cancelButtonText: whatsapp_buttons[2].text,
              denyButtonText: whatsapp_buttons[1].text,
            }).then((result) => {
              /* Read more about isConfirmed, isDenied below */
              if (result.isConfirmed) {
                location.href = whatsapp_buttons[0].url;
              } else if (result.isDenied) {
                location.href = whatsapp_buttons[1].url;
              } else if (result.dismiss === Swal.DismissReason.cancel) {
                location.href = whatsapp_buttons[2].url;
              }
            });
          } else {
            if (jsonData.message) {
              $(".whatsapp_help_block ul").append(
                `<li class="alert alert-danger">${jsonData.message}</li>`
              );
            }
          }
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
          $(button_id).removeAttr("disabled");
          Swal.fire({
            title: "Error!",
            // text: 'Do you want to continue',
            icon: "error",
            // confirmButtonText: 'Cool'
          });
          // showErrorMessage(errorThrown)
        },
      });
    };

    Statusautomation.prototype.resendVerificationCode = function (
      button_id,
      CURRENT_URL
    ) {
      $(".whatsapp_help_block ul li").remove();
      $(button_id).attr("disabled", "disabled");
      $.ajax({
        type: "POST",
        url: CURRENT_URL,
        dataType: "JSON",
        data: $("#verify_phone-form input"),
        success: function (jsonData) {
          $(button_id).removeAttr("disabled");
          if (jsonData.status) {
            Swal.fire({
              title: jsonData.message,
              // text: jsonData.message,
              icon: "success",
            });
          } else {
            if (jsonData.message) {
              $(".whatsapp_help_block ul").append(
                `<li class="alert alert-danger">${jsonData.message}</li>`
              );
            }
          }
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
          $(button_id).removeAttr("disabled");

          Swal.fire({
            title: "Error!",
            // text: 'Do you want to continue',
            icon: "error",
            // confirmButtonText: 'Cool'
          });
        },
      });
    };

    Statusautomation.prototype.addWhatsappButtonOnLoginPage = function () {
      const current_value = $('[name="whatsapp"]').val();
      $('[name="whatsapp"]').replaceWith(
        `<div class="input-group"><span class="input-group-addon">+212</span><input class="form-control" name="whatsapp" type="text" value="${current_value}" required=""></div>`
      );
    };

    (function test() {
      // $('[name="phone_verify_code"]').val("V0XWMS");
    })();

    (function initProcess() {
      Statusautomation.prototype.addWhatsappButtonOnLoginPage();
    })();
  })
  .on("click", "#submit-verify", function () {
    const obj = new Statusautomation();
    obj.verifyPhone("#submit-verify", $("#verify_phone-form").val());
  })
  .on("click", "#submit-resend_verification_code", function () {
    const obj = new Statusautomation();
    obj.resendVerificationCode(
      "#submit-resend_verification_code",
      $("#submit-resend_verification_code").data("url")
    );
  });

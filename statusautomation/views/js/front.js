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

// dependent on psvipflow module

$(document).ready(function () {
  $(".user-info").parent().before(`
		<div>
			<div class="user-info">
		          <a href="${STATUSAUTOMATION_LOGIN_URL}" title="" rel="nofollow">
		        <i class="material-icons"></i>
		        <span class="hidden-sm-down">${STATUSAUTOMATION_SIGN_IN_LOGIN}</span>
		      </a>
	       </div>
       </div>
	`);
});

function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

async function verifyStatusAutomationForm(
  next_url = "",
  type = "UPDATE_TO_VERIFIED"
) {
  const temp_id = `statusautomation-form-${getRandomInt(1000000, 9990000)}`;
  Swal.fire({
    title: PSVIPFLOW_VERIFICATION_FORM_TITLE,
    showCancelButton: false,
    html: `
		  <div class="statusautomation-form temp_id ${type.toLowerCase()}">
		  	<div class="form-group row">
				<label class="col-md-2 form-control-label required"></label>
				<div class="col-md-8">
					<div class="input-group"><span class="input-group-addon">+212</span>
					<input readonly class="form-control" name="whatsapp" type="text" value="${
            typeof STATUSAUTOMATION_POPUP_WHATSAPP_VALUE != "undefined" &&
            STATUSAUTOMATION_POPUP_WHATSAPP_VALUE
              ? STATUSAUTOMATION_POPUP_WHATSAPP_VALUE
              : ""
          }" required=""></div>          
				<div class="whatsapp_help_block help-block">
					<ul></ul>
				</div>
					</div>
					
					</div>
		
				<div class="form-group row">
				<label class="col-md-2 form-control-label"></label>				
					<div class="col-md-8">
						<div class="my-sm-2">${PSVIPFLOW_VERIFICATION_FORM_ENTER_CODE_HERE_TEXT}</div>
						<div>
						<input class="form-control" name="phone_verify_code" type="text" value="" required="">
						</div>
						<div>${PSVIPFLOW_VERIFICATION_FORM_NOT_RECEIVED_TEXT}
							<button type="button"
							id="submit-resend_verification_code" 
							data-url="${PSVIPFLOW_VERIFICATION_FORM_ORDER_PAGE_RESEND_OTP_URL}" 
							class="btn btn-link">${PSVIPFLOW_VERIFICATION_FORM_RESEND_CODE_TEXT}</button>
						</div>
					</div>
				</div>
				<footer>
		  			<input type="hidden" name="submitVerifyLogin" value="1" />
		  			<input type="hidden" name="PSVIPFLOW_UPDATE_LINK" value="${PSVIPFLOW_UPDATE_LINK_BASE_ENCODE}" />
					<button id="submit-verify" data-reload="1" class="btn btn-primary" data-link-action="sign-in" type="button" data-url="">
						${PSVIPFLOW_VERIFICATION_FORM_CONFIRM_BUTTON_TEXT}
					</button>
				</footer>
		  </div>
		`,
	showConfirmButton: false,   
    allowOutsideClick: false, // 🔒 disables closing by outside click
    allowEscapeKey: false, // 🔒 disables ESC key
    allowEnterKey: false, // optional: disables ENTER key
  });

//   if (formValues == true) {
//     function loginAndUpdateToVerifiedAccount(next_url) {
//       $.ajax({
//         type: "POST",
//         dataType: "JSON",
//         url: next_url,
//         data: JSON.stringify({
//           whatsapp: $(`.${temp_id} [name="whatsapp"]`).val(),
//         }),
//       })
//         .done((response) => {
//           if (response.status) {
//             Swal.fire({
//               icon: "success",
//               text: response.message,
//             });
//           } else {
//             Swal.fire({
//               icon: "error",
//               text: response.message,
//             });
//           }
//         })
//         .fail((errors) => {
//           Swal.fire({
//             icon: "error",
//             // title: "Oops...",
//             text: errors.responseJSON,
//           });
//         });
//     }
//   }
}

if (typeof STATUSAUTOMATION_IS_PRODUCT_PAGE != "undefined") {
  localStorage.setItem("STATUSAUTOMATION_LAST_PRODUCT_PAGE_URL", location.href);
}

var Statusautomation = function () {};
$(document)
  .ready(function () {
    Statusautomation.prototype.redirectURL = function (link) {
      if (link.type == "LAST_PRODUCT_SEEN") {
        const product_url = localStorage.getItem(
          "STATUSAUTOMATION_LAST_PRODUCT_PAGE_URL"
        );
        if (product_url) {
          location.href = product_url;
          return;
        }
      }

      location.href = whatsapp_buttons[1].url;
    };

    Statusautomation.prototype.verifyPhone = function (button_id, CURRENT_URL) {
      $(".whatsapp_help_block ul li").remove();
      $(button_id).attr("disabled", "disabled");

      $.ajax({
        type: "POST",
        url: CURRENT_URL,
        dataType: "JSON",
        data: $("#verify_phone-form input, .statusautomation-form input"),
        success: function (jsonData) {
          $(button_id).removeAttr("disabled");

          if (jsonData.status) {
            // fallback to reload page
            $("#verify_phone-form").before(
              `<center><button type="button" class="btn btn-primary" onclick="window.location.reload()">${
                typeof TEXT_RELOAD == "undefined" ? "Reload" : TEXT_RELOAD
              }<a></center>`
            );
            $("#verify_phone-form").remove();
			
            Swal.fire({
              title: jsonData.message,
              icon: "success",
              showDenyButton: whatsapp_buttons[1].status,
              showCancelButton: whatsapp_buttons[2].status,
              showConfirmButton: whatsapp_buttons[0].status,
              confirmButtonText: whatsapp_buttons[0].text,
              cancelButtonText: whatsapp_buttons[2].text,
              denyButtonText: whatsapp_buttons[1].text,
              allowOutsideClick: false,
              allowEscapeKey: false,
            }).then((result) => {
              /* Read more about isConfirmed, isDenied below */
              if (result.isConfirmed) {
                Statusautomation.prototype.redirectURL(whatsapp_buttons[0]);
              } else if (result.isDenied) {
                Statusautomation.prototype.redirectURL(whatsapp_buttons[1]);
              } else if (result.dismiss === Swal.DismissReason.cancel) {
                Statusautomation.prototype.redirectURL(whatsapp_buttons[2]);
              } else {
                window.location.reload();
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
        //   Swal.fire({
        //     title: errorThrown,
        //     // text: 'Do you want to continue',
        //     icon: "error",
        //     // confirmButtonText: 'Cool'
        //   });
		  $(".whatsapp_help_block ul").append(
			`<li class="alert alert-danger">${errorThrown}</li>`
		  );
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
        data: $("#verify_phone-form input, .statusautomation-form input"),
        success: function (jsonData) {
          $(button_id).removeAttr("disabled");
          if (jsonData.status) {
            // Swal.fire({
            //   title: jsonData.message,
            //   // text: jsonData.message,
            //   icon: "success",
            // });
			$(".whatsapp_help_block ul").append(
				`<li class="alert alert-success">${jsonData.message}</li>`
			);
			// showOldForm()
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
  })
  .on("click", "#submit-verify", function () {
    const obj = new Statusautomation();
    obj.verifyPhone("#submit-verify", ORDER_PAGE_VALIDATE_OTP_URL);
  })
  .on("click", "#submit-resend_verification_code", function () {
    const obj = new Statusautomation();
    obj.resendVerificationCode(
      "#submit-resend_verification_code",
      $("#submit-resend_verification_code").data("url")
    );
  });

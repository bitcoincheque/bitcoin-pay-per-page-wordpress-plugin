/* Copyright (C) 2016 Bitcoin Cheque Foundation.
 * GNU LESSER GENERAL PUBLIC LICENSE (GNU LGPLv3)
 */

var text = "";
var retry = false;
var timeout = 0;
var error_counter = 0;
var processing_text = "";

jQuery(document).ready(function($)
{
	$( 'a#bcf_paylink1' ).click(
		function()
		{
            processing_text = "Processing";

            window.setTimeout(pppc_update_payment_status, 500);
			$('p#bcf_payment_status').html(processing_text);

			timeout = 0;
			error_counter = 0;
			ok = false;
		});

	$('#bcf_pppc_do_login').click(
		function()
		{
			var error_in_text = false;
			var username = $('#bcf_pppc_username').val();
			var password = $('#bcf_pppc_password').val();

			if(username == '')
			{
				$('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_error">Username missing.</span></p>');
				error_in_text = true;
			}

			if(password == '')
			{
				$('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_error">Password missing.</span></p>');
				error_in_text = true;
			}

			if(error_in_text == false) {
				$('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_info">Logging in...</span></p>');

				var data = {
					action: 'bcf_pppc_do_login',
					username: username,
					password: password,
					nonce: pppc_script_handler_vars.nonce
				};

				$.post(pppc_script_handler_vars.url_to_my_site, data, function (resp, status) {
					if (status == "success") {
						if (resp.result == "OK") {
							pppc_load_remaining_content();
						} else {
							$('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_error">Wrong username or password.</span></p>');
						}
					} else {
						$('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_error">Site error. Contact server admin.</span></p>');
					}
				}, 'json');
			}
		});

	function pppc_update_payment_status()
	{
		var data = {
			action: 'bcf_payperpage_process_ajax_pay_status',
			ref: pppc_script_handler_vars.post_id_ref,
			nonce : pppc_script_handler_vars.nonce
		}

		$.post(pppc_script_handler_vars.url_to_my_site, data, function(resp, status)
		{
			retry = false;

			if(status == "success")
			{
				if(resp.pay_status == "OK")
				{
					text = "Payment OK!";
					window.setTimeout(pppc_load_remaining_content, 500);
				}
				else if(resp.pay_status == "INVALID")
				{
					text = "Cheque invalid!";
				}
				else if(resp.pay_status == "WAIT")
				{
					text = "Waiting for payment processing..." + resp.request_counter;
					retry = true;
				}
				else
				{
					text = "ERROR: Unexpected response.<br>Message from server:<br>Result=" + resp.pay_status + "<br>Message=" + resp.message;
				}

				error_counter=0;
			}
			else
			{
				// Count concequtive errors.
				text = "Server error. Retry...";
				error_counter++;
				retry = true;
			}

			if(retry == true)
			{
				processing_text = processing_text.concat('.');
				text = processing_text;

				if(error_counter < 3)
				{
					if(timeout < 10)
					{
						window.setTimeout(pppc_update_payment_status, 500);
						timeout++;
					}
					else
					{
						text = "Time-out. Payment canceled!";
					}
				}
				else
				{
					text = "Error. Give up. Can not read server!";
				}
			}

			$('p#bcf_payment_status').html(text);

		},'json');

	}

	function pppc_load_remaining_content()
	{
		$('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_info">Loading content...</span></p>');

		var data = {
			action: 'bcf_payperpage_load_rest_of_content',
			ref: pppc_script_handler_vars.post_id_ref,
			nonce : pppc_script_handler_vars.nonce,
			post_id : pppc_script_handler_vars.postid
		}

		$.post(pppc_script_handler_vars.url_to_my_site, data, function(resp, status)
		{
			if(status == "success") {
				if(resp.result == "OK"){
					$('div#pppc_fade_content').remove();
					$('div#bcf_pppc_login_form').remove();
					$('div#bcf_remaining_content').html(resp.message);
				}else{
					$('p#bcf_payment_status').html("ERROR uploading text. Message from server:"  + resp.message);
				}
			}else {
				$('p#bcf_payment_status').html("ERROR. Payment is OK, but time-out reading text. Please retry loading page.");
			}
		},'json');
	}

});

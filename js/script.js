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
    function GetCheckInputTextFormData(selector, error_message) {
        var text = $(selector).val();
        if(text == '') {
            $(selector).css('border', '1px solid red');
            SetStatusMessage('error', error_message);
        }
        return text;
    }

    function SetStatusMessage(color_code, message){
        var span_class = 'bcf_pppc_status_info';
        switch (color_code)
        {
            case 'error':
                span_class = 'bcf_pppc_status_error';
                break;
        }

        $('p#bcf_payment_status').html('<p><span class="' + span_class + '">' + message + '</span></p>');
    }

    function load_register_form(data){
        $.post(pppc_script_handler_vars.url_to_my_site, data, function (resp, status) {
            if (status == "success") {
                if (resp.result == "OK") {
                    if(resp.action == 'load_form') {
                        $('div#bcf_pppc_login_form').html(resp.form);
                    }else if(resp.action == 'load_remaining_content') {
                        pppc_load_remaining_content();
                    }else{
                        $('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_error">Error. Unexpected server message.</span></p>');
                    }
                } else if (resp.result == "ERROR") {
                    $('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_error">' + resp.message + '</span></p>');
                }else{
                    $('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_error">Error. Unexpected server response.</span></p>');
                }
            } else {
                $('p#bcf_payment_status').html('<p><span class="bcf_pppc_status_error">Site error. Contact server admin.</span></p>');
            }
        }, 'json');
    }

    $(document).on('click', '#bcf_pppc_do_login', function() {
        var username = GetCheckInputTextFormData('#bcf_pppc_username', 'Username missing.');
        var password = GetCheckInputTextFormData('#bcf_pppc_password', 'Password missing.');
        var reg_id = GetCheckInputTextFormData('input#bcf_pppc_reg_id', '');

        if (username == '' && password == '') {
            SetStatusMessage('error','Username and password missing');
        }

        if (username != '' && password != '') {
            SetStatusMessage('info', 'Logging in...');

            var data = {
                action: 'bcf_pppc_do_login',
                rid : reg_id,
                event: 'login',
                username: username,
                password: password,
                nonce: pppc_script_handler_vars.nonce
            };

            load_register_form(data);
        }
    });

    $(document).on('click', '#bcf_pppc_do_register', function() {
        var username = GetCheckInputTextFormData('input#bcf_pppc_username', 'Username missing.');
        var password = GetCheckInputTextFormData('input#bcf_pppc_password', 'Password missing.');
        var reg_id = GetCheckInputTextFormData('input#bcf_pppc_reg_id', '');
        var post_id = GetCheckInputTextFormData('input#bcf_pppc_post_id', '');

        if (username == '' && password == '') {
            SetStatusMessage('error','You must select a username and password to register');
        }

        if (username != '' && password != '') {
            var data = {
                action: 'bcf_pppc_do_login',
                rid : reg_id,
                event: 'register',
                username: username,
                password: password,
                post_id: post_id,
                nonce: pppc_script_handler_vars.nonce
            };

            SetStatusMessage('info', 'Register...');

            load_register_form(data);
        }
    });

    $(document).on('click', '#bcf_pppc_do_register_email', function() {
        var email = GetCheckInputTextFormData('#bcf_pppc_email', 'E-mail address missing.');
        var reg_id = GetCheckInputTextFormData('input#bcf_pppc_reg_id', '');

        if(email != '') {
            var data = {
                action: 'bcf_pppc_do_login',
                rid : reg_id,
                event: 'register_email',
                email: email,
                nonce: pppc_script_handler_vars.nonce
            };

            SetStatusMessage('info', 'Register and sending you verification e-mail...');

            load_register_form(data);
        }
    });

    $(document).on('click', '#bcf_pppc_do_return_login' , function(){
        var reg_id = GetCheckInputTextFormData('input#bcf_pppc_reg_id', '');

        var data = {
            action: 'bcf_pppc_do_login',
            rid : reg_id,
            event: 'goto_login',
            nonce: pppc_script_handler_vars.nonce
        };
        load_register_form(data);
    });

    $(document).on('click', '#bcf_pppc_do_resend_email' , function(){
        var reg_id = GetCheckInputTextFormData('input#bcf_pppc_reg_id', '');

        var data = {
            action: 'bcf_pppc_do_login',
            rid : reg_id,
            event: 'resend_register',
            nonce: pppc_script_handler_vars.nonce
        };
        load_register_form(data);
    });

    $( 'a#bcf_paylink1' ).click(
        function()
        {
            processing_text = "Processing";

            window.setTimeout(pppc_update_payment_status, 500);
            SetStatusMessage('info', processing_text);

            timeout = 0;
            error_counter = 0;
            ok = false;
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

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

    $(document).on('click', '#bcf_pppc_post_data', function() {
        var data = {};

        data['event'] = this.name;

        var input_list = $('input.bcf_form_field');
        for(var i=0; i<input_list.length; i++) {
            input_item = input_list[i];
            var element_name = input_item.name;
            var element_value = '';
            switch(input_item.type) {
                case 'text':
                    element_value = input_item.value;
                    break;
                case 'checkbox':
                    element_value = input_item.checked?1:0;
                    break;
                case 'password':
                    element_value = input_item.value;
                    break;
                case 'hidden':
                    element_value = input_item.value;
                    break;
                default:
                    element_value = null;
                    break;
            }
            data[element_name] = element_value;
        }

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
            nonce: pppc_script_handler_vars.nonce,
            wp_nonce: pppc_script_handler_vars.wp_nonce
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
            wp_nonce: pppc_script_handler_vars.wp_nonce,
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

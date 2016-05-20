/* Copyright (C) 2016 Bitcoin Cheque Foundation.
 * GNU LESSER GENERAL PUBLIC LICENSE (GNU LGPLv3)
 */

var text = "";
var retry = false;
var timeout = 0;
var error_counter = 0;

jQuery(document).ready(function($)
{

	$('p#bcf_payment_status').html('x');

	$( 'a#bcf_paylink1' ).click(
		function()
		{
			window.setTimeout(bcf_demo_update_payment_status, 500);
			$('p#bcf_payment_status').html('Processing...');

			timeout = 0;
			error_counter = 0;
			ok = false;
		});

	function bcf_demo_update_payment_status()
	{
        // http://localhost/wordpress/wp-admin/admin-ajax.php?action=bcf_payperpage_process_ajax_pay_status

		var data = {
			action: 'bcf_payperpage_process_ajax_pay_status',
			post_id: '1'
			// Must add cheque data here...x
		}

		$.getJSON('http://localhost/wordpress/wp-admin/admin-ajax.php', data, function(resp, status)
        //$.getJSON('wp-admin/admin-ajax.php', data, function(resp, status)
		{
			retry = false;

			if(status == "success")
			{
				if(resp.pay_status == "OK")
				{
					text = "Payment OK!";
					window.setTimeout(bcf_demo_update_load_remaining_content, 500);
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
					text = "ERROR: Strange json response.{" + resp.pay_status + "}";
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
				text = "Processing " + timeout.toString();

				if(error_counter < 3)
				{
					if(timeout < 10)
					{
						window.setTimeout(bcf_demo_update_payment_status, 500);
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

		});

	}

	function bcf_demo_update_load_remaining_content()
	{
		$('div#bcf_remaining_content').html("Loading...");

		var data = {
			action: 'bcf_payperpage_load_rest_of_content',
			post_id: '1'
		}

        $.post('http://localhost/wordpress/wp-admin/admin-ajax.php', data, function(resp)
        //$.post('wp-admin/admin-ajax.php', data, function(resp)
		{
			$('div#bcf_remaining_content').html(resp);
		});
	}

});

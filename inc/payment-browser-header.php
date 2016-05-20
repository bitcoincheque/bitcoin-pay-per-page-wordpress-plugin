<?php
/**
 * Payment App Browser library written in php.
 * Original written to demonstrate the usage of Bitcoin Cheques.
 *
 * Copyright (C) 2016 Arild Hegvik and Bitcoin Cheque Foundation.
 *
 * GNU LESSER GENERAL PUBLIC LICENSE (GNU LGPLv3)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace BCF_PayPerPage;

define ('BCF_BHP_HEADER_NAME_PAYMENT_APP', 'Payment-App');
define ('BCF_BHP_PAYMENT_APP_MAX_LENGTH', 200);

/* Payment field keys */
define ('PAYMENT_APP_BROWSER_HEADER_FIELD_VERSION',           'v');
define ('PAYMENT_APP_BROWSER_HEADER_FIELD_PAYMENT_PROTOCOL',  'pp');
define ('PAYMENT_APP_BROWSER_HEADER_FIELD_BITCOIN_CHEQUE',    'pq');
define ('PAYMENT_APP_BROWSER_HEADER_FIELD_PREFERED_CURRENCY', 'pc');

/* Payment Browser Header Standard version field attribute values: */
define ('PAYMENT_APP_BROWSER_HEADER_VERSION_1', '1');

/* Payment Protocol field attribute values: */
define ('PAYMENT_APP_BROWSER_HEADER_PAYMENT_PROTOCOL_NONE', '0');
define ('PAYMENT_APP_BROWSER_HEADER_PAYMENT_PROTOCOL_PRE_BIP70', '1');
define ('PAYMENT_APP_BROWSER_HEADER_PAYMENT_PROTOCOL_BIP70', '2');
define ('PAYMENT_APP_BROWSER_HEADER_PAYMENT_PROTOCOL_BITCOIN_CHEQUE', '3');

/* Payment Cheque field attribute values: */
define ('PAYMENT_APP_BROWSER_HEADER_PAYMENT_CHEQUE_VER_1', '1');


/*
 * Gets the Payment Browser HTTP Header string.
 *
 * Does no sanitizing of it, use it with care.
 */
function bcf_pbh_get_payment_app_browser_header_str()
{
	$header_name_string = 'HTTP_' . strtoupper(BCF_BHP_HEADER_NAME_PAYMENT_APP);
	$header_name_string = str_replace('-', '_', $header_name_string);

	$browser_header_str = $_SERVER[$header_name_string];

	return $browser_header_str;
}

/*
 * Check if the Payment App Browser HTTP Header string is within maximum length.
 *
 * @return (bool)   true     OK.
 * @return (bool)   false    Error , too long.
 */
function bcf_pbh_check_size_payment_app_browser_header_str($browser_header_str)
{
	if(strlen($browser_header_str) < BCF_BHP_PAYMENT_APP_MAX_LENGTH)
	{
		return true;
	}
	else
	{
		return false;
	}
}

/*
 * Check if a Payment App Browser HTTP Header string only contains valid characters.
 *
 * This function should always be called before a string is used. You should never trust
 * user input, it may contain injected code.
 *
 * @return (bool)   true     OK.
 * @return (bool)   false    Error, illegal characters found.
 */
function bcf_pbh_sanitize_payment_app_browser_header_str($browser_header_str)
{
	$result = false;

	if(strlen($browser_header_str) < BCF_BHP_PAYMENT_APP_MAX_LENGTH)
	{
		if (!preg_match_all('/[^A-Za-z0-9-:;,]/', $browser_header_str))
		{
			$result = true;
		}
	}

	return $result;
}

/*
 * Converts a Paymen App Browser HTTP Header string to a equivalent JSON string.
 *
 * @param   Raw string as read from the Browser Headers.
 * @return  (bool)   false  Error encoding JSON string.
 * @return  (string)        JSON string
 */
function bcf_pbh_get_browser_json($browser_header_str)
{
	$header_data_json = false;

	if(strlen($browser_header_str) < BCF_BHP_PAYMENT_APP_MAX_LENGTH)
	{
		if (!preg_match_all('/[^A-Za-z0-9-:;,]/', $browser_header_str))
		{
			# Convert to json format
			$browser_header_json = str_replace(',', '","', $browser_header_str);
			$browser_header_json = str_replace(':', '":"', $browser_header_json);
			$browser_header_json = '{"' . $browser_header_json . '"}';

			$header_data_json = json_decode($browser_header_json);

			if ($header_data_json) {
				foreach ($header_data_json as $key => $value) {
					if (preg_match_all('/[^A-Za-z0-9-;]/', $key)) {
						$header_data_json = false;
						break;
					}

					if (preg_match_all('/[^A-Za-z0-9-;]/', $value)) {
						$header_data_json = false;
						break;
					}
				}
			} else {
				$header_data_json = false;
			}
		}
	}

	return $header_data_json;
}


function bcf_pabh_get_field_text_description($field_name)
{
	switch($field_name)
	{
		case PAYMENT_APP_BROWSER_HEADER_FIELD_VERSION: return 'Version';
		case PAYMENT_APP_BROWSER_HEADER_FIELD_PAYMENT_PROTOCOL: return 'Payment Protocol';
		case PAYMENT_APP_BROWSER_HEADER_FIELD_BITCOIN_CHEQUE: return 'Payment Cheque';
		case PAYMENT_APP_BROWSER_HEADER_FIELD_PREFERED_CURRENCY: return 'Prefered Currency';
		default: return 'Unknown field';
	}
}


function bcf_pabh_get_attr_text_description($field_name, $attribute)
{
	switch($field_name)
	{
		case PAYMENT_APP_BROWSER_HEADER_FIELD_VERSION:
			switch ($attribute)
			{
				case PAYMENT_APP_BROWSER_HEADER_VERSION_1:
					return 'Payment App Browser HTTP Header Standard, Version 1';
					break;
				default:
					return 'Undefined version';
					break;
			}
			break;

		case PAYMENT_APP_BROWSER_HEADER_FIELD_PAYMENT_PROTOCOL:
			switch ($attribute)
			{
				case PAYMENT_APP_BROWSER_HEADER_PAYMENT_PROTOCOL_NONE:
					return 'N/A';
					break;
				case PAYMENT_APP_BROWSER_HEADER_PAYMENT_PROTOCOL_PRE_BIP70:
					return 'Pre BIT-70';
					break;
				case PAYMENT_APP_BROWSER_HEADER_PAYMENT_PROTOCOL_BIP70:
					return 'BIP-70';
					break;
				default:
					return 'Undefined Payment Protocol';
					break;
			}
			break;

		case PAYMENT_APP_BROWSER_HEADER_FIELD_BITCOIN_CHEQUE:
			switch ($attribute)
			{
				case PAYMENT_APP_BROWSER_HEADER_PAYMENT_CHEQUE_VER_1:
					return 'Bitcoin Cheque Standard, Version 1';
					break;
				default:
					return 'Undefined Payment Cheque version';
					break;
			}
			break;
	}
}

/*
 * Converts an attribute value list expression into array of attribute values.
 *
 * Examples:
 * 1-3      is converted to {'1', '2', '3'}
 * 1;3-4;6  is converted to {'1', '3, '4', '6'}
 *
 * @param (string)  value   String containg attribute value list expressions.
 * @param (bool)    false   Error in list.
 * @param (array)           OK. Array contains the attribute values.
 */
function bcf_pbh_get_attribute_array($value)
{
	$attribute_raw_array = explode(';', $value);
	$attribute_array = array();

	foreach($attribute_raw_array as $attribute_expression)
	{
		if(preg_match('/[-]/', $attribute_expression))
		{
			if(substr_count($attribute_expression, '-') === 1)
			{
				# If a minus sign is detected, we can only have one of it.
				# A minus sign indicates a range
				$attribute_array_range = explode('-', $value);
				$start_range = $attribute_array_range[0];
				$end_range = $attribute_array_range[1];
				foreach (range($start_range, $end_range) as $attribute_value)
				{
					array_push($attribute_array, strval($attribute_value));
				}
			}
			else
			{
				# Attribute expression contains more than one minus sign.
				# This is a formating error.
				$attribute_array = false;
			}
		}
		else
		{
			array_push($attribute_array, $attribute_expression);
		}
	}

	return $attribute_array;
}


function bcf_pbh_get_list_of_attributes($field_name, $attribute_array)
{
	$str = '';
	$more_than_one = false;

	foreach($attribute_array as $attribute)
	{
		if($more_than_one)
		{
			$str .= ', ';
		}
		$str .= bcf_pabh_get_attr_text_description($field_name, $attribute);
		$more_than_one = true;
	}
	return $str;
}

function payment_app_get_browser_header_json()
{
	$output = '';
	$header_data_json = false;

	$browser_header_str = bcf_pbh_get_payment_app_browser_header_str();

	if($browser_header_str)
	{
		#$output .= "Payment-App browser header detected:<br>";
		#$output .= bcf_bph_create_paymentapp_header_table($browser_header_str);

		if (bcf_pbh_check_size_payment_app_browser_header_str($browser_header_str))
		{
			if (bcf_pbh_sanitize_payment_app_browser_header_str($browser_header_str))
			{
				$header_data_json = bcf_pbh_get_browser_json($browser_header_str);

				if ($header_data_json) {
					foreach ($header_data_json as $key => $value)
					{
						switch ($key) {
							case PAYMENT_APP_BROWSER_HEADER_FIELD_VERSION:
							case PAYMENT_APP_BROWSER_HEADER_FIELD_PAYMENT_PROTOCOL:
							case PAYMENT_APP_BROWSER_HEADER_FIELD_BITCOIN_CHEQUE:
								$attribute_list = bcf_pbh_get_attribute_array($value);
								#$output .= bcf_pbh_create_attribute_section($key, $value, $attribute_list);
								break;

							case PAYMENT_APP_BROWSER_HEADER_FIELD_PREFERED_CURRENCY:
								$attribute_list = bcf_pbh_get_attribute_array($value);
								#$output .= bcf_pbh_create_prefered_currency_section($key, $value, $attribute_list);
								break;

							default:
								$output .= 'Warning: Unknown attribute detected.' . $key . ' ' . $value;
								break;
						}
					}
				}
				else
				{
					$output .= '<p style="font-weight:bold;color:red">No valid header data found for Payment-App header field</p>';
				}
			}
			else
			{
				$output .= '<p style="font-weight:bold;color:red">' . BCF_BHP_HEADER_NAME_PAYMENT_APP . ' contains illegal characters.</p>';
			}
		}
		else
		{
			$output .= '<p style="font-weight:bold;color:red">' . BCF_BHP_HEADER_NAME_PAYMENT_APP . ' browser header value  too long. Max ' . BCF_BHP_PAYMENT_APP_MAX_LENGTH . ' characters.</p>';
		}
	}
	else
	{
		$output .= '<p style="font-weight:bold;color:red">' . BCF_BHP_HEADER_NAME_PAYMENT_APP . ' browser header field is missing</p>';
	}

	#$output .= var_dump(json_decode($browser_header_json));

	return $header_data_json;
}

/*
 * Check of a Payment App Browser HTTP Header key and attribute value is set.
 *
 * @param (string)	$header_data_json	Browser header in json format
 * @param (string)  $search_key			Key to search for
 * @param (string)	$search_attribute   Attribute value to search for
 */
function payment_app_browser_header_contains($header_data_json, $search_key, $search_attribute)
{
	if ($header_data_json) {
		foreach ($header_data_json as $key => $value) {
			if($search_key === $key){
				$attribute_list = bcf_pbh_get_attribute_array($value);
				foreach ($attribute_list as $attribute)
				{
					if($search_attribute === $attribute)
					{
						return true;
					}
				}
			}
		}
	}
	return false;
}

function payment_app_bitcoin_cheque_supported()
{
	$header_data_json = payment_app_get_browser_header_json();

	return payment_app_browser_header_contains($header_data_json, PAYMENT_APP_BROWSER_HEADER_FIELD_PAYMENT_PROTOCOL, PAYMENT_APP_BROWSER_HEADER_PAYMENT_PROTOCOL_BITCOIN_CHEQUE);
}

?>
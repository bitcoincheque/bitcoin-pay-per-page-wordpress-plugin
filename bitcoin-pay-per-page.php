<?php
/** Bitcoin Bank accounting library written in php.
 *  Original written to demonstrate the usage of Bitcoin Cheques.
 *
 *  Copyright (C) 2016 Arild Hegvik and Bitcoin Cheque Foundation.
 *
 *  GNU LESSER GENERAL PUBLIC LICENSE (GNU LGPLv3)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
Plugin Name: Bitcoin Pay-Per-Page
Plugin URI: http://www.bitcoincheque.org
Description: This is a Bitcoin Pay-Per-Page plugin to demonstrate the usage of "Bitcoin Cheques".
Version: 0.0.1
Author: Bitcoin Cheque Foundation
Author URI: http://www.bitcoincheque.org
License: GNU GPLv3
License URI: license.txt
Text Domain: bcf_payperpage
*/

namespace BCF_PayPerPage;

require_once ('inc/pageview_manager.php');
require_once ('inc/payment-browser-header.php');
require_once ('inc/payment_data_codec.php');


define ('BCF_PAYPAGE_OPTION_REQ_COUNTER',       'bcf_paypage_option_req_counter');
define ('BCF_PAYPAGE_OPTION_SALES_COUNTER',     'bcf_paypage_option_sales_counter');
define ('BCF_PAYPAGE_OPTION_COOCKIE_COUNTER',   'bcf_paypage_option_coockie_counter');

define ('BCF_PAYPAGE_PAYMENT_OPTIONS',          'bcf_payperpage_payment_options');
define ('BCF_PAYPAGE_WALLET_OPTIONS',           'bcf_payperpage_wallet_options');
define ('BCF_PAYPAGE_RECEIVER_OPTIONS',         'bcf_payperpage_receiver_options');
define ('BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS', 'bcf_payperpage_recommended_bank_options');
define ('BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS', 'bcf_payperpage_cheque_condition_options');
define ('BCF_PAYPAGE_ADVANCED_OPTIONS',         'bcf_payperpage_advanced_options');

define ('BCF_PAYPAGE_REQUIRE_PAYMENT_TAG', '[require_payment]');


function SanitizeInputText($text)
{
    $text = str_replace('<', '&lt;', $text);
    $text = str_replace('>', '&gt;', $text);
    //$text = str_replace('"', '&quot;', $text);

    return $text;
}

function SanitizeInputInteger($text)
{
    if (preg_match('/^[1-9][0-9]{0,15}$/', $text))
    {
        $value = intval($text);
    }
    else
    {
        $value = null;
    }

    return $value;
}

function SafeReadGetString($key)
{
    if(!empty($_GET[$key]))
    {
        return SanitizeInputText($_GET[$key]);
    }
    else
    {
        return null;
    }
}

function SafeReadGetInt($key)
{
    if(!empty($_GET[$key]))
    {
        return SanitizeInputInteger($_GET[$key]);
    }
    else
    {
        return null;
    }
}

function SafeReadPostString($key)
{
    if(!empty($_POST[$key]))
    {
        return SanitizeInputText($_POST[$key]);
    }
    else
    {
        return null;
    }
}

function SafeReadPostInt($key)
{
    if(!empty($_POST[$key]))
    {
        return SanitizeInputInteger($_POST[$key]);
    }
    else
    {
        return null;
    }
}


function EncodeAndSignBitcoinCheque($cheque_data)
{
    $payment_file = new PaymentDataFile();
    $payment_file->SetDataArray($cheque_data, 'PAYMENT_CHEQUE_');
    $payment_file->SetFilePrefix('PAYMENT_CHEQUE');
    $encoded_file = $payment_file->GetEncodedPaymentFile();

    return $encoded_file;
}

function DecodeAndVerifyPaymentFile($payment_file)
{
    $encoded_payment_file = new PaymentDataFile();
    $encoded_payment_file->SetEncodedPaymentFile($payment_file);
    $decoded_data = $encoded_payment_file->GetDataArray();

    return $decoded_data;
}

function ValidateCheque($cheque, $hash)
{
    $result = 'ERROR';

    $collect_url = $cheque['collect_url'];

    $data = array(
        'body' => array(
            'action'        => 'validate_payment_cheque',
            'cheque_no'     => $cheque['cheque_id'],
            'access_code'   => $cheque['access_code'],
            'hash'          => $hash
        )
    );

    $response = wp_remote_post( $collect_url, $data );

    if(is_wp_error($response))
    {
        $result = 'ERROR';
    }
    else
    {
        $answer = json_decode($response['body'], true);

        if($answer['result'] == 'OK')
        {
            $result = 'VALID';
        }
        else
        {
            $result = $answer['message'];
        }
    }

    return $result;
}

function GetPaymentRequest($ref)
{
    $options = get_option(BCF_PAYPAGE_ADVANCED_OPTIONS);
    $ajax_handler = $options['ajax_handler'];
    $payment_info_link = site_url() . $ajax_handler . '?action=bcf_payperpage_process_ajax_get_payment_data&ref='. $ref;

    if(payment_app_bitcoin_cheque_supported())
    {
        $options = get_option(BCF_PAYPAGE_WALLET_OPTIONS);
        $wallet_address = $options['receiver_wallet'];

        if($wallet_address == '')
        {
            $payment_url = "No wallet address specified in admin setting.";
        }
        else
        {
            $href = 'bitcoin:' . $wallet_address . '?request=' . $payment_info_link;
            $payment_url = '<a id="bcf_paylink1" href=' . $href . ' class="bitcoin-address">Bitcoin Cheque Payment Link</a>';
        }
    }
    else
    {
        $options = get_option(BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS);
        $help_text = $options['help_text'];
        $bank_url = $options['bank_url'];

        $payment_info_link = rawurlencode($payment_info_link);

        $payment_url = '<a class="bcf_test" href="' . $bank_url .  '?request=' . $payment_info_link . '">' . $help_text . '</a>';
    }

    return $payment_url;
}


function FilterContent( $content )
{
    $position = strpos ($content, BCF_PAYPAGE_REQUIRE_PAYMENT_TAG);

    if($position)
    {
        $pageview_manager = new PageViewManagerClass();

        $post_id_val = get_the_ID();
        $post_id = new UnsigedIntegerTypeClass($post_id_val);

        if($pageview_manager->HasUserPaidForThisPage($post_id))
        {
            $content = str_replace(BCF_PAYPAGE_REQUIRE_PAYMENT_TAG, '', $content);
        }
        else
        {
            $options = get_option(BCF_PAYPAGE_PAYMENT_OPTIONS);
            $price_int = intval($options['default_price']);
            $BTC_denominator = $options['btc_unit'];
            $price = new ValueTypeClass($price_int);

            $ref = $pageview_manager->RegisterNewPageView($post_id, $price);

            $options = get_option(BCF_PAYPAGE_ADVANCED_OPTIONS);
            $ajax_handler = $options['ajax_handler'];

            $url_to_my_site = site_url() . $ajax_handler;

            $translation_array = array(
                'url_to_my_site'    => $url_to_my_site,
                'post_id_ref'       => intval($ref['ref']),
                'nonce'             => $ref['nonce']
            );
            wp_localize_script('bcf_payperpage_script_handler', 'bcf_demo_script_handler_vars', $translation_array);

            $content = substr ($content , 0 , $position);

            $content .= '<div id="bcf_remaining_content">';

            $content .= '<br><b>To read the rest of the article, please pay ' . $price->GetFormattedCurrencyString($BTC_denominator, true) . ' to this address:</b>';
            $content .= '<br>';
            $content .= GetPaymentRequest($ref['ref']);
            $content .= "<br>";

            $content .= '<p id="bcf_payment_status"></p>';
            $content .= '<p id="bcf_payment_debug"></p>';

            $content .= '</div>';

            update_option(BCF_PAYPAGE_OPTION_SALES_COUNTER, 0);
        }
    }

    return $content;
}

function LoadRestOfContent()
{
    $pageview_ref_int = SafeReadPostInt('ref');
    $nonce_str = SafeReadPostString('nonce');

    if(is_null($pageview_ref_int))
    {
        $response_data = array(
            'result'    => 'ERROR',
            'message'   => 'Page ref missing in text request.'
        );
        echo json_encode($response_data);
        die();
    }

    if(is_null($nonce_str))
    {
        $response_data = array(
            'result'    => 'ERROR',
            'message'   => 'Nonce missing in text request.'
        );
        echo json_encode($response_data);
        die();
    }

    $pageview_manager = new PageViewManagerClass();
    $pageview         = $pageview_manager->GetPaymentInfo($pageview_ref_int);

    $pay_status     = $pageview->GetPayStatus();
    $pay_status_str = $pay_status->GetString();

    $my_nonce     = $pageview->GetNonce();
    $my_nonce_str = $my_nonce->GetString();

    if($pay_status_str != 'PAID')
    {
        $response_data = array(
            'result'    => 'ERROR',
            'message'   => 'ERROR: Page has not been paid.'
        );
        echo json_encode($response_data);
        die();
    }

    if($nonce_str != $my_nonce_str)
    {
        $response_data = array(
            'result'    => 'ERROR',
            'message'   => 'ERROR: Invalid nonce.'
        );
        echo json_encode($response_data);
        die();
    }

    if($pageview_ref_int >= 0)
    {
        $pageview_id = new PageViewIdTypeClass($pageview_ref_int);

        if( ! is_null($pageview_id))
        {
            $pageview_manager = new PageViewManagerClass();

            $post_id = $pageview_manager->HasUserPaidForThisPageView($pageview_id);

            if( ! is_null($post_id))
            {
                $post_id_val = $post_id->GetInt();

                $post    = get_post($post_id_val);
                $content = $post->post_content;

                $position = strpos($content, BCF_PAYPAGE_REQUIRE_PAYMENT_TAG) + strlen(BCF_PAYPAGE_REQUIRE_PAYMENT_TAG);

                $content = substr($content, $position);

                $content_remaining = str_replace("\r\n", '<p>', $content);
                //$content_remaining = base64_encode($content_remaining);

                $response_data = array(
                    'result'    => 'OK',
                    'message'   => $content_remaining
                );

                echo json_encode($response_data);
                die();
            }
            else
            {
                $response_data = array(
                    'result'    => 'OK',
                    'message'   => 'ERROR: No payment verified.'
                );
                echo json_encode($response_data);
                die();
            }
        }
    }

    $response_data = array(
        'result'    => 'OK',
        'message'   => 'ERROR: Undefined error.'
    );
    echo json_encode($response_data);
    die();
}

function ProcessAjaxPayStatus()
{
    $pageview_ref_int = SafeReadGetInt('ref');
    $nonce_str = SafeReadGetString('nonce');

    if(is_null($pageview_ref_int))
    {
        $response_data = array(
            'pay_status' => 'ERROR',
            'message'    => 'Missing page ref.'
        );
        echo json_encode($response_data);
        die();
    }

    if(is_null($nonce_str))
    {
        $response_data = array(
            'pay_status' => 'ERROR',
            'message'    => 'Missing nonce.'
        );
        echo json_encode($response_data);
        die();
    }

    $pageview_manager = new PageViewManagerClass();
    $pageview         = $pageview_manager->GetPaymentInfo($pageview_ref_int);

    $pay_status     = $pageview->GetPayStatus();
    $pay_status_str = $pay_status->GetString();

    $my_nonce     = $pageview->GetNonce();
    $my_nonce_str = $my_nonce->GetString();

    if($nonce_str == $my_nonce_str)
    {
        if($pay_status_str == "UNPAID")
        {
            /* Waiting for payment */
            $response_data = array(
                'pay_status' => 'WAIT'
            );
        }
        elseif($pay_status_str == "PAID")
        {
            $response_data = array(
                'pay_status' => 'OK'
            );
        }
        else
        {
            /* Cheque payment invalid */
            $response_data = array(
                'pay_status' => 'INVALID'
            );
        }
    }
    else
    {
        /* Received invalid nonce */
        $response_data = array(
            'pay_status' => 'ERROR',
            'message'    => 'Invalid nonce'
        );
    }

    echo json_encode($response_data);
    die();
}

function ProcessAjaxReceiveCheque()
{
    if(!empty($_REQUEST['cheque']))
    {
        $payment_cheque_file = SanitizeInputText($_REQUEST['cheque']);

        $encoded_payment_file = new PaymentDataFile();
        $encoded_payment_file->SetEncodedPaymentFile($payment_cheque_file);
        $cheque_data = $encoded_payment_file->GetDataArray();
        $hash = $encoded_payment_file->GetHash();

        $cheque_is_valid = ValidateCheque($cheque_data, $hash);


        if($cheque_is_valid == 'VALID')
        {
            $pageview_id_val  = intval($cheque_data['receiver_reference']);
            $pageview_manager = new PageViewManagerClass();
            $pageview_id      = new PageViewIdTypeClass($pageview_id_val);
            $pageview_manager->SetPagePaid($pageview_id);
            $post_id = $pageview_manager->GetPaymentPostId($pageview_id);

            $data          = array(
                'result'          => 'OK',
                'message'         => 'Cheque accepted.',
                'return_link'     => site_url() . '?p=' . $post_id->GetString()
            );

            $sales_counter = 1;
            update_option(BCF_PAYPAGE_OPTION_SALES_COUNTER, $sales_counter);
        }
        else
        {
            $data = array(
                'result'          => 'ERROR',
                'message'         => 'Error validating cheque at bank. (Message from bank: ' . $cheque_is_valid . ')'
            );
        }
    }
    else
    {
        $data = array(
            'result'    => 'ERROR',
            'message'   => 'No cheque received.'
        );

        $sales_counter = 2;
        update_option(BCF_PAYPAGE_OPTION_SALES_COUNTER, $sales_counter);
    }

    echo json_encode($data);
    die();
}

function AjaxGetPaymentData()
{
    $ref = intval($_REQUEST['ref']);

    $pageview_manager = new PageViewManagerClass();

    $pageview = $pageview_manager->GetPaymentInfo($ref);

    if(!is_null($pageview))
    {
        $options = get_option(BCF_PAYPAGE_ADVANCED_OPTIONS);
        $ajax_handler = $options['ajax_handler'];

        $options = get_option(BCF_PAYPAGE_RECEIVER_OPTIONS);
        $receiver_name = $options['receiver_name'];
        $receiver_address = $options['receiver_address'];

        $receiver_url = $options['receiver_url'];
        $receiver_email = $options['receiver_email'];
        $business_no = $options['business_no'];
        $registration_country = $options['registration_country'];

        $options = get_option(BCF_PAYPAGE_WALLET_OPTIONS);
        $receiver_wallet = $options['receiver_wallet'];

        $options = get_option(BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS);
        $min_expire_sec = strval(intval($options['min_expire_hour']) * 3600);
        $max_escrow_sec = strval(intval($options['max_escrow_hour']) * 3600);

        $price = $pageview->GetPrice();
        $post_id = $pageview->GetPostId();

        $description = 'Payment for page ' . get_the_title($post_id->GetInt());

        $data = array(
            'ver'               => 1,
            'request_no'        => 1,
            'ref'               => $ref,
            'amount'            => $price->GetString(),
            'currency'          => 'BTC',
            'paylink'           => site_url() . $ajax_handler,
            'receiver_name'     => $receiver_name,
            'receiver_address'  => $receiver_address,
            'receiver_url'      => $receiver_url,
            'receiver_email'    => $receiver_email,
            'business_no'       => $business_no,
            'reg_country'       => $registration_country,
            'receiver_wallet'   => $receiver_wallet,
            'min_expire_sec'    => $min_expire_sec,
            'max_escrow_sec'    => $max_escrow_sec,
            'description'       => $description
        );

        $payment_file_base64 = new PaymentDataFile();
        $payment_file_base64->SetDataArray($data);
        $payment_file_base64->SetFilePrefix('PAYMENT_REQUEST');

        $response_data = array(
            'payment_request' => $payment_file_base64->GetEncodedPaymentFile()
        );

        echo json_encode($response_data);

    }

    die();
}

function Init()
{
    $src = plugin_dir_url( __FILE__ ) . 'js/script.js';
    wp_enqueue_script('bcf_payperpage_script_handler', $src, array( 'jquery' ), '0.1', true);

    if(!isset($_COOKIE[BCF_PAYPAGE_OPTION_COOKIE_NAME]))
    {
        $coockie_counter = get_option(BCF_PAYPAGE_OPTION_COOCKIE_COUNTER);
        $coockie_counter += 1;
        update_option(BCF_PAYPAGE_OPTION_COOCKIE_COUNTER, $coockie_counter);

        $seconds = 30 * 24 * 3600;  // Cookie live for 30 days
        $expire = time() + $seconds;

        if(setcookie(BCF_PAYPAGE_OPTION_COOKIE_NAME, $coockie_counter, $expire, COOKIEPATH, COOKIE_DOMAIN) == true)
        {
            //echo 'Set coockie: ' . BCF_PAYPAGE_OPTION_COOKIE_NAME . ' = ' . $coockie_counter . ' (Expire ' . $seconds . ' sec.)';
        }
    }
}

function AdminPage()
{
    echo '<div class="wrap">';
    echo '<h2>Pay-Per-Page Plugin Admin Settings</h2>';
    echo '<hr>';

    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPAGE_PAYMENT_OPTIONS);
    echo do_settings_sections('settings_section_payment_option');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '<br><hr>';

    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPAGE_WALLET_OPTIONS);
    echo do_settings_sections('settings_section_wallet_option');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';
    echo '<br><hr>';

    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPAGE_RECEIVER_OPTIONS);
    echo do_settings_sections('settings_section_receiver_option');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '<br><hr>';

    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS);
    echo do_settings_sections('settings_section_cheque_options');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '<br><hr>';

    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS);
    echo do_settings_sections('settings_section_recommended_bank');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '<br><hr>';

    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPAGE_ADVANCED_OPTIONS);
    echo do_settings_sections('settings_section_advanced_settings');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '</div>';
}

function AdminPaymentStatus()
{
    echo '<div class="wrap">';
    echo '<h2>Payment Status</h2>';
    echo '<hr>';

    echo '</div>';
}

function AdminDrawSettingsDefaultPrice()
{
    $options = get_option(BCF_PAYPAGE_PAYMENT_OPTIONS);
    $selected = $options['default_price'];
    echo '<input name="' . BCF_PAYPAGE_PAYMENT_OPTIONS . '[default_price]" type="text" value="' . $selected . '" /> Satoshis';
}

function AdminDrawSettingsBtcUnitsOption()
{
    $BTC_units = array('BTC', 'mBTC', 'uBTC');
    $options = get_option(BCF_PAYPAGE_PAYMENT_OPTIONS);

    echo '<select name="' . BCF_PAYPAGE_PAYMENT_OPTIONS . '[btc_unit]">';
    foreach($BTC_units as $BTC_unit)
    {
        if($options['btc_unit'] == $BTC_unit)
        {
            $currency_option = '<option value="' . $BTC_unit . '" selected="1">' . $BTC_unit . '</option>';
        }
        else
        {
            $currency_option = '<option value="' . $BTC_unit . '">' . $BTC_unit . '</option>';
        }
        echo $currency_option;
    }
    echo '</select>';
}

function AdminDrawSettingsWalletAddress()
{
    $options = get_option(BCF_PAYPAGE_WALLET_OPTIONS);
    $selected = $options['receiver_wallet'];
    //echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_WALLET_OPTIONS . '[receiver_wallet]" type="text">'.$selected.'</textarea>';
    echo '<input name="' . BCF_PAYPAGE_WALLET_OPTIONS . '[receiver_wallet]" type="text" value="' . $selected . '" />';
}

function AdminDrawSettingsReceiverName()
{
    $options = get_option(BCF_PAYPAGE_RECEIVER_OPTIONS);
    $selected = $options['receiver_name'];
    echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[receiver_name]" type="text">'.$selected.'</textarea>';
    //echo '<input name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[receiver_name]" type="text" value="' . $selected . '" />';
}

function AdminDrawSettingsReceiverAddress()
{
    $options = get_option(BCF_PAYPAGE_RECEIVER_OPTIONS);
    $selected = $options['receiver_address'];
    echo '<textarea rows="4" cols="50" name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[receiver_address]" type="text">'.$selected.'</textarea>';
    //echo '<input name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[receiver_address]" type="text" value="' . $selected . '" />';
}

function AdminDrawSettingsReceiverUrl()
{
    $options = get_option(BCF_PAYPAGE_RECEIVER_OPTIONS);
    $selected = $options['receiver_url'];
    echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[receiver_url]" type="text">'.$selected.'</textarea>';
    //echo '<input name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[receiver_url]" type="text" value="' . $selected . '" />';
}

function AdminDrawSettingsReceiverEmail()
{
    $options = get_option(BCF_PAYPAGE_RECEIVER_OPTIONS);
    $selected = $options['receiver_email'];
    echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[receiver_email]" type="text">'.$selected.'</textarea>';
    //echo '<input name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[receiver_email]" type="text" value="' . $selected . '" />';
}

function AdminDrawSettingsBusinessNo()
{
    $options = get_option(BCF_PAYPAGE_RECEIVER_OPTIONS);
    $selected = $options['business_no'];
    //echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[business_no]" type="text">'.$selected.'</textarea>';
    echo '<input name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[business_no]" type="text" value="' . $selected . '" />';
}

function AdminDrawSettingsRegistrationCountry()
{
    $options = get_option(BCF_PAYPAGE_RECEIVER_OPTIONS);
    $selected = $options['registration_country'];
    //echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[registration_country]" type="text">'.$selected.'</textarea>';
    echo '<input name="' . BCF_PAYPAGE_RECEIVER_OPTIONS . '[registration_country]" type="text" value="' . $selected . '" />';
}

function AdminDrawSettingsMinCollectTime()
{
    $options = get_option(BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS);
    $selected = $options['max_escrow_hour'];
    //echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS . '[max_escrow_hour]" type="text">'.$selected.'</textarea>';
    echo '<input name="' . BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS . '[max_escrow_hour]" type="text" value="' . $selected . '" /> Hours';
}

function AdminDrawSettingsMaxEscrowTime()
{
    $options = get_option(BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS);
    $selected = $options['min_expire_hour'];
    //echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS . '[min_expire_hour]" type="text">'.$selected.'</textarea>';
    echo '<input name="' . BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS . '[min_expire_hour]" type="text" value="' . $selected . '" /> Hours';
}

function AdminDrawSettingsRecommendedBank()
{
    $options = get_option(BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS);
    $selected = $options['bank_url'];
    echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS . '[bank_url]" type="text">'.$selected.'</textarea>';
}

function AdminDrawSettingsRecommendedBankHelp()
{
    $options = get_option(BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS);
    $selected = $options['help_text'];
    echo '<textarea rows="4" cols="50" name="' . BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS . '[help_text]" type="text">'.$selected.'</textarea>';
}

function AdminDrawSettingsAjaxHandler()
{
    $options = get_option(BCF_PAYPAGE_ADVANCED_OPTIONS);
    $selected = $options['ajax_handler'];
    $inp =  '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_ADVANCED_OPTIONS . '[ajax_handler]" type="text">'.$selected.'</textarea>';
    echo $inp;
}


function AdminDrawPaymentSettingsHelpText()
{
    echo 'Set price in Bitcoin for each page view.';
}

function AdminDrawWalletHelpText()
{
    echo 'Select the Bitcoin wallet address (public key) at where your payments will be sent to.';
}

function AdminDrawBusinessHelpText()
{
    echo 'Enter your business information. If your site is not part of a business, enter your personal details. This information is optional, but your should provide a name.';
}

function AdminDrawSettingsHelpRecommendedBank()
{
    echo 'In case the user has no Banking App installed, he will be directed to this bank where he can sign uf for an account and download an Banking App.';
}

function AdminDrawSettingsChequeHelp()
{
    echo 'Your required Bitcoin Cheque conditions. If your conditions is for tight, the user may no accept it.<br>Minimum collection time recommanded is 24 hour.<br>Maximum recommended escrow time is 24 hour';
}

function AdminDrawSettingsHelpAdvancedSettings()
{
    echo 'If you have a special configured Wordpress site, you may need to change the link to the AJAX handler.';
}


function AdminMenu()
{
    register_setting(BCF_PAYPAGE_PAYMENT_OPTIONS, BCF_PAYPAGE_PAYMENT_OPTIONS);
    register_setting(BCF_PAYPAGE_WALLET_OPTIONS, BCF_PAYPAGE_WALLET_OPTIONS);
    register_setting(BCF_PAYPAGE_RECEIVER_OPTIONS, BCF_PAYPAGE_RECEIVER_OPTIONS);
    register_setting(BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS, BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS);
    register_setting(BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS, BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS);
    register_setting(BCF_PAYPAGE_ADVANCED_OPTIONS, BCF_PAYPAGE_ADVANCED_OPTIONS);

    add_settings_section(
        'settings_section_payment_option_tag',
        'Price settings',
        'BCF_PayPerPage\AdminDrawPaymentSettingsHelpText',
        'settings_section_payment_option'
    );
    add_settings_field(
        'bcf_payperpage_settings_price',
        'Default price per page purchase:',
        '\BCF_PayPerPage\AdminDrawSettingsDefaultPrice',
        'settings_section_payment_option',
        'settings_section_payment_option_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_btc_denominator',
        'Price unit to display on page:',
        '\BCF_PayPerPage\AdminDrawSettingsBtcUnitsOption',
        'settings_section_payment_option',
        'settings_section_payment_option_tag'
    );

    add_settings_section(
        'settings_section_wallet_option_tag',
        'Bitcoin wallet settings',
        'BCF_PayPerPage\AdminDrawWalletHelpText',
        'settings_section_wallet_option'
    );
    add_settings_field(
        'bcf_payperpage_settings_wallet',
        'Receive Bitcoin Wallet address for payments:',
        '\BCF_PayPerPage\AdminDrawSettingsWalletAddress',
        'settings_section_wallet_option',
        'settings_section_wallet_option_tag'
    );

    add_settings_section(
        'settings_section_receiver_option_tag',
        'Business information:',
        'BCF_PayPerPage\AdminDrawBusinessHelpText',
        'settings_section_receiver_option'
    );
    add_settings_field(
        'bcf_payperpage_settings_name',
        'Business name (receiver\'s name):',
        '\BCF_PayPerPage\AdminDrawSettingsReceiverName',
        'settings_section_receiver_option',
        'settings_section_receiver_option_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_office_address',
        'Business office address (street, town, state, zip, country etc.):',
        '\BCF_PayPerPage\AdminDrawSettingsReceiverAddress',
        'settings_section_receiver_option',
        'settings_section_receiver_option_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_url',
        'Business web site:',
        '\BCF_PayPerPage\AdminDrawSettingsReceiverUrl',
        'settings_section_receiver_option',
        'settings_section_receiver_option_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_email',
        'Business e-mail:',
        '\BCF_PayPerPage\AdminDrawSettingsReceiverEmail',
        'settings_section_receiver_option',
        'settings_section_receiver_option_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_enterprice_no',
        'Business registration number:',
        '\BCF_PayPerPage\AdminDrawSettingsBusinessNo',
        'settings_section_receiver_option',
        'settings_section_receiver_option_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_registration_country',
        'Registration country:',
        '\BCF_PayPerPage\AdminDrawSettingsRegistrationCountry',
        'settings_section_receiver_option',
        'settings_section_receiver_option_tag'
    );

    add_settings_section(
        'settings_section_cheque_options_tag',
        'Rrequired Bitcoin Cheque conditions',
        'BCF_PayPerPage\AdminDrawSettingsChequeHelp',
        'settings_section_cheque_options'
    );
    add_settings_field(
        'bcf_payperpage_settings_min_expire_time',
        'Minimum collect time (Expire time):',
        '\BCF_PayPerPage\AdminDrawSettingsMinCollectTime',
        'settings_section_cheque_options',
        'settings_section_cheque_options_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_max_escrow_time',
        'Maximum escrow time:',
        '\BCF_PayPerPage\AdminDrawSettingsMaxEscrowTime',
        'settings_section_cheque_options',
        'settings_section_cheque_options_tag'
    );

    add_settings_section(
        'settings_section_recommended_bank_options_tag',
        'Recommended Bitcoin Bank',
        'BCF_PayPerPage\AdminDrawSettingsHelpRecommendedBank',
        'settings_section_recommended_bank'
    );
    add_settings_field(
        'bcf_payperpage_settings_help_text',
        'Instructions to display if visitor\'s browser has no Banking App:',
        '\BCF_PayPerPage\AdminDrawSettingsRecommendedBankHelp',
        'settings_section_recommended_bank',
        'settings_section_recommended_bank_options_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_recommended_bank',
        'URL to recommended Bitcoin Bank:',
        '\BCF_PayPerPage\AdminDrawSettingsRecommendedBank',
        'settings_section_recommended_bank',
        'settings_section_recommended_bank_options_tag'
    );

    add_settings_section(
        'settings_section_advanced_options_tag',
        'Advanced Settings',
        'BCF_PayPerPage\AdminDrawSettingsHelpAdvancedSettings',
        'settings_section_advanced_settings'
    );
    add_settings_field(
        'bcf_payperpage_settings_recommended_bank',
        'AJAX php handler:',
        '\BCF_PayPerPage\AdminDrawSettingsAjaxHandler',
        'settings_section_advanced_settings',
        'settings_section_advanced_options_tag'
    );

    add_menu_page('Pay-Per-Page Menu', 'Pay Per Page', 'manage_options', __FILE__, 'BCF_PayPerPage\AdminPage');
    add_submenu_page(__FILE__, 'Payment Status', 'Payment Status', 'manage_options', __FILE__.'about', 'BCF_PayPerPage\AdminPaymentStatus');
}

function ActivatePlugin()
{
    add_option( BCF_PAYPAGE_OPTION_COOCKIE_COUNTER, 0 );

    add_option (BCF_PAYPAGE_PAYMENT_OPTIONS, array(
            'default_price' => '100000',
            'btc_unit' => 'mBTC'
    ));

    add_option (BCF_PAYPAGE_WALLET_OPTIONS, array(
        'receiver_wallet' => ''
    ));

    add_option (BCF_PAYPAGE_RECEIVER_OPTIONS, array(
        'receiver_name' => get_bloginfo('name'),
        'receiver_address' => '',
        'receiver_url' => site_url(),
        'receiver_email' => '',
        'business_no' => '',
        'registration_country' => ''
    ));

    add_option (BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS, array(
        'min_expire_hour' => '24',
        'max_escrow_hour' => '24'
    ));

    add_option (BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS, array(
        'bank_url' => 'https://www.bitcoindemobank.com/payment/',
        'help_text'=> 'No Banking App has been installed in your browser. Get one here.'
    ));

    add_option (BCF_PAYPAGE_ADVANCED_OPTIONS, array(
        'ajax_handler' => '/wp-admin/admin-ajax.php'
    ));


    DB_CreateOrUpdateDatabaseTables();
}


function DeactivatePlugin()
{
    delete_option( BCF_PAYPAGE_PAYMENT_OPTIONS );
    delete_option( BCF_PAYPAGE_WALLET_OPTIONS );
    delete_option( BCF_PAYPAGE_RECEIVER_OPTIONS );
    delete_option( BCF_PAYPAGE_CHEQUE_CONDITION_OPTIONS );
    delete_option( BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS );
    delete_option( BCF_PAYPAGE_ADVANCED_OPTIONS );
}


function testingpage()
{
    if(isset($_COOKIE[BCF_PAYPAGE_OPTION_COOKIE_NAME]))
    {
        return "The cookie '" . BCF_PAYPAGE_OPTION_COOKIE_NAME . "' is set. Cookie is:  " . $_COOKIE[BCF_PAYPAGE_OPTION_COOKIE_NAME];
    }
    else
    {
        return "The cookie: '" . BCF_PAYPAGE_OPTION_COOKIE_NAME . "' is not set.";
    }
}

function remove_payments()
{
    $pageview_manager = new PageViewManagerClass();

    if($pageview_manager->RemoveAllUserPayments())
    {
        return 'User payments removed.';
    }
    else
    {
        return 'No user payment found.';
    }
}

/* Add AJAX handlers */
add_action('wp_ajax_bcf_payperpage_process_ajax_get_payment_data',          'BCF_PayPerPage\AjaxGetPaymentData');
add_action('wp_ajax_nopriv_bcf_payperpage_process_ajax_get_payment_data',   'BCF_PayPerPage\AjaxGetPaymentData');

add_action('wp_ajax_bcf_payperpage_load_rest_of_content',                   'BCF_PayPerPage\LoadRestOfContent');
add_action('wp_ajax_nopriv_bcf_payperpage_load_rest_of_content',            'BCF_PayPerPage\LoadRestOfContent');

add_action('wp_ajax_send_payment_cheque',                                   'BCF_PayPerPage\ProcessAjaxReceiveCheque');
add_action('wp_ajax_nopriv_send_payment_cheque',                            'BCF_PayPerPage\ProcessAjaxReceiveCheque');

add_action('wp_ajax_bcf_payperpage_process_ajax_pay_status',                'BCF_PayPerPage\ProcessAjaxPayStatus');
add_action('wp_ajax_nopriv_bcf_payperpage_process_ajax_pay_status',         'BCF_PayPerPage\ProcessAjaxPayStatus');

/* Add handlers */
add_action('init', 'BCF_PayPerPage\Init');
add_action('admin_menu', 'BCF_PayPerPage\AdminMenu');

/* Add filters */
add_filter( 'the_content', 'BCF_PayPerPage\FilterContent');

/* Add shortcodes */
add_shortcode( 'testingpage', 'BCF_PayPerPage\testingpage' );
add_shortcode( 'remove_payments', 'BCF_PayPerPage\remove_payments' );

register_activation_hook(__FILE__, 'BCF_PayPerPage\ActivatePlugin');
register_deactivation_hook(__FILE__, 'BCF_PayPerPage\DeactivatePlugin');

?>

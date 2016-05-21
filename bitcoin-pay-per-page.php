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


define ('BCF_PAYPAGE_OPTION_REQ_COUNTER',       'bcf_paypage_option_req_counter');
define ('BCF_PAYPAGE_OPTION_SALES_COUNTER',     'bcf_paypage_option_sales_counter');
define ('BCF_PAYPAGE_OPTION_COOCKIE_COUNTER',   'bcf_paypage_option_coockie_counter');

define ('BCF_PAYPAGE_PAYMENT_OPTIONS',          'bcf_payperpage_payment_options');
define ('BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS', 'bcf_payperpage_recommended_bank_options');
define ('BCF_PAYPAGE_ADVANCED_OPTIONS',         'bcf_payperpage_advanced_options');

define ('BCF_PAYPAGE_REQUIRE_PAYMENT_TAG', '[require_payment]');



function SanitizeInputText($text)
{
    $text = str_replace('<', '&lt;', $text);
    $text = str_replace('>', '&gt;', $text);

    return $text;
}

function SanitizeInputInteger($text)
{
    $value = intval($text);
    return $value;
}


function ValidateCheque($cheque)
{
    $result = 'Error';
    error_log('****** validate_cheque ******');

    error_log($cheque);

    $collect_url = $cheque['collect_url'];
    $collect_url .= '&cheque=';

    $json = json_encode($cheque);
    $json_encoded = rawurlencode($json);

    $api_url = $collect_url . $json_encoded;

    error_log($api_url);

    $api_response = wp_remote_get( $api_url );
    if(wp_remote_retrieve_response_code($api_response) == 200)
    {
        $json = wp_remote_retrieve_body( $api_response );

        if(empty($json))
        {
            echo 'JSON object empty<br>';
        }
        else
        {
            echo $json . '<br>';
            $json = json_decode($json, true);
            if($json)
            {
                if($json['status'] == 'VALID')
                {
                    $result = 'VALID';
                }
                else
                {
                    $result = $json['errors'];
                }
            }
        }
    }

    return $result;
}

function GetPaymentRequest($ref)
{
    $payment_support = $_SERVER["HTTP_PAYMENT_APP"];

    if($payment_support != 1)
    {
        $options = get_option(BCF_PAYPAGE_PAYMENT_OPTIONS);
        $wallet_address = $options['wallet_address'];

        if($wallet_address == '')
        {
            $payment_url = "No wallet address specified in admin setting.";
        }
        else
        {
            $options = get_option(BCF_PAYPAGE_ADVANCED_OPTIONS);
            $ajax_handler = $options['ajax_handler'];

            $payment_info_link = site_url() . $ajax_handler . '?action=bcf_payperpage_process_ajax_get_payment_data&ref='. $ref;

            $href = 'bitcoin:' . $wallet_address . '?request=' . $payment_info_link;

            $payment_url = '<a id="bcf_paylink1" href=' . $href . ' class="bitcoin-address">' . $wallet_address . '</a>';
        }
    }
    else
    {
        $options = get_option(BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS);
        $help_text = $options['help_text'];
        $bank_url = $options['bank_url'];

        $payment_url = '<a class="bcf_test" href="' . $bank_url . '">' . $help_text . '</a>';
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
                'post_id_ref'       => intval($ref)
            );
            wp_localize_script('bcf_payperpage_script_handler', 'bcf_demo_script_handler_vars', $translation_array);

            $content = substr ($content , 0 , $position);

            $content .= '<div id="bcf_remaining_content">';

            $content .= '<br><b>To read the rest of the article, please pay ' . $price->GetFormattedCurrencyString($BTC_denominator, true) . ' to this address:</b>';
            $content .= '<br>';
            $content .= GetPaymentRequest($ref);
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
    $pageview_ref = SanitizeInputInteger($_REQUEST['post_id']);

    if($pageview_ref >= 0)
    {
        $pageview_id = new PageViewIdTypeClass($pageview_ref);

        if(!is_null($pageview_id))
        {
            $pageview_manager = new PageViewManagerClass();

            $post_id = $pageview_manager->HasUserPaidForThisPageView($pageview_id);

            if(!is_null($post_id))
            {
                $post_id_val = $post_id->GetInt();

                $post = get_post($post_id_val);
                $content = $post->post_content;

                $position = strpos ($content, BCF_PAYPAGE_REQUIRE_PAYMENT_TAG) + strlen(BCF_PAYPAGE_REQUIRE_PAYMENT_TAG);

                $content = substr ($content , $position );

                $content2 = str_replace("\r\n", '<p>', $content);

                echo $content2;
            }
            else
            {
                echo 'No payment verified.';
            }
        }
    }

    die();
}

function ProcessAjaxPayStatus()
{
    $request_counter = get_option(BCF_PAYPAGE_OPTION_REQ_COUNTER);
    $sales_counter = get_option(BCF_PAYPAGE_OPTION_SALES_COUNTER);

    if($sales_counter == 0)
    {
        /* Waiting for payment */
        $data = array(
            'pay_status' => 'WAIT',
            'request_counter' => strval($request_counter),
            'sales_counter' => strval($sales_counter),
        );
    }
    else if($sales_counter == 1)
    {
        /* Cheque payment confirmed ok */
        $data = array(
            'pay_status' => 'OK',
            'request_counter' => strval($request_counter),
            'sales_counter' => strval($sales_counter),
        );
    }
    else if($sales_counter == 2)
    {
        /* Cheque payment invalid */
        $data = array(
            'pay_status' => 'INVALID',
            'request_counter' => strval($request_counter),
            'sales_counter' => strval($sales_counter)
        );
    }

    echo json_encode($data);

    $request_counter++;
    update_option(BCF_PAYPAGE_OPTION_REQ_COUNTER, $request_counter);

    die();
}

function ProcessAjaxReceiveCheque()
{
    $cheque_json = SanitizeInputText($_REQUEST['cheque']);
    $cheque_json = html_entity_decode($cheque_json);
    $cheque_json = str_replace ('\"', '"', $cheque_json);
    $cheque = json_decode($cheque_json, true);

    $request_counter = get_option(BCF_PAYPAGE_OPTION_REQ_COUNTER);
    $sales_counter = get_option(BCF_PAYPAGE_OPTION_SALES_COUNTER);

    $cheque_is_valid = ValidateCheque($cheque);

    if($cheque_is_valid == 'VALID')
    {
        $pageview_id_val = intval($cheque['receiver_reference']);
        $pageview_manager = new PageViewManagerClass();
        $pageview_id = new PageViewIdTypeClass($pageview_id_val);
        $pageview_manager->SetPagePaid($pageview_id);
        
        $data = array(
            'pay_status' => 'OK',
            'request_counter' => strval($request_counter),
            'sales_counter' => strval($sales_counter)
        );
        $sales_counter = 1;
    }
    else
    {
        $data = array(
            'pay_status' => 'INVALID',
            'request_counter' => strval($request_counter),
            'sales_counter' => strval($sales_counter)
        );

        $sales_counter = 2;
    }

    echo json_encode($data);

    update_option(BCF_PAYPAGE_OPTION_SALES_COUNTER, $sales_counter);

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

        $price = $pageview->GetPrice();

        $data = array(
            'amount'    => $price->GetString(),
            'ref'       => $ref,
            'paylink'   => site_url() . $ajax_handler . '?action=bcf_payperpage_process_ajax_send_cheque'
        );

        echo json_encode($data);
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
    echo '<input id="bcf_payperpage_default_price" name="' . BCF_PAYPAGE_PAYMENT_OPTIONS . '[default_price]" type="text" value="' . $selected . '" />';
}

function AdminDrawSettingsBtcUnitsOption()
{
    $BTC_units = array('BTC', 'mBTC', 'uBTC');
    $options = get_option(BCF_PAYPAGE_PAYMENT_OPTIONS);

    echo '<select id="BTC_denominator" name="' . BCF_PAYPAGE_PAYMENT_OPTIONS . '[btc_unit]">';
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
    $options = get_option(BCF_PAYPAGE_PAYMENT_OPTIONS);
    $selected = $options['wallet_address'];
    echo '<textarea rows="1" cols="50" name="' . BCF_PAYPAGE_PAYMENT_OPTIONS . '[wallet_address]" type="text">'.$selected.'</textarea>';
    //echo '<input id="bcf_payperpage_wallet_address" name="' . BCF_PAYPAGE_PAYMENT_OPTIONS . '[wallet_address]" type="text" value="' . $selected . '" />';
}

function AdminDrawSettingsRecommendedBank()
{
    $options = get_option(BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS);
    $selected = $options['bank_url'];
    echo '<textarea rows="1" cols="50" id="bcf_payperpage_recommended_bank" name="' . BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS . '[bank_url]" type="text">'.$selected.'</textarea>';
}

function AdminDrawSettingsRecommendedBankHelp()
{
    $options = get_option(BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS);
    $selected = $options['help_text'];
    echo '<textarea rows="4" cols="50" id="bcf_payperpage_recommended_bank_help_text" name="' . BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS . '[help_text]" type="text">'.$selected.'</textarea>';
}

function AdminDrawSettingsAjaxHandler()
{
    $options = get_option(BCF_PAYPAGE_ADVANCED_OPTIONS);
    $selected = $options['ajax_handler'];
    $inp =  '<textarea rows="1" cols="50" id="bcf_payperpage_ajax_handler" name="' . BCF_PAYPAGE_ADVANCED_OPTIONS . '[ajax_handler]" type="text">'.$selected.'</textarea>';
    echo $inp;
}


function AdminDrawPaymentSettingsHelpText()
{
    echo 'Payment settings';
}

function AdminDrawSettingsHelpRecommendedBank()
{
    echo 'In case the user has no Banking App installed, he will be directed to this bank where he can sign uf for an account and download an Banking App.';
}

function AdminDrawSettingsHelpAdvancedSettings()
{
    echo 'Advanced settings.';
}


function AdminMenu()
{
    register_setting(BCF_PAYPAGE_PAYMENT_OPTIONS, BCF_PAYPAGE_PAYMENT_OPTIONS);
    register_setting(BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS, BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS);
    register_setting(BCF_PAYPAGE_ADVANCED_OPTIONS, BCF_PAYPAGE_ADVANCED_OPTIONS);

    add_settings_section(
        'settings_section_payment_option_tag',
        'Payment Settings',
        'BCF_PayPerPage\AdminDrawPaymentSettingsHelpText',
        'settings_section_payment_option'
    );
    add_settings_field(
        'bcf_payperpage_settings_price',
        'Default price per page view:',
        '\BCF_PayPerPage\AdminDrawSettingsDefaultPrice',
        'settings_section_payment_option',
        'settings_section_payment_option_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_btc_denominator',
        'Price unit:', '\BCF_PayPerPage\AdminDrawSettingsBtcUnitsOption',
        'settings_section_payment_option',
        'settings_section_payment_option_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_wallet',
        'Receive Bitcoin Wallet address for payments:',
        '\BCF_PayPerPage\AdminDrawSettingsWalletAddress',
        'settings_section_payment_option',
        'settings_section_payment_option_tag'
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
        'URL to recommended Bitcoin Bank:',
        '\BCF_PayPerPage\AdminDrawSettingsAjaxHandler',
        'settings_section_advanced_settings',
        'settings_section_advanced_options_tag'
    );

    add_menu_page('Pay-Per-Page Menu', 'Plugin Settings', 'manage_options', __FILE__, 'BCF_PayPerPage\AdminPage');
    add_submenu_page(__FILE__, 'Payment Status', 'Payment Status', 'manage_options', __FILE__.'about', 'BCF_PayPerPage\AdminPaymentStatus');
}

function ActivatePlugin()
{
    add_option( BCF_PAYPAGE_OPTION_COOCKIE_COUNTER, 0 );

    add_option (BCF_PAYPAGE_PAYMENT_OPTIONS, array(
            'default_price' => '1000',
            'btc_unit' => 'mBTC',
            'wallet_address' => ''
    ));

    add_option (BCF_PAYPAGE_RECOMMENDED_BANK_OPTIONS, array(
        'bank_url' => 'http://localhost/wordpress/',
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

/* Add AJAX handlers */
add_action('wp_ajax_bcf_payperpage_process_ajax_get_payment_data',          'BCF_PayPerPage\AjaxGetPaymentData');
add_action('wp_ajax_nopriv_bcf_payperpage_process_ajax_get_payment_data',   'BCF_PayPerPage\AjaxGetPaymentData');

add_action('wp_ajax_bcf_payperpage_load_rest_of_content',                   'BCF_PayPerPage\LoadRestOfContent');
add_action('wp_ajax_nopriv_bcf_payperpage_load_rest_of_content',            'BCF_PayPerPage\LoadRestOfContent');

add_action('wp_ajax_bcf_payperpage_process_ajax_send_cheque',               'BCF_PayPerPage\ProcessAjaxReceiveCheque');
add_action('wp_ajax_nopriv_bcf_payperpage_process_ajax_send_cheque',        'BCF_PayPerPage\ProcessAjaxReceiveCheque');

add_action('wp_ajax_bcf_payperpage_process_ajax_pay_status',                'BCF_PayPerPage\ProcessAjaxPayStatus');
add_action('wp_ajax_nopriv_bcf_payperpage_process_ajax_pay_status',         'BCF_PayPerPage\ProcessAjaxPayStatus');

/* Add handlers */
add_action('init', 'BCF_PayPerPage\Init');
add_action('admin_menu', 'BCF_PayPerPage\AdminMenu');

/* Add filters */
add_filter( 'the_content', 'BCF_PayPerPage\FilterContent');

/* Add shortcodes */
add_shortcode( 'testingpage', 'BCF_PayPerPage\testingpage' );

register_activation_hook(__FILE__, 'BCF_PayPerPage\ActivatePlugin');
register_deactivation_hook(__FILE__, 'BCF_PayPerPage\DeactivatePlugin');

?>

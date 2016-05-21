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


define ('BCF_PAYPAGE_OPTION_REQ_COUNTER', 'bcf_paypage_option_req_counter');
define ('BCF_PAYPAGE_OPTION_SALES_COUNTER', 'bcf_paypage_option_sales_counter');
define ('BCF_PAYPAGE_OPTION_COOCKIE_COUNTER', 'bcf_paypage_option_coockie_counter');

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
        $wallet_address = '1Q66D5mFm278drW7RbguqT8H8khTzMfMsh';

        $payment_info_link = site_url() . '/wp-admin/admin-ajax.php?action=bcf_payperpage_process_ajax_get_payment_data&ref='. $ref;

        $href = 'bitcoin:' . $wallet_address . '?request=' . $payment_info_link;

        $payment_url = '<a id="bcf_paylink1" href=' . $href . ' class="bitcoin-address">' . $wallet_address . '</a>';
    }
    else
    {
        $payment_url = '<a class="bcf_test" href="http://bitcoincheque.org">No Payment App detected. Get one at BitcoinCheque.org</a>';
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
            $price = new ValueTypeClass(762);

            $ref = $pageview_manager->RegisterNewPageView($post_id, $price);

            $url_to_my_site = site_url() . '/wp-admin/admin-ajax.php';

            $translation_array = array(
                'url_to_my_site'    => $url_to_my_site,
                'post_id_ref'       => intval($ref)
            );
            wp_localize_script('bcf_demo_script_handler', 'bcf_demo_script_handler_vars', $translation_array);

            $content = substr ($content , 0 , $position);

            $content .= '<div id="bcf_remaining_content">';

            $content .= '<br><b>To read the rest of the article, please pay ' . $price->GetFormattedCurrencyString('BTC', true) . ' to this address:</b>';
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
        $price = $pageview->GetPrice();

        $data = array(
            'amount'    => $price->GetString(),
            'ref'       => $ref,
            'paylink'   => site_url() . '/wp-admin/admin-ajax.php?action=bcf_payperpage_process_ajax_send_cheque'
        );

        echo json_encode($data);
    }

    die();
}

function ActivatePlugin()
{
    add_option( BCF_PAYPAGE_OPTION_REQ_COUNTER, 0 );
    add_option( BCF_PAYPAGE_OPTION_SALES_COUNTER, 0 );
    add_option( BCF_PAYPAGE_OPTION_COOCKIE_COUNTER, 0 );

    DB_CreateOrUpdateDatabaseTables();
}


function DeactivatePlugin()
{
    delete_option( BCF_PAYPAGE_OPTION_REQ_COUNTER );
    delete_option( BCF_PAYPAGE_OPTION_SALES_COUNTER );
}

function AddScript()
{
    $src = plugin_dir_url( __FILE__ ) . 'js/script.js';
    wp_enqueue_script('bcf_demo_script_handler', $src, array( 'jquery' ), '0.1', true);

    if(!isset($_COOKIE[BCF_PAYPAGE_OPTION_COOKIE_NAME]))
    {
        $coockie_counter = get_option(BCF_PAYPAGE_OPTION_COOCKIE_COUNTER);
        $coockie_counter += 1;
        update_option(BCF_PAYPAGE_OPTION_COOCKIE_COUNTER, $coockie_counter);

        $seconds = 300;
        $expire = time() + $seconds;

        if(setcookie(BCF_PAYPAGE_OPTION_COOKIE_NAME, $coockie_counter, $expire, COOKIEPATH, COOKIE_DOMAIN) == true)
        {
            //echo 'Set coockie: ' . BCF_PAYPAGE_OPTION_COOKIE_NAME . ' = ' . $coockie_counter . ' (Expire ' . $seconds . ' sec.)';
        }
    }
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
add_action('init', 'BCF_PayPerPage\AddScript');

/* Add filters */
add_filter( 'the_content', 'BCF_PayPerPage\FilterContent');

/* Add shortcodes */
add_shortcode( 'testingpage', 'BCF_PayPerPage\testingpage' );

register_activation_hook(__FILE__, 'BCF_PayPerPage\ActivatePlugin');
register_deactivation_hook(__FILE__, 'BCF_PayPerPage\DeactivatePlugin');

?>

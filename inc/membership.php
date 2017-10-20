<?php
/**
 * Created by PhpStorm.
 * User: Arild
 * Date: 13.11.2016
 * Time: 00:49
 */


namespace BCF_PayPerPage;

define ('BCF_PAYPAGE_REGISTRATION_COOKIE_NAME', 'payperpage_registration');

define ('REG_AJAX_ACTION', 'pppc_membership_event');

define ('NONCE_LENGTH', 10);
define ('SECRET_LENGTH', 20);

require_once ('membership_interface.php');
require_once ('membership_admin.php');
require_once ('membership_reg_admin.php');
require_once('statistics_data.php');
require_once ('util.php');
require_once ('email.php');


function MembershipInit()
{
    MembershipInstallCookie();

    $action = SafeReadPostString('action');

    if($action == REG_AJAX_ACTION)
    {
        switch(SafeReadPostString(REG_EVENT))
        {
            case REG_EVENT_LOGIN:
                $username = SafeReadPostString(REG_USERNAME);
                $password = SafeReadPostString(REG_PASSWORD);
                $remember = SafeReadPostInt(REG_REMEMBER);

                MembershipLogInUser($username, $password, $remember);
                break;

            case REG_EVENT_LOGOUT:
                MembershipLogOutUser();
                break;
        }
    }
    else
    {
        $reg_type = SafeReadGetInt(REG_TYPE);
        $event = SafeReadGetString(REG_EVENT);

        switch($reg_type)
        {
            case MembershipRegistrationDataClass::REG_TYPE_READ_MORE_REGISTRATION:
                switch($event)
                {
                    case REG_EVENT_CONFIRM_EMAIL:
                        $reg_id     = SafeReadGetInt(REG_ID);
                        $post_id    = SafeReadGetInt(REG_POST_ID);
                        $nonce      = SafeReadGetString(REG_NONCE);
                        $secret     = SafeReadGetString(REG_SECRET);

                        $register_handler = new RegistrationHandlerClass(
                            $reg_id,
                            $reg_type,
                            $post_id,
                            $nonce,
                            $secret
                        );

                        if($register_handler->HasUserData())
                        {
                            $result = $register_handler->ConfirmEmail();

                            switch ($result)
                            {
                                case RegistrationHandlerClass::RESULT_OK:
                                    if ($register_handler->HasAllRequiredInfo())
                                    {
                                        if (!$register_handler->UserExist())
                                        {
                                            if (!$register_handler->EmailExist())
                                            {
                                                if ($register_handler->CreateNewUser())
                                                {
                                                    $register_handler->LogInRegisteredUser();
                                                }
                                            }
                                        }
                                    }
                                    break;

                                case RegistrationHandlerClass::RESULT_CONFIRM_IS_DONE:
                                    $register_handler->LogInRegisteredUser();
                                    break;
                            }
                        }
                        break;
                }
                break;
        }
    }

    wp_logout_url( home_url());
}

function MembershipRegisterForm()
{
    MembershipPrepareAjaxAndStyle();

    $reg_id                     = SafeReadGetInt(REG_ID);
    $reg_type                   = SafeReadGetInt(REG_TYPE);
    $nonce                      = SafeReadGetString(REG_NONCE);
    $input_data[REG_SECRET]     = SafeReadGetString(REG_SECRET);
    $input_data[REG_EVENT]      = SafeReadGetString(REG_EVENT);

    $input_data[REG_POST_ID] = get_the_ID();

    $register_interface = new RegistrationInterfaceClass($reg_id, $reg_type, $input_data[REG_POST_ID], $nonce, $input_data[REG_SECRET]);

    return $register_interface->CreateRegisterForm($input_data);
}

function MembershipLogin()
{
    MembershipPrepareAjaxAndStyle();

    $register_interface = new RegistrationInterfaceClass();

    $texts = array();
    $texts['error_message'] = SafeReadPostString(REG_ERROR_MSG);

    return $register_interface->CreateLoginForm($texts);
}

function ProfileForm()
{
    $texts = array();

    if(is_user_logged_in())
    {
        $action = SafeReadPostString('action');
        $event = SafeReadPostString(REG_EVENT);
        $firstname = SafeReadPostString(REG_FIRSTNAME);
        $lastname = SafeReadPostString(REG_LASTNAME);
        $password = SafeReadPostString(REG_PASSWORD);
        $password2 = SafeReadPostString(REG_CONFIRM_PW);
        $email = SafeReadPostString(REG_EMAIL);

        if($action == REG_AJAX_ACTION and $event == REG_EVENT_UPDATE_PROFILE)
        {
            $user_id = get_current_user_id();

            if($password != $password2)
            {
                $password = null;
            }

            $userdata = array(
                'ID' => $user_id,
                'first_name' => $firstname,
                'last_name' => $lastname,
                'user_pass' => $password,
                'user_email' => $email
            );

            $user_id = wp_update_user($userdata);

            if ( is_wp_error( $user_id ) ) {
                $texts[TEXT_FIELD_ERROR_MSG] = 'Error updating profile data.';
            } else {
                $texts[TEXT_FIELD_SUCCESS_MSG] = 'Profile data successfully updated.';
            }
        }
    }

    MembershipPrepareAjaxAndStyle();

    $register_interface = new RegistrationInterfaceClass();

    return $register_interface->CreateProfileForm($texts);
}

function PasswordResetForm()
{
    $input_data[REG_EVENT]      = SafeReadPostString(REG_EVENT);
    if($input_data[REG_EVENT])
    {
        /* Post method for sending new password */
        $reg_id                       = SafeReadPostInt(REG_ID);
        $nonce                        = SafeReadPostString(REG_NONCE);
        $input_data[ REG_EMAIL ]      = SafeReadPostString(REG_EMAIL);
        $input_data[ REG_PASSWORD ]   = SafeReadPostString(REG_PASSWORD);
        $input_data[ REG_CONFIRM_PW ] = SafeReadPostString(REG_CONFIRM_PW);
    }
    else
    {
        /* Get method from e-mail link */
        $reg_id                       = SafeReadGetInt(REG_ID);
        $nonce                        = SafeReadGetString(REG_NONCE);
        $input_data[REG_SECRET]       = SafeReadGetString(REG_SECRET);
        $input_data[REG_EVENT]        = SafeReadGetString(REG_EVENT);

        $input_data[REG_TYPE]         = MembershipRegistrationDataClass::REG_TYPE_PASSWORD_RECOVERY;
    }

    $post_id = get_the_ID();

    $register_interface = new RegistrationInterfaceClass(
        $reg_id,
        MembershipRegistrationDataClass::REG_TYPE_PASSWORD_RECOVERY,
        $post_id,
        $nonce,
        $input_data[REG_SECRET]
    );

    MembershipPrepareAjaxAndStyle();

    return $register_interface->CreatePasswordResetForm($input_data);
}

function MembershipLogInUser($username, $password, $remember)
{
    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember
    );

    $user = wp_signon($creds, false);

    if( ! is_wp_error($user))
    {
        $user_id = $user->ID;
        wp_set_current_user($user_id, $username);
        wp_set_auth_cookie($user_id);
        do_action('wp_login', $username);
    }
    else
    {
        $_POST[REG_ERROR_MSG] = 'Error. Invalid username or password.';
    }
}

function MembershipLogOutUser()
{
    wp_logout();
    wp_set_current_user(0);
}

function MembershipPrepareAjaxAndStyle($send_js_data=array())
{
    $options      = get_option(BCF_PAYPAGE_ADVANCED_OPTIONS);
    $ajax_handler = $options['ajax_handler'];

    $url_to_my_site = site_url() . $ajax_handler;

    $data_array = array(
        'url_to_my_site' => $url_to_my_site
    );

    if($send_js_data)
    {
        $data_array = array_merge($data_array, $send_js_data);
    }

    wp_localize_script('bcf_payperpage_script_handler', 'pppc_script_handler_vars', $data_array);

    $style_url = plugins_url() . '/bitcoin-pay-per-page-wordpress-plugin/css/pppc_style.css';

    wp_enqueue_style('pppc_style', $style_url);
}

function MembershipInstallCookie()
{
    if(!isset($_COOKIE[BCF_PAYPAGE_REGISTRATION_COOKIE_NAME]))
    {
        $cookie = MembershipRandomString(NONCE_LENGTH);
        $seconds = 30 * 24 * 3600;  // Cookie live for 30 days
        $expire = time() + $seconds;

        if(setcookie(BCF_PAYPAGE_REGISTRATION_COOKIE_NAME, $cookie, $expire, COOKIEPATH, COOKIE_DOMAIN) != true)
        {
            die();
        }
    }
}

function MembershipGetCookie()
{
    return SafeReadCookieString(BCF_PAYPAGE_REGISTRATION_COOKIE_NAME);
}

function MembershipRandomString($length)
{
    $str='';

    for($i=0; $i<$length; $i++)
    {
        $x = mt_rand (0, 61);
        if($x < 10)
        {
            $y = strval($x);
        }
        elseif($x < 36)
        {
            $y = chr(ord('a') + $x - 10);
        }
        else
        {
            $y = chr(ord('A') + $x - 36);
        }
        $str .= $y;
    }

    return $str;
}

function AjaxHandler()
{
    $reg_id                     = SafeReadPostInt(REG_ID);
    $input_data[REG_TYPE]       = SafeReadPostInt(REG_TYPE);
    $nonce                      = SafeReadPostString(REG_NONCE);
    $secret                     = SafeReadPostString(REG_SECRET);
    $input_data[REG_POST_ID]    = SafeReadPostInt(REG_POST_ID);
    $input_data[REG_EVENT]      = SafeReadPostString(REG_EVENT);
    $input_data[REG_USERNAME]   = SafeReadPostString(REG_USERNAME);
    $input_data[REG_FIRSTNAME]  = SafeReadPostString(REG_FIRSTNAME);
    $input_data[REG_LASTNAME]   = SafeReadPostString(REG_LASTNAME);
    $input_data[REG_PASSWORD]   = SafeReadPostString(REG_PASSWORD);
    $input_data[REG_CONFIRM_PW] = SafeReadPostString(REG_CONFIRM_PW);
    $input_data[REG_EMAIL]      = SafeReadPostString(REG_EMAIL);
    $input_data[REG_REMEMBER]   = SafeReadPostBool(REG_REMEMBER);
    $input_data[REG_ACCEPT_TERMS]= SafeReadPostBool(REG_ACCEPT_TERMS);

    $register_interface = new RegistrationInterfaceClass($reg_id, $input_data[REG_TYPE], $input_data[REG_POST_ID], $nonce, $secret);
    $response_data = $register_interface->EventHandler($input_data);

    if($response_data[REG_RESP_STATUS] == REG_RESP_STATUS_LOGGED_IN)
    {
        $response_data['action'] = 'load_remaining_content';

        $nonce_string = 'bcf_payperpage_load_rest_of_content-' . strval($input_data[REG_POST_ID]);
        $wp_nonce = wp_create_nonce($nonce_string);
        $send_js_data['wp_nonce'] = $wp_nonce;
        $send_js_data['postid'] = get_the_ID();
        MembershipPrepareAjaxAndStyle($send_js_data);
    }

    echo json_encode($response_data);
    die();
}

function ScheduleEvent()
{
    WriteDebugNote('ScheduleEvent.');

    $timeout = 720*60*60;  // Keep the records for 720 hours = 30 days.

    global $wpdb;
    $prefixed_table_name = $wpdb->prefix . 'bcf_payperpage_registration';
    $sql = "SELECT * FROM " . $prefixed_table_name;
    $record_list = $wpdb->get_results($sql, ARRAY_A);
    foreach($record_list as $record)
    {
        $reg_rime = $record['timestamp'];
        $reg_rime = strtotime($reg_rime);
        if($reg_rime==false)
        {
            $reg_rime=0;
        }
        $now=time();

        if(($reg_rime + $timeout) < $now)
        {
            $sql = "DELETE FROM " . $prefixed_table_name . " WHERE registration_id=" . $record['registration_id'];
            $wpdb->get_results($sql, ARRAY_A);
            if ($wpdb->last_error)
            {
                WriteDebugError('Error delete registration record ', $record['registration_id']);
            }
            else
            {
                WriteDebugNote('Delete registration record ', $record['registration_id']);
            }
        }
    }
}

function ActivateMembershipPlugin()
{
    MembershipOptionDefault();

    $registration_data = new MembershipRegistrationDataClass();
    $registration_data->InitTable();

    $statistics_data = new StatisticsDataClass();
    $statistics_data->InitTable();

    if (! wp_next_scheduled ( 'pppc_hourly_event' )) {
        wp_schedule_event(time(), 'hourly', 'pppc_hourly_event');
    }
}

function DeactivateMembershipPlugin()
{
    wp_clear_scheduled_hook('pppc_hourly_event');

}

/* Add AJAX handlers */
add_action('wp_ajax_'.REG_AJAX_ACTION, 'BCF_PayPerPage\AjaxHandler');
add_action('wp_ajax_nopriv_'.REG_AJAX_ACTION, 'BCF_PayPerPage\AjaxHandler');

add_action('pppc_hourly_event', 'BCF_PayPerPage\ScheduleEvent');

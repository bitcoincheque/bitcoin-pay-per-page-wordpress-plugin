<?php
/**
 * Membership registration and log-in library. User interface
 * class with forms ansd AJAX handlers.
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

use MongoDB\Driver\WriteConcern;

require_once('membership_handler.php');

// Constants for AJAX POST and GET requests
define('REG_ID',                    'rid');
define('REG_NONCE',                 'nonce');
define('REG_EVENT',                 'event');
define('REG_USERNAME',              'username');
define('REG_FIRSTNAME',             'firstname');
define('REG_LASTNAME',              'lastname');
define('REG_DISPLAY_NAME',          'dispname');
define('REG_PASSWORD',              'password');
define('REG_CONFIRM_PW',            'confirmpw');
define('REG_REMEMBER',              'remmember');
define('REG_EMAIL',                 'email');
define('REG_POST_ID',               'post_id');
define('REG_ERROR_MSG',             'error_message');
define('REG_SECRET',                'secret');

// Event field values:
define('REG_EVENT_LOGIN',           'login');
define('REG_EVENT_LOGOUT',          'logout');
define('REG_EVENT_REGISTER',        'register');
define('REG_EVENT_RESEND_EMAIL',    'resend_register');
define('REG_EVENT_REGISTER_EMAIL',  'register_email');
define('REG_EVENT_GOTO_LOGIN',      'goto_login');
define('REG_EVENT_CONFIRM_EMAIL',   'confirm_email');
define('REG_EVENT_UPDATE_PROFILE',  'update_profile');
define('REG_EVENT_CHANGE_PASSWORD', 'change_password');
define('REG_EVENT_SEND_RESET_LINK', 'reset_password');
define('REG_EVENT_PASSWORD_LINK',   'password_link');

// Response field constants:
define('REG_RESP_RESULT',           'result');      // Mandatory field
define('REG_RESP_ACTION',           'action');      // Optional field
define('REG_RESP_FORM',             'form');        // Mandatory of REG_RESP_TYPE is set
define('REG_RESP_STATUS',           'status');      // Logged-in status
define('REG_RESP_MSG',              'message');     // Mandatory field

// REG_RESP_RESULT values:
define('REG_RESP_RESULT_OK',        'OK');
define('REG_RESP_RESULT_ERROR',     'ERROR');

// REG_RESP_TYPE values:
define('REG_RESP_ACTION_LOAD_FORM', 'load_form');

// REG_RESP_STATUS values:
define('REG_RESP_STATUS_LOGGED_IN', 'logged_in');
define('REG_RESP_STATUS_LOGGED_OUT','logged_out');

// Form text fields:
define('TEXT_FIELD_HEADER',         'header');
define('TEXT_FIELD_DESCRIPTION',    'description');
define('TEXT_FIELD_GREEN_MSG',      'green_msg');
define('TEXT_FIELD_RED_MSG',        'red_msg');
define('TEXT_FIELD_ERROR_MSG',      'error_message');
define('TEXT_FIELD_SUCCESS_MSG',    'success_message');

// Status line color classes
define('GREEN_MESSAGE_CLASS',       'bcf_pppc_status_good');
define('RED_MESSAGE_CLASS',         'bcf_pppc_status_error');


class RegistrationInterfaceClass extends RegistrationHandlerClass
{
    public function __construct($reg_id=null, $nonce=null)
    {
        parent::__construct($reg_id, $nonce);

    }

    public function CreateLoginForm($texts)
    {
        WriteDebugLogFunctionCall();

        $current_user = wp_get_current_user();
        if ( 0 == $current_user->ID ) {
            WriteDebugNote('Create log-in form');

            $form = $this->GetSimpleLoginFormHtml($texts);
        } else {
            WriteDebugNote('Create log-out form');

            $form = $this->GetSimpleLogoutFormHtml($texts);
        }

        return $form;
    }

    public function CreateProfileForm($texts)
    {
        WriteDebugLogFunctionCall();

        $current_user = wp_get_current_user();
        if ( 0 == $current_user->ID )
        {
            WriteDebugNote('Create log-in form to show profile');

            $form = $this->GetSimpleLoginFormHtml($texts);
        }
        else
        {
            WriteDebugNote('Create profile form');

            $form = $this->CreateProfileFormHtml($texts);
        }

        return $form;
    }

    public function CreatePasswordResetForm($input_data)
    {
        WriteDebugLogFunctionCall();

        if(is_user_logged_in())
        {
            $texts = array();
            $wp_user_id = get_current_user_id();

            if($input_data[REG_EVENT] == REG_EVENT_CHANGE_PASSWORD)
            {
                if($input_data[REG_PASSWORD] == '' and $input_data[REG_CONFIRM_PW] == '')
                {
                    $texts[TEXT_FIELD_ERROR_MSG] = 'Error. No password entered.';
                }
                else if($input_data[REG_PASSWORD] == '' or $input_data[REG_CONFIRM_PW] == '')
                {
                    $texts[TEXT_FIELD_ERROR_MSG] = 'Error. You must enter the the password in both inputs.';
                }
                else if($input_data[REG_PASSWORD] != '' and $input_data[REG_PASSWORD] == $input_data[REG_CONFIRM_PW])
                {
                    wp_set_password( $input_data[REG_PASSWORD], $wp_user_id );

                    $texts[TEXT_FIELD_SUCCESS_MSG] = 'Password successfully updated.';
                }
                else
                {
                    $texts[TEXT_FIELD_ERROR_MSG] = 'Error. Entered passwords do not match.';
                }

            }
            $form = $this->GetChangePasswordFormHtml($texts, $wp_user_id);
        }
        else
        {
            if($input_data[ REG_EVENT ])
            {
                $response_data = $this->EventHandler($input_data);
                $form = $response_data[REG_RESP_FORM];

            }
            else
            {
                /* Create send e-mail to restore password form */
                $form = $this->GetResetPasswordFormHtml();
            }
        }

        return $form;
    }

    public function CreatePostContentForm($texts, $post_id)
    {
        WriteDebugLogFunctionCall();

        $form = $this->GetLoginFormHtml($texts, $post_id);
        return $form;
    }

    public function EventHandler($input_data,  $post_id=null)
    {
        WriteDebugLogFunctionCall();

        $texts = array();
        $ok = false;
        $action = null;
        $form = null;
        $status = REG_RESP_STATUS_LOGGED_OUT;
        $msg = '';

        switch($input_data[REG_EVENT])
        {
            case REG_EVENT_LOGIN:
                if($input_data[REG_USERNAME] && $input_data[REG_PASSWORD] && $input_data[REG_PASSWORD])
                {
                    $creds = array(
                        'user_login'    => $input_data[REG_USERNAME],
                        'user_password' => $input_data[REG_PASSWORD],
                        'remember'      => $input_data[REG_REMEMBER]
                    );

                    $user = wp_signon($creds, false);

                    if(is_wp_error($user))
                    {
                        $msg = 'Error. Wrong username or password.';
                    }
                    else
                    {
                        $ok = true;
                        $status = REG_RESP_STATUS_LOGGED_IN;
                    }
                }
                break;

            case REG_EVENT_REGISTER:
                if($input_data[REG_USERNAME] && $input_data[REG_PASSWORD])
                {
                    $reg_id = $this->RegisterUsernamePassword(
                        $input_data[REG_USERNAME],
                        $input_data[REG_PASSWORD],
                        $input_data[REG_REMEMBER],
                        $input_data[REG_POST_ID]);

                    if($reg_id >= 0)
                    {
                        if($this->HasAllRequiredInfo())
                        {
                            if(!$this->UserExist())
                            {
                                if(!$this->EmailExist())
                                {
                                    if($this->CreateNewUser())
                                    {
                                        if($this->LogInRegisteredUser())
                                        {
                                            $ok     = true;
                                            $status = REG_RESP_STATUS_LOGGED_IN;
                                        }
                                    }
                                }
                                else
                                {
                                    $ok = true;
                                    $action = REG_RESP_ACTION_LOAD_FORM;
                                    $form = $this->GetRegisterEmailFormHtml($texts, $input_data[REG_POST_ID], null, 'Email address already taken. Please select another email address.');
                                }
                            }
                            else
                            {
                                $msg = 'Username already taken. Please select another username.';
                            }
                        }
                        else
                        {
                            $ok = true;
                            $action = REG_RESP_ACTION_LOAD_FORM;
                            $form = $this->GetRegisterEmailFormHtml($texts, $input_data[REG_POST_ID]);
                        }
                    }else{
                        $msg = 'Invalid username or password';
                    }
                }else{
                    $msg = 'Page error. Missing username or password in request.';
                }
                break;

            case REG_EVENT_REGISTER_EMAIL:
                if($input_data[REG_EMAIL])
                {
                    if($this->RegisterEmail($input_data[ REG_EMAIL ]))
                    {
                        $ok = true;
                        $action = REG_RESP_ACTION_LOAD_FORM;
                        $texts[TEXT_FIELD_GREEN_MSG] = 'A verification e-mail has been sent to you. Please check your e-mail.';
                        $form = $this->GetCheckYourEmailFormHtml($texts);
                    }
                    else
                    {
                        $msg = $this->GetErrorMessage();
                        if(!$msg){
                            $msg = 'Error registering e-mail';
                        }
                    }
                }else{
                    $msg = 'Missing e-mail';
                }
                break;

            case REG_EVENT_RESEND_EMAIL:
                $ok = true;
                $action = REG_RESP_ACTION_LOAD_FORM;
                $form = $this->GetRegisterEmailFormHtml($texts, $input_data[REG_POST_ID]);
                break;

            case REG_EVENT_GOTO_LOGIN:
                $ok = true;
                $action = REG_RESP_ACTION_LOAD_FORM;
                $form = $this->GetLoginFormHtml($texts);
                break;

            case REG_EVENT_CONFIRM_EMAIL:
                $visitors_cookie = MembershipGetCookie();
                $my_cookie = $this->GetCookie();
                $same_browser = ($visitors_cookie == $my_cookie);

                $continue_registration = false;

                switch($this->ConfirmEmail($input_data[REG_SECRET]))
                {
                    case RegistrationHandlerClass::RESULT_OK:
                        $continue_registration = true;
                        break;

                    case RegistrationHandlerClass::RESULT_NONCE_ERROR:
                        $ok     = true;
                        $action = REG_RESP_ACTION_LOAD_FORM;
                        $texts[TEXT_FIELD_ERROR_MSG] = 'E-mail verification link error. Log-in or retry register.';
                        $form   = $this->GetLoginFormHtml($texts, $post_id);
                        break;
                    case RegistrationHandlerClass::RESULT_CONFIRM_INVALID_LINK:
                        break;
                    case RegistrationHandlerClass::RESULT_ERROR_UNDEFINED:
                        break;
                    case RegistrationHandlerClass::RESULT_CONFIRM_IS_DONE:
                        $state = $this->GetRegistrationState();
                        if($state == MembershipRegistrationDataClass::STATE_USER_CREATED)
                        {
                            $ok                              = true;
                            $action                          = REG_RESP_ACTION_LOAD_FORM;
                            $texts[ TEXT_FIELD_SUCCESS_MSG ] = 'This e-mail has already been verified. Your can now log-in using your user name and password.';
                            $form                            = $this->GetLoginFormHtml($texts, $post_id);
                        }
                        else if($state == MembershipRegistrationDataClass::STATE_EMAIL_CONFIRMED)
                        {
                            $continue_registration = true;
                        }
                        break;
                    case RegistrationHandlerClass::RESULT_USER_EXIST:
                        $ok     = true;
                        $action = REG_RESP_ACTION_LOAD_FORM;
                        $texts[TEXT_FIELD_GREEN_MSG] = 'This e-mail has been confirmed and user registration has been completed. You can now log-in.';
                        $form   = $this->GetLoginFormHtml($texts, $post_id);
                        break;
                    default:
                        die();
                }

                if($continue_registration)
                {
                    if($this->HasAllRequiredInfo() and $same_browser)
                    {
                        if( ! $this->UserExist())
                        {
                            if( ! $this->EmailExist())
                            {
                                if($this->CreateNewUser())
                                {
                                    $action                        = REG_RESP_ACTION_LOAD_FORM;
                                    $texts[ TEXT_FIELD_GREEN_MSG ] = 'E-mail address confirmed and user account created. You can now log in.';
                                    $form                          = $this->GetLoginFormHtml($texts, $post_id);
                                }
                                else
                                {
                                    $action                      = REG_RESP_ACTION_LOAD_FORM;
                                    $texts[ TEXT_FIELD_RED_MSG ] = 'Error creating new user. You can retry...';
                                    $form                        = $this->GetRegisterEmailFormHtml($texts, $post_id);
                                }
                            }
                            else
                            {
                                $action                      = REG_RESP_ACTION_LOAD_FORM;
                                $texts[ TEXT_FIELD_RED_MSG ] = 'Email address already taken. Please select another email address.';
                                $form                        = $this->GetRegisterEmailFormHtml($texts, $post_id);
                            }
                        }
                        else
                        {
                            $action                      = REG_RESP_ACTION_LOAD_FORM;
                            $texts[ TEXT_FIELD_RED_MSG ] = 'Username already taken. Please select another username.';
                            $form                        = $this->GetLoginFormHtml($texts, $post_id);
                        }
                    }
                    else
                    {
                        $action                          = REG_RESP_ACTION_LOAD_FORM;
                        $texts[ TEXT_FIELD_SUCCESS_MSG ] = 'E-mail verified. Select your username and password.';
                        $form                            = $this->GetLoginFormHtml($texts, $post_id);
                    }
                }
                break;

            case REG_EVENT_SEND_RESET_LINK:
                if( ! $input_data[ REG_EMAIL ])
                {
                    $texts[ TEXT_FIELD_ERROR_MSG ] = 'You must enter your e-mail address.';
                }
                else
                {
                    $wp_user_id = email_exists($input_data[REG_EMAIL]);
                    if($wp_user_id)
                    {
                        if($this->SendEmailResetLink($input_data[ REG_EMAIL ], $wp_user_id))
                        {
                            $texts[ TEXT_FIELD_GREEN_MSG ] = 'E-mail with username and password link is sent to ' . $input_data[ REG_EMAIL ];
                            $form = $this->GetCheckYourEmailFormHtml($texts);

                        }
                        else
                        {
                            $texts[ TEXT_FIELD_ERROR_MSG ] = 'Error sending e-mail. Retry later or contact site admin if problem persists.';
                        }
                    }
                    else
                    {
                        $texts[ TEXT_FIELD_ERROR_MSG ] = 'This e-mail has no user account.';
                    }

                    if(!$form)
                    {
                        $form = $this->GetResetPasswordFormHtml();
                    }
                }

                break;

            case REG_EVENT_PASSWORD_LINK:
                $state = $this->GetRegistrationState();
                if($state == MembershipRegistrationDataClass::STATE_RESET_PASSWD_EMAIL_SENT)
                {
                    if($input_data[REG_SECRET] === $this->GetSecret())
                    {
                        $wp_user_id = $this->GetWpUserId();
                        if($wp_user_id)
                        {
                            $form = $this->GetChangePasswordFormHtml($texts, $wp_user_id);
                        }
                        else
                        {
                            $texts[ TEXT_FIELD_ERROR_MSG ] = 'No user linked to e-mail address.';
                            $form                          = $this->GetResetPasswordFormHtml($texts);
                        }
                    }
                    else
                    {
                        $texts[ TEXT_FIELD_RED_MSG ] = 'This password reset link is invalid. You can try send a new link or contact admin.';
                        /* Create send e-mail to restore password form */
                        $form = $this->GetResetPasswordFormHtml($texts);
                    }
                }
                elseif($state == MembershipRegistrationDataClass::STATE_RESET_PASSWD_DONE)
                {
                    $texts[ TEXT_FIELD_RED_MSG ] = 'This password reset link has been used. You must send a new email to reset pssword again.';
                    $form                          = $this->GetResetPasswordFormHtml($texts);
                }
                elseif($state == MembershipRegistrationDataClass::STATE_RESET_PASSWD_TIMEOUT)
                {
                    $texts[ TEXT_FIELD_ERROR_MSG ] = 'This password reset link has expired.';
                    $form                          = $this->GetResetPasswordFormHtml($texts);
                }
                else
                {
                    $texts[ TEXT_FIELD_ERROR_MSG ] = 'Error in password rest link. Retry or contact server admin.';
                    $form                          = $this->GetResetPasswordFormHtml($texts);
                }
                break;

            case REG_EVENT_CHANGE_PASSWORD:
                if($input_data[REG_PASSWORD] == '' and $input_data[REG_CONFIRM_PW] == '')
                {
                    $texts[TEXT_FIELD_ERROR_MSG] = 'Error. No password entered.';
                }
                else if($input_data[REG_PASSWORD] == '' or $input_data[REG_CONFIRM_PW] == '')
                {
                    $texts[TEXT_FIELD_ERROR_MSG] = 'Error. You must enter the the password in both inputs.';
                }
                else if($input_data[REG_PASSWORD] != '' and $input_data[REG_PASSWORD] == $input_data[REG_CONFIRM_PW])
                {
                    if($this->HasResetPasswordOpen())
                    {
                        if($this->UpdatePassword($input_data[ REG_PASSWORD ]))
                        {
                            $texts[ TEXT_FIELD_SUCCESS_MSG ] = 'Password successfully updated.';
                            $form = $this->GetLoginFormHtml($texts);
                        }
                        else
                        {
                            $texts[ TEXT_FIELD_RED_MSG ] = 'Error changing password.';
                        }
                    }
                    else
                    {
                        $texts[ TEXT_FIELD_RED_MSG ] = 'This password reset link has been used. You must send a new email to reset pssword again.';
                        $form                          = $this->GetResetPasswordFormHtml($texts);
                    }
                }
                else
                {
                    $texts[TEXT_FIELD_ERROR_MSG] = 'Error. Entered passwords do not match.';
                }

                if(!$form)
                {
                    $wp_user_id = $this->GetWpUserId();
                    $form = $this->GetChangePasswordFormHtml($texts, $wp_user_id);
                }
                break;
        }

        $response_data = array();

        if($ok)
        {
            $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
        }else{
            $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_ERROR;

        }

        if($action)
        {
            $response_data[ REG_RESP_ACTION ] = $action;
        }

        if($form)
        {
            $response_data[ REG_RESP_FORM ] = $form;
        }

        $response_data[ REG_RESP_STATUS ] = $status;
        $response_data[ REG_RESP_MSG ] = $msg;

        WriteDebugNote('Result:', $response_data);

        return $response_data;
    }

    public function FormatMessageHtml($msg, $color_code_class)
    {
        if($color_code_class){
            return '<p><span class="' . $color_code_class . '">' . $msg . '</span></p>';
        }else{
            return '<p>' . $msg . '</p>';
        }
    }

    private function FormatFormTopPart($texts, $form_code)
    {
        $login_form = '';

        if(isset($texts[TEXT_FIELD_HEADER]) and $texts[TEXT_FIELD_HEADER])
        {
            $login_form .= '<h2>'.$texts[TEXT_FIELD_HEADER].'</h2>';
        }

        if(isset($texts[TEXT_FIELD_DESCRIPTION]) and $texts[TEXT_FIELD_DESCRIPTION])
        {
            $login_form .= '<p>' . $texts[TEXT_FIELD_DESCRIPTION] . '</p>';
        }

        if(isset($texts[TEXT_FIELD_GREEN_MSG]) and $texts[TEXT_FIELD_GREEN_MSG])
        {
            $login_form .=  $this->FormatMessageHtml($texts[TEXT_FIELD_GREEN_MSG], GREEN_MESSAGE_CLASS);
        }

        if(isset($texts[TEXT_FIELD_RED_MSG]) and $texts[TEXT_FIELD_RED_MSG])
        {
            $login_form .=  $this->FormatMessageHtml($texts[TEXT_FIELD_RED_MSG], RED_MESSAGE_CLASS);
        }

        $login_form .= '<table class="bcf_pppc_table_forms"><tr><td class="bcf_pppc_table_forms" width="400px">';

        $login_form .= '<form';
        if($form_code){
            $login_form .= ' ' . $form_code;
        }
        $login_form .= '>';

        $login_form .= '<table class="bcf_pppc_table_forms" width="100%">';

        return $login_form;
    }

    private function FormatFormEndPart($texts, $hidden_fields, $post_id)
    {
        $html = '</table>';
        if($post_id)
        {
            $html .= '<input id="bcf_pppc_post_id" type="hidden" name="post_id" value="' . $post_id . '" />';
        }
        if($hidden_fields)
        {
            foreach($hidden_fields as $name => $value)
            {
                $html .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
            }
        }

        $reg_id = $this->GetRegId();
        if($reg_id)
        {
            $html .= '<input id="bcf_pppc_reg_id" type="hidden" name="' . REG_ID . '" value="' . $reg_id . '" />';
        }

        $nonce = $this->GetNonce();
        if($reg_id)
        {
            $html .= '<input id="bcf_pppc_nonce" type="hidden" name="' . REG_NONCE . '" value="' . $nonce . '" />';
        }

        $html .= '</form>';
        $html .= '</td><td class="bcf_pppc_table_forms"></td></tr></table>';

        $msg_html = '';
        if(isset($texts[TEXT_FIELD_ERROR_MSG]) and $texts[TEXT_FIELD_ERROR_MSG])
        {
            $msg_html .= $this->FormatMessageHtml($texts[TEXT_FIELD_ERROR_MSG], RED_MESSAGE_CLASS);
        }

        if(isset($texts[TEXT_FIELD_SUCCESS_MSG]) and $texts[TEXT_FIELD_SUCCESS_MSG])
        {
            if($msg_html){
                $msg_html .= '<b>';
            }
            $msg_html = $this->FormatMessageHtml($texts[TEXT_FIELD_SUCCESS_MSG], GREEN_MESSAGE_CLASS);
        }


        $html .= '<p id="bcf_payment_status">'.$msg_html.'</p>';

        return $html;
    }

    public function GetSimpleLoginFormHtml($texts=array())
    {
        WriteDebugLogFunctionCall();

        $link_options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);

        $form_code = 'method="post"';
        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_EVENT => REG_EVENT_LOGIN
        );
        $post_id= null;

        $html = $this->FormatFormTopPart($texts, $form_code);

        $html .= '<tr>';
        $html .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Username:</lable></td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_username" type="text" class="bcf_pppc_text_input" value="" name="username" /></td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Password:</lable></td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_password" type="password" class="bcf_pppc_text_input" value="" name="password" /></td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_cell_form">';
        $html .= '<input type="submit" value="Log in" class="bcf_pppc_button" />';
        $html .= '</td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_forms">';
        $html .= '<a href="' . $link_options['PasswordPageLink'] . '">Forgotten username or password?</a><br>';
        $html .= '<a href="/">Register</a>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= $this->FormatFormEndPart($texts, $hidden_fields, $post_id);

        return $html;
    }

    public function GetSimpleLogoutFormHtml($texts=array())
    {
        WriteDebugLogFunctionCall();

        $link_options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);

        $form_code = 'method="post"';
        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_EVENT => REG_EVENT_LOGOUT
        );
        $post_id= null;

        $current_user = wp_get_current_user();

        $login_form = $this->FormatFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><p>You are now logged in.</p><p>Username: ' . $current_user->user_login . '</p></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form">';
        $login_form .= '<input type="submit" value="Log out" class="bcf_pppc_button" />';
        $login_form .= '</td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms">';
        $login_form .= '<a href="' . $link_options['ProfilePageLink'] . '">Update your profile</a><br>';
        $login_form .= '</td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    public function GetLoginFormHtml($texts=array(), $post_id=null)
    {
        WriteDebugLogFunctionCall();

        $link_options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);

        $form_code = '';
        $hidden_fields = null;
        $texts[TEXT_FIELD_HEADER] = 'Login or register';
        $texts[TEXT_FIELD_DESCRIPTION] = 'Membership required to read the rest of the page. Please log in or register for membership.';

        $login_form = $this->FormatFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Username:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_username" type="text" class="bcf_pppc_text_input" value="" name="username" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_password" type="password" class="bcf_pppc_text_input" value="" name="password" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form">';
        $login_form .= '<input id="bcf_pppc_do_login" type="button" value="Log in" class="bcf_pppc_button" />&nbsp;&nbsp;';
        $login_form .= '<input id="bcf_pppc_do_register" type="button" value="Register" class="bcf_pppc_button" />';
        $login_form .= '</td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a href="' . $link_options['PasswordPageLink'] . '">Forgotten username or password?</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    public function GetRegisterEmailFormHtml($texts=array(), $post_id)
    {
        WriteDebugLogFunctionCall();

        $form_code = '';
        $hidden_fields = null;
        $texts[TEXT_FIELD_HEADER] = 'Register e-mail address';
        $texts[TEXT_FIELD_DESCRIPTION] = 'Enter your e-mail address and verify it. You will receive a verification link.';

        $login_form = $this->FormatFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">E-mail address:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_email" type="text" class="bcf_pppc_text_input" value="" name="email" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_do_register_email" type="button" value="Register" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    public function GetCheckYourEmailFormHtml($texts=array())
    {
        WriteDebugLogFunctionCall();

        $form_code = '';
        $hidden_fields = null;
        $texts[TEXT_FIELD_HEADER] = 'Check your e-mail';
        $post_id= null;

        $login_form = $this->FormatFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_do_resend_email" type="button" value="Resend e-mail" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    public function CreateProfileFormHtml($texts=array())
    {
        WriteDebugLogFunctionCall();

        $form_code = 'method="post"';
        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_EVENT => REG_EVENT_UPDATE_PROFILE
        );
        $post_id= null;

        $current_user = wp_get_current_user();

        $login_form = $this->FormatFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Login username:</lable><br><lable class="bcf_pppc_remark">(Username can not be changed.)</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_firstname" type="text" class="bcf_pppc_text_input" value="' . $current_user->user_login . '" readonly="readonly" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Firstname:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_firstname" type="text" class="bcf_pppc_text_input" value="' . $current_user->first_name . '" name="'.REG_FIRSTNAME.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Lastname:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_lastname" type="text" class="bcf_pppc_text_input" value="' . $current_user->last_name . '" name="'.REG_LASTNAME.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">E-mail:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_email" type="text" class="bcf_pppc_text_input" value="' . $current_user->user_email . '" name="'.REG_EMAIL.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_password" type="text" class="bcf_pppc_text_input" value="" name="'.REG_PASSWORD.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Confirm password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_confirm_pw" type="text" class="bcf_pppc_text_input" value="" name="'.REG_CONFIRM_PW.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_do_update_profile" type="submit" value="Update" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    public function GetChangePasswordFormHtml($texts=array(), $wp_user_id)
    {
        WriteDebugLogFunctionCall();

        $user_info = get_userdata($wp_user_id);

        $form_code = 'method="post"';
        $texts[TEXT_FIELD_DESCRIPTION] = 'Enter your new password.<br>Username: <strong>' . $user_info->user_login . '</strong>';

        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_EVENT => REG_EVENT_CHANGE_PASSWORD
        );
        $post_id= null;

        $login_form = $this->FormatFormTopPart($texts, $form_code);
        $login_form .= '<tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_password" type="text" class="bcf_pppc_text_input" value="" name="'.REG_PASSWORD.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Confirm password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_confirm_pw" type="text" class="bcf_pppc_text_input" value="" name="'.REG_CONFIRM_PW.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_do_update_profile" type="submit" value="Update" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    public function GetResetPasswordFormHtml($texts=array())
    {
        WriteDebugLogFunctionCall();

        $form_code = 'method="post"';
        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_EVENT => REG_EVENT_SEND_RESET_LINK
        );
        $post_id= null;
        $texts[TEXT_FIELD_DESCRIPTION] = 'Enter your e-mail address and we will send you a new password.';

        $login_form = $this->FormatFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">E-mail address:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_email" type="text" class="bcf_pppc_text_input" value="" name="email" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_do_reset_password" type="submit" value="Send e-mail" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

}
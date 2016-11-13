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

require_once('membership_handler.php');

// Constants for AJAX POST and GET requests
define('REG_ID',                    'rid');
define('REG_NONCE',                 'nonce');
define('REG_EVENT',                 'event');
define('REG_USERNAME',              'username');
define('REG_PASSWORD',              'password');
define('REG_REMEMBER',              'remmember');
define('REG_EMAIL',                 'email');
define('REG_POST_ID',               'post_id');

// Event field values:
define('REG_EVENT_LOGIN',           'login');
define('REG_EVENT_REGISTER',        'register');
define('REG_EVENT_RESEND_EMAIL',    'resend_register');
define('REG_EVENT_REGISTER_EMAIL',  'register_email');
define('REG_EVENT_GOTO_LOGIN',      'goto_login');
define('REG_EVENT_CONFIRM_EMAIL',   'confirm_email');

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

// Status line color classes
define('GREEN_MESSAGE_CLASS',       'bcf_pppc_status_good');
define('RED_MESSAGE_CLASS',         'bcf_pppc_status_error');


class RegistrationInterfaceClass extends RegistrationHandlerClass
{
    public function __construct($reg_id=null, $nonce=null)
    {
        parent::__construct($reg_id, $nonce);

    }

    public function CreateForm($type, $nonce)
    {
        $form = $this->GetLoginFormHtml();
        return $form;
    }

    public function CreatePostContentForm($type, $nonce, $post_id)
    {
        $form = $this->GetLoginFormHtml($post_id);
        return $form;
    }

    public function EventHandler($input_data,  $post_id=null)
    {
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
                                        if($this->LogInUser())
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
                                    $form = $this->GetRegisterEmailFormHtml($input_data[REG_POST_ID], null, 'Email address already taken. Please select another email address.');
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
                            $form = $this->GetRegisterEmailFormHtml($input_data[REG_POST_ID]);
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
                        $form = $this->GetVerifyEmailFormHtml($input_data[REG_POST_ID]);
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
                $form = $this->GetRegisterEmailFormHtml($input_data[REG_POST_ID]);
                break;

            case REG_EVENT_GOTO_LOGIN:
                $ok = true;
                $action = REG_RESP_ACTION_LOAD_FORM;
                $form = $this->GetLoginFormHtml();
                break;

            case REG_EVENT_CONFIRM_EMAIL:
                $visitors_cookie = MembershipGetCookie();
                $my_cookie = $this->GetCookie();
                $same_browser = ($visitors_cookie == $my_cookie);

                switch($this->ConfirmEmail($input_data[REG_NONCE]))
                {
                    case RegistrationHandlerClass::RESULT_OK:
                        if($this->HasAllRequiredInfo() and $same_browser)
                        {
                            if(!$this->UserExist())
                            {
                                if(!$this->EmailExist())
                                {
                                    if($this->CreateNewUser())
                                    {
                                        $action = REG_RESP_ACTION_LOAD_FORM;
                                        $form   = $this->GetLoginFormHtml($post_id, 'E-mail address confirmed created. You can now Log-in.');
                                    }
                                    else
                                    {
                                        $action = REG_RESP_ACTION_LOAD_FORM;
                                        $form   = $this->GetRegisterEmailFormHtml($post_id, null, 'Error creating new user. You can retry...');
                                    }

                                }
                                else
                                {
                                    $action = REG_RESP_ACTION_LOAD_FORM;
                                    $form   = $this->GetRegisterEmailFormHtml($post_id, null, 'Email address already taken. Please select another email address.');
                                }
                            }
                            else
                            {
                                $action = REG_RESP_ACTION_LOAD_FORM;
                                $form   = $this->GetLoginFormHtml($post_id, null, 'Username already taken. Please select another username.');
                            }
                        }
                        else
                        {
                            $action = REG_RESP_ACTION_LOAD_FORM;
                            $form   = $this->GetLoginFormHtml($post_id, 'E-mail verified. Select your username and password.');
                        }
                        break;

                    case RegistrationHandlerClass::RESULT_NONCE_ERROR:
                        $ok     = true;
                        $action = REG_RESP_ACTION_LOAD_FORM;
                        $form   = $this->GetLoginFormHtml($post_id, null, 'E-mail verification link error. Log-in or retry register.');
                        break;
                    case RegistrationHandlerClass::RESULT_CONFIRM_INVALID:
                        break;
                    case RegistrationHandlerClass::RESULT_ERROR_UNDEFINED:
                        break;
                    case RegistrationHandlerClass::RESULT_CONFIRM_IS_DONE:
                        $ok     = true;
                        $action = REG_RESP_ACTION_LOAD_FORM;
                        $form   = $this->GetLoginFormHtml($post_id, 'This e-mail has already been verified. Your can now log-in using your user name and password.');
                        break;
                    case RegistrationHandlerClass::RESULT_USER_EXIST:
                        $ok     = true;
                        $action = REG_RESP_ACTION_LOAD_FORM;
                        $form   = $this->GetLoginFormHtml($post_id, 'This e-mail has been confirmed and user registration has been completed. You can now log-in.');
                        break;
                    default:
                        die();
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

    private function FormatFormTopPart($header, $description, $good_msg=null, $bad_msg=null)
    {
        $login_form = '<h2>Login or register</h2>';
        $login_form .= '<p>' . $description . '</p>';

        if($good_msg)
        {
            $login_form .=  $this->FormatMessageHtml($good_msg, GREEN_MESSAGE_CLASS);
        }

        if($bad_msg)
        {
            $login_form .=  $this->FormatMessageHtml($bad_msg, RED_MESSAGE_CLASS);
        }

        $login_form .= '<table class="bcf_pppc_table_forms"><tr><td class="bcf_pppc_table_forms" width="400px">';

        $login_form .= '<form>';
        $login_form .= '<table class="bcf_pppc_table_forms" width="100%">';

        return $login_form;
    }

    private function FormatFormEndPart($post_id=null, $message=null, $color_code=null)
    {
        $reg_id = $this->GetRegId();
        $msg_html = $this->FormatMessageHtml($message, $color_code);

        $login_form = '</table>';
        $login_form .= '<input type="hidden" name="action" value="bcf_pppc_do_login" />';
        $login_form .= '<input id="bcf_pppc_reg_id" type="hidden" name="id" value="' . $reg_id . '" />';
        if($post_id)
        {
            $login_form .= '<input id="bcf_pppc_post_id" type="hidden" name="post_id" value="' . $post_id . '" />';
        }
        $login_form .= '</form>';

        $login_form .= '</td><td class="bcf_pppc_table_forms"></td></tr></table>';
        $login_form .= '<p id="bcf_payment_status">'.$msg_html.'</p>';

        return $login_form;
    }

    public function GetLoginFormHtml($post_id=null, $good_msg=null, $bad_msg=null, $status_msg=null, $status_color=null)
    {
        $login_form = $this->FormatFormTopPart(
            'Login or register',
            'Membership required to read the rest of the page. Please log-in or register for membership.',
            $good_msg,
            $bad_msg
        );

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
        $login_form .= '<input id="bcf_pppc_do_login" type="button" value="Log-in" class="bcf_pppc_button" />&nbsp;&nbsp;';
        $login_form .= '<input id="bcf_pppc_do_register" type="button" value="Register" class="bcf_pppc_button" />';
        $login_form .= '</td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a href="/">Forgotten username or password?</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($post_id, $status_msg, $status_color);

        return $login_form;
    }

    public function GetRegisterEmailFormHtml($post_id, $good_msg=null, $bad_msg=null, $status_msg=null, $status_color=null)
    {
        $login_form = $this->FormatFormTopPart(
            'Register e-mail address',
            'Enter your e-mail address and verify it. You will receive a verification link.',
            $good_msg,
            $bad_msg
        );

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">E-mail address:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_email" type="text" class="bcf_pppc_text_input" value="" name="email" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_do_register_email" type="button" value="Register" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($post_id, $status_msg, $status_color);
/*
        $login_form .= '</tr></table>';
        $login_form .= '<input type="hidden" name="action" value="bcf_pppc_do_login" />';
        $login_form .= '<input id="bcf_pppc_reg_id" type="hidden" name="id" value="' . $reg_id . '" />';
        if($post_id)
        {
            $login_form .= '<input id="bcf_pppc_post_id" type="hidden" name="post_id" value="' . $post_id . '" />';
        }
        $login_form .= '</form>';

        $login_form .= '</td><td class="bcf_pppc_table_forms"></td></tr></table>';
        $login_form .= '<p id="bcf_payment_status">'.$msg_html.'</p>';
*/

        return $login_form;
    }

    public function GetVerifyEmailFormHtml($post_id, $good_msg=null, $bad_msg=null, $status_msg=null, $status_color=null)
    {
        $login_form = $this->FormatFormTopPart(
            'Check your e-mail',
            'A verification e-mail has been sent to you. Please check your e-mail.',
            $good_msg,
            $bad_msg
        );

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_do_resend_email" type="button" value="Resend e-mail" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatFormEndPart($post_id, $status_msg, $status_color);

        /*
        $login_form .= '</tr></table>';
        $login_form .= '<input type="hidden" name="action" value="bcf_pppc_do_login" />';
        $login_form .= '<input id="bcf_pppc_reg_id" type="hidden" name="id" value="' . $reg_id . '" />';
        if($post_id)
        {
            $login_form .= '<input id="bcf_pppc_post_id" type="hidden" name="post_id" value="' . $post_id . '" />';
        }
        $login_form .= '</form>';

        $login_form .= '</td><td class="bcf_pppc_table_forms"></td></tr></table>';
        $login_form .= '<p id="bcf_payment_status">'.$msg_html.'</p>';
        */

        return $login_form;
    }

    public function GetLoginPaymentFromHtml($price, $ref)
    {
        $payment_form = '<div id="bcf_remaining_content">';

        $payment_form .= '<p><b>To read the rest of the article, please pay ' . $price . ' to this address:</b></p>';
        $payment_form .= '<p>';
        $payment_form .= GetPaymentRequest($ref);
        $payment_form .= "</p>";

        $payment_form .= '<p id="bcf_payment_status"></p>';
        $payment_form .= '<p id="bcf_payment_debug"></p>';

        $payment_form .= '</div>';

        return $payment_form;
    }


}
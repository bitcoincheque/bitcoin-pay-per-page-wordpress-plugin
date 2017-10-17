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
require_once ('statistics_handler.php');

// Constants for AJAX POST and GET requests
define('REG_ID',                    'rid');
define('REG_TYPE',                  'rtype');
define('REG_NONCE',                 'nonce');
define('REG_EVENT',                 'event');
define('REG_USERNAME',              'username');
define('REG_FIRSTNAME',             'firstname');
define('REG_LASTNAME',              'lastname');
define('REG_DISPLAY_NAME',          'dispname');
define('REG_PASSWORD',              'password');
define('REG_CONFIRM_PW',            'confirmpw');
define('REG_REMEMBER',              'remember');
define('REG_ACCEPT_TERMS',          'accept_terms');
define('REG_EMAIL',                 'email');
define('REG_POST_ID',               'post_id');
define('REG_ERROR_MSG',             'error_message');
define('REG_SECRET',                'secret');

// Event field values:
define('REG_EVENT_LOGIN',           'login');
define('REG_EVENT_LOGOUT',          'logout');
define('REG_EVENT_USER_REGISTER',   'register_user');
define('REG_EVENT_READMORE_REGISTER','register_readmore');
define('REG_EVENT_RESEND_EMAIL',    'resend_register');
define('REG_EVENT_REGISTER_EMAIL',  'register_email');
define('REG_EVENT_GOTO_LOGIN',      'goto_login');
define('REG_EVENT_CONFIRM_EMAIL',   'confirm_email');      // Used by the login form in a post
define('REG_EVENT_CONFIRM_EMAIL_REG','confirm_email_reg'); // Used by the single registration form
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
    public function __construct($reg_id=null, $reg_type=null, $post_id=null, $nonce=null, $secret=null)
    {
        parent::__construct($reg_id, $reg_type, $post_id, $nonce, $secret);
    }

    public function CreateRegisterForm($input_data)
    {
        WriteDebugLogFunctionCall();

        $texts = array();

        $current_user = wp_get_current_user();
        if ( $current_user->ID == 0) {
            WriteDebugNote('Create register form');

            $post_id = get_the_ID();

            if($input_data[REG_EVENT] == REG_EVENT_CONFIRM_EMAIL)
            {
                switch($this->ConfirmEmail($input_data[REG_SECRET]))
                {
                    case RegistrationHandlerClass::RESULT_OK:
                        $form = $this->FormatHtmlFormRegisterUserdata($texts, $input_data[REG_POST_ID], $input_data[REG_SECRET]);
                        break;

                    case RegistrationHandlerClass::RESULT_NONCE_ERROR:
                        $texts[TEXT_FIELD_ERROR_MSG] = 'E-mail verification link error. Log-in or retry register.';
                        $form = $this->FormatHtmlFormRegisterVerifyEmail($texts, $post_id);
                        break;
                    case RegistrationHandlerClass::RESULT_CONFIRM_INVALID_LINK:
                        $texts[TEXT_FIELD_ERROR_MSG] = 'E-mail verification link error. Log-in or retry register.';
                        $form = $this->FormatHtmlFormRegisterVerifyEmail($texts, $post_id);
                        break;
                    case RegistrationHandlerClass::RESULT_ERROR_UNDEFINED:
                        $texts[TEXT_FIELD_ERROR_MSG] = 'E-mail verification link error. Log-in or retry register.';
                        $form = $this->FormatHtmlFormRegisterVerifyEmail($texts, $post_id);
                        break;
                    case RegistrationHandlerClass::RESULT_CONFIRM_IS_DONE:
                        $state = $this->GetRegistrationState();
                        if($state == MembershipRegistrationDataClass::STATE_USER_CREATED)
                        {
                            $texts[ TEXT_FIELD_SUCCESS_MSG ] = 'This e-mail has already been verified. Your can now log-in using your user name and password.';
                            $form                            = $this->FormatHtmlFormLogin($texts, $input_data[REG_POST_ID]);
                        }
                        else
                        {
                            $texts[TEXT_FIELD_SUCCESS_MSG] = 'E-mail has been verified. Select your username and password.';
                            $form = $this->FormatHtmlFormRegisterUserdata($texts, $input_data[REG_POST_ID], $input_data[REG_SECRET]);
                        }
                        break;
                    case RegistrationHandlerClass::RESULT_USER_EXIST:
                        $texts[TEXT_FIELD_GREEN_MSG] = 'This e-mail has been confirmed and user registration has been completed. You can now log-in.';
                        $form   = $this->FormatHtmlFormSimpleLogin($texts, $input_data[REG_POST_ID]);
                        break;
                    default:
                        die();
                }
            }
            else
            {
                $form = $this->FormatHtmlFormRegisterVerifyEmail($texts, $post_id);
            }
        } else {
            WriteDebugNote('Create register form');
            $texts[TEXT_FIELD_DESCRIPTION] = 'You are already registered and logged in.';

            $form = $this->FormatHtmlFormSimpleLogout($texts);
        }

        return $form;
    }

    public function CreateLoginForm($texts)
    {
        WriteDebugLogFunctionCall();

        $current_user = wp_get_current_user();
        if ( 0 == $current_user->ID ) {
            WriteDebugNote('Create log-in form');

            $form = $this->FormatHtmlFormSimpleLogin($texts);
        } else {
            WriteDebugNote('Create log-out form');
            $texts[TEXT_FIELD_DESCRIPTION] = 'You are now logged in.';

            $form = $this->FormatHtmlFormSimpleLogout($texts);
        }

        return $form;
    }

    public function CreateProfileForm($texts)
    {
        WriteDebugLogFunctionCall();

        $current_user = wp_get_current_user();
        if ( 0 == $current_user->ID )
        {
            $form = $this->FormatHtmlFormSimpleLogin($texts);
        }
        else
        {
            $form = $this->FormatHtmlFormProfile($texts);
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
            $form = $this->FormatHtmlFormChangePassword($texts, $wp_user_id, '', get_the_ID());
        }
        else
        {
            if(($input_data[ REG_EVENT ] == REG_EVENT_SEND_RESET_LINK)
            or ($input_data[ REG_EVENT ] == REG_EVENT_PASSWORD_LINK)
            or ($input_data[ REG_EVENT ] == REG_EVENT_CHANGE_PASSWORD))
            {
                $response_data = $this->EventHandler($input_data);
                $form = $response_data[REG_RESP_FORM];

            }
            else
            {
                /* Create send e-mail to restore password form */
                $texts = array();
                $post_id = get_the_ID();
                $form = $this->FormatHtmlFormResetPassword($texts, $post_id);
            }
        }

        return $form;
    }

    public function CreatePostContentForm($texts, $post_id)
    {
        WriteDebugLogFunctionCall();

        StatisticsPageview($post_id);

        $form = $this->FormatHtmlFormLogin($texts, $post_id);
        return $form;
    }

    private function DefaultResponseData()
    {
        $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_ERROR;
        $response_data[REG_RESP_ACTION] = null;
        $response_data[REG_RESP_FORM] = null;
        $response_data[REG_RESP_STATUS] = REG_RESP_STATUS_LOGGED_OUT;
        $response_data[REG_RESP_MSG] = '';
        return $response_data;
    }

    private function FormEventLogin($input_data)
    {
        $response_data = $this->DefaultResponseData();

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
                $response_data[REG_RESP_MSG] = 'Error. Wrong username or password.';
            }
            else
            {
                $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                $response_data[REG_RESP_STATUS] = REG_RESP_STATUS_LOGGED_IN;
            }
        }

        return $response_data;
    }

    private function FormEventReadMoreRegistration($input_data)
    {
        $response_data = $this->DefaultResponseData();
        $texts = array();

        if($input_data[REG_USERNAME] && $input_data[REG_PASSWORD])
        {
            $reg_id = $this->RegisterUsernamePassword(
                $input_data[REG_USERNAME],
                $input_data[REG_PASSWORD],
                $input_data[REG_POST_ID],
                MembershipRegistrationDataClass::REG_TYPE_READ_MORE_REGISTRATION);

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
                                    $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                                    $response_data[REG_RESP_STATUS] = REG_RESP_STATUS_LOGGED_IN;
                                }
                            }
                        }
                        else
                        {
                            $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                            $response_data[REG_RESP_ACTION] = REG_RESP_ACTION_LOAD_FORM;
                            $response_data[REG_RESP_FORM] = $this->FormatHtmlFormRegisterEmail($texts, $input_data[REG_POST_ID]);
                            StatisticsRegister($input_data[REG_POST_ID]);
                        }
                    }
                    else
                    {
                        $response_data[REG_RESP_MSG] = 'Username already taken. Please select another username.';
                    }
                }
                else
                {
                    $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                    $response_data[REG_RESP_ACTION] = REG_RESP_ACTION_LOAD_FORM;
                    $response_data[REG_RESP_FORM] = $this->FormatHtmlFormRegisterEmail($texts, $input_data[REG_POST_ID]);
                    StatisticsRegister($input_data[REG_POST_ID]);
                }
            }else{
                $response_data[REG_RESP_MSG] = 'Invalid username or password';
            }
        }else{
            $response_data[REG_RESP_MSG] = 'Page error. Missing username or password in request.';
            WriteDebugError($response_data[REG_RESP_MSG]);
        }


        return $response_data;
    }

    private function FormEventUpdateProfile($input_data)
    {
        $response_data = $this->DefaultResponseData();

        if(is_user_logged_in())
        {
            $user_id = get_current_user_id();

            $firstname = $input_data[ REG_FIRSTNAME ];
            $lastname = $input_data[ REG_LASTNAME ];
            $email = $input_data[ REG_EMAIL ];
            $password = $input_data[ REG_PASSWORD ];

            if($input_data[ REG_PASSWORD ] != $input_data[ REG_CONFIRM_PW ])
            {
                $password = null;
            }

            $userdata = array(
                'ID'         => $user_id,
                'first_name' => $firstname,
                'last_name'  => $lastname,
                'user_pass'  => $password,
                'user_email' => $email
            );

            $user_id = wp_update_user($userdata);

            if(is_wp_error($user_id))
            {
                $texts[ TEXT_FIELD_ERROR_MSG ] = 'Error updating profile data.';
            }
            else
            {
                $texts[ TEXT_FIELD_SUCCESS_MSG ] = 'Profile data successfully updated.';
                $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
            }

            $response_data[ REG_RESP_ACTION ] = REG_RESP_ACTION_LOAD_FORM;
            $response_data[ REG_RESP_FORM ] = $this->FormatHtmlFormProfile($texts);
        }

        return $response_data;
    }

    private function FormEventRegistrationEmail($input_data, $reg_type)
    {
        $response_data = $this->DefaultResponseData();

        if($input_data[REG_ACCEPT_TERMS])
        {
            if($input_data[REG_EMAIL])
            {
                if($this->RegisterEmail($input_data[ REG_EMAIL ], $reg_type))
                {
                    StatisticsVerifyEmail($input_data[ REG_POST_ID ]);

                    $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                    $response_data[ REG_RESP_ACTION ] = REG_RESP_ACTION_LOAD_FORM;
                    $texts[ TEXT_FIELD_GREEN_MSG ] = 'A verification e-mail has been sent to you. Please check your e-mail.';
                    $response_data[ REG_RESP_FORM ] = $this->FormatHtmlFormCheckYourEmail($texts);
                }
                else
                {
                    $msg = $this->GetErrorMessage();
                    if( ! $msg)
                    {
                        $response_data[ REG_RESP_MSG ] = 'Error registering e-mail';
                    }
                }
            }else{
                $msg = 'Page error. Missing e-mail in request.';
                WriteDebugError($msg);
            }
        }else{
            $response_data[ REG_RESP_MSG ] = 'You must tick of to accept the terms and conditions.';
        }

        return $response_data;
    }

    private function FormEventConfirmEmail()
    {
        /* The ConfirmEmailEvent has already been handled by the Init function. This is due to need to login before
           writing the page. Shoudl be logged inn at this moment. */
        $response_data = $this->DefaultResponseData();

        if(!is_user_logged_in()) {
            $texts = array();

            $response_data[ REG_RESP_FORM ] = $this->FormatHtmlFormLogin($texts, get_the_ID());
        }

        return $response_data;
    }

    private function FormEventRegisterUserData($input_data)
    {
        $response_data = $this->DefaultResponseData();
        $texts = array();

        if($input_data[REG_USERNAME] && $input_data[REG_PASSWORD])
        {
            if(!username_exists($input_data[REG_USERNAME]))
            {
                $email = $this->GetEmail();
                if(!email_exists($email))
                {
                    $reg_id = $this->RegisterUsernamePassword(
                        $input_data[REG_USERNAME],
                        $input_data[REG_PASSWORD],
                        $input_data[REG_POST_ID],
                        MembershipRegistrationDataClass::REG_TYPE_USER_REGISTRATION);

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
                                        $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                                        $response_data[ REG_RESP_ACTION ] = REG_RESP_ACTION_LOAD_FORM;
                                        $texts[ TEXT_FIELD_SUCCESS_MSG ] = 'User registration completed. You can now log-in.';
                                        $response_data[ REG_RESP_FORM ]  = $this->FormatHtmlFormSimpleLogin($texts, $input_data[REG_POST_ID]);
                                    }
                                }
                                else
                                {
                                    $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                                    $response_data[ REG_RESP_ACTION ] = REG_RESP_ACTION_LOAD_FORM;
                                    $response_data[ REG_RESP_FORM ] = $this->FormatHtmlFormRegisterEmail($texts, $input_data[REG_POST_ID]);
                                }
                            }
                            else
                            {
                                $response_data[ REG_RESP_MSG ] = 'Username already taken. Please select another username.';
                            }
                        }
                        else
                        {
                            $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                            $response_data[ REG_RESP_ACTION ] = REG_RESP_ACTION_LOAD_FORM;
                            $response_data[ REG_RESP_FORM ] = $this->FormatHtmlFormRegisterEmail($texts, $input_data[REG_POST_ID]);
                        }
                    }
                    else
                    {
                        $response_data[ REG_RESP_MSG ] = 'Server error.';
                    }
                }
                else
                {
                    $response_data[ REG_RESP_MSG ] = 'E-mail already taken. Please select another e-mail.';
                }
            }
            else
            {
                $response_data[ REG_RESP_MSG ] = 'Username already taken. Please select another username.';
            }
        }

        return $response_data;
    }

    private function FormEventPasswordRecoveryLink($input_data)
    {
        $response_data = $this->DefaultResponseData();
        $texts = array();

        if(!$input_data[REG_EMAIL])
        {
            $response_data[REG_RESP_MSG] = 'You must enter your e-mail address.';
        }
        else
        {
            if(filter_var($input_data[REG_EMAIL], FILTER_VALIDATE_EMAIL))
            {
                $wp_user_id = email_exists($input_data[ REG_EMAIL ]);
                if($wp_user_id)
                {
                    if($this->SendEmailResetLink(
                        $input_data[ REG_EMAIL ],
                        $wp_user_id,
                        MembershipRegistrationDataClass::REG_TYPE_PASSWORD_RECOVERY,
                        $input_data[ REG_POST_ID ]
                    ))
                    {
                        $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                        $response_data[ REG_RESP_ACTION ] = REG_RESP_ACTION_LOAD_FORM;
                        $texts[ TEXT_FIELD_GREEN_MSG ]  = 'E-mail with username and password link is sent to ' . $input_data[ REG_EMAIL ];
                        $response_data[ REG_RESP_FORM ] = $this->FormatHtmlFormCheckYourEmail($texts);

                    }
                    else
                    {
                        $response_data[ REG_RESP_MSG ] = 'Error sending e-mail. Retry later or contact site admin if problem persists.';
                    }
                }
                else
                {
                    $response_data[ REG_RESP_MSG ] = 'This e-mail has no user account.';
                }
            }
            else
            {
                $response_data[ REG_RESP_MSG ] = 'Invalid e-mail address format. Please enter a real e-mail address.';
            }

            if( ! $response_data[REG_RESP_FORM])
            {
                $response_data[REG_RESP_FORM] = $this->FormatHtmlFormResetPassword($texts=array(), get_the_ID());
            }
        }

        return $response_data;
    }

    private function FormHandlerReadMoreRegistration($input_data)
    {
        $response_data = null;

        switch($input_data[REG_EVENT])
        {
            case REG_EVENT_LOGIN:
                $response_data = $this->FormEventLogin($input_data);
                break;
            case REG_EVENT_READMORE_REGISTER:
                $response_data = $this->FormEventReadMoreRegistration($input_data);
                break;
            case REG_EVENT_REGISTER_EMAIL:
                $response_data = $this->FormEventRegistrationEmail($input_data,  MembershipRegistrationDataClass::REG_TYPE_READ_MORE_REGISTRATION);
                break;
            case REG_EVENT_CONFIRM_EMAIL:
                $response_data = $this->FormEventConfirmEmail();
                break;
            default:
                WriteDebugError('Undefined event', $input_data[REG_EVENT]);
                break;
        }
        return $response_data;
    }

    private function FormHandlerRegistration($input_data)
    {
        $response_data = null;

        switch($input_data[REG_EVENT])
        {
            case REG_EVENT_REGISTER_EMAIL:
                $response_data = $this->FormEventRegistrationEmail($input_data,  MembershipRegistrationDataClass::REG_TYPE_USER_REGISTRATION);
                break;
            case REG_EVENT_USER_REGISTER:
                $response_data = $this->FormEventRegisterUserData($input_data);
                break;
            default:
                WriteDebugError('Undefined event', $input_data[REG_EVENT]);
                break;
        }
        return $response_data;
    }

    private function FormHandlerPasswordRecovery($input_data)
    {
        $response_data = null;

        switch($input_data[REG_EVENT])
        {
            case REG_EVENT_SEND_RESET_LINK:
                $response_data = $this->FormEventPasswordRecoveryLink($input_data);
                break;
            case REG_EVENT_PASSWORD_LINK:
                switch($this->CheckResetEmailSecret())
                {
                    case RegistrationHandlerClass::RESULT_OK:
                        $wp_user_id = $this->GetWpUserId();
                        if($wp_user_id)
                        {
                            $texts = array();
                            $response_data[REG_RESP_FORM] = $this->FormatHtmlFormChangePassword($texts, $wp_user_id, $input_data[REG_SECRET], get_the_ID());
                        }
                        else
                        {
                            $texts[ TEXT_FIELD_ERROR_MSG ] = 'No user linked to e-mail address.';
                            $response_data[REG_RESP_FORM]  = $this->FormatHtmlFormResetPassword($texts, get_the_ID());
                        }
                        break;

                    case RegistrationHandlerClass::RESULT_CONFIRM_IS_DONE:
                        $texts[ TEXT_FIELD_RED_MSG ] = 'This password reset link has been used. You must send a new email to reset pssword again.';
                        $response_data[REG_RESP_FORM]= $this->FormatHtmlFormResetPassword($texts, get_the_ID());
                        break;

                    case RegistrationHandlerClass::RESULT_CONFIRM_EXPIRED_LINK:
                        $texts[ TEXT_FIELD_ERROR_MSG ] = 'This password reset link has expired.';
                        $response_data[REG_RESP_FORM]  = $this->FormatHtmlFormResetPassword($texts, get_the_ID());
                        break;

                    default:
                        $texts[ TEXT_FIELD_RED_MSG ] = 'This password reset link is invalid. You can try send a new link or contact admin.';
                        /* Create send e-mail to restore password form */
                        $response_data[REG_RESP_FORM] = $this->FormatHtmlFormResetPassword($texts, get_the_ID());
                        break;
                }
                break;

            case REG_EVENT_CHANGE_PASSWORD:
                if($input_data[ REG_PASSWORD ] != '' and ($input_data[ REG_PASSWORD ] == $input_data[ REG_CONFIRM_PW ]))
                {
                    switch($this->UpdatePassword($input_data[ REG_PASSWORD ]))
                    {
                        case RegistrationHandlerClass::RESULT_OK:
                            $texts[ TEXT_FIELD_GREEN_MSG ] = 'Password successfully updated.';
                            $response_data[REG_RESP_FORM]  = $this->FormatHtmlFormGeneralMessage($texts);
                            break;

                        case RegistrationHandlerClass::RESULT_CONFIRM_IS_DONE:
                            $texts[ TEXT_FIELD_RED_MSG ] = 'This password reset link has been used. You must send a new email to reset pssword again.';
                            break;

                        case RegistrationHandlerClass::RESULT_CONFIRM_EXPIRED_LINK:
                            $texts[ TEXT_FIELD_ERROR_MSG ] = 'This password reset link has expired.';
                            break;

                        default:
                            $texts[ TEXT_FIELD_RED_MSG ] = 'Error changing password.';
                            break;
                    }
                }
                else
                {
                    if($input_data[ REG_PASSWORD ] == '' and $input_data[ REG_CONFIRM_PW ] == '')
                    {
                        $texts[ TEXT_FIELD_ERROR_MSG ] = 'Error. No password entered.';
                    }
                    else if($input_data[ REG_PASSWORD ] == '' or $input_data[ REG_CONFIRM_PW ] == '')
                    {
                        $texts[ TEXT_FIELD_ERROR_MSG ] = 'Error. You must enter the the password in both inputs.';
                    }

                    $wp_user_id                    = $this->GetWpUserId();
                    $texts[ TEXT_FIELD_ERROR_MSG ] = 'Error. Entered passwords do not match.';
                    if($wp_user_id)
                    {
                        $response_data[REG_RESP_FORM] = $this->FormatHtmlFormChangePassword($texts, $wp_user_id, $input_data[ REG_SECRET ]);
                    }
                }

                if(!$response_data[REG_RESP_FORM])
                {
                    $response_data[REG_RESP_FORM] = $this->FormatHtmlFormResetPassword($texts);
                }

                $response_data[REG_RESP_RESULT] = REG_RESP_RESULT_OK;
                $response_data[REG_RESP_ACTION] = REG_RESP_ACTION_LOAD_FORM;
                break;
            default:
                WriteDebugError('Undefined event', $input_data[REG_EVENT]);
                break;
        }
        return $response_data;
    }

    private function FormHandlerLogin($input_data)
    {
        $response_data = null;

        switch($input_data[REG_EVENT])
        {
            case REG_EVENT_LOGIN:
                break;
            default:
                WriteDebugError('Undefined event', $input_data[REG_EVENT]);
                break;
        }
        return $response_data;
    }

    private function FormHandlerLogout($input_data)
    {
        $response_data = null;

        switch($input_data[REG_EVENT])
        {
            case REG_EVENT_LOGIN:
                break;
            default:
                WriteDebugError('Undefined event', $input_data[REG_EVENT]);
                break;
        }
        return $response_data;
    }

    private function FormHandlerUpdateProfile($input_data)
    {
        $response_data = null;

        switch($input_data[REG_EVENT])
        {
            case REG_EVENT_UPDATE_PROFILE:
                $response_data = $this->FormEventUpdateProfile($input_data);
                break;
            default:
                WriteDebugError('Undefined event', $input_data[REG_EVENT]);
                break;
        }
        return $response_data;
    }

    public function EventHandler($input_data)
    {
        $response_data = null;

        WriteDebugLogFunctionCall();

        switch($input_data[REG_TYPE])
        {
            case MembershipRegistrationDataClass::REG_TYPE_NOT_SET:
                break;

            case MembershipRegistrationDataClass::REG_TYPE_READ_MORE_REGISTRATION:
                $response_data = $this->FormHandlerReadMoreRegistration($input_data);
                break;

            case MembershipRegistrationDataClass::REG_TYPE_USER_REGISTRATION:
                $response_data = $this->FormHandlerRegistration($input_data);
                break;

            case MembershipRegistrationDataClass::REG_TYPE_PASSWORD_RECOVERY:
                $response_data = $this->FormHandlerPasswordRecovery($input_data);
                break;

            case MembershipRegistrationDataClass::REG_TYPE_LOGIN:
                $response_data = $this->FormHandlerLogin($input_data);
                break;

            case MembershipRegistrationDataClass::REG_TYPE_LOGOUT:
                $response_data = $this->FormHandlerLogout($input_data);
                break;

            case MembershipRegistrationDataClass::REG_TYPE_PROFILE:
                $response_data = $this->FormHandlerUpdateProfile($input_data);
                break;

            default:
                WriteDebugError('Undefined reg_type', $this->GetRegType());
                break;
        }

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

    private function FormatHtmlFormTopPart($texts, $form_code)
    {
        $login_form = '<div id="bcf_pppc_login_form">';

        if(isset($texts[TEXT_FIELD_HEADER]) and $texts[TEXT_FIELD_HEADER])
        {
            $login_form .= '<h2>'.$texts[TEXT_FIELD_HEADER].'</h2>';
        }

        if(isset($texts[TEXT_FIELD_GREEN_MSG]) and $texts[TEXT_FIELD_GREEN_MSG])
        {
            $login_form .=  $this->FormatMessageHtml($texts[TEXT_FIELD_GREEN_MSG], GREEN_MESSAGE_CLASS);
        }

        if(isset($texts[TEXT_FIELD_RED_MSG]) and $texts[TEXT_FIELD_RED_MSG])
        {
            $login_form .=  $this->FormatMessageHtml($texts[TEXT_FIELD_RED_MSG], RED_MESSAGE_CLASS);
        }

        if(isset($texts[TEXT_FIELD_DESCRIPTION]) and $texts[TEXT_FIELD_DESCRIPTION])
        {
            $login_form .= '<p>' . $texts[TEXT_FIELD_DESCRIPTION] . '</p>';
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

    private function FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id)
    {
        $html = '</table>';
        if($post_id)
        {
            $html .= '<input id="bcf_pppc_post_id" type="hidden" class="bcf_form_field" name="post_id" value="' . $post_id . '" />';
        }
        if($hidden_fields)
        {
            foreach($hidden_fields as $name => $value)
            {
                $html .= '<input id="'.$name.'" type="hidden" class="bcf_form_field" name="'.$name.'" value="'.$value.'" />';
            }
        }

        $reg_id = $this->GetRegId();
        if($reg_id)
        {
            $html .= '<input id="bcf_pppc_reg_id" type="hidden" class="bcf_form_field" name="' . REG_ID . '" value="' . $reg_id . '" />';
        }

        $reg_type = $this->GetRegType();
        if($reg_type)
        {
            $html .= '<input id="bcf_pppc_reg_type" type="hidden" class="bcf_form_field" name="' . REG_TYPE . '" value="' . $reg_type . '" />';
        }

        $nonce = $this->GetNonce();
        if($reg_id)
        {
            $html .= '<input id="bcf_pppc_nonce" type="hidden" class="bcf_form_field" name="' . REG_NONCE . '" value="' . $nonce . '" />';
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
            $msg_html .= $this->FormatMessageHtml($texts[TEXT_FIELD_SUCCESS_MSG], GREEN_MESSAGE_CLASS);
        }


        $html .= '<p id="bcf_payment_status">'.$msg_html.'</p>';

        $html .= '</div>';

        return $html;
    }

    private function FormatHtmlFormSimpleLogin($texts=array(), $post_id= null)
    {
        WriteDebugLogFunctionCall();

        $link_options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);

        $form_code = 'method="post"';
        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_EVENT => REG_EVENT_LOGIN
        );

        $html = $this->FormatHtmlFormTopPart($texts, $form_code);

        $html .= '<tr>';
        $html .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Username:</lable></td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_username" type="text" class="bcf_pppc_text_input" value="" name="username" /></td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Password:</lable></td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_password" type="password" class="bcf_pppc_text_input bcf_form_field" value="" name="password" /></td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_cell_form">';
        $html .= '<input type="submit" value="Log in" class="bcf_pppc_button" />';
        $html .= '</td>';
        $html .= '</tr><tr>';
        $html .= '<td class="bcf_pppc_table_forms">';
        $html .= '<a href="' . $link_options['RegisterPageLink'] . '">Forgotten username or password?</a><br>';
        $html .= '<a href="' . $link_options['PasswordPageLink'] . '">Register</a>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $html;
    }

    private function FormatHtmlFormSimpleLogout($texts=array())
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

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><p>Username: ' . $current_user->user_login . '</p></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form">';
        $login_form .= '<input type="submit" value="Log out" class="bcf_pppc_button" />';
        $login_form .= '</td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms">';
        $login_form .= '<a href="' . $link_options['ProfilePageLink'] . '">Update your profile</a><br>';
        $login_form .= '</td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    private function FormatHtmlFormLogin($texts=array(), $post_id=null)
    {
        WriteDebugLogFunctionCall();

        $link_options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);

        $form_code = '';
        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_TYPE => MembershipRegistrationDataClass::REG_TYPE_READ_MORE_REGISTRATION
        );
        $texts[TEXT_FIELD_HEADER] = 'Login or register';
        $texts[TEXT_FIELD_DESCRIPTION] = 'Membership required to read the rest of the page. Please log in or register for membership.';

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Username:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_username" type="text" class="bcf_pppc_text_input bcf_form_field" value="" name="username" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_password" type="password" class="bcf_pppc_text_input bcf_form_field" value="" name="password" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form">';
        $login_form .= '<input id="bcf_pppc_post_data" type="button" name="'.REG_EVENT_LOGIN.'" value="Log in" class="bcf_pppc_button" />&nbsp;&nbsp;';
        $login_form .= '<input id="bcf_pppc_post_data" type="button" name="'.REG_EVENT_READMORE_REGISTER.'" value="Register" class="bcf_pppc_button" />';
        $login_form .= '</td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a href="' . $link_options['PasswordPageLink'] . '">Forgotten username or password?</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    private function FormatHtmlFormRegisterEmail($texts=array(), $post_id)
    {
        WriteDebugLogFunctionCall();

        $link_options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);

        $form_code = '';
        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_TYPE => MembershipRegistrationDataClass::REG_TYPE_READ_MORE_REGISTRATION
        );
        $texts[TEXT_FIELD_HEADER] = 'Register e-mail address';
        $texts[TEXT_FIELD_DESCRIPTION] = 'Enter your e-mail address and verify it. You will receive a verification link.';

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">E-mail address:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_email" type="text" class="bcf_pppc_text_input bcf_form_field" value="" name="email" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_accept_terms" type="checkbox" class="bcf_pppc_checkbox_input bcf_form_field" value="0" name="'.REG_ACCEPT_TERMS.'" />Yes, I accept the <a href="' . $link_options['TermsPage'] . '" target="_blank">terms and condition</a>.</td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_post_data" type="button" name="'.REG_EVENT_REGISTER_EMAIL.'" value="Register" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    private function FormatHtmlFormCheckYourEmail($texts=array())
    {
        WriteDebugLogFunctionCall();

        $form_code = '';
        $hidden_fields = null;
        $texts[TEXT_FIELD_HEADER] = 'Check your e-mail';
        $post_id= null;

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_post_data" type="button" name="'.REG_EVENT_RESEND_EMAIL.'" value="Resend e-mail" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    private function FormatHtmlFormRegisterVerifyEmail($texts=array(), $post_id)
    {
        WriteDebugLogFunctionCall();

        $link_options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);

        $form_code = '';

        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_TYPE => MembershipRegistrationDataClass::REG_TYPE_USER_REGISTRATION
        );

        $texts[TEXT_FIELD_HEADER] = 'Register for membership';
        $texts[TEXT_FIELD_DESCRIPTION] = 'Your e-mail address must first be verified. You will receive an e-mail with an verification link after registering.';

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">E-mail address:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_email" type="text" class="bcf_pppc_text_input bcf_form_field" value="" name="email" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_accept_terms" type="checkbox" class="bcf_pppc_checkbox_input bcf_form_field" value="0" name="'.REG_ACCEPT_TERMS.'" />Yes, I accept the <a href="' . $link_options['TermsPage'] . '" target="_blank">terms and condition</a>.</td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_post_data" type="button" name="'.REG_EVENT_REGISTER_EMAIL.'" value="Register" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    private function FormatHtmlFormRegisterUserdata($texts=array(), $post_id, $secret)
    {
        WriteDebugLogFunctionCall();

        $form_code = '';

        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_TYPE => MembershipRegistrationDataClass::REG_TYPE_PASSWORD_RECOVERY,
            REG_SECRET => $secret
        );

        $texts[TEXT_FIELD_HEADER] = 'Register for membership';
        $texts[TEXT_FIELD_DESCRIPTION] = '<p>Select a username and password.</p>';

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Username:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_username" type="text" class="bcf_pppc_text_input bcf_form_field" value="" name="username" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_password" type="text" class="bcf_pppc_text_input bcf_form_field" value="" name="password" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_post_data" type="button" name="'.REG_EVENT_USER_REGISTER.'" value="Register" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    private function FormatHtmlFormProfile($texts=array())
    {
        WriteDebugLogFunctionCall();

        $form_code = 'method="post"';
        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_TYPE => MembershipRegistrationDataClass::REG_TYPE_PROFILE
        );
        $post_id= null;

        $current_user = wp_get_current_user();

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Login username:</lable><br><lable class="bcf_pppc_remark">(Username can not be changed.)</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_firstname" type="text" class="bcf_pppc_text_input bcf_form_field" value="' . $current_user->user_login . '" readonly="readonly" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Firstname:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_firstname" type="text" class="bcf_pppc_text_input bcf_form_field" value="' . $current_user->first_name . '" name="'.REG_FIRSTNAME.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Lastname:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_lastname" type="text" class="bcf_pppc_text_input bcf_form_field" value="' . $current_user->last_name . '" name="'.REG_LASTNAME.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">E-mail:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_email" type="text" class="bcf_pppc_text_input bcf_form_field" value="' . $current_user->user_email . '" name="'.REG_EMAIL.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_password" type="text" class="bcf_pppc_text_input bcf_form_field" value="" name="'.REG_PASSWORD.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Confirm password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_confirm_pw" type="text" class="bcf_pppc_text_input bcf_form_field" value="" name="'.REG_CONFIRM_PW.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_post_data" type="button" name="'.REG_EVENT_UPDATE_PROFILE.'" value="Update" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    private function FormatHtmlFormChangePassword($texts=array(), $wp_user_id, $secret, $post_id)
    {
        WriteDebugLogFunctionCall();

        $user_info = get_userdata($wp_user_id);

        $form_code = 'method="post"';
        $texts[TEXT_FIELD_DESCRIPTION] = 'Enter your new password.<br>Username: <strong>' . $user_info->user_login . '</strong>';

        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_TYPE => MembershipRegistrationDataClass::REG_TYPE_PASSWORD_RECOVERY,
            REG_SECRET => $secret
        );

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);
        $login_form .= '<tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_password" type="password" class="bcf_pppc_text_input bcf_form_field" value="" name="'.REG_PASSWORD.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">Confirm password:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_confirm_pw" type="password" class="bcf_pppc_text_input bcf_form_field" value="" name="'.REG_CONFIRM_PW.'" /></td>';
        $login_form .= '</tr><tr>';

        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_post_data" type="button" name="'.REG_EVENT_CHANGE_PASSWORD.'" value="Update" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    private function FormatHtmlFormResetPassword($texts=array(), $post_id)
    {
        WriteDebugLogFunctionCall();

        $form_code = 'method="post"';
        $hidden_fields = array(
            'action' => REG_AJAX_ACTION,
            REG_TYPE => MembershipRegistrationDataClass::REG_TYPE_PASSWORD_RECOVERY
        );

        $texts[TEXT_FIELD_DESCRIPTION] = 'Enter your e-mail address and we will send you a new password.';

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><lable class="bcf_pppc_label">E-mail address:</lable></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><input id="bcf_pppc_email" type="text" class="bcf_pppc_text_input bcf_form_field" value="" name="email" /></td>';
        $login_form .= '</tr><tr>';
        $login_form .= '<td class="bcf_pppc_table_cell_form"><input id="bcf_pppc_post_data" type="button" name="'.REG_EVENT_SEND_RESET_LINK.'" value="Send e-mail" class="bcf_pppc_button" /> </td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }

    private function FormatHtmlFormGeneralMessage($texts=array())
    {
        WriteDebugLogFunctionCall();

        $form_code = '';
        $hidden_fields = null;
        $post_id= null;

        $login_form = $this->FormatHtmlFormTopPart($texts, $form_code);

        $login_form .= '<tr>';
        $login_form .= '<td class="bcf_pppc_table_forms"><a id="bcf_pppc_do_return_login">Return to login</a></td>';
        $login_form .= '</tr>';

        $login_form .= $this->FormatHtmlFormBottomPart($texts, $hidden_fields, $post_id);

        return $login_form;
    }
}
<?php
/**
 * Membership registration and log-in library. Handler class for 
 * registration process.
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

use BCF_Email\Email;

require_once ('membership_reg_data.php');
require_once ('email.php');
require_once ('autoresponder.php');

class RegistrationHandlerClass
{
    const RESULT_OK = 0;
    const RESULT_NONCE_ERROR = 1;
    const RESULT_CONFIRM_INVALID_LINK = 2;
    const RESULT_ERROR_UNDEFINED = 3;
    const RESULT_CONFIRM_IS_DONE = 4;
    const RESULT_USER_EXIST = 5;
    const RESULT_CONFIRM_EXPIRED_LINK = 6;

    private $registration_data = null;
    private $has_data = false;
    private $error_message = '';

    public function __construct($reg_id=null, $nonce=null)
    {
        $this->registration_data = new MembershipRegistrationDataClass();

        if($reg_id != null)
        {
            if($nonce)
            {
                if($this->registration_data->LoadData($reg_id))
                {
                    $my_nonce = $this->registration_data->GetDataString(MembershipRegistrationDataClass::NONCE);
                    if($nonce === $my_nonce)
                    {
                        $this->has_data = true;
                    }
                    else
                    {
                        $this->registration_data = new MembershipRegistrationDataClass();
                        $cookie = MembershipGetCookie();
                        $this->registration_data->SetDataString(MembershipRegistrationDataClass::COOCKIE, $cookie);
                    }
                }
            }
        }
        else
        {
            $cookie = MembershipGetCookie();
            $this->registration_data->SetDataString(MembershipRegistrationDataClass::COOCKIE, $cookie);
        }
    }

    public function RegisterUsernamePassword($username, $password, $remember, $post_id)
    {
        WriteDebugLogFunctionCall('', $password_arg_no=1);

        $reg_id = 0;

        if($username != '' && $password != '')
        {
            $nonce = MembershipRandomString(NONCE_LENGTH);
            $this->registration_data->SetDataInt(MembershipRegistrationDataClass::TIMESTAMP, time());
            $this->registration_data->SetDataString(MembershipRegistrationDataClass::NONCE, $nonce);
            $password_hash = wp_hash_password($password);
            $this->registration_data->SetDataString(MembershipRegistrationDataClass::USERNAME, $username);
            $this->registration_data->SetDataString(MembershipRegistrationDataClass::PASSWORD, $password_hash);
            $this->registration_data->SetDataInt(MembershipRegistrationDataClass::POST_ID, $post_id);
            $reg_id = $this->registration_data->SaveData();
        }

        if($reg_id > 0)
        {
            $this->has_data = true;
        }

        return $reg_id;
    }

    public function RegisterEmail($email)
    {
        WriteDebugLogFunctionCall();

        $result = false;

        if($email != '')
        {
            $old_email = $this->registration_data->GetDataString(MembershipRegistrationDataClass::EMAIL, $email);

            if($old_email and $email != $old_email)
            {
                $this->registration_data = new MembershipRegistrationDataClass();

                $this->registration_data->SetDataString(MembershipRegistrationDataClass::EMAIL, $email);

                $post_id = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::POST_ID);
                $this->registration_data->SetDataString(MembershipRegistrationDataClass::POST_ID, $post_id);

                $nonce = MembershipRandomString(NONCE_LENGTH);
                $this->registration_data->SetDataString(MembershipRegistrationDataClass::NONCE, $nonce);

                $secret = MembershipRandomString(SECRET_LENGTH);
                $this->registration_data->SetDataString(MembershipRegistrationDataClass::SECRET, $secret);

                $reg_id = $this->registration_data->SaveData();

                $retry_counter=0;
            }
            else
            {
                $this->registration_data->SetDataString(MembershipRegistrationDataClass::EMAIL, $email);

                $nonce = $this->registration_data->GetDataString(MembershipRegistrationDataClass::NONCE);
                if($nonce == '')
                {
                    $nonce = MembershipRandomString(NONCE_LENGTH);
                    $this->registration_data->SetDataString(MembershipRegistrationDataClass::NONCE, $nonce);
                }

                $secret = $this->registration_data->GetDataString(MembershipRegistrationDataClass::SECRET);
                if($secret == '')
                {
                    $secret = MembershipRandomString(SECRET_LENGTH);
                    $this->registration_data->SetDataString(MembershipRegistrationDataClass::SECRET, $secret);
                }

                $this->registration_data->AddDataInt(MembershipRegistrationDataClass::RETRY_COUNTER, 1);
                $this->registration_data->SaveData();

                $reg_id = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::ID);
                $retry_counter = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::RETRY_COUNTER);
                $post_id = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::POST_ID);
            }

            if($retry_counter < 5)
            {
                $verification_link = site_url() . '?' . REG_EVENT . '=' . REG_EVENT_CONFIRM_EMAIL . '&' . REG_ID . '=' . $reg_id . '&' . REG_NONCE . '=' . $nonce . '&' . REG_SECRET . '=' . $secret;

                if($post_id)
                {
                    $verification_link .= '&p=' . $post_id;
                }

                $result = $this->SendEmailVerification($email, $verification_link);

                $this->has_data = true;
            }
            else
            {
                $this->error_message = 'Maximum e-mail resend retries';
            }

        }

        return $result;
    }

    private function SendEmailVerification($email, $link)
    {
        WriteDebugLogFunctionCall();

        $options = get_option(BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION);

        $site_name = get_bloginfo('name');
        $site_url = site_url();
        $href = '<a href="' . $link . '">' . $link . '</a>';

        $body = $options['email_body'];
        $body = str_replace('{site_name}', $site_name, $body);
        $body = str_replace('{site_url}', $site_url, $body);
        $body = str_replace('{link}', $href, $body);

        $verification_email = new Email($email);
        $verification_email->SetFromAddress($options['email_replay_addr']);
        $verification_email->SetSubject($options['email_subject']);
        $verification_email->SetBody($body);

        $result = $verification_email->Send();

        if($result)
        {
            WriteDebugNote('OK e-mail sent.');
        }
        else
        {
            WriteDebugWarning('ERROR E-mail not sent.');
        }

        return $result;
    }

    public function ConfirmEmail($secret)
    {
        WriteDebugLogFunctionCall();

        $result =  self::RESULT_ERROR_UNDEFINED;

        if( $this->has_data == true)
        {
            $my_secret = $this->registration_data->GetDataString(MembershipRegistrationDataClass::SECRET);
            if($secret == $my_secret)
            {
                switch($this->registration_data->GetDataString(MembershipRegistrationDataClass::STATE))
                {
                    case MembershipRegistrationDataClass::STATE_EMAIL_UNCONFIRMED:
                        $this->registration_data->SetDataInt(MembershipRegistrationDataClass::STATE, MembershipRegistrationDataClass::STATE_EMAIL_CONFIRMED);
                        $this->registration_data->SaveData();
                        $result = self::RESULT_OK;
                        break;
                    case MembershipRegistrationDataClass::STATE_EMAIL_CONFIRMED:
                        $result = self::RESULT_CONFIRM_IS_DONE;
                        break;
                    case MembershipRegistrationDataClass::STATE_USER_CREATED:
                        $result = self::RESULT_USER_EXIST;
                        break;
                    default:
                        $result = self::RESULT_ERROR_UNDEFINED;
                        break;
                }
            }
            else
            {
                $result = self::RESULT_CONFIRM_INVALID_LINK;
            }
        }else{
            $result = self::RESULT_CONFIRM_EXPIRED_LINK;
        }

        return $result;
    }

    protected function SendEmailResetLink($email, $wp_user_id)
    {
        WriteDebugLogFunctionCall();

        $options = get_option(BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION);

        $nonce = MembershipRandomString(NONCE_LENGTH);
        $secret = MembershipRandomString(SECRET_LENGTH);
        $this->registration_data->SetDataInt(MembershipRegistrationDataClass::TIMESTAMP, time());
        $this->registration_data->SetDataString(MembershipRegistrationDataClass::NONCE, $nonce);
        $this->registration_data->SetDataString(MembershipRegistrationDataClass::SECRET, $secret);
        $this->registration_data->SetDataInt(MembershipRegistrationDataClass::STATE, MembershipRegistrationDataClass::STATE_RESET_PASSWD_EMAIL_SENT);
        $this->registration_data->SetDataString(MembershipRegistrationDataClass::EMAIL, $email);
        $this->registration_data->SetDataInt(MembershipRegistrationDataClass::WP_USER_ID, $wp_user_id);
        $reg_id = $this->registration_data->SaveData();

        $site_name = get_bloginfo('name');
        $site_url = site_url();

        $user_info = get_userdata($wp_user_id);
        $username = $user_info->user_login;

        $link = site_url() . '?' . REG_EVENT . '=' . REG_EVENT_PASSWORD_LINK . '&' . REG_ID . '=' . $reg_id . '&' . REG_NONCE . '=' . $nonce . '&' . REG_SECRET . '=' . $secret . '&p=' . get_the_ID();
        $href = '<a href="' . $link . '">' . $link . '</a>';

        $body = $options['email_body'];
        $body = str_replace('{site_name}', $site_name, $body);
        $body = str_replace('{site_url}', $site_url, $body);
        $body = str_replace('{username}', $username, $body);
        $body = str_replace('{link}', $href, $body);

        $reset_email = new Email($email);
        $reset_email->SetFromAddress($options['email_replay_addr']);
        $reset_email->SetSubject($options['email_subject']);
        $reset_email->SetBody($body);

        return $reset_email->Send();
    }

    protected function SendEmailNotificationNewUser($wp_user_id)
    {
        WriteDebugLogFunctionCall();

        $options = get_option(BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION);

        if($options['send_notification'])
        {
            $site_name = get_bloginfo('name');
            $site_url  = site_url();

            $user_info = get_userdata($wp_user_id);
            $username  = $user_info->user_login;

            $body = $options['email_body'];
            $body = str_replace('{site_name}', $site_name, $body);
            $body = str_replace('{site_url}', $site_url, $body);
            $body = str_replace('{username}', $username, $body);

            $reset_email = new Email($options['email_sendto']);
            $reset_email->SetFromAddress($options['email_replay_addr']);
            $reset_email->SetSubject($options['email_subject']);
            $reset_email->SetBody($body);

            $result = $reset_email->Send();
        }
    }


    protected function UpdatePassword($password, $wp_user_id=null)
    {
        WriteDebugLogFunctionCall('', $password_arg_no=0);

        $result = false;
        if($wp_user_id)
        {
            wp_set_password( $password, $wp_user_id );
        }
        else
        {
            $state = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::STATE);
            if($state ==   MembershipRegistrationDataClass::STATE_RESET_PASSWD_EMAIL_SENT)
            {
                $wp_user_id = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::WP_USER_ID);
                wp_set_password($password, $wp_user_id);

                $this->registration_data->SetDataInt(MembershipRegistrationDataClass::STATE, MembershipRegistrationDataClass::STATE_RESET_PASSWD_DONE);
                $this->registration_data->SaveData();

                $result = true;
            }
        }
        return $result;
    }

    protected function HasResetPasswordOpen()
    {
        $state = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::STATE);

        return ($state == MembershipRegistrationDataClass::STATE_RESET_PASSWD_EMAIL_SENT);
    }

    public function HasAllRequiredInfo()
    {
        $username = $this->registration_data->GetDataString(MembershipRegistrationDataClass::USERNAME);
        $password =  $this->registration_data->GetDataString(MembershipRegistrationDataClass::PASSWORD);
        $email =  $this->registration_data->GetDataString(MembershipRegistrationDataClass::EMAIL);
        $state = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::STATE);

        return ($username and $password and $email and $state == MembershipRegistrationDataClass::STATE_EMAIL_CONFIRMED);
    }

    public function UserExist()
    {
        $username = $this->registration_data->GetDataString(MembershipRegistrationDataClass::USERNAME);

        $user_id = username_exists($username);
        if($user_id === false){
            return false;
        }else{
            return true;
        }
    }

    public function EmailExist()
    {
        $email = $this->registration_data->GetDataString(MembershipRegistrationDataClass::EMAIL);

        $user_id = email_exists($email);
        if($user_id === false){
            return false;
        }else{
            return true;
        }
    }

    public function CreateNewUser()
    {
        WriteDebugLogFunctionCall();

        $result = false;

        $username = $this->registration_data->GetDataString(MembershipRegistrationDataClass::USERNAME);

        if(validate_username($username))
        {
            $email = $this->registration_data->GetDataString(MembershipRegistrationDataClass::EMAIL);

            $temp_password = wp_hash_password(MembershipRandomString(8));

            $wp_user_id = wp_create_user($username, $temp_password, $email);

            if( ! is_wp_error($wp_user_id))
            {
                $password_hash = $this->registration_data->GetDataString(MembershipRegistrationDataClass::PASSWORD);
                $this->wp_set_hashed_password($password_hash, $wp_user_id);

                $this->registration_data->SetDataInt(MembershipRegistrationDataClass::STATE, MembershipRegistrationDataClass::STATE_USER_CREATED);
                $this->registration_data->SetDataInt(MembershipRegistrationDataClass::WP_USER_ID, $wp_user_id);
                $this->registration_data->SaveData();

                $this->SendEmailNotificationNewUser($wp_user_id);

                AutroresponderSubscribeEmail($email);

                $result = true;
            }
        }

        return $result;
    }

    private function wp_set_hashed_password($password_hashed, $user_id )
    {
        global $wpdb;
        $wpdb->update($wpdb->users, array('user_pass' => $password_hashed, 'user_activation_key' => ''), array('ID' => $user_id) );
        wp_cache_delete($user_id, 'users');
    }

    public function LogInRegisteredUser($remember=false)
    {
        WriteDebugLogFunctionCall();

        $username = $this->registration_data->GetDataString(MembershipRegistrationDataClass::USERNAME);

        $user = get_user_by('login', $username);
        if(!($user === false))
        {
            $user_id = $user->ID;
            wp_set_current_user($user_id, $username);
            wp_set_auth_cookie($user_id);
            do_action('wp_login', $username);

            WriteDebugNote(NOTE, 'User ' . $username . ' logged in.');

            return true;
        }
        else
        {
            WriteDebugNote(NOTE, 'User ' . $username . ' failed to log in.');

            return false;
        }
    }


    public function GetRegId()
    {
        return $this->registration_data->GetDataInt(MembershipRegistrationDataClass::ID);
    }

    public function GetNonce()
    {
        return $this->registration_data->GetDataString(MembershipRegistrationDataClass::NONCE);
    }

    public function GetSecret()
    {
        return $this->registration_data->GetDataString(MembershipRegistrationDataClass::SECRET);
    }

    public function GetRegistrationState()
    {
        return $this->registration_data->GetDataInt(MembershipRegistrationDataClass::STATE);
    }

    public function GetUsername()
    {
        return $this->registration_data->GetDataString(MembershipRegistrationDataClass::USERNAME);
    }

    public function GetPassword()
    {
        return $this->registration_data->GetDataString(MembershipRegistrationDataClass::PASSWORD);
    }

    public function GetEmail()
    {
        return $this->registration_data->GetDataString(MembershipRegistrationDataClass::EMAIL);
    }

    public function GetWpUserId()
    {
        return $this->registration_data->GetDataInt(MembershipRegistrationDataClass::WP_USER_ID);
    }

    public function GetCookie()
    {
        return $this->registration_data->GetDataString(MembershipRegistrationDataClass::COOCKIE);
    }

    public function GetErrorMessage()
    {
        return $this->error_message;
    }
}
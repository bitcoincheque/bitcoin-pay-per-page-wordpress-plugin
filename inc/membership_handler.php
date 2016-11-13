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

require_once ('email.php');

class RegistrationHandlerClass
{
    const RESULT_OK = 0;
    const RESULT_NONCE_ERROR = 1;
    const RESULT_CONFIRM_INVALID = 2;
    const RESULT_ERROR_UNDEFINED = 3;
    const RESULT_CONFIRM_IS_DONE = 4;
    const RESULT_USER_EXIST = 5;

    private $registration_data = null;
    private $has_data = false;
    private $error_message = '';

    public function __construct($reg_id=null)
    {
        $this->registration_data = new MembershipRegistrationDataClass();

        if($reg_id != null)
        {
            if($this->registration_data->LoadData($reg_id))
            {
                $this->has_data = true;
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
        $reg_id = 0;

        if($username != '' && $password != '')
        {
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

                $nonce = MembershipRandomString();
                $this->registration_data->SetDataString(MembershipRegistrationDataClass::NONCE, $nonce);
                $reg_id = $this->registration_data->SaveData();

                $retry_counter=0;
            }
            else
            {
                $this->registration_data->SetDataString(MembershipRegistrationDataClass::EMAIL, $email);

                $nonce = $this->registration_data->GetDataString(MembershipRegistrationDataClass::NONCE);
                if($nonce == '')
                {
                    $nonce = MembershipRandomString();
                    $this->registration_data->SetDataString(MembershipRegistrationDataClass::NONCE, $nonce);
                }

                $this->registration_data->AddDataInt(MembershipRegistrationDataClass::RETRY_COUNTER, 1);
                $this->registration_data->SaveData();

                $reg_id = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::ID);
                $retry_counter = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::RETRY_COUNTER);
                $post_id = $this->registration_data->GetDataInt(MembershipRegistrationDataClass::POST_ID);
            }

            if($retry_counter < 5)
            {
                $verification_link = site_url() . '?' . REG_EVENT . '=' . REG_EVENT_CONFIRM_EMAIL . '&' . REG_ID . '=' . $reg_id . '&' . REG_NONCE . '=' . $nonce;

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
        $body = '<p>This e-mail has been sent from '. site_url() . ' as a response to user registration.</p>';
        $body .= '<p>We need to verify your e-mail address:</p>';
        $body .= '<p><a href="' . $link . '">' . $link . '</a></p>';

        $verification_email = new Email($email);
        $verification_email->SetFromAddress('no_replay@hegvik.no');
        $verification_email->SetSubject('Verify your e-mail address');
        $verification_email->SetBody($body);

        return $verification_email->Send();

    }

    public function ConfirmEmail($confirm_nonce)
    {
        $result =  self::RESULT_ERROR_UNDEFINED;

        $nonce = $this->registration_data->GetDataString(MembershipRegistrationDataClass::NONCE);

        if($nonce == $confirm_nonce)
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
                    $result = self::RESULT_CONFIRM_INVALID;
                    break;
            }
        }else{
            $result = self::RESULT_NONCE_ERROR;
        }

        return $result;
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
        $username = $this->registration_data->GetDataString(MembershipRegistrationDataClass::USERNAME);
        $email =  $this->registration_data->GetDataString(MembershipRegistrationDataClass::EMAIL);

        $temp_password = wp_hash_password(MembershipRandomString());

        $wp_user_id = wp_create_user($username, $temp_password, $email);

        if( ! is_wp_error($wp_user_id)){
            $password_hash =  $this->registration_data->GetDataString(MembershipRegistrationDataClass::PASSWORD);
            $this->wp_set_hassed_password($password_hash,$wp_user_id);

            $this->registration_data->SetDataInt(MembershipRegistrationDataClass::STATE, MembershipRegistrationDataClass::STATE_USER_CREATED);
            $this->registration_data->SetDataInt(MembershipRegistrationDataClass::WP_USER_ID, $wp_user_id);
            $this->registration_data->SaveData();
            return true;
        }else{
            return false;
        }
    }

    private function wp_set_hassed_password( $password_hashed, $user_id )
    {
        global $wpdb;
        $wpdb->update($wpdb->users, array('user_pass' => $password_hashed, 'user_activation_key' => ''), array('ID' => $user_id) );
        wp_cache_delete($user_id, 'users');
    }

    public function LogInUser($remember=false)
    {
        $username = $this->registration_data->GetDataString(MembershipRegistrationDataClass::USERNAME);

        $user = get_user_by('login', $username);
        if(!($user === false))
        {
            $user_id = $user->ID;
            wp_set_current_user($user_id, $username);
            wp_set_auth_cookie($user_id);
            do_action('wp_login', $username);

            return true;
        }
        else
        {
            return false;
        }
    }

    public function GetRegId()
    {
        return $this->registration_data->GetDataInt(MembershipRegistrationDataClass::ID);
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

    public function GetCookie()
    {
        return $this->registration_data->GetDataString(MembershipRegistrationDataClass::COOCKIE);
    }

    public function GetErrorMessage()
    {
        return $this->error_message;
    }
}
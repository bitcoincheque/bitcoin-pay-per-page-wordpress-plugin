<?php
/**
 * Library for formating and sending e-mail
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

namespace BCF_Email;

use BCF_PayPerPage;

require_once ('debug_log.php');

class Email
{
    private $receiver_address_list = array();
    private $from_address = '';
    private $copy_address_list = array();
    private $subject = '';
    private $body = '';

    public function __construct($receiver_address='')
    {
        if($receiver_address != '')
        {
            $this->AddReceiverAddress($receiver_address);
        }
    }

    public function SetFromAddress($email_adr)
    {
        if (filter_var($email_adr, FILTER_VALIDATE_EMAIL))
        {
            $this->from_address = $email_adr;
            return true;
        }
        else
        {
            return false;
        }
    }

    public function AddReceiverAddress($email_adr)
    {
        if (filter_var($email_adr, FILTER_VALIDATE_EMAIL))
        {
            $this->receiver_address_list[] = $email_adr;
            return true;
        }
        else
        {
            return false;
        }
    }

    public function AddCopyAddress($email_adr)
    {
        if (filter_var($email_adr, FILTER_VALIDATE_EMAIL))
        {
            $this->copy_address_list[] = $email_adr;
            return true;
        }
        else
        {
            return false;
        }
    }

    public function SetSubject($subject_str)
    {
        $this->subject = $subject_str;
    }

    public function SetBody($body_str)
    {
        $this->body = $body_str;
    }

    public function Send()
    {
        $result = false;
        $headers = array('Content-Type: text/html; charset=UTF-8');

        if($this->from_address != '')
        {
            $headers[] = 'From: ' . $this->from_address;
            $headers[] = 'Reply-To: ' . $this->from_address;
        }

        foreach($this->copy_address_list as $cc)
        {
            $headers[] = 'Cc: ' . $cc;
        }

        if($this->receiver_address_list[0] != '')
        {
            if(wp_mail($this->receiver_address_list[0], $this->subject, $this->body, $headers))
            {
                $result = true;
            }
        }

        BCF_PayPerPage\WriteDebugLogFunctionResult($result, $this->receiver_address_list[0], $this->subject);

        return $result;
    }

}
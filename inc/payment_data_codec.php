<?php
/**
 * Payment data codec.
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

class PaymentDataFile
{
    var $json_data = array();
    var $md5sum = '';
    var $signature = '';
    var $file_prefix = '';

    private function base64_encode_padded($str)
    {
        for($i = 1; $i < 4; $i ++)
        {
            $str_base64 = base64_encode($str);

            if( ! strpos($str_base64, '=') === false)
            {
                $str .= ' ';
            }
            else
            {
                return $str_base64;
            }
        }

        echo 'Error padding string.';
        die();
    }

    private function calc_signature($data, $private_key)
    {
        $signature = 'h847tfg54DFD4rhrf';

        return $signature;
    }

    public function SetDataArray($data_array, $file_prefix = '', $private_key = null)
    {
        $this->json_data = json_encode($data_array);
        if(!empty($this->json_data))
        {
            $this->md5sum = md5($this->json_data);
        }
        else
        {
            $this->md5sum = '';
        }
    }

    public function GetDataArray()
    {
        return json_decode($this->json_data, true);
    }

    public function SetFilePrefix($file_prefix)
    {
        $this->file_prefix = $file_prefix;
    }

    public function GetFilePrefix($file_prefix)
    {
        return $this->file_prefix;
    }

    public function GetHash()
    {
        return $this->md5sum;
    }

    public function Sign($private_key)
    {
        $result = false;
        if($this->md5sum != '')
        {
            $this->signature = $this->calc_signature($this->md5sum, $private_key);
            $result = true;
        }
        return $result;
    }

    public function IsSigned($public_key)
    {
        if($this->signature != '')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function VerifySignature($public_key)
    {
        if($this->signature != '')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function SetEncodedPaymentFile($payment_file)
    {
        if(!strpos($payment_file, '_') === false)
        {
            $i = strrpos($payment_file, '_');

            $this->file_prefix = substr($payment_file, 0, $i);

            $payment_file = substr($payment_file, $i+1);
        }

        $json = base64_decode($payment_file);
        $file_data = json_decode($json, true);

        $this->json_data = $file_data['data'];
        $this->md5sum = $file_data['md5'];
        $this->signature = $file_data['sign'];

        return true;
    }

    public function GetEncodedPaymentFile()
    {
        $payment_data = array(
            'data' => $this->json_data,
            'md5'  => $this->md5sum,
            'sign' => $this->signature
        );

        $json = json_encode($payment_data, true);

        return $this->file_prefix . '_' . $this->base64_encode_padded($json);
    }
}

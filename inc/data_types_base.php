<?php
/**
 * Data containers base class.
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

class BaseTypeClass
{
    private $Data;
    private $DataMin;
    private $DataMax;
    private $DbPrimaryKey;
    private $HasValidData;

    protected function __construct($default_value, $primary_key, $meta_data)
    {
        $this->DataType = $meta_data['data_type'];
        $this->DataMin = $meta_data['data_min'];
        $this->DataMax = $meta_data['data_max'];
        $this->DbPrimaryKey = $primary_key;

        if($this->SanitizeData($default_value))
        {
            $this->Data = $default_value;
            $this->HasValidData = true;
        }
        else
        {
            $this->HasValidData = false;
        }
    }

    protected function SanitizeData($value)
    {
        if (!is_null($value))
        {
            if(gettype($value) == $this->DataType)
            {
                if($this->DataType == 'integer')
                {
                    if(($value < $this->DataMin) or ($value > $this->DataMax))
                    {
                        return false;
                    }
                }
                elseif($this->DataType == 'string')
                {
                    $length = strlen($value);
                    if(($length < $this->DataMin) or ($length > $this->DataMax))
                    {
                        error_log("ERROR. " . get_class(self) . " String out of range");

                        return false;
                    }
                }
                else
                {
                    error_log("ERROR. " . get_class(self) . " Missing sanitize functionality for type " . gettype($value));

                    return false;
                }
            }
            else
            {
                error_log("ERROR. " . get_class(self) . " SanitizeData failed. Value type is " . gettype($value) . ", expected " . $this->DataType);

                return false;
            }
        }
        else
        {
            return false;
        }

        return true;
    }

    public function SetData($data)
    {
        if(static::SanitizeData($data))
        {
            $this->Data = $data;
            $this->HasValidData = true;
        }
        else
        {
            $this->HasValidData = false;
        }

        return $this->HasValidData;
    }

    protected function GetData()
    {
        return $this->Data;
    }

    public function Sanitize()
    {
        if($this->HasValidData)
        {
            return $this->SanitizeData( $this->Data );
        }
        else
        {
            return false;
        }
    }

    public function HasValidData()
    {
        return $this->HasValidData;
    }

    public function GetDataType()
    {
        return $this->MetaData['data_type'];
    }

    public function GetDataMySqlType()
    {
        return $this->MetaData['mysql_type'];
    }

    public function GetDataTypeMin()
    {
        return $this->MetaData['data_min'];
    }

    public function GetDataTypeMax()
    {
        return $this->MetaData['data_max'];
    }

}

/***********************************************************************************************************************
 * Bank User Id Type (This is an unique additional id for each bank user besides the wordpress id.)
 **********************************************************************************************************************/
class UserIdTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'integer',
        'mysql_type' => 'INT(6)',
        'data_min'  => 1,
        'data_max'  => PHP_INT_MAX
    );

    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetDataFromString($data_str)
    {
        $data = intval($data_str);
        return parent::SetData($data);
    }

    public function GetInt()
    {
        return parent::GetData();
    }

    public function GetString()
    {
        return strval(parent::GetData());
    }
}

function SanitizeUserId($bank_user_id)
{
    if(gettype($bank_user_id) == 'object')
    {
        if(get_class($bank_user_id) == __NAMESPACE__ . '\UserIdTypeClass' )
        {
            return $bank_user_id->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Cheque Id Type
 **********************************************************************************************************************/
class ChequeIdTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'integer',
        'mysql_type' => 'INT(6)',
        'data_min'  => 1,
        'data_max'  => PHP_INT_MAX
    );

    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetDataFromString($data_str)
    {
        $data = intval($data_str);
        return parent::SetData($data);
    }

    public function GetInt()
    {
        return parent::GetData();
    }

    public function GetString()
    {
        return strval(parent::GetData());
    }
}

function SanitizeChequeId($bank_user_id)
{
    if(gettype($bank_user_id) == 'object')
    {
        if(get_class($bank_user_id) == __NAMESPACE__ . '\ChequeIdTypeClass' )
        {
            return $bank_user_id->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Wordpress User Id Type
 **********************************************************************************************************************/
class WpUserIdTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'integer',
        'mysql_type' => 'INT(6)',
        'data_min'  => 0,
        'data_max'  => PHP_INT_MAX
    );

    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetDataFromString($data_str)
    {
        $data = intval($data_str);
        return parent::SetData($data);
    }

    public function GetInt()
    {
        return parent::GetData();
    }

    public function GetString()
    {
        return strval(parent::GetData());
    }
}
function SanitizeWpUserId($wp_user_id)
{
    if(gettype($wp_user_id) == 'object')
    {
        if(get_class($wp_user_id) == __NAMESPACE__ . '\WpUserIdTypeClass' )
        {
            return $wp_user_id->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Integer Type
 **********************************************************************************************************************/
class UnsigedIntegerTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'integer',
        'mysql_type' => 'INT(6)',
        'data_min'  => 0,
        'data_max'  => PHP_INT_MAX
    );


    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetDataFromString($data_str)
    {
        $data = intval($data_str);
        return parent::SetData($data);
    }
    public function GetString()
    {
        return strval( parent::GetData() );
    }
}
function SanitizeUnsignedInteger($uint_obj)
{
    if(gettype($uint_obj) == 'object')
    {
        if(get_class($uint_obj) == __NAMESPACE__ . '\UnsigedIntegerTypeClass' )
        {
            return $uint_obj->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Text (string) Type
 **********************************************************************************************************************/
class TextTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'string',
        'mysql_type' => 'TINYTEXT',
        'data_min'  => 0,
        'data_max'  => 250
    );


    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetDataFromString($data_str)
    {
        return parent::SetData($data_str);
    }
    public function GetString()
    {
        return strval( parent::GetData() );
    }
}
function SanitizeText($bank_user_id)
{
    if(gettype($bank_user_id) == 'object')
    {
        if(get_class($bank_user_id) == __NAMESPACE__ . '\TextTypeClass' )
        {
            return $bank_user_id->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Name Type
 **********************************************************************************************************************/
class NameTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'string',
        'mysql_type' => 'TINYTEXT',
        'data_min'  => 0,
        'data_max'  => 32
    );


    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetDataFromString($data_str)
    {
        return parent::SetData($data_str);
    }

    public function GetString()
    {
        return parent::GetData();
    }
}
function SanitizeName($name)
{
    if(gettype($name) == 'object')
    {
        if(get_class($name) == __NAMESPACE__ . '\NameTypeClass' )
        {
            return $name->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Password Type
 **********************************************************************************************************************/
class PasswordTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'string',
        'mysql_type' => 'VARCHAR',
        'data_min'  => 0,
        'data_max'  => 32
    );

    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetDataFromString($data_str)
    {
        return parent::SetData($data_str);
    }
    public function GetString()
    {
        return parent::GetData();
    }
}
function SanitizePassword($password)
{
    if(gettype($password) == 'object')
    {
        if(get_class($password) == __NAMESPACE__ . '\PasswordTypeClass' )
        {
            return $password->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Account Id Type
 **********************************************************************************************************************/
class AccountIdTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'integer',
        'mysql_type' => 'INT(6)',
        'data_min'  => 1,
        'data_max'  => PHP_INT_MAX
    );

    public function __construct( $default_value, $primary_key=null )
    {
        parent::__construct( $default_value, $primary_key, $this->MetaData );
    }

    public function SetDataFromString( $data_str )
    {
        $data = intval( $data_str );

        return parent::SetData( $data );
    }

    public function GetInt()
    {
        return $this->GetData();
    }

    public function GetString()
    {
        return strval( $this->GetData() );
    }

    public function GetFormatedString()
    {
        $account = $this->GetData();
        if ( SanitizePositiveInteger( $account ) )
        {
            $strnumber = sprintf( "%1$09d", $account );
            $str       = substr( $strnumber, 0, 3 ) . '.' . substr( $strnumber, 3, 3 ) . '.' . substr( $strnumber, 6, 3 );
        }
        else
        {
            $str = 'Invalid account';
        }

        return $str;
    }
}
function SanitizeAccountId($account_id)
{
    if(gettype($account_id) == 'object')
    {
        if(get_class($account_id) == __NAMESPACE__ . '\AccountIdTypeClass' )
        {
            return $account_id->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Amount Value Type
 **********************************************************************************************************************/
class ValueTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'integer',
        'mysql_type' => 'BIGINT(20)',
        'data_min'  => -999999999,
        'data_max'  => PHP_INT_MAX
    );

    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetValue($value)
    {
        return parent::SetData($value);
    }

    public function SetDataFromString($data_str)
    {
        $data = intval($data_str);
        return parent::SetData($data);
    }

    public function GetInt()
    {
        return parent::GetData();
    }

    public function GetString()
    {
        return strval(parent::GetData());
    }

    private function FormatedLongCurrency($value, $fractional_length, $decimal_mark)
    {
        if($value >= 0) {
            $formatter = '%1$0' . intval($fractional_length + 1) . 'd';
        }
        else {
            $formatter = '%1$0' . intval( $fractional_length + 2 ) . 'd';
        }
        $str = sprintf($formatter, $value);
        $str = strrev($str);
        $right_part = substr($str, 0, $fractional_length);
        $left_part = substr($str, $fractional_length);
        $str = $right_part . $decimal_mark . $left_part;
        $str = strrev($str);
        return $str;
    }

    public function GetFormattedCurrencyString($currency, $include_currency_text=false, $decimal_mark=',')
    {
        if($this->HasValidData())
        {
            $value = $this->GetInt();

            if($currency == 'BTC' )
            {
                $str = $this->FormatedLongCurrency($value, 8, $decimal_mark);
                if($include_currency_text)
                {
                    $str .= ' BTC';
                }
            }
            elseif($currency == 'mBTC' )
            {
                $str = $this->FormatedLongCurrency($value, 5, $decimal_mark);
                if($include_currency_text)
                {
                    $str .= ' mBTC';
                }
            }
            elseif($currency == 'uBTC' )
            {
                $str = $this->FormatedLongCurrency($value, 2, $decimal_mark);
                if($include_currency_text)
                {
                    $str .= ' uBTC';
                }
            }
            else
            {
                $str = 'Error: Unknown currency';
            }
        }
        else
        {
            $str = 'No data.';
        }
        return $str;
    }
}
function SanitizeAmount($amount)
{
    if(gettype($amount) == 'object')
    {
        if(get_class($amount) == __NAMESPACE__ . '\ValueTypeClass' )
        {
            return $amount->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Transaction Id Type
 **********************************************************************************************************************/
class TransactionIdTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'integer',
        'mysql_type' => 'INT(6)',
        'data_min'  => 1,
        'data_max'  => PHP_INT_MAX
    );

    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetDataFromString($data_str)
    {
        $data = intval($data_str);
        return parent::SetData($data);
    }
    public function GetInt()
    {
        return parent::GetData();
    }
    public function GetString()
    {
        return strval(parent::GetData());
    }

}
function SanitizeTransactionId($transaction_id)
{
    if(gettype($transaction_id) == 'object')
    {
        if(get_class($transaction_id) == __NAMESPACE__ . '\TransactionIdTypeClass' )
        {
            return $transaction_id->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Cheque State Type
 **********************************************************************************************************************/
class ChequeStateTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'string',
        'mysql_type' => "ENUM('UNCLAIMED','CLAIMED','EXPIRED','HOLD')",
        'data_min'  => 0,
        'data_max'  => 32
    );

    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    protected function SanitizeData($value)
    {
        if(parent::SanitizeData($value))
        {
            if ( ( $value == 'UNCLAIMED' ) or ( $value == 'CLAIMED' ) or ( $value == 'EXPIRED' ) or ( $value == 'HOLD' ) )
            {
                return true;
            }
            else
            {
                error_log("ERROR. SanitizeData failed in class " . get_class($this) . " Illegal value=" . $value);
                return false;
            }
        }
        else
        {
            error_log("ERROR. SanitizeData failed in class " . get_class($this) . " value=" . $value);
        }
    }

    public function SetDataFromString($data_str)
    {
        return parent::SetData($data_str);
    }
    public function GetString()
    {
        return strval(parent::GetData());
    }
}
function SanitizeChequeState($bank_user_id)
{
    if(gettype($bank_user_id) == 'object')
    {
        if(get_class($bank_user_id) == __NAMESPACE__ . '\ChequeStateTypeClass' )
        {
            return $bank_user_id->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * DateTime Type
 **********************************************************************************************************************/
class DateTimeTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'string',
        'mysql_type' => "TIMESTAMP",
        'data_min'  => 0,
        'data_max'  => 32
    );

    public function __construct($default_value, $primary_key=null)
    {
        if(gettype($default_value) =='integer')
        {
            if($default_value > 0)
            {
                $default_value = date('Y-m-d H:i:s', $default_value);
            }
        }

        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    public function SetDataFromString($data_str)
    {
        return parent::SetData($data_str);
    }

    public function GetString()
    {
        return parent::GetData();
    }

    public function GetSeconds()
    {
        $datetime_str = parent::GetData();
        $seconds = strtotime($datetime_str);
        return $seconds;
    }
}

function SanitizeDateTime($bank_user_id)
{
    if(gettype($bank_user_id) == 'object')
    {
        if(get_class($bank_user_id) == __NAMESPACE__ . '\DateTimeTypeClass' )
        {
            return $bank_user_id->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Transaction Type
 **********************************************************************************************************************/
class TransactionDirTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'string',
        'mysql_type' => "ENUM('INITIAL','ADD','WITHDRAW','CHEQUE','REIMBURSEMENT')",
        'data_min'  => 0,
        'data_max'  => 32
    );

    public function __construct($default_value, $primary_key=null)
    {
        parent::__construct($default_value, $primary_key, $this->MetaData);
    }

    protected function SanitizeData($value)
    {
        if(parent::SanitizeData($value))
        {
            if ( ( $value == 'NA' ) or( $value == 'INITIAL' ) or ( $value == 'ADD' ) or ( $value == 'WITHDRAW' ) or ( $value == 'CHEQUE' ) or ( $value == 'REIMBURSEMENT' ) )
            {
                return true;
            }
            else
            {
                error_log("ERROR. SanitizeData failed in class " . get_class($this) . " Illegal value=" . $value);
                return false;
            }
        }
        else
        {
            error_log("ERROR. SanitizeData failed in class " . get_class($this) . " value=" . $value);
        }
    }

    public function SetDataFromString($data_str)
    {
        return parent::SetData($data_str);
    }

    public function GetString()
    {
        return parent::GetData();
    }

    public function IsDebitType()
    {
        $transaction_type = $this->GetString();
        if($transaction_type == 'ADD' || $transaction_type == 'REIMBURSEMENT')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function IsCreditType()
    {
        $transaction_type = $this->GetString();
        if($transaction_type == 'WITHDRAW' || $transaction_type == 'CHEQUE')
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}

function SanitizeTransactionType($transaction_type)
{
    if(gettype($transaction_type) == 'object')
    {
        if(get_class($transaction_type) == __NAMESPACE__ . '\TransactionDirTypeClass' )
        {
            return $transaction_type->Sanitize();
        }
    }
    return false;
}

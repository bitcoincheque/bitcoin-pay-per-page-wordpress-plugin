<?php
/**
 * Data containers.
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

require_once ('data_types_base.php');

/***********************************************************************************************************************
 * Bank User Id Type (This is an unique additional id for each bank user besides the wordpress id.)
 **********************************************************************************************************************/
class PageViewIdTypeClass extends BaseTypeClass
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

function SanitizePageViewId($pageview_id)
{
    if(gettype($pageview_id) == 'object')
    {
        if(get_class($pageview_id) == __NAMESPACE__ . '\PageViewIdTypeClass' )
        {
            return $pageview_id->Sanitize();
        }
    }
    return false;
}

/***********************************************************************************************************************
 * Pay Status Type
 **********************************************************************************************************************/
class PayStatusTypeClass extends BaseTypeClass
{
    protected $MetaData = array(
        'data_type' => 'string',
        'mysql_type' => "ENUM('UNPAID','PAID')",
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
            if ( ( $value == 'UNPAID' ) or ( $value == 'PAID' ) )
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
function SanitizePayStatus($pay_status_obj)
{
    if(gettype($pay_status_obj) == 'object')
    {
        if(get_class($pay_status_obj) ==  __NAMESPACE__ . '\PayStatusTypeClass' )
        {
            return $pay_status_obj->Sanitize();
        }
    }
    return false;
}
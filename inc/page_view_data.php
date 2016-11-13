<?php
/**
 * Page view data class library.
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

require_once('data_collection.php');
require_once('data_types.php');


class PageView_Class extends DataCollectionClass
{
    /* Database table name: */
    const DB_TABLE_NAME = 'bcf_payperpage_pageview';

    /* List of table field names: */
    const DB_FIELD_PAGEVIEW_ID = 'pageview_id';
    const DB_FIELD_DATETIME = 'datetime';
    const DB_FIELD_POST_ID = 'post_id';
    const DB_FIELD_USER_ID = 'user_id';
    const DB_FIELD_PRICE = 'price';
    const DB_FIELD_PAY_STATUS = 'pay_status';
    const DB_FIELD_NONCE = 'nonce';

    /* Metadata describing database field and data properties: */
    protected $MetaData = array(
        self::DB_FIELD_PAGEVIEW_ID => array(
            'class_type'    => 'PageViewIdTypeClass',
            'db_field_name' => self::DB_FIELD_PAGEVIEW_ID,
            'db_primary_key'=> true,
            'default_value' => 0
        ),
        self::DB_FIELD_DATETIME => array(
            'class_type'    => 'DateTimeTypeClass',
            'db_field_name' => self::DB_FIELD_DATETIME,
            'db_primary_key'=> false,
            'default_value' => ''
        ),
        self::DB_FIELD_POST_ID    => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::DB_FIELD_POST_ID,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::DB_FIELD_USER_ID    => array(
            'class_type'    => 'UserIdTypeClass',
            'db_field_name' => self::DB_FIELD_USER_ID,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::DB_FIELD_PRICE => array(
            'class_type'    => 'ValueTypeClass',
            'db_field_name' => self::DB_FIELD_PRICE,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::DB_FIELD_PAY_STATUS => array(
            'class_type'    => 'PayStatusTypeClass',
            'db_field_name' => self::DB_FIELD_PAY_STATUS,
            'db_primary_key'=> false,
            'default_value' => 'UNPAID'
        ),
        self::DB_FIELD_NONCE => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_NONCE,
            'db_primary_key'=> false,
            'default_value' => ''
        )
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function SetPageViewId($pageview_id)
    {
        if(SanitizeAccountId($pageview_id))
        {
            return $this->SetDataObject(self::DB_FIELD_PAGEVIEW_ID, $pageview_id);
        }
        return false;
    }

    public function GetPageViewId()
    {
        return $this->GetDataObjects(self::DB_FIELD_PAGEVIEW_ID);
    }

    public function SetDateTime($datetime)
    {
        if(SanitizeDateTime($datetime))
        {
            return $this->SetDataObject(self::DB_FIELD_DATETIME, $datetime);
        }
        return false;
    }

    public function GetDateTime()
    {
        return $this->GetDataObjects(self::DB_FIELD_DATETIME);
    }

    public function SetPostId($post_id)
    {
        if(SanitizeUnsignedInteger($post_id))
        {
            return $this->SetDataObject(self::DB_FIELD_POST_ID, $post_id);
        }
        return false;
    }

    public function GetPostId()
    {
        return $this->GetDataObjects(self::DB_FIELD_POST_ID);
    }
    

    public function SetUserId($user_id)
    {
        if(SanitizeUserId($user_id))
        {
            return $this->SetDataObject(self::DB_FIELD_USER_ID, $user_id);
        }
        return false;
    }

    public function GetUserId()
    {
        return $this->GetDataObjects(self::DB_FIELD_USER_ID);
    }

    public function SetPrice($price)
    {
        if(SanitizeAmount($price))
        {
            return $this->SetDataObject(self::DB_FIELD_PRICE, $price);
        }
        return false;
    }

    public function GetPrice()
    {
        return $this->GetDataObjects(self::DB_FIELD_PRICE);
    }

    public function SetPayStatus($pay_status)
    {
        if(SanitizePayStatus($pay_status))
        {
            return $this->SetDataObject(self::DB_FIELD_PAY_STATUS, $pay_status);
        }
        return false;
    }

    public function GetPayStatus()
    {
        return $this->GetDataObjects(self::DB_FIELD_PAY_STATUS);
    }

    public function SetNonce($nonce)
    {
        if(SanitizeText($nonce))
        {
            return $this->SetDataObject(self::DB_FIELD_NONCE, $nonce);
        }
        return false;
    }

    public function GetNonce()
    {
        return $this->GetDataObjects(self::DB_FIELD_NONCE);
    }

}

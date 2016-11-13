<?php
/**
 * Membership registration and log-in library. Data collection 
 * class for registration process.
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

require_once('data_collection_base_class.php');
require_once('data_types.php');

define ('BCF_PAYPERPAGE_REGISTRATION_CLASS_NAME', __NAMESPACE__ . '\RegistrationDataClass');

class MembershipRegistrationDataClass extends DataBaseClass
{
    /* Database table name: */
    const DB_TABLE_NAME = 'bcf_payperpage_registration';

    /* List of table field names: */
    const ID = 'registration_id';
    const STATE = 'state';
    const USERNAME = 'username';
    const PASSWORD = 'passwd';
    const EMAIL = 'email';
    const WP_USER_ID = 'wp_user';
    const RETRY_COUNTER = 'retries';
    const COOCKIE = 'coockie';
    const NONCE = 'nonce';
    const POST_ID = 'post_id';

    /* State values: */
    const STATE_EMAIL_UNCONFIRMED = 0;
    const STATE_EMAIL_CONFIRMED = 1;
    const STATE_USER_CREATED = 2;

    /* Metadata describing database fields and data properties: */
    protected $MetaData = array
    (
        self::ID            => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::ID,
            'db_primary_key'=> true,
            'default_value' => null
        ),
        self::STATE            => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::STATE,
            'db_primary_key'=> false,
            'default_value' => self::STATE_EMAIL_UNCONFIRMED
        ),
        self::USERNAME      => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::USERNAME,
            'db_primary_key'=> false,
            'default_value' => ''
        ),
        self::PASSWORD               => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::PASSWORD,
            'db_primary_key'=> false,
            'default_value' => ''
        ),
        self::EMAIL          => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::EMAIL,
            'db_primary_key'=> false,
            'default_value' => ''
        ),
        self::WP_USER_ID    => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::WP_USER_ID,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::RETRY_COUNTER => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::RETRY_COUNTER,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::COOCKIE => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::COOCKIE,
            'db_primary_key'=> false,
            'default_value' => null
        ),
        self::NONCE         => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::NONCE,
            'db_primary_key'=> false,
            'default_value' => ''
        ),
        self::POST_ID => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::POST_ID,
            'db_primary_key'=> false,
            'default_value' => null
        )
    );

    public function __construct()
    {
        parent::__construct();
    }
}

function SanitizeRegistrationData($registration_data)
{
    if(gettype($registration_data) == 'object')
    {
        if(get_class($registration_data) == 'RegistrationDataClass' )
        {
            return $registration_data->SanitizeData();
        }
    }
    return false;
}

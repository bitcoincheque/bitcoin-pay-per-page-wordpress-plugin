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


require_once(__DIR__ . '/../wp-plugin-utils/db_table.php');


class MembershipRegistrationDataClass extends DataTableAbsClass
{
    /* Database table name: */
    const TABLE_NAME = 'bcf_payperpage_registration';

    /* List of table field names: */
    const TIMESTAMP = 'timestamp';
    const REG_TYPE = 'reg_type';
    const STATE = 'state';
    const USERNAME = 'username';
    const PASSWORD = 'passwd';
    const EMAIL = 'email';
    const WP_USER_ID = 'wp_user';
    const RETRY_COUNTER = 'retries';
    const COOCKIE = 'coockie';
    const NONCE = 'nonce';
    const POST_ID = 'post_id';
    const SECRET = 'secret';

    /* Registration types: */
    const REG_TYPE_NOT_SET = 0;
    const REG_TYPE_READ_MORE_REGISTRATION = 1;
    const REG_TYPE_USER_REGISTRATION = 2;
    const REG_TYPE_PASSWORD_RECOVERY = 3;
    const REG_TYPE_LOGIN = 4;
    const REG_TYPE_LOGOUT = 5;
    const REG_TYPE_PROFILE = 6;

    /* State values: */
    const STATE_EMAIL_UNCONFIRMED = 0;
    const STATE_EMAIL_CONFIRMED = 1;
    const STATE_USER_CREATED = 2;
    const STATE_RESET_PASSWD_EMAIL_SENT = 3;
    const STATE_RESET_PASSWD_EMAIL_CONFIRM = 4;
    const STATE_RESET_PASSWD_DONE = 5;
    const STATE_RESET_PASSWD_TIMEOUT = 6;

    /* Metadata describing database fields and data properties: */
    static $MetaData = array
    (
        self::TIMESTAMP     => array(
            'data_type'    => 'DbTypeTimeStamp',
            'default_value' => 0
        ),
        self::REG_TYPE      => array(
            'data_type'    => 'DbTypeUnsignedInteger',
            'default_value' => self::REG_TYPE_NOT_SET
        ),
        self::STATE            => array(
            'data_type'    => 'DbTypeUnsignedInteger',
            'default_value' => self::STATE_EMAIL_UNCONFIRMED
        ),
        self::USERNAME      => array(
            'data_type'    => 'DbTypeString',
            'default_value' => ''
        ),
        self::PASSWORD               => array(
            'data_type'    => 'DbTypeString',
            'default_value' => ''
        ),
        self::EMAIL          => array(
            'data_type'    => 'DbTypeString',
            'default_value' => ''
        ),
        self::WP_USER_ID    => array(
            'data_type'    => 'DbTypeUnsignedInteger',
            'default_value' => 0
        ),
        self::RETRY_COUNTER => array(
            'data_type'    => 'DbTypeUnsignedInteger',
            'default_value' => 0
        ),
        self::COOCKIE => array(
            'data_type'    => 'DbTypeString',
            'default_value' => null
        ),
        self::NONCE         => array(
            'data_type'    => 'DbTypeString',
            'default_value' => ''
        ),
        self::POST_ID => array(
            'data_type'    => 'DbTypeUnsignedInteger',
            'default_value' => null
        ),
        self::SECRET         => array(
            'data_type'    => 'DbTypeString',
            'default_value' => ''
        ),
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

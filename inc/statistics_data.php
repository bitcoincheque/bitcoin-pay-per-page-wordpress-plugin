<?php
/**
 * Membership registration statistics library. Data collection
 * class for statistics.
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


class StatisticsDataClass extends DataCollectionClass
{
    /* Database table name: */
    const DB_TABLE_NAME = 'bcf_payperpage_statistics';

    /* List of table field names: */
    const STAT_ID = 'stat_id';
    const POST_ID = 'post_id';
    const PAGEVIEW = 'pageview';
    const REGISTER = 'register';
    const VERIFY = 'verify';
    const COMPLETED = 'completed';

    /* Metadata describing database fields and data properties: */
    protected $MetaData = array
    (
        self::STAT_ID            => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::STAT_ID,
            'db_primary_key'=> true,
            'default_value' => null
        ),
        self::POST_ID            => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::POST_ID,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::PAGEVIEW            => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::PAGEVIEW,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::REGISTER            => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::REGISTER,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::VERIFY      => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::VERIFY,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::COMPLETED               => array(
            'class_type'    => 'UnsigedIntegerTypeClass',
            'db_field_name' => self::COMPLETED,
            'db_primary_key'=> false,
            'default_value' => 0
        )
    );

    public function __construct()
    {
        parent::__construct();
    }
}

function SanitizeRegistrationStatData($registration_data)
{
    if(gettype($registration_data) == 'object')
    {
        if(get_class($registration_data) == 'StatisticsDataClass' )
        {
            return $registration_data->SanitizeData();
        }
    }
    return false;
}

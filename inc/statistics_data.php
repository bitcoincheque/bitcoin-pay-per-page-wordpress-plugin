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


require_once(__DIR__ . '/../wp-plugin-utils/db_table.php');


class StatisticsDataClass extends DataTableAbsClass
{
    /* Database table name: */
    const TABLE_NAME = 'bcf_payperpage_statistics';

    /* List of table field names: */
    const POST_ID = 'post_id';
    const PAGEVIEW = 'pageview';
    const REGISTER = 'register';
    const VERIFY = 'verify';
    const COMPLETED = 'completed';

    /* Metadata describing database fields and data properties: */
    static $MetaData = array
    (
        self::POST_ID            => array(
            'data_type'    => 'DbTypeUnsignedInteger',
            'default_value' => 0
        ),
        self::PAGEVIEW            => array(
            'data_type'    => 'DbTypeUnsignedInteger',
            'default_value' => 0
        ),
        self::REGISTER            => array(
            'data_type'    => 'DbTypeUnsignedInteger',
            'default_value' => 0
        ),
        self::VERIFY      => array(
            'data_type'    => 'DbTypeUnsignedInteger',
            'default_value' => 0
        ),
        self::COMPLETED               => array(
            'data_type'    => 'DbTypeUnsignedInteger',
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

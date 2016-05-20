<?php
/**
 * Miscellaneous library written in php.
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

function SanitizePositiveIntegerOrZero($value)
{
    if(gettype($value) == 'integer'){
        if($value >= 0){
            return true;
        }else{
            error_log('SanitizePositiveIntegerOrZero error wrong value=' . $value);
            return false;
        }
    }  else{
        error_log('SanitizePositiveIntegerOrZero error wrong type.');
        return false;
    }
}

function SanitizePositiveInteger($value)
{
    if(gettype($value) == 'integer'){
        if($value > 0){
            return true;
        }else{
            error_log('SanitizePositiveInteger error wrong value=' . $value);
            return false;
        }
    }  else{
        error_log('SanitizePositiveInteger error wrong type.');
        return false;
    }
}

function SanitizeInteger($value)
{
    if(gettype($value) == 'integer')
    {
        return true;
    }
    else
    {
        error_log('SanitizeInteger error wrong type.');
        return false;
    }
}

function SanitizeTextAlpanumeric($text)
{
    return true;
}

function SanitizeTextAlpanumericSymbols($text)
{
    return true;

}

<?php
/**
 * Miscellaneous security function to filter GET and POST requests.
 *
 * Copyright (C) 2017 Arild Hegvik
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

class SecurityFilter
{
    const STRICT_STRING = 'strict_string';
    const POSITIVE_INTEGER = 'positive_integer';
    const POSITIVE_INTEGER_ZERO = 'positive_integer_zero';
    const BOOL = 'bool';
    const STRING_KEY_NAME = 'string_key_name';
    const ALPHA_NUM = 'alpha_num';
    const ALPHA = 'alpha';
}

function SanitizeText($text)
{
    if (preg_match('/^[A-Za-z0-9 .,;:+=?_~\/\-!@#\$%\^&\*\(\)]+$/', $text))
    {
        return strval($text);
    }
    else
    {
        return null;
    }
}

function SanitizeStrictText($text)
{
    $text = utf8_encode ( $text ); // Handle Norwegian special characters

    if (preg_match('/^[A-Za-z0-9øæåØÆÅö _.,?\-]+$/', $text))
    {
        return strval($text);
    }
    else
    {
        return null;
    }
}

function SanitizeAlphaNumText($text)
{
    $text = utf8_encode ( $text ); // Handle Norwegian special characters

    if (preg_match('/^[A-Za-z0-9]+$/', $text))
    {
        return strval($text);
    }
    else
    {
        return null;
    }
}

function SanitizeAlphaText($text)
{
    $text = utf8_encode($text); // Handle Norwegian special characters

    if (preg_match('/^[A-Za-z]+$/', $text)) {
        return strval($text);
    } else {
        return null;
    }
}

function SanitizeKeyNameText($text)
{
    $text = utf8_encode ( $text ); // Handle Norwegian special characters

    if (preg_match('/^[A-Za-z0-9_]+$/', $text))
    {
        return strval($text);
    }
    else
    {
        return null;
    }
}

function SanitizeInteger($text)
{
    if (preg_match('/^[1-9][0-9]{0,15}$/', $text))
    {
        $value = intval($text);
        if(gettype($value) == 'integer')
        {
            return $value;
        }
    }

    return null;
}

function SanitizePositiveIntegerOrZero($text)
{
    if (preg_match('/^[1-9][0-9]{0,15}$/', $text))
    {
        $value = intval($text);
        if(gettype($value) == 'integer')
        {
            if($value >= 0)
            {
                return $value;
            }
        }
    }

    return null;
}

function SanitizePositiveInteger($text)
{
    if (preg_match('/^[1-9][0-9]{0,15}$/', $text))
    {
        $value = intval($text);
        if(gettype($value) == 'integer')
        {
            if($value > 0)
            {
                return $value;
            }
        }
    }

    return null;
}

function SanitizeFloat($text)
{
    if (preg_match('/^[0-9,.\-]{0,15}$/', $text))
    {
        $value = floatval($text);
        if((gettype($value) == 'float') or (gettype($value) == 'double'))
        {
            return $value;
        }
    }

    return null;
}

function SanitizeBool($text)
{
    if (preg_match('/^[01]$/', $text))
    {
        $value = ($text=='1');
    }
    else
    {
        $value = null;
    }

    return $value;
}

function filter($unfiltered, $filter_datatype)
{
    switch ($filter_datatype) {
        case 'string':
            return SanitizeText($unfiltered);
            break;

        case SecurityFilter::STRICT_STRING:
            return SanitizeStrictText($unfiltered);
            break;

        case SecurityFilter::STRING_KEY_NAME:
            return SanitizeKeyNameText($unfiltered);
            break;

        case SecurityFilter::ALPHA_NUM:
            return SanitizeAlphaNumText($unfiltered);
            break;

        case SecurityFilter::ALPHA:
            return SanitizeAlphaText($unfiltered);
            break;

        case 'integer':
            return SanitizeInteger($unfiltered);
            break;

        case SecurityFilter::POSITIVE_INTEGER:
            return SanitizePositiveInteger($unfiltered);
            break;

        case SecurityFilter::POSITIVE_INTEGER_ZERO:
            return SanitizePositiveIntegerOrZero($unfiltered);
            break;

        case 'double':
            return SanitizeFloat($unfiltered);
            break;

        case SecurityFilter::BOOL:
            return SanitizeBool($unfiltered);
            break;

        default:
            throw new \Exception('Undfined filter');
    }
}

function FilterList($unfiltered_list, $filter)
{
    $filtered_list = array();
    foreach ($unfiltered_list as $unfiltered){
        $filtered = filter($unfiltered, $filter);
        if($filtered != null) {
            $filtered_list[] = $filtered;
        }
    }
    return $filtered_list;
}

function SafeReadGetRequest($key, $filter_datatype)
{
    if(isset($_GET[$key]) && $_GET[$key] != '')
    {
        return filter($_GET[$key], $filter_datatype);
    }
    else
    {
        return null;
    }
}

function SafeReadGetKeyList()
{
    $unfiltered_list = array();
    foreach ($_GET as $key => $value){
        $unfiltered_list[] = $key;
    }
    $filtered_list = FilterList($unfiltered_list, SecurityFilter::STRING_KEY_NAME);
    return $filtered_list;
}

function SafeReadPostRequest($key, $filter)
{
    if(isset($_POST[$key]))
    {
        return filter($_POST[$key], $filter);
    }
    else
    {
        return null;
    }
}

function SafeReadPostKeyList()
{
    $unfiltered_list = array();
    foreach ($_POST as $key => $value){
        $unfiltered_list[] = $key;
    }
    $filtered_list = FilterList($unfiltered_list, SecurityFilter::STRING_KEY_NAME);
    return $filtered_list;
}

function SafeReadCookieString($cookie_name, $filter)
{
    if(isset($_COOKIE[$cookie_name]))
    {
        return filter($_COOKIE[$cookie_name], $filter);
    }
    else
    {
        $cookie = null;

    }

    return $cookie;
}


function SanitizeTextAlpanumeric($text)
{
    return true;
}

function SanitizeTextAlpanumericSymbols($text)
{
    return true;

}

function RemoveListItem($list, $item)
{
    $position = array_search($item, $list);
    if($position !== null){
        unset($list[$position]);
    }
    return $list;
}

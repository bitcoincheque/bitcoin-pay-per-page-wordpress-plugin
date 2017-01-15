<?php
/**
 * Created by PhpStorm.
 * User: Arild
 * Date: 14.01.2017
 * Time: 11:16
 */

namespace BCF_PayPerPage;

define ('ERROR', 0);
define ('WARNING', 1);
define ('NOTE', 2);

define ('MAX_STRING_LENGTH_DUMP', 50);


function WriteDebugError($data1=null, $data2=null, $data3=null)
{
    WriteDebugLog('ERROR', $data1, $data2, $data3);
}

function WriteDebugWarning($data1=null, $data2=null, $data3=null)
{
    WriteDebugLog('WARNING', $data1, $data2, $data3);
}

function WriteDebugNote($data1=null, $data2=null, $data3=null)
{
    WriteDebugLog('NOTE', $data1, $data2, $data3);
}

function WriteDebugLogFunctionResult($result, $data1=null, $data2=null, $data3=null)
{
    if($result)
    {
        WriteDebugLog('NOTE', $data1, $data2, $data3);
    }
    else
    {
        WriteDebugLog('WARNING', $data1, $data2, $data3);
    }
}

function WriteDebugLog($type, $data1, $data2, $data3)
{
    if (defined('WP_DEBUG') && true === WP_DEBUG)
    {
        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;

        $txt = $type . ' User=' . $user_id . ': ';

        $trace = debug_backtrace();
        if(isset($trace[2]['class']))
        {
            $txt .= $trace[2]['class'] . '->';
        }
        $txt .= $trace[2]['function'];

        if($data1)
        {
            $txt .= ' ' . safe_dump($data1);
        }

        if($data2)
        {
            $txt .= ' ' . safe_dump($data2);
        }

        if($data3)
        {
            $txt .= ' ' . safe_dump($data3);
        }

        WriteDebugFile($txt);
    }
}

function WriteDebugLogFunctionCall($msg='', $password_arg_no=-1)
{
    if (defined('WP_DEBUG') && true === WP_DEBUG)
    {
        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;

        $txt = 'NOTE User=' . $user_id . ': ';

        $trace = debug_backtrace();
        if(isset($trace[1]['class']))
        {
            $txt .= $trace[1]['class'] . '->';
        }
        $txt .= $trace[1]['function'] . ' ' . $msg . ' Args:';

        $i=0;
        foreach($trace[1]['args'] as $data)
        {
            if($i == $password_arg_no)
            {
                $txt .= ' ***';
            }
            else
            {
                $txt .= ' ' . safe_dump($data);
            }
            $i += 1;
        }

        WriteDebugFile($txt);
    }
}

function WriteDebugFile($txt)
{
    $txt = date("Y-d-m H:i:s ", time()) . $txt . "\r\n";
    error_log($txt, 3, WP_CONTENT_DIR . '/pay_per_page_debug.log');
}

function safe_dump($data, $clamps='')
{
    $txt = '';

    switch(gettype($data))
    {
        case 'array':
            $txt .= '{';
            foreach($data as $key => $value)
            {
                if($key == 'password')
                {
                    $value = '***';
                }

                $txt .= safe_dump($key) . '=' . safe_dump($value, '"') . ';';
            }
            $txt .= '}';
            break;

        case 'integer':
            $txt = strval($data);
            break;

        case 'string':
            if(strlen($data) > MAX_STRING_LENGTH_DUMP)
            {
                if($clamps)
                {
                    $txt .= $clamps . substr($data, 0, MAX_STRING_LENGTH_DUMP) . $clamps . '...(strlen=' . strlen($data) . ')';
                }
                else
                {
                    $txt .= substr($data, 0, MAX_STRING_LENGTH_DUMP) . '...(strlen=' . strlen($data) . ')';
                }
            }
            else
            {
                if($clamps)
                {
                    $txt .= $clamps . $data . $clamps;
                }
                else
                {
                    $txt .= $data;
                }
            }
            break;

        case 'NULL':
            $txt .= 'NULL';
            break;

        default:
            WriteDebugError(' invalid data type:', gettype($data));
            die('invalid data type '. gettype($data));
            break;
    }

    return $txt;
}
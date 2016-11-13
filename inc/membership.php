<?php
/**
 * Created by PhpStorm.
 * User: Arild
 * Date: 13.11.2016
 * Time: 00:49
 */

namespace BCF_PayPerPage;


define ('BCF_PAYPAGE_REGISTRATION_COOKIE_NAME', 'payperpage_registration');

require_once('membership_interface.php');


function MembershipInit()
{
    MembershipInstallCookie();

}

function MembershipInstallCookie()
{
    if(!isset($_COOKIE[BCF_PAYPAGE_REGISTRATION_COOKIE_NAME]))
    {
        $cookie = MembershipRandomString();
        $seconds = 30 * 24 * 3600;  // Cookie live for 30 days
        $expire = time() + $seconds;

        if(setcookie(BCF_PAYPAGE_REGISTRATION_COOKIE_NAME, $cookie, $expire, COOKIEPATH, COOKIE_DOMAIN) != true)
        {
            die();
        }
    }
}

function MembershipGetCookie()
{
    return SafeReadCookieString(BCF_PAYPAGE_REGISTRATION_COOKIE_NAME);
}

function MembershipRandomString()
{
    $r1     = rand(1, PHP_INT_MAX - 1);
    $r2     = rand(1, PHP_INT_MAX - 1);
    $r      = $r1 / $r2;
    $str    = strval($r);
    $nonce  = str_replace('.', '', $str);
    return $nonce;
}

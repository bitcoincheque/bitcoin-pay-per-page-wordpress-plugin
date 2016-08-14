<?php
/**
 * Pay-per-page manager.
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

require_once ('db_interface.php');
require_once ('page_view_data.php');
require_once ('util.php');

define ('BCF_PAYPAGE_OPTION_COOKIE_NAME', 'payperpage');


class PageViewManagerClass extends DatabaseInterfaceClass
{
    public function __construct()
    {
        parent::__construct();

    }

    private function GetUserIdFromCookie($cookie_no)
    {
        $user_id = null;

        $user = $this->DB_GetUserDataFromCookieNo($cookie_no);

        if(!is_null($user))
        {
            $user_id = $user->GetUserId();
        }

        return $user_id;
    }

    private function GrabUserId()
    {
        $user_id = null;

        if(isset($_COOKIE[BCF_PAYPAGE_OPTION_COOKIE_NAME]))
        {
            $cookie_no_str = $_COOKIE[BCF_PAYPAGE_OPTION_COOKIE_NAME];
            $cookie_no_val = intval($cookie_no_str);

            $cookie_no = new UnsigedIntegerTypeClass($cookie_no_val);

            $user_id = $this->GetUserIdFromCookie($cookie_no);
        }

        return $user_id;
    }

    public function HasUserPaidForThisPage($post_id)
    {
        $user_has_paid = false;

        $user_id = $this->GrabUserId();
        
        if(!is_null($user_id))
        {
            $pageview = $this->DB_SearchForPaidPageByUser($post_id, $user_id);

            if(!is_null($pageview))
            {
                $pay_status = $pageview->GetPayStatus();
                if($pay_status->GetString() == "PAID")
                {
                    $user_has_paid = true;
                }
            }
        }

        return $user_has_paid;
    }

    public function HasUserPaidForThisPageView($pageview_id)
    {
        $post_id = null;

        $user_id = $this->GrabUserId();

        if(!is_null($user_id))
        {
            $pageview = $this->DB_GetPageViewData($pageview_id);

            if(!is_null($pageview))
            {
                $pay_status = $pageview->GetPayStatus();
                if($pay_status->GetString() == "PAID")
                {
                    $post_id = $pageview->GetPostId();
                }
            }
        }

        return $post_id;
    }
   

    public function RegisterNewPageView($post_id, $price)
    {
        $pageview_id_val = null;

        if(SanitizeUnsignedInteger($post_id) and SanitizeAmount($price))
        {
            $user_id = $this->GrabUserId();

            if(is_null($user_id))
            {
                $user_data = new UserDataClass();

                if(isset($_COOKIE[ BCF_PAYPAGE_OPTION_COOKIE_NAME ]))
                {
                    $cookie_no_str = $_COOKIE[ BCF_PAYPAGE_OPTION_COOKIE_NAME ];
                    $cookie_no_val = intval($cookie_no_str);
                    $cookie_no     = new UnsigedIntegerTypeClass($cookie_no_val);

                    $user_data->SetCookieNo($cookie_no);
                }

                $user_id_val = $this->DB_WriteRecord($user_data);
                $user_id     = new UserIdTypeClass($user_id_val);
            }

            /* Check if this pageview already has been registered from a previous view */
            $pageview = $this->DB_SearchForPaidPageByUser($post_id, $user_id);

            if(is_null($pageview))
            {
                $datetime = $this->DB_GetCurrentTimeStamp();

                $pageview = new PageView_Class();
                $pageview->SetDateTime($datetime);
                $pageview->SetUserId($user_id);
                $pageview->SetPostId($post_id);
                $pageview->SetPrice($price);

                $pageview_id_val = $this->DB_WriteRecord($pageview);
            }
            else
            {
                $pageview_id     = $pageview->GetPageViewId();
                $pageview_id_val = $pageview_id->GetInt();
            }
        }

        return $pageview_id_val;
    }

    public function GetPaymentInfo($ref)
    {
        $pageview = null;
        
        if(SanitizeInteger($ref))
        {
            $pageview_id = new PageViewIdTypeClass($ref);

            $pageview = $this->DB_GetPageViewData($pageview_id);
        }

        return $pageview;
    }

    public function SetPagePaid($pageview_id)
    {
        if(SanitizePageViewId($pageview_id))
        {
            $pageview = $this->DB_GetPageViewData($pageview_id);

            $pay_status = new PayStatusTypeClass('PAID');

            $pageview->SetPayStatus($pay_status);

            $this->DB_UpdateRecord($pageview);
        }
    }

    public function GetPaymentPostId($pageview_id)
    {
        $pageview = null;

        $pageview = $this->DB_GetPageViewData($pageview_id);

        return $pageview->GetPostId();
    }

    public function RemoveAllUserPayments()
    {
        $result = false;

        $user_id = $this->GrabUserId();

        if(!is_null($user_id))
        {
            $result = $this->DB_RemovePaymentRecord($user_id);
        }

        return $result;
    }
}

function AnotherTest()
{
    return "xyq";
}

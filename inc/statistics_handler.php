<?php
/**
 * Created by PhpStorm.
 * User: Arild
 * Date: 14.01.2017
 * Time: 20:34
 */

namespace BCF_PayPerPage;

require_once ('debug_log.php');


function StatisticsPageview($post_id)
{
    RegisterEvent($post_id, StatisticsDataClass::PAGEVIEW);
}

function StatisticsRegister($post_id)
{
    RegisterEvent($post_id, StatisticsDataClass::REGISTER);
}

function StatisticsVerifyEmail($post_id)
{
    RegisterEvent($post_id, StatisticsDataClass::VERIFY);
}

function StatisticsCompleted($post_id)
{
    RegisterEvent($post_id, StatisticsDataClass::COMPLETED);
}

function RegisterEvent($post_id, $event_type)
{
    if(gettype($post_id) == 'integer')
    {
        $statistics_data = new StatisticsDataClass();
        if($statistics_data->LoadData($post_id))
        {
            $statistics_data->AddDataInt($event_type, 1);
            $statistics_data->SaveData();
        }
        else
        {
            $statistics_data->SetDataInt(StatisticsDataClass::STAT_ID, $post_id);
            $statistics_data->AddDataInt($event_type, 1);
            $statistics_data->SaveData(true);
        }
    }
    else
    {
        WriteDebugError(' invalid data type:', gettype($post_id));
    }
}

<?php

namespace BCF_PayPerPage;

require_once ('autoresponder_admin.php');
require_once ('debug_log.php');


function AutroresponderSubscribeEmail($email, $fname='')
{
    $options = get_option(BCF_PAYPERPAGE_MAILCHIMP_OPTION);

    if($options['EnableMailchimp'])
    {
        WriteDebugLogFunctionCall('Mailchimp');

        $apikey = $options['ApiKey'];
        $listid = $options['ListId'];
        $dataCenter = substr($apikey,strpos($apikey,'-')+1);

        $auth = base64_encode( 'user:'.$apikey );

        $data = array(
            'apikey'        => $apikey,
            'email_address' => $email,
            'status'        => 'subscribed',
            'merge_fields'  => array(
                'FNAME' => $fname
            )
        );
        $json_data = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://'.$dataCenter.'.api.mailchimp.com/3.0/lists/'.$listid.'/members/');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic '.$auth));
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        $result = curl_exec($ch);

        if(!$result)
        {
            $error_info = curl_error($ch);
            WriteDebugWarning('Error subscribe: '. $error_info);
            return $error_info;
        }
        else
        {
            WriteDebugNote('E-mail subscribed successfully.');
        }

        #var_dump($result);
    }
}
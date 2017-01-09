<?php

require_once ('autoresponder_admin.php');


function AutroresponderSubscribeEmail($email, $fname='')
{
    $options = get_option(BCF_PAYPERPAGE_MAILCHIMP_OPTION);

    if($options['EnableMailchimp'])
    {
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
            return curl_error($ch);
        }

        #var_dump($result);
    }
}
<?php

require_once ('security.php');

function AjaxActionHandler()
{
    $sender_id = SafeReadPostRequest('sender_id', SecurityFilter::STRING_KEY_NAME);
    $sender_action_data = explode('_', $sender_id);

    if($sender_action_data[0] ==='util') {
        $controller = new $sender_action_data[1]();
        $action = $sender_action_data[2];

        $params = array();
        $key_list = SafeReadPostKeyList();
        foreach ($key_list as $key) {
            $value = SafeReadPostRequest($key, SecurityFilter::STRICT_STRING);
            if($value){
                $params[$key] = $value;
            }
        }

        $response = array(
            'result'    => 'OK',
            'responses' => $controller->AjaxActionHandler($action, $params)
        );
    } else {
        $response = array(
            'result'    => 'ERROR',
            'message'   => 'Error in parameters'
    );
    }

    echo json_encode($response);
    die();
}

/* AJAX handlers */
add_action('wp_ajax_fa_ajax_action', 'AjaxActionHandler');
add_action('wp_ajax_nopriv_fa_ajax_action', 'AjaxActionHandler');

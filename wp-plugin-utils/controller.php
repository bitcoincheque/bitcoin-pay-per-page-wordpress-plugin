<?php



class Controller
{
    function Draw()
    {
        $html = GetTopPartHtml(null, null);
        $html .= $this->DrawView();
        $html .= GetBottomPartHtml();

        PrepareScriptAndStyle();

        return $html;
    }

    function PrepareScriptAndStyle($send_js_data=array())
    {
        $ajax_handler = '/wp-admin/admin-ajax.php/';

        $url_to_my_site = site_url() . $ajax_handler;

        $data_array = array(
            'url_to_my_site' => $url_to_my_site,
            'action' => 'fa_ajax_action'
        );

        if($send_js_data)
        {
            $data_array = array_merge($data_array, $send_js_data);
        }

        $script_handler = 'fa_script_handler';

        $script_src = plugin_dir_url(__FILE__) . '../js/util.js';
        wp_enqueue_script($script_handler, $script_src, array('jquery'), '0.21', true);

        wp_localize_script($script_handler, 'fa_script_handler_vars', $data_array);

        //$style_url = plugins_url() . '/bitcoin-pay-per-page-wordpress-plugin/css/pppc_style.css';
        //wp_enqueue_style('pppc_style', $style_url);
    }

    function AjaxActionHandler($action, $params)
    {
        $func = 'action' . $action;
        return $this->{$func}($params);
    }
}


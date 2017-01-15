<?php
/**
 * Created by PhpStorm.
 * User: Arild
 * Date: 15.01.2017
 * Time: 10:58
 */

function pppc_compact_login_widget()
{
    $link_options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);

    if(is_user_logged_in())
    {
        $wp_user_id = get_current_user_id();
        $user_info = get_userdata($wp_user_id);
        $username = $user_info->user_login;
        $firstname = $user_info->first_name;
        $lastname = $user_info->last_name;

        if($firstname == '' and $lastname=='')
        {
            $showname = $username;
        }
        else
        {
            $showname = $firstname . ' ' . $lastname;
        }

        $output = $showname . ' <a href="' . wp_logout_url(). ' ">Logout</a> <a href="' . $link_options['ProfilePageLink'] . '">Profile</a>';
    }
    else
    {
        $output = '<a href="' . $link_options['LoginPageLink'] . '">Login</a> <a href="' . $link_options['RegisterPageLink'] . '">Register</a>';
    }

    return $output;
}

<?php

namespace BCF_PayPerPage;

define('BCF_PAYPERPAGE_MEMBERSHIP_OPTION', 'bcf_payperpage_membership_option');
define('BCF_PAYPERPAGE_LINKING_OPTION', 'bcf_payperpage_linking_option');
define('BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION', 'bcf_payperpage_email_verification_option');
define('BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION', 'bcf_payperpage_email_reset_password_option');
define('BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION', 'bcf_payperpage_email_register_notification_option');

function MembershipOptionDefault()
{
    add_option(
        BCF_PAYPERPAGE_MEMBERSHIP_OPTION, array(
            'RequireMembership' => '1',
            'RequireEmailConfirmation' => '0'
        )
    );
    add_option(
        BCF_PAYPERPAGE_LINKING_OPTION, array(
            'LoginPageLink' => '/login',
            'ProfilePageLink' => '/profile',
            'PasswordPageLink' => '/password',
            'LogoutPage' => '/'
        )
    );
    add_option(
        BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION, array(
            'verify_new_emails' => '1',
            'verify_changed_emails' => '1',
            'email_replay_addr' => '',
            'email_subject' => 'Verify your e-mail',
            'email_body' => '<p>In order to complete the registration at <strong>{site_name}</strong> you must verify your e-mail address.</p>&#13;&#10;<p>Click or copy and paste this link into your web browser:</p>&#13;&#10;<p>{link}</p>'
        )
    );
    add_option(
        BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION, array(
            'email_replay_addr' => '',
            'email_subject' => 'Recover username and reset password',
            'email_body' => '<p>You have requested to recover your username or password at <strong>{site_name}</strong>.</p>&#13;&#10;<p>Your username is: <strong>{username}</strong></p>&#13;&#10;<p>In order to reset your password, use this link:</p>&#13;&#10;<p>{link}</p>'
        )
    );
    add_option(
        BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION, array(
            'send_notification' => '1',
            'email_replay_addr' => '',
            'email_sendto' => '',
            'email_subject' => 'Notification of New Member Registration',
            'email_body' => '<p>A new member has registered at <strong>{site_name}</strong>.</p>&#13;&#10;<p>Username: {username}</p>'
        )
    );
}

function MembershipAdminMenu()
{
    register_setting(BCF_PAYPERPAGE_MEMBERSHIP_OPTION, BCF_PAYPERPAGE_MEMBERSHIP_OPTION);
    register_setting(BCF_PAYPERPAGE_LINKING_OPTION, BCF_PAYPERPAGE_LINKING_OPTION);
    register_setting(BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION, BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION);
    register_setting(BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION, BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION);
    register_setting(BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION, BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION);

    add_settings_section(
        'bcf_payperpage_membership_settings_section_id',
        'Membership registration',
        'BCF_PayPerPage\MembershipAdminDrawSettingsHelpMembership',
        'bcf_payperpage_membership_settings_section_page'
    );
    add_settings_field(
        'bcf_payperpage_membership_requiremembership_settings_field_id',
        'Require membership sign-up before payments',
        'BCF_PayPerPage\MembershipAdminDrawSettingsMembershipRequireMembership',
        'bcf_payperpage_membership_settings_section_page',
        'bcf_payperpage_membership_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_membership_requireemailconfirmation_settings_field_id',
        'Require membership sign-up before payments',
        'BCF_PayPerPage\MembershipAdminDrawSettingsMembershipRequireEmailConfirmation',
        'bcf_payperpage_membership_settings_section_page',
        'bcf_payperpage_membership_settings_section_id'
    );
    add_settings_section(
        'bcf_payperpage_linking_settings_section_id',
        'Page linking',
        'BCF_PayPerPage\MembershipAdminDrawSettingsHelpLinking',
        'bcf_payperpage_linking_settings_section_page'
    );
    add_settings_field(
        'bcf_payperpage_linking_loginpagelink_settings_field_id',
        'Login page',
        'BCF_PayPerPage\MembershipAdminDrawSettingsLinkingLoginPageLink',
        'bcf_payperpage_linking_settings_section_page',
        'bcf_payperpage_linking_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_linking_profilepagelink_settings_field_id',
        'Profile page',
        'BCF_PayPerPage\MembershipAdminDrawSettingsLinkingProfilePageLink',
        'bcf_payperpage_linking_settings_section_page',
        'bcf_payperpage_linking_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_linking_passwordpagelink_settings_field_id',
        'Reset Password page',
        'BCF_PayPerPage\MembershipAdminDrawSettingsLinkingPasswordPageLink',
        'bcf_payperpage_linking_settings_section_page',
        'bcf_payperpage_linking_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_linking_logoutpage_settings_field_id',
        'Log-out redirect',
        'BCF_PayPerPage\MembershipAdminDrawSettingsLinkingLogoutPage',
        'bcf_payperpage_linking_settings_section_page',
        'bcf_payperpage_linking_settings_section_id'
    );
    add_settings_section(
        'bcf_payperpage_email_verification_settings_section_id',
        'Verify new e-mail addresses',
        'BCF_PayPerPage\MembershipAdminDrawSettingsHelpemail_verification',
        'bcf_payperpage_email_verification_settings_section_page'
    );
    add_settings_field(
        'bcf_payperpage_email_verification_verify_new_emails_settings_field_id',
        'Require new users to verify their e-mail address:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_verificationverify_new_emails',
        'bcf_payperpage_email_verification_settings_section_page',
        'bcf_payperpage_email_verification_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_verification_verify_changed_emails_settings_field_id',
        'Require existing users to verify change of e-mail address:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_verificationverify_changed_emails',
        'bcf_payperpage_email_verification_settings_section_page',
        'bcf_payperpage_email_verification_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_verification_email_replay_addr_settings_field_id',
        'E-mail sender/replay address:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_verificationemail_replay_addr',
        'bcf_payperpage_email_verification_settings_section_page',
        'bcf_payperpage_email_verification_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_verification_email_subject_settings_field_id',
        'E-mail subject:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_verificationemail_subject',
        'bcf_payperpage_email_verification_settings_section_page',
        'bcf_payperpage_email_verification_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_verification_email_body_settings_field_id',
        'E-mail body message:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_verificationemail_body',
        'bcf_payperpage_email_verification_settings_section_page',
        'bcf_payperpage_email_verification_settings_section_id'
    );
    add_settings_section(
        'bcf_payperpage_email_reset_password_settings_section_id',
        'Reset password e-mail',
        'BCF_PayPerPage\MembershipAdminDrawSettingsHelpemail_reset_password',
        'bcf_payperpage_email_reset_password_settings_section_page'
    );
    add_settings_field(
        'bcf_payperpage_email_reset_password_email_replay_addr_settings_field_id',
        'E-mail sender/replay address:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_reset_passwordemail_replay_addr',
        'bcf_payperpage_email_reset_password_settings_section_page',
        'bcf_payperpage_email_reset_password_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_reset_password_email_subject_settings_field_id',
        'E-mail subject:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_reset_passwordemail_subject',
        'bcf_payperpage_email_reset_password_settings_section_page',
        'bcf_payperpage_email_reset_password_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_reset_password_email_body_settings_field_id',
        'E-mail body message:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_reset_passwordemail_body',
        'bcf_payperpage_email_reset_password_settings_section_page',
        'bcf_payperpage_email_reset_password_settings_section_id'
    );
    add_settings_section(
        'bcf_payperpage_email_register_notification_settings_section_id',
        'Send e-mail notification when new member registers',
        'BCF_PayPerPage\MembershipAdminDrawSettingsHelpemail_register_notification',
        'bcf_payperpage_email_register_notification_settings_section_page'
    );
    add_settings_field(
        'bcf_payperpage_email_register_notification_send_notification_settings_field_id',
        'Send notification e-mail:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_register_notificationsend_notification',
        'bcf_payperpage_email_register_notification_settings_section_page',
        'bcf_payperpage_email_register_notification_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_register_notification_email_replay_addr_settings_field_id',
        'E-mail sender/replay address:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_register_notificationemail_replay_addr',
        'bcf_payperpage_email_register_notification_settings_section_page',
        'bcf_payperpage_email_register_notification_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_register_notification_email_sendto_settings_field_id',
        'Send to E-mail:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_register_notificationemail_sendto',
        'bcf_payperpage_email_register_notification_settings_section_page',
        'bcf_payperpage_email_register_notification_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_register_notification_email_subject_settings_field_id',
        'E-mail subject:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_register_notificationemail_subject',
        'bcf_payperpage_email_register_notification_settings_section_page',
        'bcf_payperpage_email_register_notification_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_email_register_notification_email_body_settings_field_id',
        'E-mail body message:',
        'BCF_PayPerPage\MembershipAdminDrawSettingsemail_register_notificationemail_body',
        'bcf_payperpage_email_register_notification_settings_section_page',
        'bcf_payperpage_email_register_notification_settings_section_id'
    );
}

function MembershipDrawAdminPage()
{
    echo '<div class="wrap">';
    echo '<h2>Pay-Per-Page-Click Member Sign-up</h2>';
    echo '<p>Settings for member registration and e-mail verification.</p>';

    echo '<hr>';
    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPERPAGE_MEMBERSHIP_OPTION);
    echo do_settings_sections('bcf_payperpage_membership_settings_section_page');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '<hr>';
    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPERPAGE_LINKING_OPTION);
    echo do_settings_sections('bcf_payperpage_linking_settings_section_page');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '<hr>';
    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION);
    echo do_settings_sections('bcf_payperpage_email_verification_settings_section_page');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '<hr>';
    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION);
    echo do_settings_sections('bcf_payperpage_email_reset_password_settings_section_page');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '<hr>';
    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION);
    echo do_settings_sections('bcf_payperpage_email_register_notification_settings_section_page');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '</div>';
}

function MembershipAdminDrawSettingsHelpMembership()
{
    echo '<p>Here you can configure the membership options.</p>';
}

function MembershipAdminDrawSettingsHelpLinking()
{
    echo '<p>Here you can configure links for pages containg various forms.</p>';
}

function MembershipAdminDrawSettingsHelpemail_verification()
{
    echo '<p>Here you can configure sending of e-mails to user in order to confirm the e-mail exists..</p>';
}

function MembershipAdminDrawSettingsHelpemail_reset_password()
{
    echo '<p>Here you can configure sending of e-mails to user in order to reset a password. The e-mail must contain the {link} code that will be substituted with a link to the reset page.</p>';
}

function MembershipAdminDrawSettingsHelpemail_register_notification()
{
    echo '<p>Here you can configure the sending of e-mail to admins when a new user has signed up for membership.</p>';
}

function MembershipAdminDrawSettingsMembershipRequireMembership()
{
    $options = get_option(BCF_PAYPERPAGE_MEMBERSHIP_OPTION);
    if(isset($options['RequireMembership'])){
        $selected = $options['RequireMembership'];
    }else{
        $selected = false;
    }
    echo '<input name="' . BCF_PAYPERPAGE_MEMBERSHIP_OPTION . '[RequireMembership]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}

function MembershipAdminDrawSettingsMembershipRequireEmailConfirmation()
{
    $options = get_option(BCF_PAYPERPAGE_MEMBERSHIP_OPTION);
    if(isset($options['RequireEmailConfirmation'])){
        $selected = $options['RequireEmailConfirmation'];
    }else{
        $selected = false;
    }
    echo '<input name="' . BCF_PAYPERPAGE_MEMBERSHIP_OPTION . '[RequireEmailConfirmation]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}

function MembershipAdminDrawSettingsLinkingLoginPageLink()
{
    $options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);
    if(isset($options['LoginPageLink'])){
        $selected = $options['LoginPageLink'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_LINKING_OPTION . '[LoginPageLink]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsLinkingProfilePageLink()
{
    $options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);
    if(isset($options['ProfilePageLink'])){
        $selected = $options['ProfilePageLink'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_LINKING_OPTION . '[ProfilePageLink]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsLinkingPasswordPageLink()
{
    $options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);
    if(isset($options['PasswordPageLink'])){
        $selected = $options['PasswordPageLink'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_LINKING_OPTION . '[PasswordPageLink]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsLinkingLogoutPage()
{
    $options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);
    if(isset($options['LogoutPage'])){
        $selected = $options['LogoutPage'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_LINKING_OPTION . '[LogoutPage]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsemail_verificationverify_new_emails()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION);
    if(isset($options['verify_new_emails'])){
        $selected = $options['verify_new_emails'];
    }else{
        $selected = false;
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION . '[verify_new_emails]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}

function MembershipAdminDrawSettingsemail_verificationverify_changed_emails()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION);
    if(isset($options['verify_changed_emails'])){
        $selected = $options['verify_changed_emails'];
    }else{
        $selected = false;
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION . '[verify_changed_emails]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}

function MembershipAdminDrawSettingsemail_verificationemail_replay_addr()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION);
    if(isset($options['email_replay_addr'])){
        $selected = $options['email_replay_addr'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION . '[email_replay_addr]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsemail_verificationemail_subject()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION);
    if(isset($options['email_subject'])){
        $selected = $options['email_subject'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION . '[email_subject]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsemail_verificationemail_body()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION);
    if(isset($options['email_body'])){
        $selected = $options['email_body'];
    }else{
        $selected = "";
    }
    echo '<textarea rows="8" cols="80" name="' . BCF_PAYPERPAGE_EMAIL_VERIFICATION_OPTION . '[email_body]" type="text">'.$selected.'</textarea>';
}

function MembershipAdminDrawSettingsemail_reset_passwordemail_replay_addr()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION);
    if(isset($options['email_replay_addr'])){
        $selected = $options['email_replay_addr'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION . '[email_replay_addr]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsemail_reset_passwordemail_subject()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION);
    if(isset($options['email_subject'])){
        $selected = $options['email_subject'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION . '[email_subject]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsemail_reset_passwordemail_body()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION);
    if(isset($options['email_body'])){
        $selected = $options['email_body'];
    }else{
        $selected = "";
    }
    echo '<textarea rows="8" cols="80" name="' . BCF_PAYPERPAGE_EMAIL_RESET_PASSWORD_OPTION . '[email_body]" type="text">'.$selected.'</textarea>';
}

function MembershipAdminDrawSettingsemail_register_notificationsend_notification()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION);
    if(isset($options['send_notification'])){
        $selected = $options['send_notification'];
    }else{
        $selected = false;
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION . '[send_notification]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}

function MembershipAdminDrawSettingsemail_register_notificationemail_replay_addr()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION);
    if(isset($options['email_replay_addr'])){
        $selected = $options['email_replay_addr'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION . '[email_replay_addr]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsemail_register_notificationemail_sendto()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION);
    if(isset($options['email_sendto'])){
        $selected = $options['email_sendto'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION . '[email_sendto]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsemail_register_notificationemail_subject()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION);
    if(isset($options['email_subject'])){
        $selected = $options['email_subject'];
    }else{
        $selected = "";
    }
    echo '<input name="' . BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION . '[email_subject]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsemail_register_notificationemail_body()
{
    $options = get_option(BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION);
    if(isset($options['email_body'])){
        $selected = $options['email_body'];
    }else{
        $selected = "";
    }
    echo '<textarea rows="8" cols="80" name="' . BCF_PAYPERPAGE_EMAIL_REGISTER_NOTIFICATION_OPTION . '[email_body]" type="text">'.$selected.'</textarea>';
}


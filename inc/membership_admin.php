<?php
/**
 * Created by PhpStorm.
 * User: Arild
 * Date: 21.11.2016
 * Time: 21:20
 */

namespace BCF_PayPerPage;

define ('BCF_PAYPAGE_MEMBERSHIP_OPTION', 'bcf_paypage_member_signup_option');

function MembershipOptionDefault()
{
    add_option(
        BCF_PAYPAGE_MEMBERSHIP_OPTION, array(
            'fade_enable' => '0',
            'fade_height' => '0'
        )
    );
}

function MembershipAdminMenu()
{
    register_setting(BCF_PAYPAGE_MEMBERSHIP_OPTION, BCF_PAYPAGE_MEMBERSHIP_OPTION);

    add_settings_section(
        'settings_section_membership_options_tag',
        'Membership registration',
        'BCF_PayPerPage\AdminDrawSettingsHelpMemberSignUp',
        'settings_section_membership_settings'
    );
    add_settings_field(
        'bcf_payperpage_settings_membership_required',
        'Require membership sign-up before payments:',
        '\BCF_PayPerPage\AdminDrawSettingsRequireMembership',
        'settings_section_membership_settings',
        'settings_section_membership_options_tag'
    );
    add_settings_field(
        'bcf_payperpage_settings_verify_email',
        'Require e-mail verification:',
        '\BCF_PayPerPage\AdminDrawSettingsVerifyEmail',
        'settings_section_membership_settings',
        'settings_section_membership_options_tag'
    );

}

function AdminMembership()
{
    echo '<div class="wrap">';
    echo '<h2>Pay-Per-Page-Click Member Sign-up</h2>';
    echo '<p>Settings for member registration and e-mail verification.</p>';
    echo '<hr>';

    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPAGE_MEMBERSHIP_OPTION);
    echo do_settings_sections('settings_section_membership_settings');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '</div>';
}


function AdminDrawSettingsHelpMemberSignUp()
{
    echo '<p>Here you can configure the membership options.</p>';
}

function AdminDrawSettingsRequireMembership()
{
    $options = get_option(BCF_PAYPAGE_MEMBERSHIP_OPTION);
    $selected = $options['must_be_member'];
    echo '<input name="' . BCF_PAYPAGE_MEMBERSHIP_OPTION . '[must_be_member]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}

function AdminDrawSettingsVerifyEmail()
{
    $options = get_option(BCF_PAYPAGE_MEMBERSHIP_OPTION);
    $selected = $options['verify_email'];
    echo '<input name="' . BCF_PAYPAGE_MEMBERSHIP_OPTION . '[verify_email]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}


<?php

namespace BCF_PayPerPage;

define('BCF_PAYPERPAGE_MEMBERSHIP_OPTION', 'bcf_payperpage_membership_option');
define('BCF_PAYPERPAGE_LINKING_OPTION', 'bcf_payperpage_linking_option');

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
            'PasswordPageLink' => '/password'
        )
    );
}

function MembershipAdminMenu()
{
    register_setting(BCF_PAYPERPAGE_MEMBERSHIP_OPTION, BCF_PAYPERPAGE_MEMBERSHIP_OPTION);
    register_setting(BCF_PAYPERPAGE_LINKING_OPTION, BCF_PAYPERPAGE_LINKING_OPTION);

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

    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPERPAGE_LINKING_OPTION);
    echo do_settings_sections('bcf_payperpage_linking_settings_section_page');
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

function MembershipAdminDrawSettingsMembershipRequireMembership()
{
    $options = get_option(BCF_PAYPERPAGE_MEMBERSHIP_OPTION);
    $selected = $options['RequireMembership'];
    echo '<input name="' . BCF_PAYPERPAGE_MEMBERSHIP_OPTION . '[RequireMembership]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}

function MembershipAdminDrawSettingsMembershipRequireEmailConfirmation()
{
    $options = get_option(BCF_PAYPERPAGE_MEMBERSHIP_OPTION);
    $selected = $options['RequireEmailConfirmation'];
    echo '<input name="' . BCF_PAYPERPAGE_MEMBERSHIP_OPTION . '[RequireEmailConfirmation]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}

function MembershipAdminDrawSettingsLinkingLoginPageLink()
{
    $options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);
    $selected = $options['LoginPageLink'];
    echo '<input name="' . BCF_PAYPERPAGE_LINKING_OPTION . '[LoginPageLink]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsLinkingProfilePageLink()
{
    $options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);
    $selected = $options['ProfilePageLink'];
    echo '<input name="' . BCF_PAYPERPAGE_LINKING_OPTION . '[ProfilePageLink]" type="text" value="' . $selected . '" />';
}

function MembershipAdminDrawSettingsLinkingPasswordPageLink()
{
    $options = get_option(BCF_PAYPERPAGE_LINKING_OPTION);
    $selected = $options['PasswordPageLink'];
    echo '<input name="' . BCF_PAYPERPAGE_LINKING_OPTION . '[PasswordPageLink]" type="text" value="' . $selected . '" />';
}


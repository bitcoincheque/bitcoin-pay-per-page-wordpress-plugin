<?php

namespace BCF_PayPerPage;

define('BCF_PAYPERPAGE_MAILCHIMP_OPTION', 'bcf_payperpage_mailchimp_option');

function AutroresponderOptionDefault()
{
    delete_option(BCF_PAYPERPAGE_MAILCHIMP_OPTION);

    add_option(
        BCF_PAYPERPAGE_MAILCHIMP_OPTION, array(
            'EnableMailchimp' => '1',
            'ApiKey' => '',
            'ListId' => ''
        )
    );
}

function AutroresponderAdminMenu()
{
    register_setting(BCF_PAYPERPAGE_MAILCHIMP_OPTION, BCF_PAYPERPAGE_MAILCHIMP_OPTION);

    add_settings_section(
        'bcf_payperpage_mailchimp_settings_section_id',
        'MailChimp settings',
        'BCF_PayPerPage\AutroresponderAdminDrawSettingsHelpMailchimp',
        'bcf_payperpage_mailchimp_settings_section_page'
    );
    add_settings_field(
        'bcf_payperpage_mailchimp_enablemailchimp_settings_field_id',
        'Enable MailChimp',
        'BCF_PayPerPage\AutroresponderAdminDrawSettingsMailchimpEnableMailchimp',
        'bcf_payperpage_mailchimp_settings_section_page',
        'bcf_payperpage_mailchimp_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_mailchimp_apikey_settings_field_id',
        'API key',
        'BCF_PayPerPage\AutroresponderAdminDrawSettingsMailchimpApiKey',
        'bcf_payperpage_mailchimp_settings_section_page',
        'bcf_payperpage_mailchimp_settings_section_id'
    );
    add_settings_field(
        'bcf_payperpage_mailchimp_listid_settings_field_id',
        'List ID',
        'BCF_PayPerPage\AutroresponderAdminDrawSettingsMailchimpListId',
        'bcf_payperpage_mailchimp_settings_section_page',
        'bcf_payperpage_mailchimp_settings_section_id'
    );
}

function AutroresponderDrawAdminPage()
{
    echo '<div class="wrap">';
    echo '<h2>Autoresponder settings</h2>';
    echo '<p>Settings for e-mail list subscriptions.</p>';

    echo '<hr>';
    echo '<form action="options.php" method="post">';
    echo settings_fields(BCF_PAYPERPAGE_MAILCHIMP_OPTION);
    echo do_settings_sections('bcf_payperpage_mailchimp_settings_section_page');
    echo '<input type="submit" name="Submit" value="Save Options" />';
    echo '</form>';

    echo '</div>';
}

function AutroresponderAdminDrawSettingsHelpMailchimp()
{
    echo '<p>Here you can configure the MailChimp. New registered members will be added to your MailChimp list. The API Key and List ID can be found in your MailChimp account settings.</p>';
}

function AutroresponderAdminDrawSettingsMailchimpEnableMailchimp()
{
    $options = get_option(BCF_PAYPERPAGE_MAILCHIMP_OPTION);
    $selected = $options['EnableMailchimp'];
    echo '<input name="' . BCF_PAYPERPAGE_MAILCHIMP_OPTION . '[EnableMailchimp]" type="checkbox" value="1" ' . checked(1, $selected, false) . ' />';
}

function AutroresponderAdminDrawSettingsMailchimpApiKey()
{
    $options = get_option(BCF_PAYPERPAGE_MAILCHIMP_OPTION);
    $selected = $options['ApiKey'];
    echo '<textarea rows="1" cols="80" name="' . BCF_PAYPERPAGE_MAILCHIMP_OPTION . '[ApiKey]" type="text">'.$selected.'</textarea>';
}

function AutroresponderAdminDrawSettingsMailchimpListId()
{
    $options = get_option(BCF_PAYPERPAGE_MAILCHIMP_OPTION);
    $selected = $options['ListId'];
    echo '<textarea rows="1" cols="80" name="' . BCF_PAYPERPAGE_MAILCHIMP_OPTION . '[ListId]" type="text">'.$selected.'</textarea>';
}


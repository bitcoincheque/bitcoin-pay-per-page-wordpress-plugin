<?php

namespace BCF_PayPerPage;

require_once ('membership_reg_data.php');

function GetRegStatusName($reg_status)
{
    switch($reg_status)
    {
        case MembershipRegistrationDataClass::STATE_EMAIL_UNCONFIRMED:
            $name='Registering';
            break;

        case MembershipRegistrationDataClass::STATE_EMAIL_CONFIRMED:
            $name='Verified';
            break;

        case MembershipRegistrationDataClass::STATE_USER_CREATED:
            $name='Completed';
            break;

        default:
            $name = strval($reg_status);
    }

    return $name;
}

function MembershipDrawRegAdminPage()
{
    echo '<div class="wrap">';
    echo '<h2>Registration status</h2>';
    echo '<p>Status for membership registrations.</p>';

    global $wpdb;
    $prefixed_table_name = $wpdb->prefix . 'bcf_payperpage_registration';

    $sql = "SELECT * FROM " . $prefixed_table_name;

    $record_list = $wpdb->get_results($sql, ARRAY_A);

    echo '<table border="1">';
    echo '<tr>';
    echo '<th>Reg.id.</th>';
    echo '<th>Time-stamp</th>';
    echo '<th>State</th>';
    echo '<th>Username</th>';
    echo '<th>Email</th>';
    echo '<th>Post ID</th>';
    echo '</tr>';
    foreach($record_list as $record)
    {
        echo '<tr>';
        echo '<td>' . $record['registration_id'] . '</td>';
        echo '<td>' . $record['timestamp'] . '</td>';
        echo '<td>' . GetRegStatusName($record['state']) . '</td>';
        echo '<td>' . $record['username'] . '</td>';
        echo '<td>' . $record['email'] . '</td>';
        echo '<td>' . $record['post_id'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';


    echo '</div>';
}
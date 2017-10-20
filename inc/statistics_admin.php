<?php

namespace BCF_PayPerPage;

require_once ('statistics_data.php');

function StatisticsDrawAdminPage()
{
    echo '<div class="wrap">';
    echo '<h2>Statistics</h2>';
    echo '<p>Statistics for the membership registrations.</p>';

    global $wpdb;
    $prefixed_table_name = $wpdb->prefix . 'bcf_payperpage_statistics';

    $sql = "SELECT * FROM " . $prefixed_table_name;

    $record_list = $wpdb->get_results($sql, ARRAY_A);

    echo '<table border="1">';
    echo '<tr>';
    echo '<th>Post ID</th>';
    echo '<th>Name</th>';
    echo '<th>Page view</th>';
    echo '<th>Start register</th>';
    echo '<th>Verify e-mail</th>';
    echo '<th>Completed</th>';
    echo '</tr>';
    foreach($record_list as $record)
    {
        echo '<tr>';
        echo '<td>' . $record['id'] . '</td>';
        echo '<td>' . get_the_title($record['id']) . '</td>';
        echo '<td>' . $record['pageview'] . '</td>';
        echo '<td>' . $record['register'] . '</td>';
        echo '<td>' . $record['verify'] . '</td>';
        echo '<td>' . $record['completed'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';


    echo '</div>';
}

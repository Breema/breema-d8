<?php

/**
 * @file
 * Provide views data for breema.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function breema_views_data_alter(&$data) {
  $data['views']['breema_group_dashboard_link'] = [
    'title' => t('Group dashboard link'),
    'help' => t("Custom link to the current user's group dashboard."),
    'area' => [
      'id' => 'breema_group_dashboard_link',
    ],
  ];

  // Allow the filter for 'User: Email' to test for NULL.
  $data['users_field_data']['mail']['filter']['allow empty'] = TRUE;
}

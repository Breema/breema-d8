<?php

/**
 * @file
 * Provide views data for breema.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function breema_views_data_alter(&$data) {
  // Allow the filter for 'User: Email' to test for NULL.
  $data['users_field_data']['mail']['filter']['allow empty'] = TRUE;
}

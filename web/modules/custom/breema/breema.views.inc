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

  $data['sessions'] = [];
  $data['sessions']['table'] = [];
  $data['sessions']['table']['group'] = t('Sessions');
  $data['sessions']['table']['provider'] = 'system';
  $data['sessions']['hostname'] = [
    'title' => t('Hostname'),
    'help' => t('The hostname the user is logged in from.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];
  $data['sessions']['timestamp'] = [
    'title' => t('Last access'),
    'help' => t('The timestamp when this session last requested a page.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
  ];
  // Add a relationship from the {users} table to the {sessions} table via UID.
  $data['users']['session_uid'] = [
    'title' => t('User session'),
    'help' => t('Relate users to their logged-in session information.'),
    'relationship' => [
      'base' => 'sessions',
      'base field' => 'uid',
      'field' => 'uid',
      'id' => 'standard',
      'label' => t('Session information from user'),
    ],
  ];

}

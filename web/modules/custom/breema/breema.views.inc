<?php

/**
 * @file
 * Provide views data for breema.module.
 */

use Drupal\field\Entity\FieldStorageConfig;

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
  // Expose image media dimensions directly to views as numbers.
  $field_manager = \Drupal::service('entity_field.manager');
  $entity_fields = $field_manager->getFieldMapByFieldType('image');
  foreach ($entity_fields as $entity_type => $fields) {
    foreach ($fields as $field_name => $field) {
      $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
      if (!empty($field_storage)) {
        $views_data = views_field_default_views_data($field_storage);
        foreach ($views_data as $table_name => $table_data) {
          if (isset($data[$table_name])) {
            $group = $data[$table_name][$field_storage->getName() . '_target_id']['group'];
            $help = $data[$table_name][$field_storage->getName() . '_target_id']['help'];
            $data[$table_name][$field_storage->getName() . '_width'] = [
              'title' => t('Image width'),
              'group' => $group,
              'help' => $help,
              'field' => ['id'  => 'numeric'],
            ];
            $data[$table_name][$field_storage->getName() . '_height'] = [
              'title' => t('Image height'),
              'group' => $group,
              'help' => $help,
              'field' => ['id' => 'numeric'],
            ];
          }
        }
      }
    }
  }
}

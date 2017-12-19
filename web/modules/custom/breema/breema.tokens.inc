<?php

/**
 * @file
 * Contains token-related hook implementations for breema.module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function breema_token_info() {
  $info = array();
  $info['tokens']['site']['password-url'] = [
    'name' => t('Password page'),
    'description' => t("The URL of the site's password reset page."),
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function breema_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();
  if ($type == 'site') {
    $url_options = ['absolute' => TRUE];
    foreach ($tokens as $name => $original) {
      // Find the desired token by name
      switch ($name) {
        case 'password-url':
          $password_url = Url::fromRoute('user.pass', [], $url_options);
          $replacements[$original] = $password_url->toString();
          break;
      }
    }
  }   
  // Return the replacements.
  return $replacements;
}

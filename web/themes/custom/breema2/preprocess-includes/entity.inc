<?php

/**
 * Implements hook_preprocess_HOOK() for node, invokes bundle-specific function.
 */
function breema2_preprocess_node(&$variables) {
  // Invoke the bundle-specific function (if any).
  $function = __FUNCTION__ . '__' . $variables['node']->bundle();
  if (function_exists($function)) {
    $function($variables);
  }
}

/**
 * Bundle-specific callback to preprocess event nodes.
 */
function breema2_preprocess_node__event(&$variables) {
  $main_images = $variables['node']->get('field_main_image')->getValue();
  $variables['attributes']['class'][] = empty($main_images) ? 'event-no-image' : 'event-with-image';
}

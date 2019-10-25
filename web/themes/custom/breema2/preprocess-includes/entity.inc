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

/**
 * Bundle-specific callback to preprocess private_page nodes.
 */
function breema2_preprocess_node__private_page(&$variables) {
  $variables['changed'] = date('F j, Y', $variables['node']->get('changed')->value);
}

/**
 * Bundle-specific callback to preprocess Essence of Breema nodes.
 */
function breema2_preprocess_node__essence(&$variables) {
  $source_type = $variables['node']->get('field_source_type')->value;
  switch ($source_type) {
    case 'class':
      $variables['source'] = t('classes at The Breema Center');
      break;

    case 'product':
      $products = $variables['node']->get('field_source_product')->referencedEntities();
      if (!empty($products)) {
        $product = array_pop($products);
        $variables['source'] = [
          '#markup' => '<cite>' . $product->label() . '</cite>',
        ];
        if ($variables['view_mode'] == 'full') {
          $variables['source']['#prefix'] = '<a href="' . $product->toUrl()->toString() . '">';
          $variables['source']['#suffix'] = '</a>';
        }
      }
      break;

    case 'other':
      $variables['source'] = $variables['node']->get('field_source_other')->value;
      break;

  }
}

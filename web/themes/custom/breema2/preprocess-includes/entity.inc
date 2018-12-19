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
 * Bundle-specific callback to preprocess Essence of Breema nodes.
 */
function breema2_preprocess_node__essence(&$variables) {
  $products = $variables['node']->get('field_related_product')->referencedEntities();
  if (!empty($products)) {
    $product = array_pop($products);
    $variables['product'] = [
      'title' => $product->label(),
      'url' => $product->toUrl()->toString(),
      'cite' => [
        '#markup' => '<cite>' . $product->label() . '</cite>',
      ],
    ];
    if ($variables['view_mode'] == 'full') {
      $variables['product']['cite']['#prefix'] = '<a href="' . $product->toUrl()->toString() . '">';
      $variables['product']['cite']['#suffix'] = '</a>';
    }
  }
}

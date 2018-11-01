<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * A block that renders an event's date and time.
 *
 * @Block(
 *   id = "event_datetime_block",
 *   admin_label = @Translation("Breema event date/time"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaEventDatetimeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#cache' => ['contexts' => ['route']],
    ];
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!empty($node)) {
      if ($node->bundle() == 'event') {
        $block['#cache']['tags'][] = 'node:' . $node->id();
        $options = [
          'label' => 'hidden',
          'type' => 'daterange_compact',
          'settings' => ['format_type' => 'medium'],
        ];
        $block['datetime'] = $node->get('field_date_time')->view($options);
      }
    }
    return $block;
  }
}

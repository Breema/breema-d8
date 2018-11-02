<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Views;

/**
 * A block that renders an event's schedule (if any).
 *
 * @Block(
 *   id = "event_schedule_block",
 *   admin_label = @Translation("Breema event schedule"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaEventScheduleBlock extends BlockBase {

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
        $parent_event = $node->get('field_parent_event')->getValue();
        if (!empty($parent_event)) {
          $block['#cache']['tags'][] = 'node:' . $parent_event[0]['target_id'];
          $args = [$parent_event[0]['target_id']];
        }
        else {
          $args = [$node->id()];
        }
        $view = Views::getView('breema_event_children');
        $config = $this->getConfiguration();
        $display = $config['view_display'];
        $view->setDisplay($display);
        $view->setArguments($args);
        $view->execute();
        if (count($view->result)) {
          $block['results'] = $view->preview($display, $args);
        }
      }
    }
    return $block;
  }
}

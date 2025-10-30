<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;
use Drupal\views\Views;

/**
 * A block for users to manage their own Breema events.
 *
 * @Block(
 *   id = "breema_event_dashboard_block",
 *   admin_label = @Translation("Breema event dashboard"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaEventDashboardBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['node_list'],
      ],
      '#attached' => [
        'library' => [
          'breema/block.event-dashboard',
        ],
      ],
    ];
    $current_user = \Drupal::currentUser();
    $url_options['query']['destination'] = \Drupal::destination()->get();

    $dashboard_url = Url::fromUri('internal:/user/' . $current_user->id() . '/events');
    if (\Drupal::entityTypeManager()->getAccessControlHandler('node')->createAccess('event', NULL, [], FALSE)) {
      $add_url = Url::fromRoute('node.add', ['node_type' => 'event'], $url_options);
    }
    $args = [$current_user->id(), $current_user->id()];
    foreach (['pending', 'upcoming'] as $event_status) {
      $display = 'embed_' . $event_status;
      $view = Views::getView('breema_event_dashboard');
      $view->setDisplay($display);
      $view->setArguments($args);
      $view->execute();
      if (count($view->result)) {
        $block[$event_status] = [
          'heading' => [
            '#prefix' => '<h3>',
            '#markup' => $view->getTitle(),
            '#suffix' => '</h3>',
          ],
          'results' => $view->preview($display, $args),
        ];
        $events[$event_status] = TRUE;
      }
    }
    // If we still haven't found anything, see if there are past events.
    if (empty($events)) {
      $view = Views::getView('breema_event_dashboard');
      $view->setDisplay('embed_past');
      $view->setArguments($args);
      $view->execute();
      if (count($view->result)) {
        $events['past'] = TRUE;
        $block['past'] = [
          '#prefix' => '<p>',
          '#markup' => $this->t('No upcoming events. <a href=":dashboard">Manage events</a> to view and clone past events.', [':dashboard' => $dashboard_url->toString()]),
          '#suffix' => '</p>',
        ];
      }
      else {
        $events_url = Url::fromUri('internal:/events');
        $block['empty'] = [
          '#prefix' => '<p>',
          '#markup' => $this->t('You do not have any <a href=":events">Breema events</a>.', [':events' => $events_url->toString()]),
          '#suffix' => '</p>',
        ];
      }
    }
    if (!empty($add_url)) {
      $block['add-link'] = [
        '#type' => 'link',
        '#title' => $this->t('Add event'),
        '#url' => $add_url,
        '#prefix' => '<div class="action action--primary">',
        '#suffix' => '</div>',
      ];
    }
    if (!empty($events)) {
      $block['dashboard'] = [
        '#type' => 'link',
        '#title' => $this->t('Manage events'),
        '#url' => $dashboard_url,
        '#prefix' => '<div class="action action--secondary">',
        '#suffix' => '</div>',
      ];
    }
    return $block;
  }
}

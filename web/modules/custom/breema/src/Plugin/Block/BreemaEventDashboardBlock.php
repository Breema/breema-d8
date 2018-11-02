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
    $current_page = Url::fromRoute('<current>');
    $url_options['query']['destination'] = $current_page->toString();

    $dashboard_url = Url::fromUri('internal:/user/' . $current_user->id() . '/events');
    $dashboard_link = [
      '#prefix' => '<div class="action-secondary">',
      '#markup' => $this->t('<a href=":dashboard">Event dashboard</a>', [':dashboard' => $dashboard_url->toString()]),
      '#suffix' => '</div>',
    ];
    $node_type = NodeType::load('event');
    $result = \Drupal::service('access_check.node.add')->access($current_user, $node_type);
    if ($result) {
      $add_url = Url::fromRoute('node.add', ['node_type' => 'event'], $url_options);
      $add_link = [
        '#prefix' => '<div class="action">',
        '#markup' => $this->t('<a href=":add">Add event</a>', [':add' => $add_url->toString()]),
        '#suffix' => '</div>',
      ];
    }
    /// @todo Also handle field_instructors
    $args = [$current_user->id()];
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
          /// @todo: We can mention "clone" once it exists on the dashboard.
          '#markup' => $this->t('View past events at your <a href=":dashboard">Event dashboard</a>.', [':dashboard' => $dashboard_url->toString()]),
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
    if (!empty($add_link)) {
      $block['add-link'] = $add_link;
    }
    if (!empty($events)) {
      $block['dashboard'] = $dashboard_link;
    }
    return $block;
  }
}

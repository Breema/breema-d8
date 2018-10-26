<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

/**
 * A block for users to manage their own Breema events.
 *
 * @Block(
 *   id = "breema_event_mgr_block",
 *   admin_label = @Translation("Breema event manager"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaEventMgrBlock extends BlockBase {

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
          'breema/block.event-mgr',
        ],
      ],
    ];
    $current_user = \Drupal::currentUser();
    $current_page = Url::fromRoute('<current>');
    $url_options['query']['destination'] = $current_page->toString();

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
    /// @todo Should this be a view?
    /// @todo Also handle field_instructors
    /// @todo Start time
    /// @todo Warn users if there are events pending moderation.
    /// @todo Link to a full management page?
    /// @todo Clone button for past events?
    $query = \Drupal::entityQuery('node')
           ->condition('type', 'event')
           ->condition('status', 1)
           ->condition('uid', $current_user->id());
    $nids = $query->execute();
    if (!empty($nids)) {
      $events = entity_load_multiple('node', $nids);
      foreach ($events as $event) {
        $event_links[] = $event->toLink()->toString();
      }
      return $block + [
        'events' => [
          '#theme' => 'item_list',
          '#items' => $event_links,
        ],
        'add-link' => $add_link,
      ];
    }
    elseif (!empty($add_link)) {
      return $block + [
        'empty-text' => [
          '#prefix' => '<p>',
          '#markup' => $this->t('You do not have any upcoming events.'),
          '#suffix' => '</p>',
        ],
        'add-link' => $add_link,
      ];
    }
  }
}

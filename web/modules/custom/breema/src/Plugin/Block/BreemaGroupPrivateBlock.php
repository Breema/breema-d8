<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Url;

/**
 * Shows the current user's private group(s), if any.
 *
 * @Block(
 *   id = "group_private_block",
 *   admin_label = @Translation("Group: Private info"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaGroupPrivateBlock extends BreemaUserGroupBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    $groups = $this->getCurrentUserGroups();

    if (!empty($groups['private'])) {
      $group_count = 0;
      foreach ($groups['private'] as $group) {
        $block['#cache']['tags'][] = 'group:' . $group->id();
        $block['group_' . $group->id()] = [
          '#prefix' => '<h3>' . $group->toLink()->toString() . '</h3>',
        ];
        $group_count++;
      }

      $all_group_count = $group_count;
      if (!empty($groups['audio'])) {
        $all_group_count += count($groups['audio']);
      }
      if ($all_group_count > 1) {
        $block['dashboard_link'] = [
          '#type' => 'link',
          '#title' => $this->t('View all groups'),
          '#url' => Url::fromRoute('entity.user.breema_group_dashboard', ['user' => $this->currentUser->id()]),
          '#prefix' => '<div class="action action--secondary">',
          '#suffix' => '</div>',
        ];
      }
      $block['#title'] = [
        '#markup' => $this->formatPlural($group_count, 'Your private group', 'Your private groups'),
      ];
    }

    return $block;
  }

}

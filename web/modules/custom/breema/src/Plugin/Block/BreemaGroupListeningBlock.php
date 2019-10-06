<?php

namespace Drupal\breema\Plugin\Block;


/**
 * Shows the current info from a user's Listening group(s), if any.
 *
 * @Block(
 *   id = "group_listening_block",
 *   admin_label = @Translation("Group: Listening info"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaGroupListeningBlock extends BreemaUserGroupBlockBase {

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

    if (!empty($groups['listening'])) {
      $options = [
        'label' => 'hidden',
        'type' => 'text_summary_or_trimmed',
      ];
      $group_count = 0;
      foreach ($groups['listening'] as $group) {
        $block['#cache']['tags'][] = 'group:' . $group->id();
        $block['group_' . $group->id()] = [
          '#prefix' => '<h3>' . $group->toLink()->toString() . '</h3>',
          'body' => $group->get('field_body')->view($options),
        ];
        $group_count++;
      }
      $block['#title'] = [
        '#markup' => $this->formatPlural($group_count, 'Your listening group', 'Your listening groups'),
      ];
    }
    return $block;
  }

}

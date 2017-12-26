<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

/**
 * A block for users to manage their own Breema Directory entry.
 *
 * @Block(
 *   id = "directory_entry_block",
 *   admin_label = @Translation("Breema Directory entry"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaDirectoryEntryBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#cache' => [
        'contexts' => ['user'],
      ],
      '#attached' => [
        'library' => [
          'breema/block.directory_entry',
        ],
      ],
    ];
    $current_user = \Drupal::currentUser();
    $current_page = Url::fromRoute('<current>');
    $url_options['query']['destination'] = $current_page->toString();
    $query = \Drupal::entityQuery('node')
           ->condition('type', 'directory_entry')
           ->condition('uid', $current_user->id());
    $nids = $query->execute();
    if (!empty($nids)) {
      $directory_entry = entity_load('node', array_pop($nids));
      $view_builder = \Drupal::entityManager()->getViewBuilder('node');
      $edit_url = Url::fromRoute('entity.node.edit_form', ['node' => $directory_entry->id()], $url_options);
      $delete_url = Url::fromRoute('entity.node.delete_form', ['node' => $directory_entry->id()], $url_options);
      return $block + [
        'teaser' => $view_builder->view($directory_entry, 'teaser'),
        'edit-link' => [
          '#prefix' => '<div class="action">',
          '#markup' => $this->t('<a href=":edit">Edit</a>', [':edit' => $edit_url->toString()]),
          '#suffix' => '</div>',
        ],
        'delete-link' => [
          // For reasons that aren't yet clear, #prefix and #suffix aren't
          // working for this, so we directly stuff those into #markup.
          '#markup' => '<div class="danger">' . $this->t('<a href=":delete" class="danger">Delete</a>', [':delete' => $delete_url->toString()]) . '</div>',
        ],
      ];
    }
    else {
      $node_type = NodeType::load('directory_entry');
      $result = \Drupal::service('access_check.node.add')->access($current_user, $node_type);
      if ($result) {
        $directory_url = Url::fromUri('base:/directory');
        $add_entry_url = Url::fromRoute('node.add', ['node_type' => 'directory_entry'], $url_options);
        $markup = '<p>' . $this->t('You do not have an entry in the <a href=":breema-directory">International Breema directory</a>.', [':breema-directory' => $directory_url->toString()]) . '</p>';
        $markup .= '<div class="action">' . $this->t('<a href=":create-directory-entry">Create entry</a>', [':create-directory-entry' => $add_entry_url->toString()]) . '</div>';
        return $block + [
          '#markup' => $markup,
        ];
      }
    }
  }
}

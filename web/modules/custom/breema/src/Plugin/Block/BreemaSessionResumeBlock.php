<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;
use Drupal\views\Views;

/**
 * A block for users to manage their own Breema session resumes.
 *
 * @Block(
 *   id = "breema_session_resume_block",
 *   admin_label = @Translation("Breema session resume block"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaSessionResumeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Either way, we need to cache this block per-user.
    $block = [
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
    $current_user = \Drupal::currentUser()->getAccount();
    // @todo We might have another way to test for certification in the future.
    if (in_array('practitioner', $current_user->getRoles(TRUE))) {
      // Already certified, return the block now without content.
      return $block;
    }

    // If we're going to show anything, it's a node list and we need to rebuild
    // on node changes.
    // @todo We could be smarter and have a resume-specific cache tag.
    // @see https://www.drupal.org/project/drupal/issues/2145751
    // @see https://www.drupal.org/project/handy_cache_tags
    $block['#cache']['tags'] = ['node_list'];

    $args = [$current_user->id()];
    $display = 'embed_1';
    $view = Views::getView('breema_resumes');
    $view->setDisplay($display);
    $view->setArguments($args);
    $view->execute();
    if (count($view->result)) {
      $block['resumes'] = $view->preview($display, $args);
    }
    else {
      $block['empty'] = [
        '#prefix' => '<p>',
        '#markup' => $this->t('You do not have any Breema session resumes.'),
        '#suffix' => '</p>',
      ];
    }
    $url_options['query']['destination'] = \Drupal::destination()->get();
    $node_type = NodeType::load('session_resume');
    if (\Drupal::service('access_check.node.add')->access($current_user, $node_type)) {
      $block['add-link'] = [
        '#prefix' => '<div class="action action--primary">',
        '#type' => 'link',
        '#title' => $this->t('Add session resume'),
        '#url' => Url::fromRoute('node.add', ['node_type' => 'session_resume'], $url_options),
        '#suffix' => '</div>',
        '#weight' => 10,
      ];
    }
    // @todo Do we need a resume dashboard of some kind?
    /*
    if (!empty($resumes)) {
      $block['dashboard'] = [
        '#prefix' => '<div class="action action--secondary">',
        '#type' => 'link',
        '#title' => $this->t('Resume dashboard'),
        '#url' => Url::fromUri('internal:/user/dashboard/resumes', $url_options),
        '#suffix' => '</div>',
        '#weight' => 20,
      ];
    }
    */
    return $block;
  }
}

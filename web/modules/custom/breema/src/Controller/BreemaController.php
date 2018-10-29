<?php

namespace Drupal\breema\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class BreemaController extends ControllerBase {
  /**
   * Access callback to ensure we're dealing with a parent event node.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function isParentEvent() {
    $bundle = '';
    $edit_access = FALSE;
    $node = \Drupal::routeMatch()->getParameter('node');
    // WTF? Why do we sometimes get a fully loaded node, and other times an id?
    if (is_string($node) || is_int($node)) {
      $node = Node::Load($node);
    }
    if ($node instanceof \Drupal\node\NodeInterface) {
      $edit_access = $node->access('update');
      $bundle = $node->bundle();
      if ($bundle == 'event') {
        $parent = $node->get('field_parent_event')->getValue();
      }
    }
    return AccessResult::allowedIf($edit_access && $bundle == 'event' && empty($parent));
  }
}

<?php

namespace Drupal\breema\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class BreemaController extends ControllerBase {
  /**
   * Access callback to see if a clone operation should be allowed.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function allowClone() {
    $bundle = '';
    $node = $this->loadNodeFromRoute();
    if (!empty($node)) {
      $bundle = $node->bundle();
    }
    // For now, we only want to allow cloning events.
    return AccessResult::allowedIf($bundle == 'event');
  }

  /**
   * Access callback to ensure we're dealing with a parent event node.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function isParentEvent() {
    $bundle = '';
    $node = $this->loadNodeFromRoute();
    if (!empty($node)) {
      $bundle = $node->bundle();
      if ($bundle == 'event') {
        $parent = $node->get('field_parent_event')->getValue();
      }
    }
    return AccessResult::allowedIf($bundle == 'event' && empty($parent));
  }

  /**
   * Shared helper function to get the current node (if any) from the route.
   *
   * @return NodeInterface
   *   The fully loaded node entity object, or NULL if there is none.
   */
  protected function loadNodeFromRoute() {
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!empty($node)) {
      // WTF? Why do sometimes get a fully loaded node and others just the id?
      if (is_string($node) || is_int($node)) {
        $node = Node::Load($node);
      }
      if ($node instanceof \Drupal\node\NodeInterface) {
        return $node;
      }
    }
  }

}

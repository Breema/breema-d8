<?php

namespace Drupal\breema\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

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
   * Access callback to see if we should let the user use 'add schedule'.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function allowAddSchedule() {
    $bundle = '';
    $edit_access = FALSE;
    $node = $this->loadNodeFromRoute();
    if (!empty($node)) {
      $bundle = $node->bundle();
      $edit_access = $node->access('update');
      if ($bundle == 'event') {
        $parent = $node->get('field_parent_event')->getValue();
      }
    }
    return AccessResult::allowedIf($bundle == 'event' && $edit_access && empty($parent));
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

  /**
   * Redirect to the appropriate page for the current user's directory entry.
   *
   * Redirects to the entry if it exists, else /node/add/directory_entry.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse to the right page for a user's directory entry.
   */
  public function myDirectoryEntry() {
    $account = $this->currentUser();
    $user = User::load($account->id());
    $directory_entry = $user->get('field_directory_entry')->getValue();
    if (!empty($directory_entry[0]['target_id'])) {
      return $this->redirect('entity.node.canonical', ['node' => $directory_entry[0]['target_id']]);
    }
    return $this->redirect('node.add', ['node_type' => 'directory_entry']);
  }

}

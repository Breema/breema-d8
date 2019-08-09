<?php

namespace Drupal\breema\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\views\Views;

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
   * Shared helper function to get the current user (if any) from the route.
   *
   * @return UserInterface
   *   The fully loaded user entity object, or NULL if there is none.
   */
  protected function loadUserFromRoute() {
    $user = \Drupal::routeMatch()->getParameter('user');
    if (!empty($user)) {
      // WTF? Why do we sometimes get a full user and other times just the id?
      if (is_string($user) || is_int($user)) {
        $user = User::Load($user);
      }
      if ($user instanceof \Drupal\user\UserInterface) {
        return $user;
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

  /**
   * Redirect to the current user's event dashboard.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse to the right page for a user's event dashboard.
   */
  public function eventDashboard() {
    $account = $this->currentUser();
    return $this->redirect('view.breema_event_dashboard.page_user_dashboard', ['user' => $account->id()]);
  }

  /**
   * Redirect to the current user's group dashboard.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse to the right page for a user's group dashboard.
   */
  public function groupDashboard() {
    $account = $this->currentUser();
    return $this->redirect('view.breema_group_user.page_user_group_dashboard', ['user' => $account->id()]);
  }

  /**
   * Access callback for the 'Resumes' tab on a given user.
   *
   * Currently, users can see their own tab, and content admins can see any.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function accessResumes() {
    $route_user = $this->loadUserFromRoute();
    $current_user = $this->currentUser();
    $is_admin = $current_user->hasPermission('edit any session_resume content');
    return AccessResult::allowedIf($is_admin || (!empty($route_user) && $current_user->id() == $route_user->id()));
  }

  /**
   * Page title callback for the entity.user.breema_resumes route.
   *
   * @return string
   *   The page title.
   */
  public function resumesPageTitle() {
    $user = $this->loadUserFromRoute();
    if (!empty($user)) {
      return $this->t('Resumes for @user', ['@user' => $user->label()]);
    }
    $this->t('Resumes');
  }

  /**
   * Page controller for the resumes tab on user accounts.
   */
  public function resumesPage() {
    $url_options['query']['destination'] = \Drupal::destination()->get();
    $add_url = Url::fromRoute('node.add', ['node_type' => 'session_resume'], $url_options);
    $build = [
      '#cache' => [
        'contexts' => ['route', 'user'],
      ],
      'add' => [
        '#prefix' => '<div class="action action--primary">',
        '#markup' => $this->t('<a href=":add">Add session resume</a>', [':add' => $add_url->toString()]),
        '#suffix' => '</div>',
      ],
    ];
    $view = Views::getView('breema_resumes');
    $user = $this->loadUserFromRoute();
    $view->setDisplay('embed_1');
    $view->setArguments([$user->id()]);
    $view->execute();
    if (count($view->result)) {
      $build['resumes'] = $view->preview();
    }
    else {
      $build['no_results'] = [
        '#markup' => $this->t('There are no resumes for %user.', ['%user' => $user->label()]),
      ];
    }
    return $build;
  }

}

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
    // @todo Dependecy injection
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
    // @todo Dependecy injection
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
  public function eventDashboardRedirect() {
    $account = $this->currentUser();
    return $this->redirect('entity.user.breema_event_dashboard', ['user' => $account->id()]);
  }

  /**
   * Redirect to the current user's group dashboard.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse to the right page for a user's group dashboard.
   */
  public function groupDashboardRedirect() {
    $account = $this->currentUser();
    return $this->redirect('entity.user.breema_group_dashboard', ['user' => $account->id()]);
  }

  /**
   * Access callback for the 'Events' tab on a given user.
   *
   * Currently, users can see their own tab (if they belong to any groups), and
   * group admins can see any user's dashboard (if the user is in any groups).
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function accessEventDashboard() {
    $route_user = $this->loadUserFromRoute();
    if (!$route_user) {
      return AccessResult::forbidden('User not found, no event dashboard possible.');
    }
    if (!$route_user->hasPermission('create event content')) {
      return AccessResult::forbidden($route_user->label() . ' does not have permission to create events.');
    }
    $current_user = $this->currentUser();
    if ($current_user->hasPermission('edit any event content')) {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIf($current_user->id() == $route_user->id());
  }

  /**
   * Page title callback for the entity.user.breema_event_dashboard route.
   *
   * @return string
   *   The page title.
   */
  public function eventDashboardPageTitle() {
    $user = $this->loadUserFromRoute();
    if (!empty($user)) {
      return $this->t("@user's events", ['@user' => $user->label()]);
    }
    return $this->t('Events');
  }

  /**
   * Page controller for the events tab on user accounts.
   */
  public function eventDashboardPage() {
    $view = Views::getView('breema_event_dashboard');
    $user = $this->loadUserFromRoute();
    $view->setDisplay('embed_user_dashboard');
    $view->setArguments([$user->id(), $user->id()]);
    $view->execute();
    // @todo Dependecy injection
    $url_options['query']['destination'] = \Drupal::destination()->get();
    return [
      '#cache' => [
        'contexts' => ['route', 'user'],
      ],
      'add-link' => [
        '#type' => 'link',
        '#title' => 'Add new event',
        '#url' => Url::fromRoute('node.add', ['node_type' => 'event'], $url_options),
        '#prefix' => '<div class="action action--primary">',
        '#suffix' => '</div>',
      ],
      'events' => $view->preview(),
    ];
  }

  /**
   * Access callback for the 'Groups' tab on a given user.
   *
   * Currently, users can see their own tab (if they belong to any groups), and
   * group admins can see any user's dashboard (if the user is in any groups).
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function accessGroupDashboard() {
    $route_user = $this->loadUserFromRoute();
    if (!$route_user) {
      return AccessResult::forbidden('User not found, no group dashboard possible.');
    }
    // @todo Dependency injection
    $route_user_groups = \Drupal::service('group.membership_loader')->loadByUser($route_user);
    if (empty($route_user_groups)) {
      return AccessResult::forbidden($route_user->label() . ' is not a member of any groups.');
    }
    $current_user = $this->currentUser();
    if ($current_user->hasPermission('administer group')) {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIf($current_user->id() == $route_user->id());
  }

  /**
   * Page title callback for the entity.user.breema_group_dashboard route.
   *
   * @return string
   *   The page title.
   */
  public function groupDashboardPageTitle() {
    $user = $this->loadUserFromRoute();
    if (!empty($user)) {
      return $this->t("@user's groups", ['@user' => $user->label()]);
    }
    return $this->t('Groups');
  }

  /**
   * Page controller for the groups tab on user accounts.
   */
  public function groupDashboardPage() {
    $view = Views::getView('breema_group_user');
    $user = $this->loadUserFromRoute();
    $view->setDisplay('embed_group_dashboard');
    $view->setArguments([$user->id()]);
    $view->execute();
    // The access callback should already ensure we have groups.
    assert(count($view->result));
    return [
      '#cache' => [
        'contexts' => ['route', 'user'],
      ],
      'groups' => $view->preview(),
    ];
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
    if (!$route_user) {
      return AccessResult::forbidden('User not found, no session resume page.');
    }
    $current_user = $this->currentUser();
    if ($current_user->hasPermission('edit any session_resume content')) {
      return AccessResult::allowed();
    }
    if ($route_user->hasRole('practitioner') || $route_user->hasRole('instructor')) {
      return AccessResult::forbidden($route_user->label() . ' is certified, no session resume page needed.');
    }
    return AccessResult::allowedIf($current_user->id() == $route_user->id());
  }

  /**
   * Page title callback for the entity.user.breema_resume_dashboard route.
   *
   * @return string
   *   The page title.
   */
  public function resumesPageTitle() {
    $user = $this->loadUserFromRoute();
    if (!empty($user)) {
      return $this->t("@user's resumes", ['@user' => $user->label()]);
    }
    return $this->t('Resumes');
  }

  /**
   * Page controller for the resumes tab on user accounts.
   */
  public function resumesPage() {
    // @todo Dependecy injection
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

  /**
   * Redirect to the current user's resume dashboard.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse to the right page for a user's resume dashboard.
   */
  public function resumeDashboardRedirect() {
    $account = $this->currentUser();
    return $this->redirect('entity.user.breema_resume_dashboard', ['user' => $account->id()]);
  }

  /**
   * Handle legacy directory URLs and redirect to the appropriate search.
   *
   * @param string $region
   *   The region of the world, one of 'US', 'americas', 'europe' or 'other'.
   * @param string $detail
   *   The specific page (state codes for US, country codes for the others).
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse to the right international directory search.
   */
  public function legacyDirectoryRedirect($region, $detail = NULL) {
    $url_options = [];
    if ($region === 'US') {
      $url_options['query']['country'] = 'US';
      if (!empty($detail)) {
        $url_options['query']['state'] = $detail;
      }
    }
    elseif (!empty($detail)) {
      $url_options['query']['country'] = $detail;
    }
    return $this->redirect('view.breema_directory.page_list', [], $url_options);
  }

  /**
   * Handle legacy inspiration URLs and redirect to the Essence of Breema list.
   *
   * @param string $slug
   *   Whatever the rest of the URL contains.
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse to send visitors to the Essence of Breema list.
   */
  public function legacyInspirationRedirect($slug = NULL) {
    return $this->redirect('view.breema_essence.page_1', [], []);
  }

  /**
   * Handle legacy deutsch* URLs and redirect to the front page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse to send visitors to the front page.
   *
   * @see \Drupal\breema\PathProcessor\BreemaPathProcessor
   */
  public function legacyDeutschRedirect() {
    // @todo: Get good German text for this.
    //\Drupal::messenger()->addWarning(t('Unfortunately, the German section of the site is now gone.'));
    return $this->redirect('<front>');
  }

}

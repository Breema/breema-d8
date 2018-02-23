<?php

namespace Drupal\breema\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\contact\Access\ContactPageAccess;
use Drupal\user\UserInterface;

/**
 * Checks access for the per-user contact tab.
 */
class BreemaContactPageAccess extends ContactPageAccess implements AccessInterface {
  /**
   * Checks access to the given user's contact page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being contacted.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(UserInterface $user, AccountInterface $account) {
    // To avoid confusion, $account is the user we're checking acccess for.
    // $user is the account of the contact tab we're checking access to.
    // So, let's refer to $user as $contact_account to be more clear.
    $contact_account = $user;

    // Anonymous users cannot have contact forms.
    if ($contact_account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    // We can only cache access results per-user.
    $access = AccessResult::neutral()->cachePerUser();

    // Forbid access if the requested user has disabled their contact form.
    $account_data = $this->userData->get('contact', $contact_account->id(), 'enabled');
    if (isset($account_data) && !$account_data) {
      return $access;
    }

    // Unlike core, we *want* to let users access their own contact tab.
    if ($account->id() == $contact_account->id()) {
      return AccessResult::allowed()->cachePerUser();
    }

    // Otherwise, fallback to the core logic.
    return parent::access($user, $account);
  }
}

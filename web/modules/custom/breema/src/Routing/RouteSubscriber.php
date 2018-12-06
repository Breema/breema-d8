<?php

namespace Drupal\breema\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change access rules on user contact tabs.
    if ($route = $collection->get('entity.user.contact_form')) {
      // Clobber, don't add.
      $route->setRequirements(['_access_breema_contact_personal_tab' => 'TRUE']);
    }
    if ($route = $collection->get('user.register')) {
      $route->setDefault('_title', (string)t('Practitioner Certificate Program online application'));
    }
  }

}

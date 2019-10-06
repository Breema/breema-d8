<?php

namespace Drupal\breema\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller class for handling legacy URLs and redirects.
 *
 * All this code lives in a separate class so we don't need to instantiate it
 * and load it on most page loads. Also keeps things a bit clearer and cleaner.
 */
class BreemaLegacyController extends ControllerBase {

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
  public function redirectDirectory($region, $detail = NULL) {
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
  public function redirectInspiration($slug = NULL) {
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
  public function redirectDeutsch() {
    // Note: Don't use t() for this, since we always want it in German.
    \Drupal::messenger()->addWarning('Vorläufig können wir keine deutsche Sektion anbieten. Wir hoffen aber in der Zukunft wieder eine hinzufügen zu können.');
    return $this->redirect('<front>');
  }

  /**
   * Handle legacy brochure PDF URLs and redirect to 'Events'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse.
   *
   * @see \Drupal\breema\PathProcessor\BreemaPathProcessor
   */
  public function redirectBrochurePDF() {
    return $this->redirect('view.breema_event_search.page_1');
  }

  /**
   * Handle legacy intensive PDF URLs and redirect to /about-breema/intensives.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse.
   *
   * @see \Drupal\breema\PathProcessor\BreemaPathProcessor
   */
  public function redirectIntensivePDF() {
    return $this->redirect('entity.node.canonical', ['node' => 36]);
  }

  /**
   * Handle legacy schedule PDF URLs and redirect to /events/center (node 38).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The RedirectResponse.
   *
   * @see \Drupal\breema\PathProcessor\BreemaPathProcessor
   */
  public function redirectSchedulePDF() {
    return $this->redirect('entity.node.canonical', ['node' => 38]);
  }

}

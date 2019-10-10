<?php

namespace Drupal\breema\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

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
    if (!empty($detail)) {
      $uc_detail = mb_strtoupper($detail);
    }
    $url_options = [];
    if ($region === 'US' || $region === 'us') {
      $url_options['query']['country'] = 'US';
      if (!empty($uc_detail)) {
        $url_options['query']['state'] = $uc_detail;
      }
    }
    elseif (!empty($uc_detail)) {
      // 'UK' isn't actually a country code. It's really 'GB'.
      if ($uc_detail === 'UK') {
        $uc_detail = 'GB';
      }
      $url_options['query']['country'] = $uc_detail;
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
    // Lanugage 13 is 'Deutsch'.
    $articles_url = Url::fromRoute('view.breema_articles.page_1', [], ['query' => ['language' => 13]]);
    // User 66 is Aron Saltiel.
    $contact_url = Url::fromRoute('entity.user.contact_form', ['user' => 66]);
    $placeholders = [
      ':articles_url' => $articles_url->toString(),
      ':contact_url' => $contact_url->toString(),
    ];
    // Note: Don't use t() for this, since we always want it in German.
    // However, we need a FormattableMarkup object so the links aren't escaped.
    \Drupal::messenger()->addWarning(new FormattableMarkup('Vorläufig können wir keine deutsche Sektion anbieten. Wir hoffen aber in der Zukunft wieder eine hinzufügen zu können. Sie können Artikel auf Deutsch in der <a href=":articles_url">Articles about Breema</a>-Sektion finden. Für Bücher über Breema auf Deutsch <a href=":contact_url">wenden Sie sich bitte an Aron Saltiel</a>.', $placeholders));
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

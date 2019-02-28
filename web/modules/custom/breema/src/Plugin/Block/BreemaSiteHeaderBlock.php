<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * A site-wide block for 'The Breema Center' header text.
 *
 * @Block(
 *   id = "breema_site_header_block",
 *   admin_label = @Translation("Breema site header text"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaSiteHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#attributes' => [
        'class' => ['site-branding'],
      ],
      'site-name' => [
        '#type' => 'link',
        '#title' => $this->t('The Breema Center'),
        '#attributes' => [
          'title' => $this->t('Home'),
        ],
        '#url' => Url::fromRoute('<front>'),
        '#prefix' => '<div class="site-branding__name">',
        '#suffix' => '</div>',
      ],
    ];
  }
}

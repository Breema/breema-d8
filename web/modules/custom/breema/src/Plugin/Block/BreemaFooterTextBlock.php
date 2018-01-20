<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;

/**
 * A site-wide block for Breema Center footer text.
 *
 * @Block(
 *   id = "footer_text_block",
 *   admin_label = @Translation("Breema Footer text"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaFooterTextBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'copyright' => [
        '#prefix' => '<div class="copyright">',
        '#markup' => $this->t('Copyright © :year The Breema Center', [':year' => date('Y')]),
        '#suffix' => '</div>',
        '#weight' => 0,
      ],
      'address' => [
        '#prefix' => '<div class="address"><address>',
        '#markup' => $this->t('6076 Claremont Avenue, Oakland, CA 94618'),
        '#suffix' => '</address></div>',
        '#weight' => 1,
      ],
      'contact' => [
        '#prefix' => '<div class="contact">',
        '#markup' => '<a class="telephone" href="tel:+15104280937">510-428-0937</a> <a class="email" href="mailto:center@breema.com">center@breema.com</a>',
        '#suffix' => '</div>',
        '#weight' => 2,
      ],
      'service-mark' => [
        '#prefix' => '<div class="service-mark">',
        '#markup' => $this->t('Breema® is a service mark of The Breema Center'),
        '#suffix' => '</div>',
        '#weight' => 3,
      ],
    ];
  }
}

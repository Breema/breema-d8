<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * A block at the bottom of all Essence posts (to link back to the overview).
 *
 * @Block(
 *   id = "essence_footer_block",
 *   admin_label = @Translation("Breema Essence footer"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaEssenceFooterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#cache' => [
        // @todo Explicitly say this is globally cachable.
      ],
      'link' => [
        '#type' => 'link',
        '#title' => t('Read more Essence of Breema'),
        '#url' => Url::fromUri('internal:/about-breema/essence'),
        '#prefix' => '<div class="action action--secondary">',
        '#suffix' => '</div>',
      ],
    ];
  }

}

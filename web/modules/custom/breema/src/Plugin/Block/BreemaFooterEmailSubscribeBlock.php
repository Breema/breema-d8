<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * A site-wide block for the Breema Center email subscribe link.
 *
 * @Block(
 *   id = "footer_email_subscribe_block",
 *   admin_label = @Translation("Breema footer email subscribe"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaFooterEmailSubscribeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = 'https://visitor.r20.constantcontact.com/d.jsp?llr=9ssf66vab&p=oi&m=1123063876186&sit=mxkiksfkb&f=d46ac83b-8362-4d98-9e1f-2fed8fe941b9';
    return [
      'link' => [
        '#prefix' => '<div class="text-link">',
        '#markup' => $this->t('<a href=":url">Join one or more of our e-mail lists for announcements and inspiration!</a>', [':url' => $url]),
        '#suffix' => '</div>',
      ],
      'subscribe' => [
        '#prefix' => '<div class="action action--secondary">',
        '#markup' => $this->t('<a href=":url">Subscribe</a>', [':url' => $url]),
        '#suffix' => '</div>',
      ],
    ];
  }
}

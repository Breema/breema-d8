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
    return [
      'subscribe' => [
        '#markup' => $this->t('<a href=":url">Join one or more of our email lists for announcements and inspiration!</a>', [':url' => 'https://visitor.r20.constantcontact.com/d.jsp?llr=9ssf66vab&p=oi&m=1123063876186&sit=mxkiksfkb&f=d46ac83b-8362-4d98-9e1f-2fed8fe941b9']),
      ],
    ];
  }
}

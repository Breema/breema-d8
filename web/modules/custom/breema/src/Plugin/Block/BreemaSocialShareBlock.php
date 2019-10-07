<?php

namespace Drupal\breema\Plugin\Block;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * A block that renders a page's social media share links.
 *
 * @Block(
 *   id = "social_share_block",
 *   admin_label = @Translation("Social media share links"),
 *   category = @Translation("Breema"),
 * )
 */
class BreemaSocialShareBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#cache' => ['contexts' => ['route']],
      '#attached' => [
        'library' => [
          'breema/block.share',
        ],
      ],
    ];
    $info = $this->getCurrentPageInfo();
    // If we're not on a node that we want to share, return an empty block.
    if (empty($info)) {
      return $block;
    }
    $block['#cache']['tags'][] = 'node:' . $info['nid'];
    $all_services = $this->getServices($info);
    foreach ($all_services as $group => $services) {
      $block[$group] = [
        '#theme' => 'item_list',
        '#type' => 'ul',
        '#wrapper_attributes' => ['class' => ['share', $group . '-links']],
      ];
      if ($group === 'more') {
        // Add the close link at the top of the list.
        $block[$group]['#items'][] = [
          '#prefix' => '<div class="more-links-close">',
          '#markup' => '<span class="text">' . $this->t('Close') . '</span><span class="x">X</span>',
          '#suffix' => '</div>',
        ];
      }
      foreach ($services as $service_id => $service_data) {
        $block[$group]['#items'][] = [
          '#prefix' => '<a href="' . $service_data['link'] . '" title="' . $service_data['text'] . '" class="' . $service_id . '" target="_blank">',
          '#markup' => $this->getLinkMarkup($group, $service_id, $service_data['text']),
          '#suffix' => '</a>',
        ];
      }
    }
    return $block;
  }

  /**
   * Returns shareable info for the current page, if any.
   *
   * @return array
   *  - nid: Node ID of the page being shared.
   *  - title: Title of the page being shared.
   *  - url: Absolute (full) URL to share.
   *  - summary: Summary text.
   */
  protected function getCurrentPageInfo() {
    $info = [];
    $shareable_bundles = [
      'article', 'directory_entry', 'essence', 'event', 'page', 'place',
    ];
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!empty($node) && in_array($node->bundle(), $shareable_bundles)) {
      $info = [
        'nid' => $node->id(),
        'title' => rawurlencode($node->getTitle()),
        'url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
        'summary' => rawurlencode(breema_get_node_meta_description($node)),
      ];
      if ($node->bundle() === 'event') {
        $fb_event = $node->get('field_facebook_event')->getValue();
        if (!empty($fb_event[0]['uri'])) {
          $info['fb_event'] = $fb_event[0]['uri'];
        }
      }
    }
    return $info;
  }

  /**
   * Returns a nested array of service links.
   *
   * @param array $info
   *   Array of strings containing info about the page to share:
   *   - nid: The page's node ID.
   *   - title: The page title.
   *   - $url: The absolute URL to share.
   *   - $summary: The 'teaser' summary of the page to share.
   *   - $fb_event: The Facebook event link (if any).
   *
   * @return array
   *   Nested array of service info. Top-level keys are 'main' and 'more'.
   *   Entries are keyed by the service name and contain 'link' and 'text'.
   */
  protected function getServices($info) {
    $fb_link = !empty($info['fb_event']) ? $info['fb_event'] : $info['url'];
    // @see https://developers.facebook.com/docs/sharing/reference/share-dialog
    $options = [
      'query' => [
        'app_id' => BREEMA_FB_APP_ID,
        'display' => 'page',
        'href' => $fb_link,
        'redirect_uri' => $info['url'],
      ],
    ];
    return [
      'main' => [
        'facebook' => [
          'link' => Url::fromUri('https://www.facebook.com/dialog/share', $options)->toString(),
          'text' => 'Share on Facebook',
        ],
        'forward' => [
          'link' => Url::fromUri('internal:/forward/node/' . $info['nid'])->toString(),
          'text' => 'Send via e-mail',
        ],
        'more' => [
          'link' => '#',
          'text' => 'Click for more options',
        ],
      ],
      'more' => [
        'twitter' => [
          'link' => sprintf('https://twitter.com/intent/tweet?url=%s&text=%s', $info['url'], $info['title']),
          'text' => 'Share on Twitter',
        ],
        'linkedin' => [
          'link' => sprintf('https://www.linkedin.com/shareArticle?mini=true&url=%s&title=%s&summary=%s', $info['url'], $info['title'], $info['summary']),
          'text' => 'Share on LinkedIn',
        ],
        'pinterest' => [
          'link' => sprintf('https://pinterest.com/pin/create/link/?url=%s&description=%s', $info['url'], $info['title']),
          'text' => 'Share on Pintrest',
        ],
      ],
    ];
  }

  /**
   * Returns the desired markup for a given service link.
   *
   * @param string $group
   *   Which link group the service is in (either 'main' or 'more').
   * @param string $service_id
   *   Machine name for the service to link to.
   * @param string $service_text
   *   Service-specific help text.
   * @return string
   *   The full HTML for a given link.
   */
  protected function getLinkMarkup($group, $service_id, $service_text) {
    if ($group == 'more') {
      return Unicode::ucfirst($service_id);
    }
    switch ($service_id) {
      case 'facebook':
        $fa_icon = 'fa-facebook';
        break;

      case 'forward':
        $fa_icon = 'fa-envelope';
        break;

      case 'more':
        $fa_icon = 'fa-plus';
        break;
    }
    return '<span class="fa ' . $fa_icon . '"></span>' .
      '<span class="visually-hidden">' . $service_text . '</span>';
  }

}

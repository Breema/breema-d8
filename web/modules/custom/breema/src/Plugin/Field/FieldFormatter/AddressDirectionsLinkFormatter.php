<?php

namespace Drupal\breema\Plugin\Field\FieldFormatter;

use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\address\AddressInterface;
use Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter;

/**
 * Plugin implementation of the 'address_directions_link' formatter.
 *
 * @FieldFormatter(
 *   id = "address_directions_link",
 *   label = @Translation("Directions link"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressDirectionsLinkFormatter extends AddressDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $directions_url = $this->getDirectionsLink($item);
      $elements[$delta] = [
        '#prefix' => '<a href="' . $directions_url . '" target="_blank"><p class="address" translate="no">',
        '#suffix' => '</p></a>',
        '#post_render' => [
          [get_class($this), 'postRender'],
        ],
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
          ],
        ],
      ];
      $elements[$delta] += $this->viewElement($item, $langcode);
    }
    return $elements;
  }

  /**
   * Builds a link for directions to a given address.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   *
   * @return string
   *   The string representation of the URL for directions.
   */
  protected function getDirectionsLink(AddressInterface $address) {
    $country_code = $address->getCountryCode();
    $address_format = $this->addressFormatRepository->get($country_code);
    $values = $this->getValues($address, $address_format);
    $link = 'https://www.google.com/maps/dir/Current+Location/';
    $parts = [];
    $fields = [
      'addressLine1',
      'dependentLocality',
      'locality',
      'administrativeArea',
      'postalCode',
    ];
    foreach ($fields as $field) {
      if (!empty($values[$field])) {
        $parts[] = urlencode($values[$field]);
      }
    }
    $parts[] = $country_code;
    $link .= implode(',', $parts);
    return $link;
  }
}

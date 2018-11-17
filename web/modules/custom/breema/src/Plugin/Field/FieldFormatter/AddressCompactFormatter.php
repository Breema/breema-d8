<?php

namespace Drupal\breema\Plugin\Field\FieldFormatter;

use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use Drupal\address\AddressInterface;
use Drupal\address\Plugin\Field\FieldFormatter\AddressPlainFormatter;

/**
 * Plugin implementation of the 'address_compact' formatter.
 *
 * @FieldFormatter(
 *   id = "address_compact",
 *   label = @Translation("Compact"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressCompactFormatter extends AddressPlainFormatter {
  /**
   * Builds a renderable array for a single address item.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement(AddressInterface $address, $langcode) {
    $country_code = $address->getCountryCode();
    $address_format = $this->addressFormatRepository->get($country_code);
    $values = $this->getValues($address, $address_format);

    $address_parts = [];
    foreach (['locality', 'administrativeArea'] as $address_part) {
      if (!empty($values[$address_part]['code'])) {
        $address_parts[] = $values[$address_part]['code'];
      }
      elseif (!empty($values[$address_part]) && is_string($values[$address_part])) {
        $address_parts[] = $values[$address_part];
      }
    }
    $address_parts[] = $country_code;
    return [
      '#type' => 'markup',
      '#markup' => implode(', ', $address_parts),
    ];
  }
}

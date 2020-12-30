<?php

namespace Drupal\breema\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'tzfield_abbreviation' formatter.
 *
 * Uses PHP date manipulation functions to map official timezone names (e.g.
 * 'America/Los_Angeles') into the official TZ abbreviation (e.g. 'PST'). Based
 * on the actual date the TZ is related to, finds out if DST is in effect or
 * not. Automatically gets the official TZ abbreviation (e.g. PDT).
 *
 * @todo The corresponding date field should be a formatter setting.
 *
 * @FieldFormatter(
 *   id = "tzfield_abbreviation",
 *   label = @Translation("Timezone abbreviation"),
 *   field_types = {
 *     "tzfield"
 *   }
 * )
 */
class TimeZoneFieldAbbreviationFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $entity = $item->getEntity();
      // @todo These could be formatter settings.
      $date_time = $entity->bundle() !== 'event' ? 'now' :
         $entity->get('field_date_time')->getValue()[0]['value'];
      // Render each element as a plain text abbreviation string.
      $element[$delta] = [
        '#plain_text' => $this->getTimeZoneAbbreviation($item->value, $date_time),
      ];
    }
    return $element;
  }

  /**
   * Gets the official abbreviation for a given timezone.
   *
   * @param string $timezone_name
   *   The official timezone name (e.g. 'America/Los Angeles').
   * @param string $date_time
   *   The date being displayed (for the appropriate DST logic).
   *
   * @return string
   *   The official timezone abbreviation (e.g. 'PDT' or 'CET').
   */
  protected function getTimeZoneAbbreviation($timezone_name, $date_time) {
    $date = new \DateTime($date_time, new \DateTimeZone($timezone_name));
    return $date->format('T');
  }

}

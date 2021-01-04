<?php

namespace Drupal\breema\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'tzfield_details' formatter.
 *
 * Uses PHP date manipulation functions to map official timezone names
 * (e.g. 'America/Los_Angeles') into something more user-friendly. Based on the
 * actual date the TZ is related to, finds out if DST is in effect or
 * not. Automatically gets the official TZ abbreviation (e.g. PDT), and the
 * offset from UTC (e.g. '-07:00'). If known, it also gets the full name of the
 * corresponding TZ abbreviation (e.g. 'Pacific Daylight Time').
 *
 * Field values are formatted as a <details> element, with the TZ abbreviation
 * as the summary, and the rest of the info as the body.
 *
 * @todo The corresponding date field should be a formatter setting.
 *
 * @FieldFormatter(
 *   id = "tzfield_details",
 *   label = @Translation("Timezone details"),
 *   field_types = {
 *     "tzfield"
 *   }
 * )
 */
class TimeZoneFieldDetailsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      // Render each element as a details container with all the info.
      $entity = $item->getEntity();
      // @todo These could be formatter settings.
      $date_time = $entity->bundle() !== 'event' ? 'now' :
         $entity->get('field_date_time')->getValue()[0]['value'];
      $element[$delta] = $this->getTimeZoneDetails($item->value, $date_time);
    }
    return $element;
  }

  /**
   * Gets a render array for the <details> element for a given timezone.
   *
   * @param string $timezone_name
   *   The official timezone name (e.g. 'America/Los Angeles').
   * @param string $date_time
   *   The date being displayed (e.g. for the appropriate DST logic).
   *
   * @return array
   *   A render array with the desired abbreviation as the #title and all the
   *   detailed info in the body.
   */
  protected function getTimeZoneDetails($timezone_name, $date_time) {
    $info = $this->getTimeZoneInfo($timezone_name, $date_time);
    $items = [];
    if (!empty($info['full_name'])) {
      $items[] = $info['full_name'];
    }
    $items[] = $this->t('UTC @offset', ['@offset' => $info['offset']]);
    //$items[] = $this->t('DST: @dst', ['@dst' => $info['is_dst'] ? 'Yes' : 'No']);
    $items[] = $timezone_name;
    return [
      '#type' => 'details',
      '#title' => $info['abbreviation'],
      '#open' => FALSE,
      '#attributes' => ['class' => ['tzfield-details']],
      'info' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

  /**
   * Gets all the info for a given time zone.
   *
   * @param string $timezone_name
   *   The official timezone name (e.g. 'America/Los Angeles').
   * @param string $date_time
   *   The date being displayed (e.g. for the appropriate DST logic).
   *
   * @return array
   *   Information about the given timezone. Includes the following keys:
   *   - (bool) dst: Is DST happening in that timezone on the given date.
   *   - (string) abbreviation: The preferred abbreviation (e.g. 'PST')
   *   - (string) offset: The offset from UTC (e.g. '+01:00').
   *   - (string) full_name: The human-friendly full name if known
   *     (e.g. 'Pacific Standard Time')
   */
  protected function getTimeZoneInfo($timezone_name, $date_time) {
    $date = new \DateTime($date_time, new \DateTimeZone($timezone_name));
    $dst = (bool)$date->format('I');
    $info = [
      'is_dst' => $dst,
      'offset' => $date->format('P'),
      'abbreviation' => $date->format('T'),
    ];

    switch ($timezone_name) {
      case 'America/Los_Angeles':
      case 'America/Vancouver':
        $info['full_name'] = $dst ? $this->t('Pacific Daylight Time') : $this->t('Pacific Standard Time');
        break;

      case 'America/Chicago':
      case 'America/Mexico_City':
        $info['full_name'] = $dst ? $this->t('Central Daylight Time') : $this->t('Central Standard Time');
        break;

      case 'America/Phoenix':
        // Arizona doesn't observe DST, so $dst should always be false.
      case 'America/Denver':
        $info['full_name'] = $dst ? $this->t('Mountain Daylight Time') : $this->t('Mountain Standard Time');
        break;

      case 'America/Cancun':
        // Cancun doesn't observe DST, so $dst should always be false.
      case 'America/New_York':
        $info['full_name'] = $dst ? $this->t('Eastern Daylight Time') : $this->t('Eastern Standard Time');
        break;

      case 'America/Sao_Paulo':
        $info['full_name'] = $this->t('Brasília time');
        // Sadly, PHP thinks the abbreviation is '-03'. Give it something more
        // accurate and in use. There is no daylight savings, so we're safe.
        $info['abbreviation'] = 'BRT';
        break;

      case 'Asia/Jerusalem':
        $info['full_name'] = $dst ? $this->t('Israel Daylight Time') : $this->t('Israel Standard Time');
        break;

      case 'Europe/Berlin':
      case 'Europe/Copenhagen':
      case 'Europe/Madrid':
      case 'Europe/Paris':
      case 'Europe/Rome':
      case 'Europe/Stockholm':
      case 'Europe/Vienna':
      case 'Europe/Zagreb':
        $info['full_name'] = $dst ? $this->t('Central European Summer Time') : $this->t('Central European Time');
        break;

      case 'Europe/Lisbon':
        $info['full_name'] = $dst ? $this->t('Western European Summer Time') : $this->t('Western European Time');
        break;

      case 'Europe/London':
        $info['full_name'] = $dst ? $this->t('British Summer Time') : $this->t('Greenwich Mean Time');
        break;

      case 'Pacific/Honolulu':
        // Hawaii doesn't use DST, so it's always standard time.
        $info['full_name'] = $this->t('Hawaii Standard Time');
        break;

    }
    return $info;
  }
}

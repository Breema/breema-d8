<?php

namespace Drupal\breema\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\datetime_extras\Plugin\Field\FieldFormatter\DateRangeCompactFormatter;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Plugin implementation of the 'Multiple timezone' formatter for 'daterange'.
 *
 * This formatter renders the date range start time in various timezones.
 *
 * @FieldFormatter(
 *   id = "daterange_multi_tz",
 *   label = @Translation("Multiple timezone start times"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeMultiTimezoneFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date)) {
        $start_time = $item->start_date->format('Y-m-d H:i:s');
        $start_date = $item->start_date->format('Y-m-d');
        $entity = $item->getEntity();
        // @todo It's a bit funky to hard-code this, but probably simpler.
        if (empty($entity->field_online->value)) {
          continue;
        }
        // @todo The field to use for this should be configurable.
        $event_timezone =  new \DateTimeZone($entity->field_timezone->value);
        $datetime = new \DateTime($start_time, $event_timezone);
        // @todo This should be configurable.
        $display_timezones = [
          //'Pacific/Honolulu' => 'g:ia T',
          'America/Los_Angeles' => 'g:ia T',
          'America/New_York' => 'g:ia T',
          'Europe/Vienna' => 'G:i T',
          'Asia/Jerusalem' => 'G:i T',
        ];
        $times = [];
        foreach ($display_timezones as $display_tz => $format) {
          $datetime->setTimezone(new \DateTimeZone($display_tz));
          $display_date = $datetime->format('Y-m-d');
          $display_time = $datetime->format($format);
          if ($display_date === $start_date) {
            $times[] = $display_time;
          }
          elseif ($display_date < $start_date) {
            $times[] = $this->t('@time (previous day)', ['@time' => $display_time]);
          }
          else {
            $times[] = $this->t('@time (following day)', ['@time' => $display_time]);
          }
        }
        // @todo Maybe a twig template would be cleaner for this?
        $elements[$delta] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => ['class' => ['multi-tz']],
          // @todo Should this be configurable, too?
          '#value' => $this->t('This online event begins at @times.', ['@times' => implode(', ', $times)]),
        ];
      }
    }

    return $elements;
  }

}

<?php

/**
 * @file
 * The Breema Event manager class.
 */

namespace Drupal\breema;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class BreemaEventMgr {

  /**
   * Finds all place nodes used by upcoming or in-progress events.
   *
   * @return array
   *   Array of node IDs of places that have active / upcoming events, or an
   *   empty array if there are no such places.
   */
  public function getActivePlaces() {
    $active_places = [];
    $now = new DrupalDateTime('now');
    $now->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    $query = \Drupal::entityQuery('node');
    $query
      ->condition('status', 1)
      ->condition('type', 'event')
      ->condition('field_date_time.end_value', $now->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=');
    $results = $query->execute();
    if (!empty($results)) {
      $events = Node::loadMultiple($results);
    }
    if (!empty($events)) {
      foreach ($events as $event) {
        $location = $event->get('field_location')->getValue();
        $location_id = $location[0]['target_id'];
        $active_places[$location_id] = TRUE;
      }
    }
    return $active_places;
  }


  /**
   * Update field_has_active_event for any place node out of sync with a list.
   *
   * @param array $active_places
   *   Array of node IDs (nids) of place nodes that have active events.
   *
   */
  public function updateActivePlaces(array $active_places = NULL) {
    if (!isset($active_places)) {
      $active_places = $this->getActivePlaces();
    }
    if (empty($active_places)) {
      $stale_place_ids = \Drupal::entityQuery('node')
        ->condition('type', 'place')
        ->condition('field_has_active_event', 1, '=')
        ->execute();
    }
    else {
      $stale_place_ids = \Drupal::entityQuery('node')
        ->condition('type', 'place')
        ->condition('field_has_active_event', 1, '=')
        ->condition('nid', array_keys($active_places), 'NOT IN')
        ->execute();
      $now_active_place_ids = \Drupal::entityQuery('node')
        ->condition('type', 'place')
        ->condition('field_has_active_event', 0, '=')
        ->condition('nid', array_keys($active_places), 'IN')
        ->execute();
    }
    if (!empty($stale_place_ids)) {
      $stale_places = Node::loadMultiple($stale_place_ids);
      if (!empty($stale_places)) {
        foreach ($stale_places as $stale_place) {
          $stale_place->setNewRevision(FALSE);
          $stale_place->set('field_has_active_event', 0);
          $stale_place->save();
        }
      }
    }
    if (!empty($now_active_place_ids)) {
      $now_active_places = Node::loadMultiple($now_active_place_ids);
      if (!empty($now_active_places)) {
        foreach ($now_active_places as $now_active_place) {
          $now_active_place->setNewRevision(FALSE);
          $now_active_place->set('field_has_active_event', 1);
          $now_active_place->save();
        }
      }
    }
  }
}

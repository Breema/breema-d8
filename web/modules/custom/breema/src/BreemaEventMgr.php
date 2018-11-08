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
   *   Nested array, indexed by node IDs of places that have active / upcoming
   *   events, where the value is a sub-array of the node IDs of the next 3
   *   events at that location, or an empty array if there are no such places.
   */
  public function getActivePlaces() {
    $active_places = [];
    $now = new DrupalDateTime('now');
    $now->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    $query = \Drupal::entityQuery('node');
    $query
      ->condition('status', 1)
      ->condition('type', 'event')
      ->condition('field_date_time.end_value', $now->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=')
      ->sort('sticky', 'DESC')
      ->sort('field_date_time.value');
    $results = $query->execute();
    if (!empty($results)) {
      $events = Node::loadMultiple($results);
    }
    if (!empty($events)) {
      foreach ($events as $event) {
        $location = $event->get('field_location')->getValue();
        $location_id = $location[0]['target_id'];
        if (empty($active_places[$location_id]) || count($active_places[$location_id]) < 3) {
          $active_places[$location_id][] = $event->id();
        }
      }
    }
    return $active_places;
  }


  /**
   * Update field_has_active_event for any place node out of sync with a list.
   *
   * @param array $active_places
   *   Nested array of places with active events. Top-level index is node IDs
   *   (nids) of place nodes, and the subarrays are the next 3 event node IDs at
   *   that location.
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
    }
    if (!empty($stale_place_ids)) {
      $stale_places = Node::loadMultiple($stale_place_ids);
      if (!empty($stale_places)) {
        foreach ($stale_places as $stale_place) {
          $stale_place->setNewRevision(FALSE);
          $stale_place->set('field_has_active_event', 0);
          $stale_place->set('field_upcoming_events', []);
          $stale_place->save();
        }
      }
    }
    if (!empty($active_places)) {
      $active_place_nodes = Node::loadMultiple(array_keys($active_places));
      if (!empty($active_place_nodes)) {
        foreach ($active_place_nodes as $nid => $active_place) {
          $has_active = $active_place->get('field_has_active_event')->value;
          $upcoming = $active_place->get('field_upcoming_events')->getValue();
          $upcoming_nids = [];
          foreach ($upcoming as $upcoming_event) {
            $upcoming_nids[] = $upcoming_event['target_id'];
          }
          if (!$has_active || $upcoming_nids != $active_places[$nid]) {
            $active_place->setNewRevision(FALSE);
            $active_place->set('field_has_active_event', 1);
            $active_place->set('field_upcoming_events', $active_places[$nid]);
            $active_place->save();
          }
        }
      }
    }
  }
}

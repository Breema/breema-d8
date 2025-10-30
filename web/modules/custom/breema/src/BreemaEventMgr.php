<?php

/**
 * @file
 * The Breema Event manager class.
 */

namespace Drupal\breema;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class BreemaEventMgr {

  /**
   * Nested array of place nodes used by upcoming events.
   *
   * Top-level array is indexed by node IDs of places, values are a sub-array of
   * the node IDs of the next 3 events (at most) at that location.
   */
  protected $activePlaces;

  /**
   * Nested array of directory_entry nodes of instructors of upcoming events.
   *
   * Top-level array is indexed by node IDs of directory entries, values are a
   * sub-array of the node IDs of the next 3 events (at most) taught by the
   * instructor.
   */
  protected $activeInstructors;

  /**
   * Finds all place nodes and directory entries used by upcoming events.
   */
  public function __construct() {
    $this->activePlaces = [];
    $this->activeInstructors = [];

    $now = new DrupalDateTime('now');
    $now->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $results = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('type', 'event')
      ->condition('field_date_time.end_value', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>=')
      ->sort('sticky', 'DESC')
      ->sort('field_date_time.value')
      ->execute();

    if (!empty($results)) {
      $events = Node::loadMultiple($results);
    }
    if (!empty($events)) {
      foreach ($events as $event) {
        $location = $event->get('field_location')->getValue();
        $location_id = $location[0]['target_id'];
        if (empty($this->activePlaces[$location_id]) || count($this->activePlaces[$location_id]) < 3) {
          $this->activePlaces[$location_id][] = $event->id();
        }
        $instructors = $event->get('field_instructors')->referencedEntities();
        if (!empty($instructors)) {
          foreach ($instructors as $instructor) {
            $directory_entry = $instructor->get('field_directory_entry')->getValue();
            if (!empty($directory_entry)) {
              $nid = $directory_entry[0]['target_id'];
              if (empty($this->activeInstructors[$nid]) || count($this->activeInstructors[$nid]) < 3) {
                $this->activeInstructors[$nid][] = $event->id();
              }
            }
          }
        }
      }
    }
  }

  /**
   * Update all event-related nodes that aren't in sync with reality.
   */
  public function update() {
    foreach (['place', 'directory_entry'] as $node_type) {
      $this->updateData($node_type);
    }
    $this->clearEnhancementsFromPastEvents();
  }

  /**
   * Update nodes of a given type that aren't in sync with reality.
   *
   * @param string $node_type
   *   The node type (bundle) to update. Should be either 'place' or
   *   'directory_entry'
   */
  public function updateData($node_type) {
    $property_names = [
      'place' => 'activePlaces',
      'directory_entry' => 'activeInstructors',
    ];
    if (empty($property_names[$node_type])) {
      /// @todo Throw an exception or something?
      return;
    }
    $property_name = $property_names[$node_type];

    if (empty($this->$property_name)) {
      $stale_ids = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('type', $node_type)
        ->condition('field_has_active_event', 1, '=')
        ->execute();
    }
    else {
      $stale_ids = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('type', $node_type)
        ->condition('field_has_active_event', 1, '=')
        ->condition('nid', array_keys($this->$property_name), 'NOT IN')
        ->execute();
    }
    if (!empty($stale_ids)) {
      $stale_nodes = Node::loadMultiple($stale_ids);
      if (!empty($stale_nodes)) {
        foreach ($stale_nodes as $stale_node) {
          $stale_node->setNewRevision(FALSE);
          $stale_node->set('field_has_active_event', 0);
          $stale_node->set('field_upcoming_events', []);
          $stale_node->save();
        }
      }
    }
    if (!empty($this->$property_name)) {
      $active_nodes = Node::loadMultiple(array_keys($this->$property_name));
      if (!empty($active_nodes)) {
        foreach ($active_nodes as $nid => $active_node) {
          $has_active = $active_node->get('field_has_active_event')->value;
          $upcoming = $active_node->get('field_upcoming_events')->getValue();
          $upcoming_nids = [];
          foreach ($upcoming as $upcoming_event) {
            $upcoming_nids[] = $upcoming_event['target_id'];
          }
          if (!$has_active || $upcoming_nids != $this->$property_name[$nid]) {
            $active_node->setNewRevision(FALSE);
            $active_node->set('field_has_active_event', 1);
            $active_node->set('field_upcoming_events', $this->$property_name[$nid]);
            $active_node->save();
          }
        }
      }
    }
  }

  /**
   * Clears the 'sticky' and/or 'promote' bits from events in the past.
   */
  public function clearEnhancementsFromPastEvents() {
    // First, clear everything that's both promoted and sticky.
    $this->clearEnhancementFromPastEvents(TRUE, TRUE);
    // Then, clear only sticky.
    $this->clearEnhancementFromPastEvents(TRUE, FALSE);
    // Finally, clear only promoted.
    $this->clearEnhancementFromPastEvents(FALSE, TRUE);
  }

  /**
   * Clears any enhancements (sticky and promote) from events in the past.
   *
   * @param bool $use_sticky
   *   TRUE if the query should include events that are sticky.
   * @param bool $use_promote
   *   TRUE if the query should include events that are promoted.
   */
  protected function clearEnhancementFromPastEvents(bool $use_sticky, bool $use_promote) {
    $now = new DrupalDateTime('now');
    $now->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('type', 'event')
      ->condition('field_date_time.end_value', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '<');
    if ($use_sticky) {
      $query->condition('sticky', 1);
      $revision_log = t('Cron: Removing sticky flag from event that is now over.');
    }
    if ($use_promote) {
      $query->condition('promote', 1);
      $revision_log = $revision_log ?
        t('Cron: Removing sticky and promoted flags from event that is now over.')
        : t('Cron: Removing promoted flag from event that is now over.');
    }
    $results = $query->execute();
    if (!empty($results)) {
      $events = Node::loadMultiple($results);
    }
    if (!empty($events)) {
      foreach ($events as $event) {
        $event->setNewRevision(TRUE);
        $event->revision_log = $revision_log;
        $event->revision_uid = 1;
        $event->revision_timestamp = $now->format('U');
        // Doesn't hurt to always clear both flags for past events.
        $event->sticky = 0;
        $event->promote = 0;
        $event->save();
      }
    }
  }
}

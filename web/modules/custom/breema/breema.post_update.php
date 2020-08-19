<?php

/**
 * @file
 * Post update functions for Breema.
 */

use Drupal\group\Entity\Group;

/**
 * Converts 'Dear student' listening group into type 'audio'.
 */
function breema_post_update_convert_dear_student_group() {
  // For the purposes of this update, we can hard-code this.
  $group = Group::load(5);
  // This approach work because of patch [#2923755-7] Ability to change group
  // type after group creation: update all GroupContent types automatically.
  // @see https://www.drupal.org/project/group/issues/2923755
  $group->type = 'audio';
  $group->save();
}

/**
 * Converts the remaining listening groups into type 'audio'.
 */
function __breema_post_update_convert_audio_groups() {
  // For the purposes of this update, we can hard-code the listening groups.
  for ($i = 6; $i <= 7; $i++) {
    $group = Group::load($i);
    // This approach work because of patch [#2923755-7] Ability to change group
    // type after group creation: update all GroupContent types automatically.
    // @see https://www.drupal.org/project/group/issues/2923755
    $group->type = 'audio';
    $group->save();
  }
}

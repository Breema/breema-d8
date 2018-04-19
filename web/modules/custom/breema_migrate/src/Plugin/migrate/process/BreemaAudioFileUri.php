<?php

namespace Drupal\breema_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process an audio filename and timestamp into a D8 compatible URL.
 *
 * @MigrateProcessPlugin(
 *   id = "breema_audio_file_uri"
 * )
 */
class BreemaAudioFileUri extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($filename, $timestamp) = $value;
    $parts[] = date('Y', $timestamp);
    $parts[] = date('m', $timestamp);
    $parts[] = str_replace(' ', '-', strtolower($filename));
    return 'public://audio/' . implode('/', $parts);
  }

}

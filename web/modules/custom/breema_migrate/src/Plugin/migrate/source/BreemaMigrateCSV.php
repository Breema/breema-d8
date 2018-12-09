<?php

namespace Drupal\breema_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Source plugin for CSV migrations.
 *
 * @MigrateSource(
 *   id = "breema_migrate_csv"
 * )
 */
class BreemaMigrateCSV extends CSV {
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    // Override the path to point to the local module directory.
    $this->configuration['path'] = drupal_get_path('module', 'breema_migrate') . '/csv/' . $this->configuration['path'];
  }
}

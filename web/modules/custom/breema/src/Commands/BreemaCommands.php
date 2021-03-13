<?php

namespace Drupal\breema\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;

/**
 * Custom Breema Drush commands.
 */
class BreemaCommands extends DrushCommands {

  /**
   * Prune files from StreamGuys that have expired.
   *
   * @option key-path
   *   The full path to the RSA private key to use for authentication. Defaults
   *   to '~/.ssh/id_rsa'.
   *
   * @usage breema-purgeStreamGuys
   *   Removes stale files from StreamGuys.
   *
   * @command breema:purgeStreamGuys
   * @aliases bpsg
   * @bootstrap max
   */
  public function purgeStreamGuys($options = ['key-path' => '~/.ssh/id_rsa']) {
    $files = [];
    $now = new DrupalDateTime('now', new \DateTimezone('America/Los_Angeles'));
    $today = $now->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);

    // Find all the private audio nodes that have expired but that have
    // available stream links.
    $query = \Drupal::entityQuery('node');
    $query
      ->accessCheck(FALSE)
      ->condition('type', 'private_audio_stream')
      ->condition('field_available_dates.end_value', $today, '<')
      ->condition('field_stream_status', 1, '=');
    $results = $query->execute();
    if (!empty($results)) {
      $nodes = Node::loadMultiple($results);
    }
    if (!empty($nodes)) {
      foreach ($nodes as $nid =>$node) {
        $info = $node->get('field_stream_url')->getValue();
        if (!empty($info[0]['uri'])) {
          if ($filename = $this->getFilenameFromUrl($info[0]['uri'])) {
            $files[$nid] = $filename;
          }
        }
      }
    }

    if (empty($files)) {
      $this->logger()->warning(dt('No stale files to remove.'));
      return;
    }

    $rsa_key = $this->getKeyFromPath($options['key-path']);
    if (empty($rsa_key)) {
      $this->logger()->error(dt('Cannot find RSA private key. Use --key-file option to define it.'));
      return;
    }
    $key = new RSA();
    if (!$key->loadKey($rsa_key)) {
      $this->logger()->error(dt('Cannot load RSA private key from @file', ['@file' => $options['key-path']]));
      return;
    }

    $sftp = new SFTP('breema.streamguys1.com');
    if (!$sftp->login('c2918', $key)) {
      $this->logger()->error(dt('Failed to login.'));
      // @todo Print error messages?
      return;
    }
    $sftp->chdir('content');
    $removed = [];
    foreach ($files as $nid => $file) {
      if ($sftp->delete($file, FALSE)) {
        $removed[$nid] = $file;
        $this->logger()->success(dt('Removed: @file', ['@file' => $file]));
      }
      else {
        $this->logger()->error(dt('Failed to remove: @file', ['@file' => $file]));
        // @todo: If we failed to remove, it might already be gone. Maybe we
        // can test the URL and if the status is 404, we can safely consider
        // it removed?
      }
    }
    if ($removed) {
      $u_now = $now->format('U');
      // We want to log these to the dblog, not just CLI output.
      $db_logger = \Drupal::service('logger.factory')->get('breema_streamguys');
      foreach ($removed as $nid => $file) {
        $nodes[$nid]->setNewRevision(TRUE);
        $nodes[$nid]->field_stream_status = 0;
        $nodes[$nid]->revision_log = 'StreamGuys purge: Stream file removed.';
        $nodes[$nid]->revision_uid = 1;
        $nodes[$nid]->revision_timestamp = $u_now;
        $nodes[$nid]->save();
        $context = [
          '%file' => $file,
          '%title' => $nodes[$nid]->label(),
          'link' => $nodes[$nid]->toLink(dt('View'))->toString(),
        ];
        $db_logger->info('Removed %file from StreamGuys since %title has expired.', $context);
      }
      $this->logger()->success(dt('Stale files removed, stream status values updated.'));
    }
    else {
      $this->logger()->error(dt('Unable to remove any files.'));
    }
  }

  /**
   * Gets the StreamGuys filename for a given listening audio stream URL.
   *
   * @param string $url
   *   The link to the audio on StreamGuys.
   *
   * @return string
   *   The filename on StreamGuys that link corresponds to.
   */
  protected function getFilenameFromUrl(string $url) {
    // https://breema.streamguys1.com/vod/Work_Meeting_082304.m4a/playlist.m3u8
    $matches = [];
    if (preg_match('|^http(s?)://breema\.streamguys(1?)\.com/vod/(\w+)\.m4a/playlist\.m3u8$|', $url, $matches)) {
      return $matches[3] . '.m4a';
    }
    return '';
  }

  /**
   * Reads the private key from the specified file.
   *
   * @param string $key_path
   *   The full path to the private key file.
   *
   * @return string|bool
   *   The key contents if available, otherwise FALSE.
   */
  protected function getKeyFromPath($key_path) {
    if (file_exists($key_path)) {
      return file_get_contents($key_path);
    }
    $this->logger()->error(dt('Failed to read key file @file', ['@file' => $key_path]));
    return FALSE;
  }

}

<?php

namespace Drupal\breema;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Publishes and unpublishes content based on date fields on each entity.
 *
 * Currently supports private audio pages for listening groups, and Essence of
 * Breema (EoB) pages for the main public site.
 */
class PublishScheduler {

  use MessengerTrait;

  /**
   * The current time (in UTC).
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $now;

  /**
   * The logger for the 'breema_scheduler' log channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger = NULL;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   */
  protected $accountSwitcher = NULL;


  /**
   * The user with full access to view and edit unpublished group nodes.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser = NULL;

  /**
   * Constructs a PublishScheduler object.
   */
  public function __construct() {
    // It will be easier to do all the date math with 'now' based in Oakland.
    $this->now = new DrupalDateTime('now', new \DateTimezone('America/Los_Angeles'));
    $this->accountSwitcher = \Drupal::service('account_switcher');
    $this->adminUser = User::load(1);
  }

  /**
   * Enforces the scheduled publication of content based on their date fields.
   *
   * This handles both publishing content that should now be available, and
   * unpublishing content that has expired.
   */
  public function enforceSchedule() {
    // Never do anything before 5am PDT.
    if ($this->now->format('G') < 5) {
      return;
    }

    // This will generally run as an anonymous user via hook_cron, so we need to
    // switch to a super group admin to be able to see the unpublished nodes
    // across all groups, publish them, etc.
    $this->accountSwitcher->switchTo($this->adminUser);
    $this->publishAvailablePrivateAudio();
    $this->unpublishExpiredPrivateAudio();
    $this->publishEssenceOfBreema();
    $this->accountSwitcher->switchBack();
  }

  /**
   * Publishes 'Private audio stream' content that should now be visible.
   */
  protected function publishAvailablePrivateAudio() {
    $now = $this->now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    // Find all currently unpublished private audio nodes that have entered the
    // window of time when they should be available.
    $query = \Drupal::entityQuery('node');
    $query
      ->condition('status', 0)
      ->condition('type', 'private_audio_stream')
      ->condition('field_available_dates.value', $now, '<=')
      ->condition('field_available_dates.end_value', $now, '>');
    $results = $query->execute();
    if (!empty($results)) {
      $nodes = Node::loadMultiple($results);
    }
    if (!empty($nodes)) {
      $u_now = $this->now->format('U');
      foreach ($nodes as $node) {
        $node->setNewRevision(TRUE);
        $node->revision_log = 'PublishScheduler: Publish audio that is now available.';
        $node->revision_uid = 1;
        $node->revision_timestamp = $u_now;
        // Tell group_notify to trigger notifications.
        $node->group_notify_all = TRUE;
        $node->setPublished();
        $node->save();
        $this->logAction($node, 'Published');
      }
    }
  }

  /**
   * Unpublishes 'Private audio stream' content that has expired.
   */
  protected function unpublishExpiredPrivateAudio() {
    $now = $this->now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    // Find all currently published private audio nodes that have expired.
    $query = \Drupal::entityQuery('node');
    $query
      ->condition('status', 1)
      ->condition('type', 'private_audio_stream')
      ->condition('field_available_dates.end_value', $now, '<');
    $results = $query->execute();
    if (!empty($results)) {
      $nodes = Node::loadMultiple($results);
    }
    if (!empty($nodes)) {
      $u_now = $this->now->format('U');
      foreach ($nodes as $node) {
        $node->setNewRevision(TRUE);
        $node->revision_log = 'PublishScheduler: Unpublish audio that has now expired.';
        $node->revision_uid = 1;
        $node->revision_timestamp = $u_now;
        $node->setUnpublished();
        $node->save();
        $this->logAction($node, 'Unpublished');
      }
    }
  }

  /**
   * Publishes Essence of Breema posts that should now be visible.
   */
  protected function publishEssenceOfBreema() {
    // @todo
  }

  /**
   * Logs a message about a publishing action on a specific node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to log a message about.
   * @param string $action
   *   The publishing action (either 'Published' or 'Unpublished').
   */
  protected function logAction(NodeInterface $node, $action) {
    // If we haven't yet, initialize our logger channel.
    if (empty($this->logger)) {
      $this->logger = \Drupal::service('logger.factory')->get('breema_scheduler');
    }
    $context = [
      '@action' => $action,
      '%label' => $node->label(),
      '@id' => $node->id(),
      'link' => $node->toLink(t('View'))->toString(),
    ];
    if ($node->bundle() === 'private_audio_stream') {
      $message = '@action %label (id: @id) [available: @start - @end]';
      $context['@start'] = $node->get('field_available_dates')->value;
      $context['@end'] = $node->get('field_available_dates')->end_value;
    }
    elseif ($node->bundle() === 'essence') {
      $message = 'Published %label (id: @id) [available: @start]';
      // @todo
      // $context['@start'] = $node->get('field_available_dates')->value;
    }
    $this->logger->info($message, $context);
  }

}

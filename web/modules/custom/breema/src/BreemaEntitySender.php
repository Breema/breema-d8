<?php

namespace Drupal\breema;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for sending entities.
 */
class BreemaEntitySender implements BreemaEntitySenderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The mail service.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailer;

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * Constructs a ForwardEntitySender object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Mail\MailManager $mailer
   *   The mail service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, MailManager $mailer, RendererInterface $renderer, Token $token_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->mailer = $mailer;
    $this->renderer = $renderer;
    $this->tokenService = $token_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('breema_mail'),
      $container->get('mailer'),
      $container->get('renderer'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sendEntity(EntityInterface $entity, $key, $to, $header = [], $footer = []) {
    // Build the entity content.
    $view_mode = '';
    $elements = [];
    if ($entity->access('view')) {
      $langcode = $entity->language()->getId();
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      foreach (['forward', 'teaser', 'full', 'default'] as $view_mode) {
        if ($this->isValidDisplay($entity, $view_mode)) {
          $elements = $view_builder->view($entity, $view_mode, $langcode);
        }
        if (!empty($elements)) {
          break;
        }
      }
    }
    $body = [
      '#theme' => 'breema_send',
      '#recipient' => $to,
      '#header' => $header,
      '#entity' => $entity,
      '#content' => $this->renderer->render($elements),
      '#view_mode' => $view_mode,
      '#footer' => $footer,
    ];

    // Render the message.
    $params['body'] = $this->renderer->render($body);
    $params['entity'] = $entity;

    // @todo DI
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $result = $this->mailer->mail('breema', $key, $to, $langcode, $params, NULL, TRUE);
    $placeholders = [
      '@email' => $to,
      '@key' => $key,
      '@id' => $entity->id(),
      '@title' => $entity->label(),
    ];
    if ($result['result'] !== true) {
      $message = t('There was a problem sending an e-mail notification to @email for @key about @title (id: @id).', $placeholders);
      $this->logger->error($message);
      return;
    }
    $message = t('An e-mail notification has been sent to @email for @key about @title (id: @id).', $placeholders);
    $this->logger->notice($message);
  }

  /**
   * Determines if a given display is valid for an entity.
   */
  private function isValidDisplay(EntityInterface $entity, $view_mode) {
    // Assume the display is valid.
    $valid = TRUE;

    // Build display name.
    if ($entity->getEntityType()->hasKey('bundle')) {
      // Bundled entity types, e.g. node.
      $display_name = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $view_mode;
    }
    else {
      // Entity types without bundles, e.g. user.
      $entity_type_id = $entity->getEntityTypeId();
      $display_name = "$entity_type_id.$entity_type_id.$view_mode";
    }

    // Attempt load.
    $display = $this->entityTypeManager->getStorage('entity_view_display')->load($display_name);
    if ($display) {
      // If the display loads, it exists in configuration, and status can be
      // checked.
      $valid = FALSE;
      if ($display->status()) {
        $valid = TRUE;
      }
    }

    return $valid;
  }

}

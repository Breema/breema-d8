<?php

namespace Drupal\breema;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for sending an entity via forward module.
 */
interface BreemaEntitySenderInterface {

  /**
   * Sends a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to send.
   * @param string $key
   *   The mail key to use when sending this entity.
   * @param string $to
   *   The e-mail address to send the message to.
   */
  public function sendEntity(EntityInterface $entity, $key, $to);

}

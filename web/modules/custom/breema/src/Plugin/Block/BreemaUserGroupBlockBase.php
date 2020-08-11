<?php

namespace Drupal\breema\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\group\Entity\GroupContent;

/**
 * A base class for custom blocks that deal with a user's groups.
 */
abstract class BreemaUserGroupBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new group-related block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Gets all the groups the current user belongs to.
   *
   * @return \Drupal\group\Entity\GroupInterface[][]
   *   Nested array of groups the current user is a member of. The top level
   *   keys are group type/bundle ('private' or 'audio'). Those arrays are then
   *   keyed by group ID and the values are the loaded Group entities.
   */
  protected function getCurrentUserGroups() {
    $groups = [];
    $user_entity = User::load($this->currentUser->id());
    // Load all groups the current user belongs to.
    $groups = GroupContent::loadByEntity($user_entity);
    foreach ($groups as $user_group) {
      $group = $user_group->getGroup();
      $groups[$group->bundle()][$group->id()] = $group;
    }
    return $groups;
  }

}

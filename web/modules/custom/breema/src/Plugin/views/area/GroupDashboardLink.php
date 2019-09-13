<?php

namespace Drupal\breema\Plugin\views\area;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an area for a link to the current user's group dashboard.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("breema_group_dashboard_link")
 */
class GroupDashboardLink extends AreaPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

 /**
   * Constructs a new GroupDashboardLink.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return [
        '#type' => 'link',
        '#title' => $this->t('View all groups'),
        '#url' => Url::fromRoute('entity.user.breema_group_dashboard', ['user' => $this->currentUser->id()]),
        '#prefix' => '<div class="action action--secondary">',
        '#suffix' => '</div>',
      ];
    }
    return [];
  }

}

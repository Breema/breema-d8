<?php

namespace Drupal\breema\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

use Drupal\node\Entity\Node;

use Symfony\Component\DependencyInjection\ContainerInterface;

class BreemaNodeCloneForm extends ConfirmFormBase {
  /**
   * The entity ready to clone.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $parent;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new Add Schedule Event form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(RouteMatchInterface $route_match, MessengerInterface $messenger) {
    $this->parent = Node::Load($route_match->getParameter('node'));
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'breema_node_clone_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to clone %label?', ['%label' => $this->parent->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // Hide the default message.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clone');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($this->isScheduleEvent($this->parent)) {
      $form['subtitle'] = [
        '#title' => t('Subtitle'),
        '#type' => 'textfield',
        '#default_value' => $this->parent->get('field_subtitle')->value . t(' - copy'),
        '#required' => TRUE,
      ];
    }
    else {
      $form['title'] = [
        '#title' => t('Title'),
        '#type' => 'textfield',
        '#default_value' => $this->parent->label() . t(' - copy'),
        '#required' => TRUE,
      ];
    }

    /**
     * @todo: Let editor decide which fields to clone vs. reset to default?
     * Iterate through the whole entity form, and for every element, add a
     * drop-down or radios to select "clone" vs. "reset to default"?
     */
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Submit callback.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $child = $this->cloneEntity($this->parent);
    if ($this->isScheduleEvent($this->parent)) {
      $child->set('field_subtitle', $form_values['subtitle']);
      $parent_events = $this->parent->get('field_parent_event')->referencedEntities();
      $parent_event = array_pop($parent_events);
      $form_values['title'] = $form_values['subtitle'] . ' - ' . $parent_event->label();
    }
    $child->setTitle($form_values['title']);
    $child->save();
    $this->messenger->addStatus($this->t('Cloned %label.', ['%label' => $this->parent->label()]));
    $form_state->setRedirect('entity.node.edit_form', ['node' => $child->id()]);
  }

  /**
   * Clone an entity and reset various fields to proper defaults.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to clone.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The cloned entity, with various fields reset, but *not* yet saved.
   */
  protected function cloneEntity(EntityInterface $entity) {
    // Is there a more slick way to get this?
    $current_user = \Drupal::currentUser();
    $child = $entity->createDuplicate();
    $child->set('uid', $current_user->id());
    $child->set('created', '');
    $child->set('changed', '');
    $child->set('sticky', 0);
    $child->set('status', 0);
    return $child;
  }

  /**
   * Test if an entity is a schedule (child) event node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to test.
   *
   * @param return bool
   */
  protected function isScheduleEvent(EntityInterface $entity) {
    return isset($entity)
      && $entity->getEntityTypeId() == 'node'
      && $entity->bundle() == 'event'
      && !empty($entity->get('field_parent_event')->getValue())
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.node.canonical', ['node' => $this->parent->id()]);
  }

}

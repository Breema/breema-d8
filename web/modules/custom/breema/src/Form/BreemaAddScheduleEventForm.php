<?php

namespace Drupal\breema\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

use Drupal\node\Entity\Node;

use Symfony\Component\DependencyInjection\ContainerInterface;

class BreemaAddScheduleEventForm extends ConfirmFormBase {
  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
    return 'breema_add_schedule_event_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to add a schedule event under %label?', ['%label' => $this->parent->label()]);
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
    return $this->t('Add schedule event');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['title'] = [
      '#title' => t('Title'),
      '#type' => 'textfield',
      '#default_value' => $this->parent->label() . t(' - details'),
    ];
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
    // Is there a more slick way to get this?
    $current_user = \Drupal::currentUser();
    $form_values = $form_state->getValues();
    $child = $this->parent->createDuplicate();
    $child->setTitle($form_values['title']);
    $child->set('field_parent_event', $this->parent->id());
    $child->set('uid', $current_user->id());
    $child->set('created', '');
    $child->set('changed', '');
    $child->set('sticky', 0);
    $child->set('status', 0);
    $child->save();
    $this->messenger->addStatus($this->t('Added schedule event under %label.', ['%label' => $this->parent->label()]));
    $form_state->setRedirect('entity.node.edit_form', ['node' => $child->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.node.canonical', ['node' => $this->parent->id()]);
  }

}

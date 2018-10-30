<?php

namespace Drupal\breema\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

use Drupal\node\Entity\Node;

use Symfony\Component\DependencyInjection\ContainerInterface;

class BreemaAddScheduleEventForm extends BreemaNodeCloneForm {
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
    return $this->t('Only do this if you want to create sub-events with different information (instructors, subtitle, description, etc).');
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
      '#prefix' => '<h3>',
      '#markup' => $this->parent->label(),
      '#suffix' => '</h3>',
    ];
    $form['subtitle'] = [
      '#title' => t('Subtitle'),
      '#type' => 'textfield',
      '#required' => TRUE,
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
    $form_values = $form_state->getValues();
    $child = $this->cloneEntity($this->parent);
    $child->set('field_parent_event', $this->parent->id());
    $child->set('field_subtitle', $form_values['subtitle']);
    $child->setTitle($form_values['subtitle'] . ' - ' . $this->parent->label());
    $child->save();
    $this->messenger->addStatus($this->t('Added schedule event under %label.', ['%label' => $this->parent->label()]));
    $form_state->setRedirect('entity.node.edit_form', ['node' => $child->id()]);
  }

}

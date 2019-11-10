<?php

namespace Drupal\breema\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Metatag display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "breema_metatag_display_extender",
 *   title = @Translation("Metatag display extender"),
 *   help = @Translation("Metatag settings for this view."),
 *   no_ui = FALSE
 * )
 */
class BreemaMetatagDisplayExtender extends DisplayExtenderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') === 'metatags') {
      $form['#title'] .= t('The meta tags for this display');
      $metatags = $this->getMetatags();
      // Build/inject the Metatag form.
      $form['metatags'] = [
        'description' => [
          '#type' => 'textarea',
          '#title' => $this->t('Meta description'),
          '#default_value' => $metatags['description'],
        ],
        'image' => [
          '#type' => 'number',
          '#min' => 1,
          '#size' => 5,
          '#title' => $this->t('Social media image media ID'),
          '#default_value' => $metatags['image'],
        ]
      ];
      if (!empty($metatags['image'])) {
        $media_entity = Media::load($metatags['image']);
        $form['metatags']['image']['#description'] = $this->t('%title', ['%title' => $media_entity->label()]);
        $media_files = $media_entity->get('field_media_image')->referencedEntities();
        if (!empty($media_files[0])) {
          $form['metatags']['image_thumbnail'] = [
            '#theme' => 'image_style',
            '#style_name' => '224x126_smaller',
            '#uri' => $media_files[0]->getFileUri(),
            '#weight' => 10,
          ];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $image_id = $form_state->getValue('image');
    if (!empty($image_id)) {
      $media_entity = Media::load($image_id);
      if (empty($media_entity)) {
        $form_state->setError($form['metatags']['image'], $this->t('No media exists with ID: %id', ['%id' => $image_id]));
      }
      elseif ($media_entity->bundle() !== 'image') {
        $form_state->setError($form['metatags']['image'], $this->t('Media %title (ID: %id) is not an image.', ['%title' => $media_entity->label(), '%id' => $image_id]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') === 'metatags') {
      // Process submitted metatag values and remove empty tags.
      $tag_values = [];
      $metatags = $form_state->cleanValues()->getValues();
      foreach ($metatags as $tag_id => $tag_value) {
        if (!empty($tag_value)) {
          $tag_values[$tag_id] = $tag_value;
        }
      }
      $this->options['metatags'] = $metatags;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['metatags'] = [
      'title' => t('Meta tags'),
      'column' => 'second',
    ];
    $summary = t('Using defaults');
    $metatags = $this->getMetatags();
    if (!empty($metatags)) {
      $parts = [];
      foreach ($metatags as $key => $value) {
        if (!empty($value)) {
          $parts[] = $key;
        }
      }
      if (!empty($parts)) {
        $summary = t('Overridden: %parts', ['%parts' => breema_fancy_implode($parts)]);
      }
    }
    $options['metatags'] = [
      'category' => 'metatags',
      'title' => t('Meta tags'),
      'value' => $summary,
    ];
  }

  /**
   * Get the metatag configuration for this display.
   *
   * @return array
   *   The meta tag values.
   */
  public function getMetatags() {
    $metatags = [];
    if (!empty($this->options['metatags'])) {
      $metatags = $this->options['metatags'];
    }
    return $metatags;
  }

}

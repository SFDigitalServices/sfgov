<?php

namespace Drupal\sfgov_admin\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_reference_revisions paragraphs' widget.
 *
 * @FieldWidget(
 *   id = "sfgov_paragraphs",
 *   label = @Translation("Paragraphs EXPERIMENTAL (SF.gov)"),
 *   description = @Translation("An custom experimental paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class SfgovParagraphsWidget extends ParagraphsWidget {

  /**
   * The bundle info manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * SfgovParagraphsWidget constructor.
   *
   * @param $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle info manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeBundleInfoInterface $bundle_info) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getSettingOptions($setting_name) {
    $options = parent::getSettingOptions($setting_name);

    // Add our custom dropbutton options.
    if ($setting_name == 'add_mode' && isset($options)) {
      $options['dropdown_custom'] = $this->t('Add drop button');
    }

    return isset($options) ? $options : NULL;
  }


  /**
   * {@inheritdoc}
   */
  protected function buildAddActions() {
    if ($this->getSetting('add_mode') !== 'dropdown_custom') {
      return parent::buildAddActions();
    }

    if (count($this->getAccessibleOptions()) === 0) {
      if (count($this->getAllowedTypes()) === 0) {
        $add_more_elements['icons'] = $this->createMessage($this->t('You are not allowed to add any of the @title types.', ['@title' => $this->getSetting('title')]));
      }
      else {
        $add_more_elements['icons'] = $this->createMessage($this->t('You did not add any @title types yet.', ['@title' => $this->getSetting('title')]));
      }

      return $add_more_elements;
    }

    return $this->buildButtonsAddMode();
  }


  /**
   * {@inheritdoc}
   */
  protected function buildButtonsAddMode() {
    // Build the button list.
    $elements = parent::buildButtonsAddMode();

    // Check the add mode.
    if ($this->getSetting('add_mode') == 'dropdown_custom') {
      // Get the add button options.
      $options = $this->getAccessibleOptions();

      // Remove "Add" from all
      foreach ($options as $machine_name => $label) {
        $button_key = 'add_more_button_' . $machine_name;
        $elements[$button_key]['#value'] = $this->t('&nbsp;@type', ['@type' => $label]);
      }
      $elements = $this->buildDropbutton($elements);
      // Adds an "Add" button to the top of the list.
      $elements["operations"]["#links"] = ['add_label' => [
        'title' => [
          '#type' => 'link',
          '#url' => Url::fromRoute('<none>', [], [
            'attributes' => [
              'class' => [
                'sfgov-admin-paragraph-add-link',
              ],
              'alt' => $this->t('Add'),
              'title' => $this->t('Add'),
              'disabled' => 'disabled',
            ],
          ]),
          '#title' => $this->t('Add...'),
          '#attached' => ['library' => ['sfgov_admin/paragraphs']],
          ],
        ]] + $elements["operations"]["#links"];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];
    $item_bundles = $this->bundleInfo->getBundleInfo('paragraph');

    $widget_state = static::getWidgetState($parents, $field_name, $form_state);
    $paragraphs_entity = NULL;
    if ($element['#paragraph_type'] === "data_story_section") {
      $child_bundles = [];
      if (isset($widget_state['paragraphs'][$delta]['entity'])) {
        $paragraphs_entity = $widget_state['paragraphs'][$delta]['entity'];
      }
      elseif (isset($items[$delta]->entity)) {
        $paragraphs_entity = $items[$delta]->entity;
      }
      if ($paragraphs_entity) {
        /** @var \Drupal\paragraphs\ParagraphInterface[] $child_paragraphs */
        $child_paragraphs = $paragraphs_entity->get('field_content')->referencedEntities();
        $content = [];

        if ($heading = $paragraphs_entity->get('field_title')->value) {
          $content[] = $heading;
        }


        foreach ($child_paragraphs as $child_paragraph) {
          $language = $form_state->get('langcode');
          $localized_paragraph = ($child_paragraph->hasTranslation($language)) ? $child_paragraph->getTranslation($form_state->get('langcode')) : $child_paragraph;
          $child_bundles[] = $item_bundles[$child_paragraph->bundle()]['label'];
          $row_content = $child_paragraph->bundle() === "powerbi_embed" ? $item_bundles[$child_paragraph->bundle()]['label'] : '';
          if (!$heading && $child_paragraph->hasField('field_text')) {
            if ($text = $localized_paragraph->get('field_text')->value) {
              $text = strip_tags($text);
              $row_content = $text;
            }
          }

          $content[] = $row_content;
        }

        $element['top']['summary']['fields_info']['#summary']['content'] = array_filter($content);
      }

      if (count($child_bundles)) {
        $element['top']['type']['label']['#markup'] .= ' (' . implode(', ', $child_bundles) . ')';
      }
    }

    return $element;
  }

}

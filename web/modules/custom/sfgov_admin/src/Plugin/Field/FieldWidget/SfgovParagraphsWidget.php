<?php

namespace Drupal\sfgov_admin\Plugin\Field\FieldWidget;

use Drupal\Core\Url;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Plugin implementation of the 'entity_reference_revisions paragraphs' widget.
 *
 * @FieldWidget(
 *   id = "sfgov_paragraphs",
 *   label = @Translation("Paragraphs EXPERIMENTAL (Custom)"),
 *   description = @Translation("An custom experimental paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class SfgovParagraphsWidget extends ParagraphsWidget {

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
          '#title' => $this->t('Add'),
          '#attached' => ['library' => ['sfgov_admin/paragraphs']],
          ],
        ]] + $elements["operations"]["#links"];
      $elements['#suffix'] = $this->t('to %type', ['%type' => $this->fieldDefinition->getLabel()]);
    }

    return $elements;
  }

}

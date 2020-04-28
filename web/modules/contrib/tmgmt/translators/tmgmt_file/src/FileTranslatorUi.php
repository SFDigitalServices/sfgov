<?php

namespace Drupal\tmgmt_file;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;

/**
 * File translator UI.
 */
class FileTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $form['export_format'] = array(
      '#type' => 'radios',
      '#title' => t('Export to'),
      '#options' => \Drupal::service('plugin.manager.tmgmt_file.format')->getLabels(),
      '#default_value' => $translator->getSetting('export_format'),
      '#description' => t('Please select the format you want to export data.'),
    );

    $xliff_states = [
      '#states' => [
        'visible' => [
          ':input[name="settings[export_format]"]' => ['value' => 'xlf'],
        ],
      ],
    ];
    $form['format_configuration']['target'] = [
      '#type' => 'select',
      '#title' => t('Target content'),
      '#options' => [
        'source' => t('Same as source'),
      ],
      '#empty_option' => t('Empty'),
      '#default_value' => $translator->getSetting('format_configuration.target'),
      '#description' => t('Defines what the &lt;target&gt; in the XLIFF file should contain, either empty or the same as the source text.'),
    ] + $xliff_states;

    $form['xliff_cdata'] = [
      '#type' => 'checkbox',
      '#title' => t('XLIFF CDATA'),
      '#description' => t('Check to use CDATA for import/export.'),
      '#default_value' => $translator->getSetting('xliff_cdata'),
    ] + $xliff_states;

    $form['xliff_processing'] = [
      '#type' => 'checkbox',
      '#title' => t('Extended XLIFF processing'),
      '#description' => t('Check to further process content semantics and mask HTML tags instead just escaping it.'),
      '#default_value' => $translator->getSetting('xliff_processing'),
    ] + $xliff_states;

    $form['xliff_message'] = [
      '#type' => 'container',
      '#markup' => t('By selecting CDATA option, XLIFF processing will be ignored.'),
      '#attributes' => [
        'class' => ['messages messages--warning'],
      ],
    ] + $xliff_states;

    $form['allow_override'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow to override the format per job'),
      '#default_value' => $translator->getSetting('allow_override'),
    );

    // Any visible, writeable wrapper can potentially be used for the files
    // directory, including a remote file system that integrates with a CDN.
    foreach (\Drupal::service('stream_wrapper_manager')->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE) as $scheme => $description) {
      $options[$scheme] = Html::escape($description);
    }

    if (!empty($options)) {
      $form['scheme'] = array(
        '#type' => 'radios',
        '#title' => t('Download method'),
        '#default_value' => $translator->getSetting('scheme'),
        '#options' => $options,
        '#description' => t('Choose the location where exported files should be stored. The usage of a protected location (e.g. private://) is recommended to prevent unauthorized access.'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    if ($job->getTranslator()->getSetting('allow_override')) {
      $form['export_format'] = array(
        '#type' => 'radios',
        '#title' => t('Export to'),
        '#options' => \Drupal::service('plugin.manager.tmgmt_file.format')->getLabels(),
        '#default_value' => $job->getTranslator()->getSetting('export_format'),
        '#description' => t('Please select the format you want to export data.'),
      );
    }
    return parent::checkoutSettingsForm($form, $form_state, $job);
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    // If the job is finished, it's not possible to import translations anymore.
    if ($job->isFinished()) {
      return parent::checkoutInfo($job);
    }
    $form = array(
      '#type' => 'fieldset',
      '#title' => t('Import translated file'),
    );

    $supported_formats = array_keys(\Drupal::service('plugin.manager.tmgmt_file.format')->getDefinitions());
    $form['file'] = array(
      '#type' => 'file',
      '#title' => t('File'),
      '#size' => 50,
      '#description' => t('Supported formats: @formats.', array('@formats' => implode(', ', $supported_formats))),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Import'),
      '#submit' => array('tmgmt_file_import_form_submit'),
    );
    return $this->checkoutInfoWrapper($job, $form);
  }

}

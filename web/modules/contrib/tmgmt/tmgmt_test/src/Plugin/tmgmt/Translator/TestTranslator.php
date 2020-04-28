<?php

namespace Drupal\tmgmt_test\Plugin\tmgmt\Translator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\Translator\TranslatableResult;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\TranslatorRejectDataInterface;

/**
 * Test source plugin implementation.
 *
 * @TranslatorPlugin(
 *   id = "test_translator",
 *   label = @Translation("Test provider"),
 *   description = @Translation("Simple provider for testing purposes."),
 *   default_settings = {
 *     "expose_settings" = TRUE,
 *   },
 *   ui = "Drupal\tmgmt_test\TestTranslatorUi",
 *   logo = "icons/tmgmt_test.svg",
 * )
 */
class TestTranslator extends TranslatorPluginBase implements TranslatorRejectDataInterface, ContinuousTranslatorInterface {

  /**
   * {@inheritdoc}
   */
  protected $escapeStart = '[[[';

  /**
   * {@inheritdoc}
   */
  protected $escapeEnd = ']]]';

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return array(
      'en' => 'en-us',
      'de' => 'de-ch',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hasCheckoutSettings(JobInterface $job) {
    return $job->getTranslator()->getSetting('expose_settings');
  }

  /**
   * {@inheritdoc}
   */
  function requestTranslation(JobInterface $job) {
    // Add a debug message.
    $job->addMessage('Test translator called.', array(), 'debug');

    // Do something different based on the action, if defined.
    $action =$job->getSetting('action') ?: '';
    switch ($action) {
      case 'submit':
        $job->submitted('Test submit.');
        break;

      case 'reject':
        $job->rejected('This is not supported.');
        break;

      case 'fail':
        // Target not reachable.
        $job->addMessage('Service not reachable.', array(), 'error');
        break;

      case 'translate':
      default:
        $job->submitted('Test translation created.');
        $this->requestJobItemsTranslation($job->getItems());
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  function checkTranslatable(TranslatorInterface $translator, JobInterface $job) {
    if ($job->getSetting('action') == 'not_translatable') {
      return TranslatableResult::no(t('@translator can not translate from @source to @target.', array(
        '@translator' => $job->getTranslator()->label(),
        '@source' => $job->getSourceLanguage()->getName(),
        '@target' => $job->getTargetLanguage()->getName()
      )));
    }
    return parent::checkTranslatable($translator, $job);
  }
  /**
   * {@inheritdoc}
   */
  function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('action') == 'not_available') {
      return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
        '@translator' => $translator->label(),
        ':configured' => $translator->toUrl()->toString(),
      ]));
    }
    return parent::checkAvailable($translator);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $languages = array('en', 'de', 'es', 'it', 'pt', 'zh-hans', 'gsw-berne');
    $languages = array_combine($languages, $languages);
    unset($languages[$source_language]);
    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function rejectDataItem(JobItemInterface $job_item, array $key, array $values = NULL) {
    $key = '[' . implode('][', $key) . ']';
    $job_item->addMessage('Rejected data item @key for job item @item in job @job.', array('@key' => $key, '@item' => $job_item->id(), '@job' => $job_item->getJobId()));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rejectForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $job_items) {

    $data_service = \Drupal::service('tmgmt.data');

    $group = [];
    /** @var JobItemInterface $job_item */
    foreach ($job_items as $job_item) {
      $job = $job_item->getJob();
      $target_langcode = $job->getTargetLangcode();
      $remote_target_langcode = $job->getRemoteTargetLanguage();
      $group[] = ['item_id' => $job_item->id(), 'job_id' => $job_item->getJobId()];
      // Add a debug message.
      $job_item->active('Requested translation to the continuous translator.', [], 'debug');

      // The dummy translation prefixes strings with the target language.
      $data = $data_service->filterTranslatable($job_item->getData());
      $tdata = [];
      foreach ($data as $key => $value) {
        // Special handling for path fields that start with the language
        // prefix, keep them valid by just replacing the path prefix.
        if (strpos($value['#text'], '/' . $job->getSourceLangcode()) === 0) {
          $tdata[$key]['#text'] = str_replace('/' . $job->getSourceLangcode(), '/' . $job->getTargetLangcode(), $value['#text']);
        }
        elseif ($target_langcode != $remote_target_langcode) {
          $tdata[$key]['#text'] = $target_langcode . '(' . $remote_target_langcode . '): ' . $value['#text'];
        }
        else {
          $tdata[$key]['#text'] = $target_langcode . ': ' . $value['#text'];
        }
      }
      $job_item->addTranslatedData($data_service->unflatten($tdata));
    }
    $groups = \Drupal::state()->get('job_item_groups') ?: [];
    $groups[] = $group;
    \Drupal::state()->set('job_item_groups', $groups, $group);
  }

}

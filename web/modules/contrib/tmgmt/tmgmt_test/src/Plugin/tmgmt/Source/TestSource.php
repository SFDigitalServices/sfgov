<?php

namespace Drupal\tmgmt_test\Plugin\tmgmt\Source;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\SourcePluginBase;
use Drupal\Core\Render\Element;

/**
 * Test source plugin implementation.
 *
 * @SourcePlugin(
 *   id = "test_source",
 *   label = @Translation("Test source"),
 *   description = @Translation("Simple source for testing purposes.")
 * )
 */
class TestSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getUrl(JobItemInterface $job_item) {
    // Provide logic which allows to test for source which is either accessible
    // or not accessible to anonymous user. This is may then be used to test if
    // the source url is attached to the job comment sent to a translation
    // service.
    if ($job_item->getItemType() == 'test_not_accessible') {
      return Url::fromRoute('system.admin');
    }
    else {
      return Url::fromRoute('<front>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(JobItemInterface $job_item) {
    $label = $this->pluginId . ':' . $job_item->getItemType() . ':' . $job_item->getItemId();

    // We need to test if job and job item labels get properly truncated,
    // therefore in case the job item type is "test_with_long_label" we append
    // further text to the existing label.
    if ($job_item->getItemType() == 'test_with_long_label') {
      $label .= 'Some very long and boring label that definitely exceeds hundred and twenty eight characters which is the maximum character count for the job item label.';
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(JobItemInterface $job_item) {
    // Allow tests to set custom source data.
    $source = \Drupal::state()->get('tmgmt.test_source_data', array(
      'dummy' => array(
        'deep_nesting' => array(
          '#text' => 'Text for job item with type @type and id @id.',
          '#label' => 'Label for job item with type @type and id @id.',
          '#translate' => TRUE,
        ),
      ),
    ));

    $variables = array(
      '@type' => $job_item->getItemType(),
      '@id' => $job_item->getItemId(),
    );

    $this->replacePlaceholders($source, $variables);

    return $source;
  }

  /**
   * Will replace placeholders in the #text offsets.
   *
   * @param array $data
   *   Data structures where to replace placeholders.
   * @param $variables
   *   Key value pairs.
   */
  protected function replacePlaceholders(&$data, $variables) {
    foreach (Element::children($data) as $key) {
      if (isset($data[$key]['#text'])) {
        $data[$key]['#text'] = (string) new FormattableMarkup($data[$key]['#text'], $variables);
      }
      else {
        $this->replacePlaceholders($data[$key], $variables);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveTranslation(JobItemInterface $job_item, $target_langcode) {
    // Set a variable that can be checked later for a given job item.
    \Drupal::state()->set('tmgmt_test_saved_translation_' . $job_item->getItemType() . '_' . $job_item->getItemId(), $job_item->getData());
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingLangCodes(JobItemInterface $job_item) {
    return array_keys(\Drupal::languageManager()->getLanguages());
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLangCode(JobItemInterface $job_item) {
    $source_languages = \Drupal::state()->get('tmgmt.test_source_languages', array());
    if (isset($source_languages[$job_item->id()])) {
      return $source_languages[$job_item->id()];
    }

    return 'en';
  }
}

<?php

namespace Drupal\tmgmt_file\Plugin\tmgmt_file\Format;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_file\Format\FormatInterface;

/**
 * Export into HTML.
 *
 * @FormatPlugin(
 *   id = "html",
 *   label = @Translation("HTML")
 * )
 */
class Html implements FormatInterface {
  use MessengerTrait;

  /**
   * Returns base64 encoded data that is safe for use in xml ids.
   */
  protected function encodeIdSafeBase64($data) {
    // Prefix with a b to enforce that the first character is a letter.
    return 'b' . rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  /**
   * Returns decoded id safe base64 data.
   */
  protected function decodeIdSafeBase64($data) {
    // Remove prefixed b.
    $data = substr($data, 1);
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
  }

  /**
   * Implements TMGMTFileExportInterface::export().
   */
  public function export(JobInterface $job, $conditions = array()) {
    $items = array();
    foreach ($job->getItems($conditions) as $item) {
      $data = \Drupal::service('tmgmt.data')->filterTranslatable($item->getData());
      foreach ($data as $key => $value) {
        $items[$item->id()][$this->encodeIdSafeBase64($item->id() . '][' . $key)] = $value;
      }
    }
    $elements = array(
      '#theme' => 'tmgmt_file_html_template',
      '#tjid' => $job->id(),
      '#source_language' => $job->getRemoteSourceLanguage(),
      '#target_language' => $job->getRemoteTargetLanguage(),
      '#items' => $items,
    );
    return \Drupal::service('renderer')->renderPlain($elements);
  }

  /**
   * Implements TMGMTFileExportInterface::import().
   */
  public function import($imported_file, $is_file = TRUE) {
    $dom = new \DOMDocument();
    $is_file ? $dom->loadHTMLFile($imported_file) : $dom->loadHTML($imported_file);
    $xml = simplexml_import_dom($dom);

    $data = array();
    foreach ($xml->xpath("//div[@class='atom']") as $atom) {
      // Assets are our strings (eq fields in nodes).
      $key = $this->decodeIdSafeBase64((string) $atom['id']);
      $data[$key]['#text'] = (string) $atom;
    }
    return \Drupal::service('tmgmt.data')->unflatten($data);
  }

  /**
   * {@inheritdoc}
   */
  public function validateImport($imported_file, $is_file = TRUE) {
    $dom = new \DOMDocument();
    if (!$dom->loadHTMLFile($imported_file)) {
      return FALSE;
    }
    $xml = simplexml_import_dom($dom);

    // Collect meta information.
    $meta_tags = $xml->xpath('//meta');
    $meta = array();
    foreach ($meta_tags as $meta_tag) {
      $meta[(string) $meta_tag['name']] = (string) $meta_tag['content'];
    }

    // Check required meta tags.
    foreach (array('JobID', 'languageSource', 'languageTarget') as $name) {
      if (!isset($meta[$name])) {
        return FALSE;
      }
    }

    // Attempt to load the job.
    if (!$job = Job::load($meta['JobID'])) {
      \Drupal::messenger()->addError(t('The imported file job id @file_id is not available.', array(
        '@file_id' => $meta['JobID'],
      )));
      return FALSE;
    }

    // Check language.
    if ($meta['languageSource'] != $job->getRemoteSourceLanguage() ||
        $meta['languageTarget'] != $job->getRemoteTargetLanguage()) {
      return FALSE;
    }

    // Validation successful.
    return $job;
  }

}

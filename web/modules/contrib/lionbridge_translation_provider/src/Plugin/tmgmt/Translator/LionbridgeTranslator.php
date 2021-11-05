<?php

namespace Drupal\lionbridge_translation_provider\Plugin\tmgmt\Translator;

use Drupal\Component\Serialization\Json;
use Drupal\tmgmt\Entity\Job;
use Drupal\Core\Url;
use Drupal\lionbridge_translation_provider\LionbridgeConnector;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\Translator\TranslatableResult;

/**
 * Lionbridge provider.
 *
 * @TranslatorPlugin(
 *   id = "lionbridge",
 *   label = @Translation("Lionbridge translator"),
 *   description = @Translation("Lionbridge translator service."),
 *   ui = "Drupal\lionbridge_translation_provider\LionbridgeTranslatorUi",
 *   default_settings = {
 *     "po_number" = "123456"
 *   },
 * )
 */
class LionbridgeTranslator extends TranslatorPluginBase {

  /**
   * Translation keys.
   *
   * @var array
   */
  protected $translationKeys;

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    if ($job->isUnprocessed()) {
      try {
        $this->submitJob($job);

        if (!$job->isRejected()) {
          // @todo: Unprocessed Jobs should not trigger success message.
          $job->submitted(t('Job has been submitted.'));
        }
      }
      catch (TMGMTException $e) {
        watchdog_exception('lionbridge_translation_provider', $e);
        $job->rejected('Job has been rejected with following error: @error',
          ['@error' => $e->getMessage()], 'error');
      }
    }

    if ($job->isActive()) {
      try {
        $this->fetchJob($job);
      }
      catch (TMGMTException $e) {
        watchdog_exception('lionbridge_translation_provider', $e);
        $job->addMessage('Translation could not be completed: @error',
          ['@error' => $e->getMessage()], 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('access_key_id') && $translator->getSetting('access_key')) {
      return AvailableResult::yes();
    }

    return AvailableResult::no(t('Access key ID and access key are not set.'));
  }


  /**
   * {@inheritdoc}
   */
  public function checkTranslatable(TranslatorInterface $translator, JobInterface $job) {
    if ($job->isUnprocessed() || $job->isRejected() || $job->isAborted()) {
      return TranslatableResult::no(t("Please use the ContentAPI connector."));
    }
    else {
      return TranslatableResult::yes();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    if (!$translator->checkAvailable()->getSuccess()) {
      return parent::getSupportedRemoteLanguages($translator);
    }

    $api_client = new LionbridgeConnector($translator);
    $locales = $api_client->listLocales();
    $languages = [];
    foreach ($locales['Locale'] as $language) {
      $languages[$language['Code']] = $language['Code'];
    }

    return $languages;
  }

  /**
   * Submits a job to Lionbridge translation service.
   *
   * This does the work of actually submitting the translation job to the
   * Lionbridge translation service. There are 3 major parts to submitting a
   * translations job. Each part has been broken out into an individual
   * function to make the code more readable.
   *
   * 1. Create a JSON encoded file that contains all the translatable content
   *    and send it to Lionbridge. If this is successful, an array containing
   *    the details of the uploaded file is returned.
   *
   * 2. Create a project using the settings from the job settings page and the
   *    AssetID of the array returned from the successful file upload. If there
   *    were more than one file uploaded, multiple file asset ID's could be
   *    used in a project, however, in this model there is only 1 asset ID for
   *    a given project.
   *
   * 3. Generate a quote for the translation job using the Project ID from the
   *    array returned from a successful project creation request.
   *
   * NOTE: Translation Memory update job files are XML encoded.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job object.
   *
   * @throws TMGMTException
   */
  public function submitJob(JobInterface $job) {
    $translator = $job->getTranslator();
    $api_client = new LionbridgeConnector($translator);
    $items = $job->getItems();
    $file_assets = [];
    $source_language = $translator->mapToRemoteLanguage($job->getSourceLangcode());
    $target_language = $translator->mapToRemoteLanguage($job->getTargetLangcode());

    if ($translator->getSetting('notification_url')) {
      $project_complete_url = $translator->getSetting('notification_url');
    }
    else {
      $project_complete_url = Url::fromRoute('lionbridge_translation_provider.lionbridge_translation_provider_project_complete_callback', [], [
        'query' => ['secret' => $job->getSetting('secret')],
        'absolute' => TRUE,
      ])->toString();
    }

    $service_type = $translator->getSetting('service_type');

    switch ($service_type) {
      case 'tm_update':
        // If service type is an update, then we send a xml file with the
        // content that was translated and updated locally.
        // Load the entity and check if there is a translation already.
        $job_item = reset($items);
        $storage = \Drupal::entityTypeManager()->getStorage($job_item->getItemType());
        $entity = $storage->load($job_item->getItemId());

        // If there is translated data, load it.
        if ($entity->hasTranslation($job->getTargetLangcode())) {
          // The $job_item already has the data with the original content to be
          // translated, here we load the source plugin and the already
          // translated entity and format the translated entity just like it is
          // already formatted in the job.
          $source_plugin = $job_item->getSourcePlugin();
          $translated_entity = $entity->getTranslation($job->getTargetLangcode());
          $translated_data = $source_plugin->extractTranslatableData($translated_entity);
        }
        else {
          throw new TMGMTException('Source is required to have translated content for language requested.');
        }

        // Get the item path, e.g. node/15, taxonomy/1, etc.
        $item_path = $entity->toUrl()->getInternalPath();

        // Build data to be added to the xml file.
        $update_data = [
          'theme' => 'lionbridge_update_content',
          'source_language' => $source_language,
          'target_language' => $target_language,
          'item_path' => $item_path,
        ];

        $file_name = $this->generateFileName($job->label(), $source_language, $target_language);

        $file_asset = $this->generateUpdateFile($file_name, $update_data, $job->getData(), $translated_data, $api_client);
        if (isset($file_asset['Errors']['Error'])) {
          throw new TMGMTException($file_asset['Errors']['Error']['DetailedMessage']);
        }

        $file_assets[] = $file_asset['AssetID'];

        break;

      default:
        // If service is of type translation, then we send a json file with the
        // translation and a xml file for a new project.
        // This is the name of the JSON file that will be sent to Lionbridge.
        $file_name = $this->generateFileName($job->label(), $source_language, $target_language);

        $file_asset = $this->generateFile($file_name, $source_language, $job->getData(), $api_client);
        if (isset($file_asset['Errors']['Error'])) {
          throw new TMGMTException($file_asset['Errors']['Error']['DetailedMessage']);
        }

        $file_assets[] = $file_asset['AssetID'];

        break;
    }

    // Create a new project with the file asset, send it to Lionbridge. This
    // will create a new project and return the project XML in the form of an
    // array.
    $service_id = $translator->getSetting('service');

    $project_data = [
      'theme' => 'lionbridge_add_project',
      'project_title' => $job->getSetting('project_title') . ' to ' . $target_language,
      'service_id' => $service_id,
      'source_language' => $source_language,
      'target_languages' => [$target_language],
      'file_assets' => $file_assets,
    ];

    $project = $this->addProject($project_data, $api_client);

    // Check if there are errors in the project.
    if (isset($project['Errors']['Error']) && !empty($project['Errors']['Error'])) {
      throw new TMGMTException($project['Errors']['Error']['DetailedMessage']);
    }

    // Get a quote for the project.
    $quote = $this->generateQuote($project['ProjectID'], $project_complete_url, $api_client);

    if (isset($quote['Errors']) && !empty($quote['Errors'])) {
      throw new TMGMTException($quote['Errors']['Error']['DetailedMessage']);
    }

    // Set the job reference to the Quote ID.
    $job->set('reference', $quote['QuoteID']);

    // Add project ID to `tmgmt_remote` table.
    foreach ($this->translationKeys as $translation_key) {
      $items[$translation_key['tjiid']]->addRemoteMapping(
        $translation_key['data_item_key'],
        $project['ProjectID']
      );
    }

  }

  /**
   * Gets status of job items from Lionbridge and downloads translated items.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The translation job object.
   *
   * @return bool
   *   Completion indicator.
   *
   * @throws TMGMTException
   */
  public function fetchJob(JobInterface $job) {
    $translator = $job->getTranslator();
    $api_client = new LionbridgeConnector($translator);
    if ($job->getReference()) {
      $quote = $api_client->getQuote($job->getReference());
      if (!$quote || isset($quote['Error'])) {
        throw new TMGMTException('Could not get quote.');
      }

      $translation_complete = FALSE;
      $service_type = $translator->getSetting('service_type');

      switch ($quote['Status']) {
        case LionbridgeConnector::QUOTE_STATUS_ERROR:
          $job->addMessage('An error occurred with this quote.');
          $this->abortTranslation($job);
          break;

        case LionbridgeConnector::QUOTE_STATUS_PENDING:
          // If this is a pending quote, add a message and a link to approve.
          $job->addMessage(
            'Quote ready for approval: <a href=":url" target="_blank">View on Lionbridge</a>', [
              ':url' => Url::fromUri($api_client->getEndpoint() . '/project/' . $quote['QuoteID'] . '/details')->toString(),
            ]
          );
          break;

        case LionbridgeConnector::QUOTE_STATUS_CALCULATING:
          $job->addMessage('Calculating quote...');
          break;

        case LionbridgeConnector::QUOTE_STATUS_AUTHORIZED:
          if ($service_type == 'tm_update') {
            $job->addMessage('Translation Memory Update in progress.');
          }
          else {
            $job->addMessage('Translation in progress.');
          }
          break;

        case LionbridgeConnector::QUOTE_STATUS_COMPLETE:
          // If it's a TM update, complete job items.
          if ($service_type == 'tm_update') {
            foreach ($job->getItems() as $job_item) {
              $variables = [
                '@source' => $job_item->getSourceLabel(),
                '@language' => $job->getTargetLanguage()->getName(),
              ];
              $job_item->accepted('Translation Memory Update of "@source" for language @language is finished.', $variables);
            }
          }
          else {
            // Get the file which contains translations for all job items.
            // Loop through the remotes, populate translated data.
            $asset_id = $quote['Projects']['Project']['Files']['File']['AssetID'];
            $data_json = $api_client->getFileTranslation($asset_id, $translator->mapToRemoteLanguage($job->getTargetLangcode()));
            $data = Json::decode($data_json);

            if (!$data) {
              $data_error_message = json_last_error_msg();
              throw new TMGMTException($data_error_message);
            }
            // Batch process the storing of the translated data.
            batch_set(_lionbridge_translation_provider_batch($job, $data));
          }

          $translation_complete = TRUE;
          break;

      }
      return $translation_complete;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Jobs with authorized Quotes should not be able to be aborted.
   */
  public function abortTranslation(JobInterface $job) {
    $translator = $job->getTranslator();
    $api_client = new LionbridgeConnector($translator);
    $quote = $api_client->getQuote($job->getReference());

    // If the status is Pending reject the quote on Lionbridge.
    if ($quote && empty($quote['Errors']) && $quote['Status'] == LionbridgeConnector::QUOTE_STATUS_PENDING) {
      $api_client->rejectQuote($quote['QuoteID']);
    }

    $job->aborted();
    return TRUE;
  }

  /**
   * Sends a file to the Lionbridge translation service.
   */
  protected function addFile($translation, LionbridgeConnector $api_client) {
    return $api_client->addFile(
      $translation['source_language'],
      $translation['fileName'],
      $translation['content_type'],
      $translation['file_content']
    );
  }

  /**
   * Sends a create project request to the Lionbridge translation service.
   */
  protected function addProject($project_data, LionbridgeConnector $api_client) {
    return $api_client->addProject(
      $project_data['project_title'],
      $project_data['service_id'],
      $project_data['source_language'],
      $project_data['target_languages'],
      $project_data['file_assets']
    );
  }

  /**
   * Send Memory Update in xml to Lionbridge.
   *
   * @param string $file_name
   *    The file name.
   * @param array $update_data
   *    The data to be used in render array to form file content.
   * @param array $entity_data
   *    The source entity data.
   * @param array $translated_entity_data
   *    The translated entity data.
   * @param LionbridgeConnector $api_client
   *   The API client.
   *
   * @return array
   *   The information about the file returned from the API client.
   */
  public function generateUpdateFile($file_name, $update_data, $entity_data, $translated_entity_data, LionbridgeConnector $api_client) {
    // Get the content to be translated and the already translated content and
    // build an array of "content_corrections" that will be sent to Lionbridge.
    $content_corrections = [];

    foreach (['source_content' => reset($entity_data), 'target_content' => $translated_entity_data] as $key => $data_to_flat) {
      if ($data_to_flat == NULL) {
        $data_flat = NULL;
      }
      else {
        $data_flat = array_filter(\Drupal::service('tmgmt.data')->flatten($data_to_flat), [\Drupal::service('tmgmt.data'), 'filterData']);
      }
      foreach ($data_flat as $flat_key => $value) {
        $flat_key = str_replace(['[', ']'], ':', $flat_key);
        $content_corrections[$flat_key][$key] = trim(str_replace(['“', '”'], '\"', htmlspecialchars($value['#text'])));
      }
    }

    foreach ($content_corrections as $data_item_key => $value) {
      // Preserve the original data.
      $this->translationKeys[] = [
        'tjiid' => key($entity_data),
        'data_item_key' => $data_item_key,
      ];
    }

    // Compose render array with the translation update properties and values.
    $update_xml = [
      '#theme' => $update_data['theme'],
      '#source_language' => $update_data['source_language'],
      '#target_language' => $update_data['target_language'],
      '#item_path' => $update_data['item_path'],
      '#content_corrections' => $content_corrections,
    ];

    // Generate update file content.
    $file_content = \Drupal::service('renderer')->render($update_xml)->__toString();

    $file_data = [
      'source_language' => $update_data['source_language'],
      'fileName' => urlencode($file_name . '.xml'),
      'content_type' => 'text/xml',
      'file_content' => $file_content,
    ];

    return $this->addFile($file_data, $api_client);
  }

  /**
   * Gets a quote from the Lionbridge translation service.
   */
  protected function generateQuote($project_id, $notification_url, LionbridgeConnector $api_client) {
    return $api_client->generateQuote($project_id, $notification_url);
  }

  /**
   * Create JSON encoded file.
   *
   * @param string $file_name
   *   The lionbridge connector.
   * @param string $source_language
   *   The source language.
   * @param mixed $data
   *   The data to be flattened.
   * @param LionbridgeConnector $api_client
   *   The API client.
   *
   * @return array
   *   The created file.
   */
  protected function generateFile($file_name, $source_language, $data, LionbridgeConnector $api_client) {
    $translatable_content = [];

    $data_flat = array_filter(\Drupal::service('tmgmt.data')->flatten($data), array(\Drupal::service('tmgmt.data'), 'filterData'));

    foreach ($data_flat as $key => $value) {
      list($tjiid, $data_item_key) = explode('][', $key, 2);

      // Preserve the original data.
      $this->translationKeys[] = [
        'tjiid' => $tjiid,
        'data_item_key' => $data_item_key,
      ];

      // This is the actual text sent to Lionbridge for translation.
      $translatable_content[$tjiid][$data_item_key] = str_replace(array('“', '”'), '\"', $value['#text']);
    }

    $file_content = json_encode($translatable_content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $translation = [
      'source_language' => $source_language,
      'fileName' => urlencode($file_name . '.json'),
      'content_type' => 'application/json',
      'file_content' => $file_content,
    ];

    return $this->addFile($translation, $api_client);
  }

  /**
   * Create a file name.
   *
   * @param string $file_name
   *   The file name.
   * @param string $source_language
   *   The source language code.
   * @param string $target_language
   *   The target language code.
   *
   * @return string
   *   The generated file name.
   */
  protected function generateFileName($file_name, $source_language, $target_language) {
    $length = 0;
    $file_name_string = '';
    $string = strtolower($file_name);

    if (preg_match_all('/(\w+)/is', $string, $matches)) {
      foreach ($matches[0] as $match) {
        $length += strlen($match);

        if ($length < 45) {
          $file_name_string .= $match . '-';
        }
      }

      $file_name_string .= $source_language . '-' . $target_language;
      return $file_name_string;
    }

    // Something went wrong, just return $string.
    return $string;
  }

}

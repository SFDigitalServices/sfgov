<?php

namespace Drupal\tmgmt_contentapi;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\tmgmt_contentapi\Swagger\Client\Api\JobApi;
use Drupal\tmgmt_contentapi\Swagger\Client\Api\RequestApi;

use Drupal\tmgmt_contentapi\Swagger\Client\ApiException;
use Drupal\tmgmt_contentapi\Swagger\Client\Model\ArrayOfRequestIdsNote;
use Drupal\tmgmt_contentapi\Swagger\Client\Model\ArrayOfRequestIds;

use Drupal\tmgmt_contentapi\Swagger\Client\Model\Request;
use Drupal\tmgmt_contentapi\Swagger\Client\Model\StatusCodeEnum;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tmgmt_contentapi\Swagger\Client\Model\CreateToken;
use Drupal\tmgmt_contentapi\Swagger\Client\Api\TokenApi;

use Drupal\tmgmt_contentapi\Util\ConentApiHelper;
use Exception;

use Drupal\tmgmt_contentapi\Swagger\Client\Api\ProviderApi;


/**
 * Freeway File translator UI.
 */
class ContentApiTranslatorUI extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $capisettings = $translator->getSetting('capi-settings');
    $cronsettings = $translator->getSetting('cron-settings');
    $exportsettings = $translator->getSetting('export_format');
    $transfersettings = $translator->getSetting('transfer-settings');
    $token = $capisettings['token'];
    $form['export_format'] = array(
      '#type' => 'radios',
      '#title' => t('Export to'),
      '#options' => \Drupal::service('plugin.manager.tmgmt_contentapi.format')->getLabels(),
      '#default_value' => isset($exportsettings) ? $exportsettings : "contentapi_xlf",
      '#description' => t('Select the format for exporting data.'),
    );
    $form['xliff_cdata'] = array(
      '#type' => 'checkbox',
      '#title' => t('XLIFF CDATA'),
      '#description' => t('Select to use CDATA for import/export.'),
      '#default_value' => $translator->getSetting('xliff_cdata'),
    );

    $form['xliff_processing'] = array(
      '#type' => 'checkbox',
      '#title' => t('Extended XLIFF processing'),
      '#description' => t('Select to further process content semantics and mask HTML tags instead of just escaping them.'),
      '#default_value' => $translator->getSetting('xliff_processing'),
    );

    $form['xliff_message'] = array(
      '#type' => 'container',
      '#markup' => t('By selecting CDATA option, XLIFF processing will be ignored.'),
      '#attributes' => array(
        'class' => array('messages messages--warning'),
      ),
    );

    $form['allow_override'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow export-format overrides'),
      '#default_value' => $translator->getSetting('allow_override'),
    );

    $form['one_export_file'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use one export file for all items in job'),
      '#description' => t('Select to export all items to one file. Clear to export items to multiple files.'),
      '#default_value' => $translator->getSetting('one_export_file'),
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
        '#description' => t('Choose where  to store exported files. Recommendation: Use a secure location to prevent unauthorized access.'),
      );
    }

    $form['capi-settings'] = array(
      '#type' => 'details',
      '#title' => t('Lionbridge Content API Settings'),
      '#open' => TRUE,
    );

    $form['capi-settings']['po_reference'] = array(
      '#type' => 'textfield',
      '#title' => t('PO Number'),
      '#required' => FALSE,
      '#description' => t('Enter your Lionbridge purchase order number.'),
      '#default_value' => $capisettings['po_reference'],
    );

    $form['capi-settings']['capi_username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#required' => TRUE,
      '#description' => t('Enter your Lionbridge username.'),
      '#default_value' => $capisettings['capi_username'],
    );

    $form['capi-settings']['capi_password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#required' => TRUE,
      '#description' => t('Enter your Lionbridge password.'),
      //'#default_value' => $capisettings['capi_password'],
    );
    $form['capi-settings'] += parent::addConnectButton();

    $form['capi-settings']['token'] = array(
      '#type' => 'hidden',
      '#value' => (isset($token) && $token != "") ? $token : NULL
    );


    $providers = NULL;
    if(isset($token) && $token != '') {
      try {
        $providerapi = new ProviderApi();
        $providers = $providerapi->providersGet($token);
      } catch (Exception $e) {
        drupal_set_message($e->getMessage());
      }
    }
    $providersarray = array();
    foreach ($providers as $provider) {
      $prid = $provider->getProviderId();
      $prname = $provider->getProviderName();
      $providersarray[$prid] = $prname;
    }
    asort($providersarray, SORT_REGULAR);
    $defaultprovidervalue = isset($capisettings['provider']) ? $capisettings['provider'] : NULL;
    $form['capi-settings']['provider'] = array(
      '#type' => 'select',
      '#title' => t('Provider configuration'),
      '#required' => (isset($token) && $token != "") ? TRUE : FALSE,
      '#options' => $providersarray,
      '#default_value' => $defaultprovidervalue,
      '#description' => t('Please select a Provider for your project.'),
    );

    $form['capi-settings']['allow_provider_override'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow provider overrides'),
      '#default_value' => $translator->getSetting('capi-settings')['allow_provider_override'],
    );

    $form['transfer-settings'] = array(
      '#type' => 'checkbox',
      '#title' => t('Transfer all files as zip'),
      '#description' => t('Select to transfer all exported files for a job as a .zip file.'),
      '#default_value' => $transfersettings,
    );

    $form['cron-settings'] = array(
      '#type' => 'details',
      '#title' => t('Scheduled Tasks'),
      '#description' => t('Specify settings for scheduled tasks.'),
      '#open' => TRUE,
    );

    $form['cron-settings']['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Receive translated jobs automatically.'),
      '#description' => t('Select to receive translated jobs automatically, by scheduled task. Clear to download translated jobs manually.'),
      '#default_value' => $cronsettings['status'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    $valid_provider = $this->getValidProvider($form, $form_state, $job);
    $translator = $job->getTranslator();
    $capisettings = $translator->getSetting('capi-settings');
    $jobcapisettings = $job->getSetting("capi-settings");
    $exportsettingstranslator = $job->getTranslator()->getSetting('export_format');
    $exportsettings = $job->getSetting('exports-settings')['cpexport_format'];
    $allowprovideroverride = $capisettings['allow_provider_override'];
    $form['exports-settings'] = array(
      '#type' => 'details',
      '#title' => t('Export Settings'),
      '#open' => TRUE,
    );
    $form['exports-settings']['cpexport_format'] = array(
      '#type' => 'radios',
      '#title' => t('Export to'),
      '#options' => \Drupal::service('plugin.manager.tmgmt_contentapi.format')->getLabels(),
      '#default_value' => isset($exportsettings) ? $exportsettings : $exportsettingstranslator,
      '#description' => t('Select the format for exporting data.'),
    );
    $form['capi-settings'] = array(
      '#type' => 'details',
      '#title' => t('Content API Job Details'),
      '#open' => TRUE,
    );

    $form['capi-settings']['po_reference'] = array(
      '#type' => 'textfield',
      '#title' => t('PO Reference'),
      '#required' => FALSE,
      '#description' => t('Please enter your PO Reference'),
      '#default_value' => isset($jobcapisettings["po_reference"]) ? $jobcapisettings["po_reference"] : $capisettings['po_reference'],
    );


    $form['capi-settings']['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#required' => FALSE,
      '#description' => t('Please enter a description for the job.'),
      '#default_value' => isset($jobcapisettings["description"]) ? $jobcapisettings["description"] : '',
    );
    $form['capi-settings']['due_date'] = array(
      '#type' => 'date',
      '#title' => t('Expected Due Date'),
      '#required' => FALSE,
      '#description' => t('Please enter the expected due date.'),
      '#default_value' => isset($jobcapisettings["due_date"]) ? $jobcapisettings["due_date"] : \date("Y-m-d"),
    );
    $form['capi-settings']['task'] = array(
      '#type' => 'select',
      '#title' => t('Task'),
      '#options' => array("trans" => "Translation"), //, "tm" => "TM Update"),
      '#default_value' => isset($jobcapisettings["task"]) ? $jobcapisettings["task"] : 'trans',
      '#description' => t('Please select a task for your project.'),
    );
    $token = $capisettings['token'];
    $providers = NULL;
    try {
      $providerapi = new ProviderApi();
      $providers = $providerapi->providersGet($token);
    }
    catch (Exception $e) {
      $linkToConfig= Link::fromTextAndUrl(t('connector configuration'), Url::fromUserInput('/admin/tmgmt/translators'));
      drupal_set_message(\Drupal\Core\Render\Markup::create($e->getMessage().'! ' . t('Please check the '.$linkToConfig->toString().'!')),'error');
    }
    $providersarray = array();
    foreach ($providers as $provider) {
      $prid = $provider->getProviderId();
      $prname = $provider->getProviderName();
      $providersarray[$prid] = $prname;
    }
    asort($providersarray, SORT_REGULAR);
    //$defaultproviderkey = key($providersarray);
    $form['capi-settings']['provider'] = array(
      '#type' => 'select',
      '#title' => t('Provider configuration'),
      '#required' => TRUE,
      '#options' => $providersarray,
      '#default_value' => isset($valid_provider) ? $valid_provider : NULL,
      '#description' => t('Please select a Provider for your project.'),
      '#ajax' => [
        'callback' => 'ajax_tmgmt_contentapi_provider_changed',
        'wrapper' => 'quote',
      ],
    );
    if(!$allowprovideroverride){
      $form['capi-settings']['provider']['#attributes']['disabled'] = 'disabled';
    }


    $form['capi-settings']['quote'] = [
      '#type' => 'container',
      '#prefix' => '<div id="quote">',
      '#suffix' => '</div>',
    ];

    if (isset($valid_provider)) {
      $form['capi-settings']['quote']['supported_languages'] = array(
        '#type' => 'details',
        '#title' => t('Supported Languages'),
        '#open' => FALSE,
        '#description' => t('Supported language pairs by the selected provider.'),
      );
      $providerapi = new ProviderApi();
      $rows = array();
      $header = array(t('source languages'), t('target languages'));
      try {
        $selected_provider = $providerapi->providersProviderIdGet($token, $valid_provider);
        $capabilities = $selected_provider->getCapabilities();
        $supported_lang_pairs = isset($capabilities) ? $capabilities->getSupportedLanguages() : array();
        foreach ($supported_lang_pairs as $pair) {
          $rows[] = [
            join(',', isset($pair) ? $pair->getSources() : []),
            join(',', isset($pair) ? $pair->getTargets() : NULL)
          ];
        }
      }
      catch (ApiException $ex) {
        $rows[] = array($ex->getMessage());
      }
      $form['capi-settings']['quote']['supported_languages']['lang_table'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => t('No language specific settings defined for the selected provider.')
      );
    }



    $form['capi-settings']['quote']['is_quote'] = array(
      '#type' => 'checkbox',
      '#title' => t('Quote'),
      '#description' => t('Check to receive a quote before translation starts. Quote has to be approved in order to start the translation'),
      '#default_value' => 0,
    );

    $provider = getProvider($token, $valid_provider);
    $capabilities = isset($provider) ? $provider->getCapabilities() : NULL;
    $supportsQuote = $capabilities != NULL ? $capabilities->getSupportQuote() : TRUE;
    $supportsQuote = isset($supportsQuote) ? $supportsQuote : TRUE;
    if (!$supportsQuote) {
      $form_values = $form_state->getValues();
      $form_user_input = $form_state->getUserInput();
      $form_values['settings']['capi-settings']['quote']['is_quote'] = 0;
      $form_user_input['settings']['capi-settings']['quote']['is_quote'] = FALSE;
      $form_state->setValues($form_values);
      $form_state->setUserInput($form_user_input);
      $form['capi-settings']['quote']['is_quote']['#attributes'] = array('disabled' => TRUE);;
    }
    else {
      unset($form['capi-settings']['quote']['is_quote']['#attributes']['disabled']);
    }

    return parent::checkoutSettingsForm($form, $form_state, $job);
  }

  /**
   * @param array $form
   * @param array $form_state
   * @param \TMGMTJob $job
   */
  public function getValidProvider(array $form, FormStateInterface &$form_state, JobInterface $job){
    // can be triggered by translator, provider dropdown, language drop down, request translation, submit button in job overview
    $who_triggered = $form_state->getTriggeringElement();
    $values = $form_state->getValues();
    $form_user_input = $form_state->getUserInput();
    $trigger_name = isset($who_triggered) ? $who_triggered['#name'] : NULL;
    $translator = $job->getTranslator();
    // we need this, otherwise when new jobs are submitted and no translator saved, ajax causes problems when switching translator
    // TMGMT bug?
    $job->save();
    $capisettings = $translator->getSetting('capi-settings');
    $jobcapisettings = $job->getSetting("capi-settings");
    $translator_provider_id = isset($capisettings) ? $capisettings['provider'] : NULL;
    $job_provider_id = isset($values) && isset($values['settings']['capi-settings']['provider']) ?
      $values['settings']['capi-settings']['provider'] : $translator_provider_id;
    switch ($trigger_name) {
      case 'translator':
        $values['settings']['capi-settings']['provider'] = $translator_provider_id;
        $form_user_input['settings']['capi-settings']['provider'] = $translator_provider_id;
        $form_state->setValues($values);
        $form_state->setUserInput($form_user_input);
        return $translator_provider_id;

      default:
        $values['settings']['capi-settings']['provider'] = $job_provider_id;
        $form_user_input['settings']['capi-settings']['provider'] = $job_provider_id;
        $form_state->setValues($values);
        $form_state->setUserInput($form_user_input);
        return $job_provider_id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    $requestobjs = unserialize($job->getSetting("capi-remote"));
    // Check if we have any request and take from first jobid.
    $task = $job->getSetting('capi-settings')['task'];
    $capijobid = NULL;
    if($task == 'trans'){
      $capijobid = isset($requestobjs[0]) && count($requestobjs) > 0 ? reset($requestobjs[0])->getJobId() : NULL;
    }
    else {
      $job->setState(Job::STATE_FINISHED);
      $capijobid = isset($requestobjs[0]) && count($requestobjs) > 0 ? $requestobjs[0]->getJobId() : NULL;
    }
    $form = array();
    $projectInfo = null;
    If($job->getState() > \Drupal\tmgmt\Entity\Job::STATE_UNPROCESSED) {
      try {
        $jobapi = new JobApi();
        $token = ConentApiHelper::generateToken($job->getTranslator());
        $projectInfo = $jobapi->jobsJobIdGet($token, $capijobid, "fullWithStats");
        if($projectInfo->getStatusCode()->getStatusCode() == StatusCodeEnum::REVIEW_TRANSLATION){
          $updatedremotejob = ConentApiHelper::checkJobFinishAndApproveRemote($job);
          $projectInfo = $updatedremotejob != NULL ? $updatedremotejob:$projectInfo;
        }
      }
      catch (Exception $ex){
        $respbody = $ex->getResponseBody();
        drupal_set_message('The API returned an error. '. $respbody,'warning');
        $projectInfo = null;
      }
    }
    $cpodername = 'n/a';
    $cporderid = 'n/a';
    $cpstatuscode = 'n/a';
    $lateerror = 'n/a';
    $providerid = 'n/a';
    $poreference = 'n/a';
    $duedate = NULL;
    $archived = 'n/a';
    $description = 'n/a';
    $jobstats = NULL;
    if($projectInfo != null){
      $cpodername = $projectInfo->getJobName();
      $cporderid = $projectInfo->getJobId();
      $cpstatuscode = $projectInfo->getStatusCode()->getStatusCode();
      $lateerror = $projectInfo->getLatestErrorMessage();
      $providerid = $projectInfo->getProviderId();
      $poreference = $projectInfo->getPoReference();
      $duedate = $projectInfo->getDueDate();
      $archived = $projectInfo->getArchived() ? "TRUE" : "FALSE";
      $description = $projectInfo->getDescription();
      $jobstats = $projectInfo->getJobStats();
    }

    $this->createCpOrderForm(
      $form,
      $cpodername,
      $cporderid,
      $cpstatuscode,
      $description,
      $poreference,
      $duedate,
      $providerid,
      $lateerror,
      $archived,
      $jobstats
    );


    if($task == 'trans') {
      $form['fw-immport-palaceholder'] = [
        '#prefix' => '<div id="fw-im-placholder">',
        '#suffix' => '</div>',
      ];
      if ($projectInfo != NULL && $projectInfo->getShouldQuote()) {
        $form['fw-immport-palaceholder']['quote-info'] = [
          '#prefix' => '<div role="contentinfo" aria-label="Warning message" class="messages messages--warning">'
            . '<div role="alert"><h2 class="visually-hidden">Warning message</h2>',
          '#markup' => t('This job was submitted for a quote. To submit your job for processing, you must log into your translation provider\'s system to approve this quote.
'),
          '#suffix' => '</div></div>'
        ];
      }

      $form['fw-immport-palaceholder']['fieldset-import'] = [
        '#type' => 'fieldset',
        '#title' => t('IMPORT TRANSLATED FILE'),
        '#collapsible' => TRUE,
      ];


      $form['fw-immport-palaceholder']['fieldset-import']['automatic-import'] = [
        '#type' => 'details',
        '#title' => t('Import automatically | Update TM'),
        '#open' => TRUE,
      ];

      $form['fw-immport-palaceholder']['fieldset-import']['automatic-import']['auto-submit'] = [
        '#type' => 'submit',
        '#value' => t('Auto-Import'),
        '#submit' => ['tmgmt_contentapi_semi_import_form_submit'],
      ];

      $form['fw-immport-palaceholder']['fieldset-import']['automatic-import']['tm-update'] = [
        '#type' => 'submit',
        '#value' => t('Update TM'),
        '#submit' => ['tmgmt_contentapi_update_tm_form_submit'],
      ];

      $form['fw-immport-palaceholder']['fieldset-import']['manual-import'] = [
        '#type' => 'details',
        '#title' => t('Manual Import'),
        '#open' => TRUE,
      ];


      $form['fw-immport-palaceholder']['fieldset-import']['manual-import']['file'] = [
        '#type' => 'file',
        '#title' => t('File'),
        '#size' => 50,
        '#description' => t('Supported formats: xlf.'),
      ];
      $form['fw-immport-palaceholder']['fieldset-import']['manual-import']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Manual Import'),
        '#submit' => ['tmgmt_contentapi_import_form_submit'],
        '#validate' => ['tmgmt_contentapi_check_empty_file']
      ];
    }
    return $form;
  }


  public function createCpOrderForm(&$fieldset, $ordername, $orderid, $orderstatus, $description, $poreference, $duedate, $providerid, $errors, $archived, $jobstats){
    $fieldset['fw-table'] = array(
      '#prefix' => '<table class="views-table views-view-table cols-8"><thead>
                            <tr>
                                <th>Job Name</th>
                                <th>Job ID</th>
                                <th>Job Status</th>
                                <th>Description</th>
                                <th>PO Number</th>
                                <th>Due Date</th>
                                <th>Provider ID</th>
                                <th>Latest Error</th>
                                <th>Archived</th>
                                <th>Statistics</th>
                            </tr></thead>',
      '#suffix' => '</table>',
    );
    $fieldset['fw-table']['first-row'] = array(
      '#prefix' => '<tr>',
      '#suffix' => '</tr>'
    );
    $fieldset['fw-table']['first-row']['ordername'] = array(
      '#prefix' => '<td>',
      '#markup' => $ordername,
      '#suffix' => '</td>'
    );
    $fieldset['fw-table']['first-row']['id'] = array(
      '#prefix' => '<td>',
      '#markup' => $orderid,
      '#suffix' => '</td>'
    );
    $fieldset['fw-table']['first-row']['status'] = array(
      '#prefix' => '<td>',
      '#markup' => $orderstatus,
      '#suffix' => '</td>'
    );
    $fieldset['fw-table']['first-row']['description'] = array(
      '#prefix' => '<td>',
      '#markup' => $description,
      '#suffix' => '</td>'
    );
    $fieldset['fw-table']['first-row']['po-reference'] = array(
      '#prefix' => '<td>',
      '#markup' => $poreference,
      '#suffix' => '</td>'
    );
    $fieldset['fw-table']['first-row']['due-date'] = array(
      '#prefix' => '<td>',
      '#markup' => isset($duedate) && $duedate !== NULL ? $duedate->format('D, m/d/Y - H:i:s') : "n/a",
      '#suffix' => '</td>'
    );

    $fieldset['fw-table']['first-row']['provider-id'] = array(
      '#prefix' => '<td>',
      '#markup' => $providerid,
      '#suffix' => '</td>'
    );
    $fieldset['fw-table']['first-row']['error'] = array(
      '#prefix' => '<td>',
      '#markup' => $errors,
      '#suffix' => '</td>'
    );

    $fieldset['fw-table']['first-row']['archived'] = array(
      '#prefix' => '<td>',
      '#markup' => $archived,
      '#suffix' => '</td>'
    );

    $fieldset['fw-table']['first-row']['statistics'] = array(
      '#prefix' => '<td>',
      '#markup' => $this->createMarkupForStats($jobstats),
      '#suffix' => '</td>'
    );

  }

  public function createMarkupForStats($stats) {
    $markup = "";
    if (isset($stats)) {
      $totalcompleted = t("total completed: ") . $stats->getTotalCompleted();
      $totalintrans = t("total in translation: ") . $stats->getTotalInTranslation();
      $totalreceived = t("total received: ") . $stats->getTotalReceived();
      $totalerrors = t("total errors: ") . $stats->getTotalError();
      $markup += "<p>" . $totalcompleted . "</p>";
      $markup += "<p>" . $totalreceived . "</p>";
      $markup += "<p>" . $totalintrans . "</p>";
      $markup += "<p>" . $totalerrors . "</p>";
    }
    return $markup;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here by default.
    $whotriggered = $form_state->getTriggeringElement();
    $typeofTrigger = $whotriggered['#type'];
    if ($typeofTrigger != 'select') {
      try {
        $translator = $form_state->getFormObject()->getEntity();
        $capisettings = $translator->getSetting('capi-settings');
        $settings = $form_state->getValue('settings');
        $capisettings = $settings['capi-settings'];
        $username = $capisettings['capi_username'];
        $password = $capisettings['capi_password'];
        $tokenrequest = new CreateToken(array('username' => $username, 'password' => $password));
        $capi = new TokenApi();
        $tokenobj = $capi->oauth2TokenPost($tokenrequest);
        $form_state->setValue(array('settings','capi-settings','capi_password'),'');
        $form_state->setValue(array('settings','capi-settings','token'),$tokenobj->getAccessToken());
        $capisettings['token'] = $tokenobj->getAccessToken();
        $translator->setSetting('capi-settings',$capisettings);

      }
      catch (Exception $exception) {
        \Drupal::logger('TMGMT_CONTENTAPI')->error('Failed to valideate form: %message ', [
          '%message' => $exception->getMessage(),
        ]);
        $form_state->setErrorByName('settings][capi-settings][capi_password', 'Please check your username and password Settings: ' . $exception->getMessage());
        $form_state->setErrorByName('settings][capi-settings][capi_username');
      }
    }

  }

  public function reviewForm(array $form, FormStateInterface $form_state, JobItemInterface $item) {
     // TODO: Change the autogenerated stub


    return $form;
  }

  public function reviewDataItemElement(array $form, FormStateInterface $form_state, $data_item_key, $parent_key, array $data_item, JobItemInterface $item) {
    return parent::reviewDataItemElement($form, $form_state, $data_item_key, $parent_key, $data_item, $item); // TODO: Change the autogenerated stub
  }

  public function reviewFormValidate(array $form, FormStateInterface $form_state, JobItemInterface $item) {
    parent::reviewFormValidate($form, $form_state, $item); // TODO: Change the autogenerated stub
  }

  public function reviewFormSubmit(array $form, FormStateInterface $form_state, JobItemInterface $item) {
    parent::reviewFormSubmit($form, $form_state, $item);
    $triggertby = $form_state->getTriggeringElement();
    $triggerid = $triggertby['#id'];
    // If reject button has been pressed, reject request in content api.
    if($triggerid == 'edit-reject'){
      $job = $item->getJob();
      $submittedrequestsarray = unserialize($job->getSetting('capi-remote'));
      if(isset($submittedrequestsarray) && count($submittedrequestsarray)>0){
        $arraywithrequest = $submittedrequestsarray[0];
        $itemid = $item->id();
        foreach ($arraywithrequest as $request){
          $requestSourceNativeId = explode("_",$request->getSourceNativeId())[1];
          // Check to cancel the request which belongs to the item or if all item sent in one request then all.
          if($requestSourceNativeId == $itemid || $requestSourceNativeId == 'all'){
            try {
              $translator = $job->getTranslator();
              $token = ConentApiHelper::generateToken($translator);
              $requestapi = new RequestApi();
              $arrayrequestid = new ArrayOfRequestIdsNote();$test = new Request();
              $arrayrequestid->setRequestIds(array($request->getRequestId()));
              $arrayrequestid->setNote('Translation has been rejected by Client using Drupal Connector. Please check the Translation.');
              $returnarray = $requestapi->jobsJobIdRequestsRejectPut($token,$request->getJobId(),$arrayrequestid);
              if(count($returnarray) == 1 && $returnarray[0] instanceof Request){
                $job->addMessage(t('Remote request rejected: '. $request->getRequestId()));
              }
            }
            catch (Exception $ex){
              $job->addMessage(t('Remote Job could not be rejected: ' . $ex->getMessage()),array(),'warning');
            }
          }
        }
      }

    }
    // If Item have been saved as completed, approve request, but not all.
    if($triggerid == 'edit-accept'){
      $job = $item->getJob();
      $submittedrequestsarray = unserialize($job->getSetting('capi-remote'));
      if(isset($submittedrequestsarray) && count($submittedrequestsarray)>0){
        $arraywithrequest = $submittedrequestsarray[0];
        $itemid = $item->id();
        foreach ($arraywithrequest as $request){
          $requestSourceNativeId = explode("_",$request->getSourceNativeId())[1];
          // Check to cancel the request which belongs to the item or if all item sent in one request then all.
          if($requestSourceNativeId == $itemid){
            try {
              $translator = $job->getTranslator();
              $token = ConentApiHelper::generateToken($translator);
              $requestapi = new RequestApi();
              $arrayrequestid = new ArrayOfRequestIds();
              $arrayrequestid->setRequestIds(array($request->getRequestId()));
              $returnarray = $requestapi->jobsJobIdRequestsApprovePut($token, $request->getJobId(),$arrayrequestid);
              if(count($returnarray) == 1 && $returnarray[0] instanceof Request){
                $job->addMessage(t('Remote request approved: '. $request->getRequestId()));
              }
            }
            catch (Exception $ex){
              $job->addMessage(t('Remote Job could not be approved: ' . $ex->getMessage()),array(),'warning');
            }
          }
          //TODO: Not sure if this is required, as when displaying Job details, check happens if job finished and approves all. comment out to check?
          $allaccepteditems =  $job->getItems(array('state'=>JobItemInterface::STATE_ACCEPTED));
          $allitems = $job->getItems();
          // check if all all job items excluding this one, as this one has not been saved as comleted yet, are accepted.
          if(count($allitems) == (count($allaccepteditems)+1)){
            // Generate array with requestIds to approve, all will be approved.
            try {
              $translator = $job->getTranslator();
              $token = ConentApiHelper::generateToken($translator);
              $requestapi = new RequestApi();
              $arrayrequestid = new ArrayOfRequestIds();
              $arrayrequestid->setRequestIds(array($request->getRequestId()));
              $returnarray = $requestapi->jobsJobIdRequestsApprovePut($token, $request->getJobId(),$arrayrequestid);
              $jobapi = new JobApi();
              $jobapi->jobsJobIdArchivePut($token,$request->getJobId());
              if(count($returnarray) == 1 && $returnarray[0] instanceof Request){
                $job->addMessage(t('Remote request archived: '. $request->getJobId()));
              }
            }
            catch (Exception $ex){
              $job->addMessage(t('Remote Job could not be approved: ' . $ex->getMessage()),array(),'warning');
            }
          }

        }

      }
    }

  }

}

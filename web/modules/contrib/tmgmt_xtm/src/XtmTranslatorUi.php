<?php

namespace Drupal\tmgmt_xtm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\tmgmt_xtm\Plugin\tmgmt\Translator\Connector;
use Drupal\tmgmt_xtm\Plugin\tmgmt\Translator\Helper;

/**
 * Class XtmTranslatorUi
 * @package Drupal\tmgmt_xtm
 */
class XtmTranslatorUi extends TranslatorPluginUiBase
{

    /**
     * XTM API Service constants.
     */
    const XTM_STATE_ERROR = 'ERROR';
    const XTM_STATE_FINISHED = 'FINISHED';
    const XTM_STATE_DELETED = 'DELETED';
    const TMGMT_JOB_STATE_ACTIVE = '1';
    const XTM_STATE_ACTIVE = 'ACTIVE';
    const XTM_STATE_IN_PROGRESS = 'IN_PROGRESS';
    const XTM_STATE_PARTIALLY_FINISHED = 'PARTIALLY_FINISHED';

    /**
     * @param array $form
     * @param FormStateInterface $formState
     * @param JobInterface $job
     * @return mixed
     */
    public function checkoutSettingsForm(array $form, FormStateInterface $formState, JobInterface $job)
    {
        $translator = $job->getTranslator();
        $connector = new Connector();
        $templates = $connector->getTemplates($translator);

        if (empty($templates)) {
            $form['#description'] = t("The @translator translator doesn't have any templates.",
                ['@translator' => $job->getTranslator()->label()]);
        } else {
            $form[Connector::API_TEMPLATE_ID] = [
                '#type'          => 'select',
                '#title'         => t('XTM project template'),
                '#default_value' => null,
                '#description'   => t('Select an XTM template for this project.'),
                '#options'       => ['' => t('none')] + $templates
            ];
        }

        $helper = new Helper();
        $form[Connector::API_PROJECT_MODE] = [
            '#type'          => 'radios',
            '#title'         => t('Project mode'),
            '#default_value' => !is_null($projectMode = $translator->getSetting('api_default_project_mode'))
                ? $projectMode : 0,
            '#options'       => $helper->getProjectModes(),
        ];
        return parent::checkoutSettingsForm($form, $formState, $job);
    }

    /**
     * @param array $form
     * @param FormStateInterface $formState
     * @return mixed
     */
    public function checkoutInfoAjax(array &$form, FormStateInterface $formState)
    {
        $parameters = \Drupal::routeMatch()->getParameters();
        $job = $parameters->get('tmgmt_job');
        $connector = new Connector();
        $response = $connector->checkProjectStatus($job);
        $message = '';

        if (empty($response)) {
            $message = t('Could not get project status.');
        } else {
            if ($response->status == self::XTM_STATE_ERROR) {
                drupal_set_message(t('The project has an error.') .
                    ' ' . t('Please check the project in XTM for more details.'),
                    'error', false);
            } else {
                list($finished, $jobsErrors) = $this->checkCheckoutJobs($response);

                if (empty($jobsErrors)) {
                    $message = $this->prepareCheckoutMessage($response, $job, $connector, $finished);
                } else {
                    $error = \Drupal::translation()->formatPlural(count($jobsErrors), 'The @files file has an error.',
                        'The following files: @files has an errors.', ['@files' => implode(', ', $jobsErrors)]);
                    drupal_set_message($error . ' ' . t('Please check the project in XTM for more details.'), 'error',
                        false);
                }
            }
        }
        $messageFormated = '<div class="region region-highlighted">
<div role="contentinfo" aria-label="Status message" class="messages messages--status">
<h2 class="visually-hidden">Status message</h2>
                  ' . $message . ' 
            </div> </div>';
        $form['translator_wrapper']['info']['status']['message']['wrapper'] = [
            '#markup' => $messageFormated
        ];

        return $form['translator_wrapper']['info']['status']['message'];
    }

    /**
     * @param JobInterface $job
     * @return array
     */
    public function checkoutInfo(JobInterface $job)
    {
        if (!$job->isActive()) {
            return [];
        }
        parent::checkoutInfo($job);

        $form['status']['desc'] = [
            '#markup' => t('Check for the project status in XTM. If the translation has been completed in XTM,
             but it is not available there, XTM translator will automatically retrieve the translation after 
             clicking the button below.'),
            '#prefix' => '<div class="fieldset-description" style="margin-bottom:15px">',
            '#suffix' => '</div>'
        ];
        $form['status']['message'] = [
            '#type'   => 'container',
            '#prefix' => '<div id="message-wrapper">',
            '#suffix' => '</div>'
        ];
        $form['status']['check'] = [
            '#type'  => 'button',
            '#value' => t('Check project status'),
            '#ajax'  => [
                'callback' => [$this, 'checkoutInfoAjax'],
                'method'   => 'after',
                'wrapper'  => 'message-wrapper',
                'effect'   => 'fade',
                'progress' => [
                    'type'    => 'throbber',
                    'message' => t('Checking project status...'),
                ],
            ],
        ];

        return $form;
    }

    /**
     * @param array $form
     * @param FormStateInterface $formState
     * @return array
     */
    public function buildConfigurationForm(array $form, FormStateInterface $formState)
    {

        $form = parent::buildConfigurationForm($form, $formState);
        /** @var \Drupal\tmgmt\TranslatorInterface $translator */
        $translator = $formState->getFormObject()->getEntity();
        $this->addApiKeyToForm($form, $translator);
        $this->addXtmApiClientNameToForm($form, $translator);
        $this->addXtmApiUserIdToForm($form, $translator);
        $this->addXtmApiPasswordToForm($form, $translator);
        $this->addXtmProjectCustomerIdToForm($form, $translator);
        $this->addProjectNamePrefixtToForm($form, $translator);
        $this->addDefaultProjectModeToForm($form, $translator);
        $this->addEnableTranslationCheckbox($form, $translator);
        $this->addRemoteLangsToForm($form, $translator);
        $this->addUrlToForm($form, $translator);
        $form += $this->addConnectButton();
        return $form;
    }

    /**
     * Enables Creating multiple languages XTM projects in Drupal 8 Connector functionality
     *
     * @param array $form
     * @param TranslatorInterface $translator
     */
    private function addEnableTranslationCheckbox(array &$form, TranslatorInterface $translator)
    {
        $form['multiple_language_xtm_project'] = [
            '#type' => 'checkbox',
            '#title' => "<b>". t('Enable multiple translations') . "</b>",
            '#description'   => t('There will be one project in XTM for all target languages.'),
            '#default_value' => $translator->getSetting('multiple_language_xtm_project'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function addConnectButton()
    {
        return parent::addConnectButton();
    }

    /**
     * {@inheritdoc}
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $formState)
    {
        parent::validateConfigurationForm($form, $formState);
        /** @var \Drupal\tmgmt\TranslatorInterface $translator */
        $translator = $formState->getFormObject()->getEntity();

        if ("" === $translator->getSetting('api_key')) {
            return;
        }
        if (!empty($translator->getSetting(Connector::XTM_PROJECT_CUSTOMER_ID))) {
            $customer = $translator->getPlugin()->findCustomer(
                $translator,
                $translator->getSetting(Connector::XTM_PROJECT_CUSTOMER_ID)
            );
        }
        /** @var \Drupal\tmgmt\TranslatorInterface $translator */
        if (empty($customer)) {
            $formState->setErrorByName('settings][api_key', t('Could not connect to XTM.'));
        }
    }

    /**
     * @param array $form
     * @param TranslatorInterface $translator
     */
    private function addApiKeyToForm(array &$form, TranslatorInterface $translator)
    {
        $form[Connector::XTM_API_URL] = [
            '#type'          => 'textfield',
            '#title'         => t('XTM API URL'),
            '#required'      => true,
            '#default_value' => $translator->getSetting(Connector::XTM_API_URL),
            '#description'   => t('Please enter the XTM URL'),
        ];
    }

    /**
     * @param array $form
     * @param  TranslatorInterface $translator
     */
    private function addXtmApiClientNameToForm(array &$form, TranslatorInterface $translator)
    {
        $form[Connector::XTM_API_CLIENT_NAME] = [
            '#type'          => 'textfield',
            '#title'         => t('XTM API Client name'),
            '#required'      => true,
            '#default_value' => $translator->getSetting(Connector::XTM_API_CLIENT_NAME),
            '#description'   => t('Please enter your Client name for XTM.'),
        ];
    }

    /**
     * @param array $form
     * @param TranslatorInterface $translator
     */
    private function addXtmApiUserIdToForm(array &$form, TranslatorInterface $translator)
    {
        $form[Connector::XTM_API_USER_ID] = [
            '#type'          => 'textfield',
            '#title'         => t('XTM API User ID'),
            '#required'      => true,
            '#default_value' => $translator->getSetting(Connector::XTM_API_USER_ID),
            '#description'   => t('Please enter your User ID for XTM.'),
        ];
    }

    /**
     * @param array $form
     * @param TranslatorInterface $translator
     */
    private function addXtmApiPasswordToForm(array &$form, TranslatorInterface $translator)
    {
        $form[Connector::XTM_API_PASSWORD] = [
            '#type'        => 'password',
            '#title'       => t('XTM API Password'),
            '#required'    => true,
            '#description' => t('Please enter your Password for XTM.'),
            '#attributes'  => ['value' => $translator->getSetting(Connector::XTM_API_PASSWORD)],
        ];
    }

    /**
     * @param array $form
     * @param TranslatorInterface $translator
     */
    private function addXtmProjectCustomerIdToForm(array &$form, TranslatorInterface $translator)
    {
        $form[Connector::XTM_PROJECT_CUSTOMER_ID] = [
            '#type'          => 'textfield',
            '#title'         => t('XTM project Customer ID'),
            '#required'      => true,
            '#default_value' => $translator->getSetting(Connector::XTM_PROJECT_CUSTOMER_ID),
            '#description'   => t('Please enter the project Customer ID'),
        ];
    }

    /**
     * @param array $form
     * @param TranslatorInterface $translator
     */
    private function addProjectNamePrefixtToForm(array &$form, TranslatorInterface $translator)
    {
        $form[Connector::PROJECT_NAME_PREFIX] = [
            '#type'          => 'textfield',
            '#title'         => t('Project name prefix '),
            '#required'      => true,
            '#default_value' => $translator->getSetting(Connector::PROJECT_NAME_PREFIX),
            '#description'   => t('Enter a name prefix for new projects in XTM. Leave blank to disable prefix.'),
        ];
    }

    /**
     * @param array $form
     * @param TranslatorInterface $translator
     */
    private function addDefaultProjectModeToForm(array &$form, TranslatorInterface $translator)
    {
        $form['default_project_mode'] = [
            '#type'          => 'radios',
            '#title'         => t('Default project mode'),
            '#required'      => true,
            '#default_value' => $translator->getSetting('default_project_mode'),
            '#description'   => t('Default mode of the project selected in job checkout.'),
            '#options'       => [
                t('Single file - translation returned at the end of the project'),
                t('Multiple files - translation returned when each file is complete '),
                t('Multiple files - translation returned when all files are complete '),
            ]
        ];
    }

    /**
     * @param array $form
     * @param TranslatorInterface $translator
     */
    private function addRemoteLangsToForm(array &$form, TranslatorInterface $translator)
    {
        $helper = new Helper();
        foreach ($translator->getRemoteLanguagesMappings() as $localLanguage => $remoteLanguage) {
            $language = $helper->getXtmLanguage();
            $options = [];
            if (isset($language[$localLanguage][0])) {
                foreach ($language[$localLanguage] as $language) {
                    $langNames = array_values($language);
                    $options[key($language)] = reset($langNames);
                }
            } else {
                if (isset($language[$localLanguage])) {
                    $langNames = array_values($language[$localLanguage]);
                    $options[key($language[$localLanguage])] = reset($langNames);
                }
            }
            $form['plugin_wrapper']['remote_languages_mappings'][$localLanguage] = [
                '#attributes'    => ['style' => 'min-width:220px'],
                '#title'         => $localLanguage,
                '#default_value' => (string)$helper->mapLanguageToXTMFormat($localLanguage, $translator),
                '#type'          => 'select',
                '#options'       => $options,
            ];

            if (true === empty($options)) {
                $form['plugin_wrapper']['remote_languages_mappings'][$localLanguage]['#type'] = 'textfield';
                $form['plugin_wrapper']['remote_languages_mappings'][$localLanguage]['#size'] = 10;
                $form['plugin_wrapper']['remote_languages_mappings'][$localLanguage]['#default_value']
                    = $helper->mapLanguageToXTMFormat($localLanguage, $translator);
                unset($form['plugin_wrapper']['remote_languages_mappings'][$localLanguage]['#options']);
            }
        }
    }

    /**
     * @param $action
     * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
     */
    private function getReadableState($action)
    {
        switch ($action) {
            case self::XTM_STATE_ACTIVE:
                return t('active');

            case self::XTM_STATE_IN_PROGRESS:
                return t('in progress');

            case self::XTM_STATE_FINISHED:
                return t('finished');

            case self::XTM_STATE_ERROR:
                return t('error');

            case self::XTM_STATE_PARTIALLY_FINISHED:
                return t('partially finished');

            default:
                return '';
        }
    }

    /**
     * @param $response
     * @param Job $job
     * @param $connector
     * @param $finished
     * @return string
     */
    private function prepareCheckoutMessage($response, Job $job, Connector $connector, $finished)
    {

        $message = [
            t('The project status is <b>@state</b>.',
                ['@state' => $this->getReadableState($response->status)])
        ];
        if ($job->getState() == self::TMGMT_JOB_STATE_ACTIVE
            && $response->status == self::XTM_STATE_FINISHED
        ) {
            if (true === $connector->retrieveTranslation($job)) {
                $message[] = t('The translation has been received. Please refresh the page (F5).');
            } else {
                $message[] = t('<b>We were unable to retrieve translation. Check XTM settings</b>');
            }
        } else {
            if ($finished > 0) {
                $message[] = t('Finished tasks: @jobs.',
                    ['@jobs' => $finished . '/' . count($response->jobs)]);
            }
        }

        return implode(' ', $message);
    }

    /**
     * @param $response
     * @return array
     */
    private function checkCheckoutJobs($response)
    {
        $finished = 0;
        $jobsErrors = [];

        foreach ($response->jobs as $jobFile) {
            if ($jobFile->status == self::XTM_STATE_FINISHED) {
                $finished++;
            } else {
                if ($jobFile->status == self::XTM_STATE_ERROR) {
                    $jobsErrors[] = $jobFile->fileName;
                }
            }
        }
        return [$finished, $jobsErrors];
    }

    /**
     * @param array $form
     * @param \Drupal\tmgmt\TranslatorInterface $translator
     */
    private function addUrlToForm(array &$form, $translator)
    {
        $form['url'] = [
            '#type'          => 'hidden',
            '#default_value' => $translator->getSetting('url'),
        ];
    }
}

<?php

namespace Drupal\tmgmt_xtm\Plugin\tmgmt\Translator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt_xtm\XtmTranslatorUi;

/**
 *
 * Class JobEdit
 * @package Drupal\tmgmt_xtm\Plugin\tmgmt\Translator
 */
class JobEdit
{
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

        /** @var object $response */
        if (empty($response)) {
            $message = t('Could not get project status.');
        } else {
            if ($response->status == XtmTranslatorUi::XTM_STATE_ERROR) {
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
     * @param array $form
     * @param FormStateInterface $formState
     */
    public function editForm(array &$form, FormStateInterface $formState)
    {
        /** @var \Drupal\tmgmt\Entity\Job $job */
        $job = $formState->getFormObject()->getEntity();
        /** @var \Drupal\tmgmt\Entity\Translator $translator */
        $translator = $job->getTranslator();

        if ('xtm' === $translator->getPluginId() && $job->isContinuous()) {
            $form['status']=[
                '#type' => 'fieldset',
                '#title' => t('XTM status'),
            ];
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
        }
    }


    /**
     * @param $action
     * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
     */
    private function getReadableState($action)
    {
        switch ($action) {
            case XtmTranslatorUi::XTM_STATE_ACTIVE:
                return t('active');

            case XtmTranslatorUi::XTM_STATE_IN_PROGRESS:
                return t('in progress');

            case XtmTranslatorUi::XTM_STATE_FINISHED:
                return t('finished');

            case XtmTranslatorUi::XTM_STATE_ERROR:
                return t('error');

            case XtmTranslatorUi::XTM_STATE_PARTIALLY_FINISHED:
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

        if ($job->getState() == Job::STATE_CONTINUOUS
            && $response->status == XtmTranslatorUi::XTM_STATE_FINISHED
        ) {
            if (true === $connector->retrieveTranslation($job)) {
                $message[] = t('The translation has been received.');
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
            if ($jobFile->status == XtmTranslatorUi::XTM_STATE_FINISHED) {
                $finished++;
            } else {
                if ($jobFile->status == XtmTranslatorUi::XTM_STATE_ERROR) {
                    $jobsErrors[] = $jobFile->fileName;
                }
            }
        }
        return [$finished, $jobsErrors];
    }

}

<?php

namespace Drupal\tmgmt_xtm\Plugin\tmgmt\Translator;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\Translator;
use Masterminds\HTML5\Exception;

/**
 *
 * Class Cart
 * @package Drupal\tmgmt_xtm\Plugin\tmgmt\Translator
 */
class Cart
{
    /**
     * @param array $job
     * @param array $jobItems
     * @param array $context
     */
    public function requestOperationMultiple(array $job, array $jobItems, array &$context)
    {

        $translator = $this->getTranslator($job['translator']);
        if (is_null($translator)) {
            drupal_set_message(t('No translator available.'));
            return;
        }
        $helper = new Helper();
        $jobItemsBySourceLanguage = $this->groupSelectedItemsBySourceLanguage($jobItems, $job);
        $targetLanguages = $job['target_languages'];
        $jobs = $jobResults = $removeJobItemIds = [];
        foreach ($targetLanguages as $targetLanguage) {
            foreach ($jobItemsBySourceLanguage as $sourceLanguage => $job_items) {
                if ($sourceLanguage == $targetLanguage) {
                    continue;
                }
                $jobModel = tmgmt_job_create($sourceLanguage,
                    $targetLanguage,
                    \Drupal::currentUser()->id()
                );
                $jobEmptyFlag = true;
                foreach ($job_items as $id => $jobItem) {
                    /** @var JobItem $jobItem */
                    try {
                        $jobModel->addItem($jobItem->getPlugin(), $jobItem->getItemType(), $jobItem->getItemId());
                        $jobItemId = $jobItem->id();
                        $removeJobItemIds[$jobItemId] = $jobItemId;
                        $jobResults['job_items'][$jobItemId] = $jobItemId;
                        $jobEmptyFlag = false;
                    } catch (Exception $e) {
                        unset($jobItemsBySourceLanguage[$sourceLanguage][$id]);
                        drupal_set_message($e->getMessage(), 'error');
                    }
                }
                if ($job['enforced_source_language']) {
                    $jobModel->set('source_language', $job['forced_source_language']);
                }
                $this->createJobs($job, $jobEmptyFlag, $jobModel, $jobs);
            }
            $this->removeJobItemsFromCart($removeJobItemIds);
        }

        if (!$jobs) {
            drupal_set_message(t('From the selection you made it was not possible to create any translation job..'));
            return;
        }

        if ($job['enforced_source_language']) {
            $this->createDrupalMessageOnEnforceSourceLanguage();
            $this->createSkippedCountMessage(0, $job['enforced_source_language']);
        }

        $connector = new MultipleTargetLanguageConnector();
        $connector->xtmRequestTranslation($jobs);

        $context['results'][] = $jobResults;
        $context['message'] = t('Creating a project for the language "@name".',
            ['@name' => $helper->mapLanguageToXTMFormat($job['source_language'], $translator),]);

        drupal_set_message(t('The batch form has been submitted successfully.'));
    }

    /**
     * @param array $job
     * @param array $jobItems
     * @param array $context
     */
    public function requestOperation(array $job, array $jobItems, array &$context)
    {
        $translator = $this->getTranslator($job['translator']);
        if (is_null($translator)) {
            drupal_set_message(t('No translator available.'));
            return;
        }
        $helper = new Helper();
        $jobItemsBySourceLanguage = $this->groupSelectedItemsBySourceLanguage($jobItems, $job);
        $targetLanguage = $job['target_language'];
        $jobs = $jobResults = $removeJobItemIds = [];

        foreach ($jobItemsBySourceLanguage as $sourceLanguage => $job_items) {
            if ($sourceLanguage == $targetLanguage) {
                continue;
            }

            $jobModel = tmgmt_job_create($sourceLanguage, $targetLanguage, \Drupal::currentUser()->id());
            $jobEmptyFlag = true;

            foreach ($job_items as $id => $jobItem) {
                /** @var JobItem $jobItem */
                try {
                    $jobModel->addItem($jobItem->getPlugin(), $jobItem->getItemType(), $jobItem->getItemId());
                    $jobItemId = $jobItem->id();
                    $removeJobItemIds[$jobItemId] = $jobItemId;
                    $jobResults['job_items'][$jobItemId] = $jobItemId;
                    $jobEmptyFlag = false;
                } catch (Exception $e) {
                    unset($jobItemsBySourceLanguage[$sourceLanguage][$id]);
                    drupal_set_message($e->getMessage(), 'error');
                }
            }
            $this->createJobs($job, $jobEmptyFlag, $jobModel, $jobs);
        }
        $this->removeJobItemsFromCart($removeJobItemIds);

        if (!$job) {
            drupal_set_message(t('From the selection you made it was not possible to create any translation job..'));
            return;
        }

        if ($job['enforced_source_language']) {
            $this->createDrupalMessageOnEnforceSourceLanguage();
            $this->createSkippedCountMessage(0, $job['enforced_source_language']);
        }

        $this->doXtmTranslation($jobs);

        $context['results'][] = $jobResults;
        $context['message'] = t('Creating a project for the langauge "@name".',
            ['@name' => $helper->mapLanguageToXTMFormat($job['source_language'], $translator),]);

        drupal_set_message(t('The batch form has been submitted successfully.'));
    }

    /**
     * @param FormStateInterface $formState
     */
    public function formSubmit(FormStateInterface $formState)
    {
        $jobItemsBySourceLanguage = [];
        $jobItemsLoad = JobItem::loadMultiple(array_filter($formState->getValue('items')));

        foreach ($jobItemsLoad as $jobItem) {
            /** @var JobItem $jobItem */
            $jobItemsBySourceLanguage[$jobItem->getSourceLangCode()][$jobItem->id()] = $jobItem;
        }

        $multiLang = $formState->getValue('multi_lang');
        $translator = $this->getTranslator($multiLang['xtm_translator']);
        if (is_null($translator)) {
            return;
        }
        $multipleLanguageXtmProject = $translator->getSetting('multiple_language_xtm_project');
        $operations = [];
        if (1 === $multipleLanguageXtmProject) {
            foreach ($jobItemsBySourceLanguage as $sourceLanguage => $jobItems) {
                $operations = $this->prepareMultipleOperations($formState, $sourceLanguage,
                    array_filter($formState->getValue('target_language')), $jobItems,
                    $operations);
            }
        } else {
            foreach (array_filter($formState->getValue('target_language')) as $targetLanguage) {
                foreach ($jobItemsBySourceLanguage as $sourceLanguage => $jobItems) {
                    if ($sourceLanguage == $targetLanguage) {
                        continue;
                    }
                    $operations = $this->prepareOperations($formState, $sourceLanguage, $targetLanguage, $jobItems,
                        $operations);
                }
            }
        }

        $batch = [
            'operations'       => $operations,
            'finished'         => 'tmgmt_xtm_cart_request_operations_finished',
            'title'            => t('Creating multiple projects'),
            'progress_message' => t('Processed @current out of @total projects.'),
            'error_message'    => t('XTM translator has encountered an error.')
        ];

        batch_set($batch);
    }

    /**
     * @param array $form
     * @param FormState $formState
     * @return array
     */
    public function getCartContentTemplates(array &$form, FormState $formState)
    {
        $multiLang = $formState->getValue('multi_lang');
        $translator = $this->getTranslator($multiLang['xtm_translator']);

        if ($translator instanceof Translator) {
            $form['multi_lang']['settings_fieldset']['template'] = $this->getTemplateForm($translator);
        }

        return $form['multi_lang']['settings_fieldset'];
    }

    /**
     * Returns Cart Form for  XTM one-click multilingual projects
     * @param array $form
     */
    public function getForm(array &$form)
    {
        $availableTranslators = [];
        $translator = null;
        $translators = $this->getXtmTranslators();
        foreach ($translators as $translator) {
            /** @var Translator $translator */
            $translatorId = $translator->id();
            $availableTranslators[$translatorId] = $translator->label();
        }

        if (empty($availableTranslators)) {
            return;
        }

        $index = array_search('target_language', array_keys($form)) + 1;
        $form = array_slice($form, 0, $index, true) + ['multi_lang' => []] + array_slice($form, $index,
                count($form) - $index, true);
        $this->createMultiLangForm($form, $availableTranslators);
        if ($translator instanceof Translator) {
            $form['multi_lang']['settings_fieldset']['template'] = $this->getTemplateForm($translator);
            $form['multi_lang']['settings_fieldset']['project_mode'] = $this->getProjectModeForm($translator);
        }

        $form['multi_lang']['request_multiple_translations'] = [
            '#type'     => 'submit',
            '#value'    => t('Request multiple translations'),
            '#submit'   => ['tmgmt_xtm_cart_request_translation_form_submit'],
            '#validate' => ['tmgmt_xtm_cart_source_overview_validate']
        ];
    }

    /**
     * @param array $form
     * @param array $availableTranslators
     */
    private function createMultiLangForm(array &$form, array $availableTranslators)
    {
        $form['multi_lang'] = [
            '#tree'        => true,
            '#type'        => 'fieldset',
            '#title'       => t('XTM one-click multilingual projects'),
            '#description' => t('Here you can request a translation with multiple target languages in XTM,
             with one click.
              It works like a cart, but without forwarding or forcing the user to click "Continue" for each job.'),
        ];

        $form['multi_lang']['xtm_translator'] = [
            '#type'          => 'select',
            '#title'         => t('XTM translator'),
            '#default_value' => null,
            '#description'   => t('Select a translator.'),
            '#options'       => ['' => t('- Please choose -')] + $availableTranslators,
            '#ajax'          => [
                'wrapper'  => 'templates-fieldset-wrapper',
                'callback' => 'ajax_tmgmt_xtm_cart_content_templates',
                'progress' => [
                    'type'    => 'throbber',
                    'message' => t('Please wait, searching for available templates...')
                ]
            ]
        ];

        $form['multi_lang']['settings_fieldset'] = [
            '#type'   => 'container',
            '#prefix' => '<div id="templates-fieldset-wrapper">',
            '#suffix' => '</div>',
        ];
    }

    /**
     * @param Translator $translator
     * @return array
     */
    private function getTemplateForm(Translator $translator)
    {
        $connector = new Connector();
        $templates = $connector->getTemplates($translator);

        if (empty($templates)) {
            $templateForm = [
                '#markup' => t('<p>There are no templates for the selected translator.</p>')
            ];
            return $templateForm;
        } else {
            $templateForm = [
                '#type'          => 'select',
                '#title'         => t('XTM project template'),
                '#default_value' => null,
                '#description'   => t('Select an XTM template for each project.'),
                '#options'       => ['' => t('none')] + $templates,
                '#name'          => 'multi_lang[settings_fieldset][template]',
                '#id'            => 'edit-multi-lang-settings-fieldset-template',
                '#validated'     => true
            ];
            return $templateForm;
        }
    }

    /**
     * @param Translator $translator
     * @return array
     */
    private function getProjectModeForm(Translator $translator)
    {
        $helper = new Helper();
        $projectModeForm = [
            '#type'          => 'radios',
            '#title'         => t('Project mode'),
            '#default_value' => !is_null($projectMode = $translator->getSetting('api_default_project_mode'))
                ? $projectMode : 0,
            '#options'       => $helper->getProjectModes(),
        ];
        return $projectModeForm;
    }

    /**
     * @return Translator | null
     * @param String @name
     */
    private function getTranslator($name = "")
    {
        $translator = null;
        foreach (tmgmt_translator_load_available(null) as $translator) {
            /** @var Translator $translator */
            if ($translator->id() == $name) {
                break;
            }
        }
        return $translator;
    }

    /**
     * @return array
     */
    private function getXtmTranslators()
    {
        $translators = [];
        foreach (tmgmt_translator_load_available(null) as $translator) {
            /** @var Translator $translator */
            if ($translator->getPluginId() == 'xtm') {
                $translators[] = $translator;
            }
        }
        return $translators;
    }

    /**
     * @param $skippedCount
     * @param $enforcedSourceLanguage
     */
    private function createSkippedCountMessage($skippedCount, $enforcedSourceLanguage)
    {
        if ($skippedCount) {
            $languages = \Drupal::languageManager()->getLanguages();
            drupal_set_message(\Drupal::translation()->formatPlural($skippedCount,
                'One item skipped as for the language @language it was not possible to retrieve a translation.',
                '@count items skipped as for the language @language it was not possible to retrieve a translations.',
                ['@language' => $languages[$enforcedSourceLanguage]->getName()]));
        }
    }

    /**
     * Remove job items from the cart.
     * @param $removeJobItemIds
     */
    private function removeJobItemsFromCart($removeJobItemIds)
    {
        if ($removeJobItemIds) {
            tmgmt_cart_get()->removeJobItems($removeJobItemIds);
            entity_delete_multiple('tmgmt_job_item', $removeJobItemIds);
        }
    }

    /**
     * Creates jobs for multiple target language XTM projects
     * @param FormStateInterface $formState
     * @param $sourceLanguage
     * @param $targetLanguage
     * @param $jobItems
     * @param $operations
     * @return array
     */
    private function prepareMultipleOperations(
        FormStateInterface $formState,
        $sourceLanguage,
        $targetLanguage,
        $jobItems,
        $operations
    ) {
        $multiLang = $formState->getValue('multi_lang');

        if (!isset($multiLang['settings_fieldset']['template'])) {
            $multiLang['settings_fieldset']['template'] = "";
        }

        $enforcedSourceLanguage = $formState->getValue('enforced_source_language');
        $forcedSourceLanguage = $formState->getValue('enforced_source_language')
            ? $formState->getValue('source_language') : false;
        $operations[] = [
            'tmgmt_xtm_cart_request_operation_multiple',
            [
                [
                    'source_language'          => $sourceLanguage,
                    'target_languages'         => $targetLanguage,
                    'user_id'                  => \Drupal::currentUser()->id(),
                    'translator'               => $multiLang['xtm_translator'],
                    'template'                 => $multiLang['settings_fieldset']['template'],
                    'project_mode'             => $multiLang['settings_fieldset']['project_mode'],
                    'enforced_source_language' => $enforcedSourceLanguage,
                    'forced_source_language'   => $forcedSourceLanguage,
                ],
                $jobItems
            ]
        ];
        return $operations;
    }

    /**
     * @param FormStateInterface $formState
     * @param $sourceLanguage
     * @param $targetLanguage
     * @param JobItem $jobItems
     * @param array $operations
     * @return array
     */
    private function prepareOperations(
        FormStateInterface $formState,
        $sourceLanguage,
        $targetLanguage,
        $jobItems,
        $operations
    ) {
        $multiLang = $formState->getValue('multi_lang');

        if (!isset($multiLang['settings_fieldset']['template'])) {
            $multiLang['settings_fieldset']['template'] = "";
        }
        $forcedSourceLanguage = $formState->getValue('enforced_source_language')
            ? $formState->getValue('source_language') : false;

        $operations[] = [
            'tmgmt_xtm_cart_request_operation',
            [
                [
                    'source_language'          => $sourceLanguage,
                    'target_language'          => $targetLanguage,
                    'user_id'                  => \Drupal::currentUser()->id(),
                    'translator'               => $multiLang['xtm_translator'],
                    'template'                 => $multiLang['settings_fieldset']['template'],
                    'project_mode'             => $multiLang['settings_fieldset']['project_mode'],
                    'enforced_source_language' => $formState->getValue('enforced_source_language'),
                    'forced_source_language'   => $forcedSourceLanguage,

                ],
                $jobItems
            ]
        ];
        return $operations;
    }

    /**
     * @param array $job
     * @param $jobEmpty
     * @param Job $jobModel
     * @param $jobs
     */
    private function createJobs(array &$job, $jobEmpty, $jobModel, &$jobs)
    {
        if ($jobEmpty) {
            return;
        }
        $translator = $this->getTranslator($job['translator']);
        $jobModel->set('translator', $translator);
        $settings = [];
        if (isset($job['template'])) {
            $settings[Connector::API_TEMPLATE_ID] = $job['template'];
        }

        if (isset($job['project_mode'])) {
            $settings[Connector::API_PROJECT_MODE] = $job['project_mode'];
        }
        $jobModel->set('settings', $settings);
        $jobModel->save();
        $jobs[] = $jobModel;
    }

    /**
     *
     */
    private function createDrupalMessageOnEnforceSourceLanguage()
    {
        drupal_set_message(t('You have enforced the job source language which most likely resulted in having a
                 translation of your original content as the job source text.
                  You should review the job translation received from the translator carefully to prevent
                   the content quality loss.'),
            'warning');
    }

    /**
     * @param $jobs
     */
    private function doXtmTranslation($jobs)
    {
        /** TODO add condition */
        $connector = new Connector();
        foreach ($jobs as $job) {
            $connector->xtmRequestTranslation($job);
        }
    }


    /**
     * @param array $jobItems
     * @param $job
     * @return array
     */
    private function groupSelectedItemsBySourceLanguage(array $jobItems, $job)
    {
        $enforcedSourceLanguage = null;
        if ($job['enforced_source_language']) {
            $enforcedSourceLanguage = $job['forced_source_language'];
        }
        $skippedCount = 0;
        $jobItemsBySourceLanguage = [];

        foreach ($jobItems as $jobItem) {
            $sourceLanguage = $enforcedSourceLanguage ? $enforcedSourceLanguage : $jobItem->getSourceLangCode();

            /** @var JobItem $jobItem */
            if (in_array($sourceLanguage, $jobItem->getExistingLangCodes())) {
                $jobItemsBySourceLanguage[$sourceLanguage][$jobItem->id()] = $jobItem;
            } else {
                $skippedCount++;
            }
        }

        if ($skippedCount > 0) {
            $languages = \Drupal::languageManager()->getLanguages();
            drupal_set_message(\Drupal::translation()->formatPlural($skippedCount,
                'One item skipped as for the language @language it was not possible to retrieve a translation.',
                '@count items skipped as for the language @language it was not possible to retrieve a translations.',
                ['@language' => $languages[$enforcedSourceLanguage]->getName()]));
        }

        return $jobItemsBySourceLanguage;
    }
}

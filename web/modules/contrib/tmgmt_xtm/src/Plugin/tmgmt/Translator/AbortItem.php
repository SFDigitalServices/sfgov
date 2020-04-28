<?php

namespace Drupal\tmgmt_xtm\Plugin\tmgmt\Translator;

use Drupal\Core\Form\FormStateInterface;

/**
 *
 * Class AbortItem
 * @package Drupal\tmgmt_xtm\Plugin\tmgmt\Translator
 */
class AbortItem
{
    /**
     * @param array $form
     */
    public function getForm(array &$form)
    {
        $form['fieldset'] = [
            '#type'   => 'container',
            '#prefix' => '<div id="templates-fieldset-wrapper">'
                . t('XTM Continuous projects will be moved to archived.'),
            '#suffix' => '</div>',
        ];
        $form['actions']['submit']['#submit'][] = 'tmgmt_xtm_form_tmgmt_job_item_abort_form_submit';
    }

    /**
     * @param FormStateInterface $formState
     */
    public function submitForm(FormStateInterface $formState)
    {
        /** @var \Drupal\tmgmt\Entity\JobItem $jobItem */
        $jobItem = $formState->getFormObject()->getEntity();
        /** @var \Drupal\tmgmt\Entity\Translator $translator */
        $translator = $jobItem->getTranslator();
        $job = $jobItem->getJob();

        if ('xtm' === $translator->getPluginId() && $job->isContinuous()) {
            $connector = new Connector();
            $connector->updateProjectActivity($job);
        }
    }


}

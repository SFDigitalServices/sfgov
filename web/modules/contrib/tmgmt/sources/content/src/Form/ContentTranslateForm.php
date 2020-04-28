<?php

namespace Drupal\tmgmt_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\TMGMTException;
use Drupal\Core\Form\FormStateInterface;

class ContentTranslateForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_content_translate_form';
  }

  /**
   * {@inheritdoc}
   */
  function buildForm(array $form, FormStateInterface $form_state, array $build = NULL) {
    // Store the entity in the form state so we can easily create the job in the
    // submit handler.

    $form_state->set('entity', $build['#entity']);

    $overview = $build['content_translation_overview'];

    $form['#title'] = $this->t('Translations of @title', array('@title' => $build['#entity']->label()));

    $form['actions'] = array(
      '#type' => 'details',
      '#title' => t('Operations'),
      '#open' => TRUE,
      '#attributes' => array('class' => array('tmgmt-source-operations-wrapper'))
    );
    $form['actions']['request'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' =>$this->t('Request translation'),
      '#submit' => array('::submitForm'),
    );
    tmgmt_add_cart_form($form['actions'], $form_state, 'content', $form_state->get('entity')->getEntityTypeId(), $form_state->get('entity')->id());

    // Inject our additional column into the header.
    array_splice($overview['#header'], -1, 0, array(t('Pending Translations')));
    // Make this a tableselect form.
    $form['languages'] = array(
      '#type' => 'tableselect',
      '#header' => $overview['#header'],
      '#options' => array(),
    );
    $languages = \Drupal::languageManager()->getLanguages();
    // Check if there is a job / job item that references this translation.
    $entity_langcode = $form_state->get('entity')->language()->getId();
    $items = tmgmt_job_item_load_latest('content', $form_state->get('entity')->getEntityTypeId(), $form_state->get('entity')->id(), $entity_langcode);
    foreach ($languages as $langcode => $language) {
      if ($langcode == LanguageInterface::LANGCODE_DEFAULT) {
        // Never show language neutral on the overview.
        continue;
      }
      // Since the keys are numeric and in the same order we can shift one element
      // after the other from the original non-form rows.
      $option = array_shift($overview['#rows']);
      if ($langcode == $entity_langcode) {
        $additional = array('data' => array('#markup' => '<strong>' . t('Source') . '</strong>'));
        // This is the source object so we disable the checkbox for this row.
        $form['languages'][$langcode] = array(
          '#type' => 'checkbox',
          '#disabled' => TRUE,
        );
      }
      elseif (isset($items[$langcode])) {
        $item = $items[$langcode];
        $states = JobItem::getStates();
        $path = \Drupal::routeMatch()->getRouteName() ? Url::fromRouteMatch(\Drupal::routeMatch())->getInternalPath() : '';
        $destination = array('destination' => $path);
        $additional = \Drupal::l($item->getStateIcon() ?: $states[$item->getState()], $item->toUrl()->setOption('query', $destination));
        // Disable the checkbox for this row since there is already a translation
        // in progress that has not yet been finished. This way we make sure that
        // we don't stack multiple active translations for the same item on top
        // of each other.
        $form['languages'][$langcode] = array(
          '#type' => 'checkbox',
          '#disabled' => TRUE,
        );
      }
      else {
        // There is no translation job / job item for this target language.
        $additional = t('None');
      }
      // Inject the additional column into the array.

      // The generated form structure has changed, support both an additional
      // 'data' key (that is not supported by tableselect) and the old version
      // without.
      if (isset($option['data'])) {
        array_splice($option['data'], -1, 0, array($additional));
        // Append the current option array to the form.
        $form['languages']['#options'][$langcode] = $option['data'];
      }
      else {
        array_splice($option, -1, 0, array($additional));
        // Append the current option array to the form.
        $form['languages']['#options'][$langcode] = $option;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    $selected = array_filter($form_state->getValue('languages'));
    if (empty($selected)) {
      $form_state->setErrorByName('languages', $this->t('You have to select at least one language for requesting a translation.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->get('entity');
    $values = $form_state->getValues();
    $jobs = array();
    foreach (array_keys(array_filter($values['languages'])) as $langcode) {
      // Create the job object.
      $job = tmgmt_job_create($entity->language()->getId(), $langcode, \Drupal::currentUser()->id());
      try {
        // Add the job item.
        $job->addItem('content', $entity->getEntityTypeId(), $entity->id());
        // Append this job to the array of created jobs so we can redirect the user
        // to a multistep checkout form if necessary.
        $jobs[$job->id()] = $job;
      }
      catch (TMGMTException $e) {
        watchdog_exception('tmgmt', $e);
        $languages = \Drupal::languageManager()->getLanguages();
        $target_lang_name = $languages[$langcode]->language;
        $this->messenger()->addError(t('Unable to add job item for target language %name. Make sure the source content is not empty.', array('%name' => $target_lang_name)));
      }
    }
    \Drupal::service('tmgmt.job_checkout_manager')->checkoutAndRedirect($form_state, $jobs);
  }

}

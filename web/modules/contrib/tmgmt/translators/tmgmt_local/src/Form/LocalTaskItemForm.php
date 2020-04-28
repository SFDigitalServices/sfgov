<?php

namespace Drupal\tmgmt_local\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\tmgmt\SourcePreviewInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\tmgmt_local\Entity\LocalTaskItem;
use Drupal\tmgmt_local\LocalTaskInterface;
use Drupal\views\Views;

/**
 * Form controller for the localTaskItem edit forms.
 *
 * @ingroup tmgmt_local_task_item
 */
class LocalTaskItemForm extends ContentEntityForm {

  /**
   * The task item.
   *
   * @var \Drupal\tmgmt_local\Entity\LocalTaskItem
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $task_item = $this->entity;

    $form['#title'] = $task_item->label();

    $job_item = $task_item->getJobItem();
    $job = $job_item->getJob();

    $form['info'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('tmgmt-local-task-info', 'clearfix')),
      '#weight' => 0,
    );

    $url = $job_item->getSourceUrl();
    $form['info']['source'] = array(
      '#type' => 'item',
      '#title' => t('Source'),
      '#markup' => $url ? Link::fromTextAndUrl($job_item->getSourceLabel(), $url)->toString() : $job_item->getSourceLabel(),
      '#prefix' => '<div class="tmgmt-ui-source tmgmt-ui-info-item">',
      '#suffix' => '</div>',
    );

    $form['info']['sourcetype'] = array(
      '#type' => 'item',
      '#title' => t('Source type'),
      '#markup' => $job_item->getSourceType(),
      '#prefix' => '<div class="tmgmt-ui-source-type tmgmt-ui-info-item">',
      '#suffix' => '</div>',
    );

    $form['info']['source_language'] = array(
      '#type' => 'item',
      '#title' => t('Source language'),
      '#markup' => $job_item->getJob()->getSourceLanguage()->getName(),
      '#prefix' => '<div class="tmgmt-ui-source-language tmgmt-ui-info-item">',
      '#suffix' => '</div>',
    );

    $form['info']['target_language'] = array(
      '#type' => 'item',
      '#title' => t('Target language'),
      '#markup' => $job_item->getJob()->getTargetLanguage()->getName(),
      '#prefix' => '<div class="tmgmt-ui-target-language tmgmt-ui-info-item">',
      '#suffix' => '</div>',
    );

    $form['info']['changed'] = array(
      '#type' => 'item',
      '#title' => t('Last change'),
      '#value' => $task_item->getChangedTime(),
      '#markup' => \Drupal::service('date.formatter')->format($task_item->getChangedTime()),
      '#prefix' => '<div class="tmgmt-ui-changed tmgmt-ui-info-item">',
      '#suffix' => '</div>',
    );

    $statuses = LocalTaskItem::getStatuses();
    $form['info']['status'] = array(
      '#type' => 'item',
      '#title' => t('Status'),
      '#markup' => $statuses[$task_item->getStatus()],
      '#prefix' => '<div class="tmgmt-ui-task-item-status tmgmt-ui-info-item">',
      '#suffix' => '</div>',
      '#value' => $task_item->getStatus(),
    );

    $task = $task_item->getTask();
    $url = $task->toUrl();
    $form['info']['task'] = array(
      '#type' => 'item',
      '#title' => t('Task'),
      '#markup' => Link::fromTextAndUrl($task->label(), $url)->toString(),
      '#prefix' => '<div class="tmgmt-ui-task tmgmt-ui-info-item">',
      '#suffix' => '</div>',
    );

    if ($job->getSetting('job_comment')) {
      $form['job_comment'] = array(
        '#type' => 'item',
        '#title' => t('Job comment'),
        '#markup' => Xss::filter($job->getSetting('job_comment')),
      );
    }

    $form['translation'] = array(
      '#type' => 'container',
    );

    // Build the translation form.
    $data = $task_item->getData();

    // Need to keep the first hierarchy. So flatten must take place inside
    // of the foreach loop.
    $zebra = 'even';
    foreach (Element::children($data) as $key) {
      $flattened = \Drupal::service('tmgmt.data')->flatten($data[$key], $key);
      $form['translation'][$key] = $this->formElement($flattened, $task_item, $zebra);
    }

    $form['footer'] = tmgmt_color_local_review_legend();
    $form['#attached']['library'][] = 'tmgmt/admin';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    /** @var LocalTaskItem $task_item */
    $task_item = $this->entity;

    $actions['save_as_completed'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#validate' => ['::validateSaveAsComplete'],
      '#submit' => ['::save', '::saveAsComplete'],
      '#access' => $task_item->isPending(),
      '#value' => t('Save as completed'),
    );

    $actions['save'] = array(
      '#type' => 'submit',
      '#submit' => ['::save'],
      '#access' => $task_item->isPending(),
      '#value' => t('Save'),
    );

    $job_item = $task_item->getJobItem();
    if ($job_item->getSourcePlugin() instanceof SourcePreviewInterface && $job_item->getSourcePlugin()->getPreviewUrl($job_item)) {
      $actions['preview'] = [
        '#type' => 'submit',
        '#submit' => ['::save', '::preview'],
        '#access' => $task_item->isPending(),
        '#value' => t('Preview'),
      ];
    }

    return $actions;
  }

  /**
   * Builds a translation form element.
   *
   * @param array $data
   *   Data of the translation.
   * @param LocalTaskItem $item
   *   The LocalTaskItem.
   * @param string $zebra
   *   Tell is translation is odd or even.
   *
   * @return array
   *   Render array with the translation element.
   */
  private function formElement(array $data, LocalTaskItem $item, &$zebra) {
    static $flip = array(
      'even' => 'odd',
      'odd' => 'even',
    );

    $form = [];

    $job = $item->getJobItem()->getJob();
    $language_list = \Drupal::languageManager()->getLanguages();

    foreach (Element::children($data) as $key) {
      if (isset($data[$key]['#text']) && \Drupal::service('tmgmt.data')->filterData($data[$key])) {
        // The char sequence '][' confuses the form API so we need to replace
        // it.
        $target_key = str_replace('][', '|', $key);
        $zebra = $flip[$zebra];
        $form[$target_key] = array(
          '#tree' => TRUE,
          '#theme' => 'tmgmt_local_translation_form_element',
          '#ajaxid' => Html::getUniqueId('tmgmt-local-element-' . $key),
          '#parent_label' => $data[$key]['#parent_label'],
          '#zebra' => $zebra,
        );
        $form[$target_key]['status'] = array(
          '#theme' => 'tmgmt_local_translation_form_element_status',
          '#value' => $this->entity->isCompleted() ? TMGMT_DATA_ITEM_STATE_COMPLETED : $data[$key]['#status'],
        );

        // Manage the height of the texteareas, depending on the lenght of the
        // description. The minimum number of rows is 3 and the maximum is 15.
        $rows = ceil(strlen($data[$key]['#text']) / 100);
        if ($rows < 3) {
          $rows = 3;
        }
        elseif ($rows > 15) {
          $rows = 15;
        }
        $form[$target_key]['source'] = [
          '#type' => 'textarea',
          '#value' => $data[$key]['#text'],
          '#title' => t('Source'),
          '#disabled' => TRUE,
          '#rows' => $rows,
        ];

        $form[$target_key]['translation'] = [
          '#type' => 'textarea',
          '#default_value' => isset($data[$key]['#translation']['#text']) ? $data[$key]['#translation']['#text'] : NULL,
          '#title' => t('Translation'),
          '#disabled' => !$item->isPending(),
          '#rows' => $rows,
          '#allow_focus' => TRUE,
        ];
        if (!empty($data[$key]['#format']) && \Drupal::config('tmgmt.settings')->get('respect_text_format') == '1') {
          $format_id = $data[$key]['#format'];
          /** @var \Drupal\filter\Entity\FilterFormat $format */
          $format = FilterFormat::load($format_id);

          if ($format && $format->access('use')) {
            // In case a user has permission to translate the content using
            // selected text format, add a format id into the list of allowed
            // text formats. Otherwise, no text format will be used.
            $form[$target_key]['source']['#allowed_formats'] = [$format_id];
            $form[$target_key]['translation']['#allowed_formats'] = [$format_id];
            $form[$target_key]['source']['#type'] = 'text_format';
            $form[$target_key]['translation']['#type'] = 'text_format';
          }
        }

        $form[$target_key]['actions'] = array(
          '#type' => 'container',
          '#access' => $item->isPending(),
        );
        $status = $item->getData(\Drupal::service('tmgmt.data')->ensureArrayKey($key), '#status');
        if ($status == TMGMT_DATA_ITEM_STATE_TRANSLATED) {
          $form[$target_key]['actions']['reject-' . $target_key] = array(
            '#type' => 'submit',
            // Unicode character &#x2717 BALLOT X.
            '#value' => '✗',
            '#attributes' => array('title' => t('Reject')),
            '#name' => 'reject-' . $target_key,
            '#submit' => ['::save', '::submitStatus'],
            '#ajax' => array(
              'callback' => '::ajaxReviewForm',
              'wrapper' => $form[$target_key]['#ajaxid'],
            ),
            '#tmgmt_local_action' => 'reject',
            '#tmgmt_local_key' => str_replace('][', '|', $key),
          );
        }
        else {
          $form[$target_key]['actions']['finish-' . $target_key] = array(
            '#type' => 'submit',
            // Unicode character &#x2713 CHECK MARK.
            '#value' => '✓',
            '#attributes' => array('title' => t('Finish')),
            '#name' => 'finish-' . $target_key,
            '#submit' => ['::save', '::submitStatus'],
            '#ajax' => array(
              'callback' => '::ajaxReviewForm',
              'wrapper' => $form[$target_key]['#ajaxid'],
            ),
            '#tmgmt_local_action' => 'finish',
            '#tmgmt_local_key' => str_replace('][', '|', $key),
          );
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    /** @var LocalTaskItem $task_item */
    $task_item = $this->entity;
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      if (is_array($value) && isset($value['translation'])) {
        // Update the translation, this will only update the translation in case
        // it has changed. We have two different cases, the first is for nested
        // texts.
        if (is_array($value['translation'])) {
          $update['#translation']['#text'] = $value['translation']['value'];
        }
        else {
          $update['#translation']['#text'] = $value['translation'];
        }
        $task_item->updateData($key, $update);
      }
    }
    $task_item->save();

    if ($form_state->getTriggeringElement()['#value'] == $form['actions']['save']['#value']) {
      $this->messenger()->addStatus(t('The translation for <a href=:task_item>@task_item_title</a> has been saved.', [
        ':task_item' => $task_item->toUrl()->toString(),
        '@task_item_title' => $task_item->label(),
      ]));
    }

    $task = $task_item->getTask();
    $uri = $task->toUrl();
    $form_state->setRedirect($uri->getRouteName(), $uri->getRouteParameters());
  }

  /**
   * Form submit callback for save as completed submit action.
   *
   * Change items to needs review state and task to completed status.
   */
  public function saveAsComplete(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt_local\Entity\LocalTask $task */
    $task = $this->entity->getTask();

    /** @var LocalTaskItem $task_item */
    $task_item = $this->entity;
    $task_item->completed();
    $task_item->save();

    // Mark the task as completed if all assigned job items are at needs done.
    $all_done = TRUE;
    /** @var \Drupal\tmgmt_local\Entity\LocalTaskItem $item */
    foreach ($task->getItems() as $item) {
      if (!$item->isCompleted() && !$item->isClosed()) {
        $all_done = FALSE;
        break;
      }
    }
    if ($all_done) {
      $task->setStatus(LocalTaskInterface::STATUS_COMPLETED);
      // If the task is now completed, redirect back to the overview.
      $view = Views::getView('tmgmt_local_task_overview');
      $view->initDisplay();
      $form_state->setRedirect($view->getUrl()->getRouteName());
    }
    else {
      // If there are more task items, redirect back to the task.
      $uri = $task->toUrl();
      $form_state->setRedirect($uri->getRouteName(), $uri->getRouteParameters());
    }

    /** @var \Drupal\tmgmt\Entity\JobItem $job_item */
    $job_item = $this->entity->getJobItem();

    // Add the translations to the job item.
    $job_item->addTranslatedData($this->prepareData($task_item->getData()), [], TMGMT_DATA_ITEM_STATE_TRANSLATED);
    $this->messenger()->addStatus(t('The translation for <a href=:task_item>@task_item_title</a> has been saved as completed.', [
      ':task_item' => $task_item->toUrl()->toString(),
      '@task_item_title' => $task_item->label(),
    ]));
  }

  /**
   * Form validate callback for save as completed submit action.
   *
   * Verify that all items are translated.
   */
  public function validateSaveAsComplete(array &$form, FormStateInterface $form_state) {
    // Loop over all data items and verify that there is a translation in there.
    foreach ($form_state->getValues() as $key => $value) {
      if (is_array($value) && isset($value['translation'])) {
        if (empty($value['translation'])) {
          $form_state->setErrorByName($key . '[translation]', t('Missing translation.'));
        }
      }
    }
  }

  /**
   * Ajax callback for the job item review form.
   */
  public function ajaxReviewForm(array $form, FormStateInterface $form_state) {
    $key = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, 3);
    $render_data = NestedArray::getValue($form, $key);
    return $render_data;
  }

  /**
   * Form submit callback for the translation state update button.
   */
  public function submitStatus(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /** @var \Drupal\tmgmt_local\Entity\LocalTaskItem $item */
    $item = $this->entity;

    $action = $form_state->getTriggeringElement()['#tmgmt_local_action'];
    $key = $form_state->getTriggeringElement()['#tmgmt_local_key'];

    // Write the translated data into the job item.
    if (isset($values[$key]) && is_array($values[$key]) && isset($values[$key]['translation'])) {
      $update['#status'] = $action == 'finish' ? TMGMT_DATA_ITEM_STATE_TRANSLATED : TMGMT_DATA_ITEM_STATE_UNTRANSLATED;
      $item->updateData($key, $update);
      $item->save();

      // We need to rebuild form so we get updated action button state.
      $form_state->setRebuild();
    }
  }

  /**
   * Prepare the date to be added to the JobItem.
   *
   * Right now JobItem looks for ['#text'] so if we send our structure it will
   * add as translation text our original text, so we are replacing ['#text']
   * with ['#translation']['#text']
   *
   * @param array $data
   *   The data items.
   *
   * @return array
   *   Returns the data items ready to be added to the JobItem.
   */
  protected function prepareData(array $data) {
    if (isset($data['#text'])) {
      if (isset($data['#translation']['#text'])) {
        $result['#text'] = $data['#translation']['#text'];
      }
      else {
        $result['#text'] = '';
      }
      return $result;
    }
    foreach (Element::children($data) as $key) {
      $data[$key] = $this->prepareData($data[$key]);
    }
    return $data;
  }

  /**
   * Form submit callback for the preview button.
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $task_item = $this->entity;
    $job_item = $task_item->getJobItem();

    $job_item->addTranslatedData($this->prepareData($task_item->getData()), [], TMGMT_DATA_ITEM_STATE_PRELIMINARY);

    /** @var \Drupal\Core\Url $url */
    $url = $job_item->getSourcePlugin()->getPreviewUrl($job_item);
    $form_state->setRedirectUrl($url);
  }

}

<?php

namespace Drupal\tmgmt;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\tmgmt\Form\SourceOverviewForm;

/**
 * Default ui controller class for source plugin.
 *
 * @ingroup tmgmt_source
 */
class SourcePluginUiBase extends PluginBase implements SourcePluginUiInterface {

  /**
   * {@inheritdoc}
   */
  public function reviewForm(array $form, FormStateInterface $form_state, JobItemInterface $item) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function reviewDataItemElement(array $form, FormStateInterface $form_state, $data_item_key, $parent_key, array $data_item, JobItemInterface $item) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function reviewFormValidate(array $form, FormStateInterface $form_state, JobItemInterface $item) {
    // Nothing to do here by default.
  }

  /**
   * {@inheritdoc}
   */
  public function reviewFormSubmit(array $form, FormStateInterface $form_state, JobItemInterface $item) {
    // Nothing to do here by default.
  }

  /**
   * Builds the overview form for the source entities.
   *
   * @param array $form
   *   Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $type
   *   Entity type.
   *
   * @return array
   *   Drupal form array.
   */
  public function overviewForm(array $form, FormStateInterface $form_state, $type) {
    $form += $this->overviewSearchFormPart($form, $form_state, $type);

    $form['#attached']['library'][] = 'tmgmt/admin';

    $form['items'] = array(
      '#type' => 'tableselect',
      '#header' => $this->overviewFormHeader($type),
      '#empty' => $this->t('No source items matching given criteria have been found.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function overviewFormValidate(array $form, FormStateInterface $form_state, $type) {
    // Nothing to do here by default.
  }

  /**
   * Submit handler for the source entities overview form.
   *
   * @param array $form
   *   Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $type
   *   Entity type.
   */
  public function overviewFormSubmit(array $form, FormStateInterface $form_state, $type) {
    // Handle search redirect.
    if ($this->overviewSearchFormRedirect($form, $form_state, $type)) {
      return;
    }

    $target_language = $form_state->getValue('target_language');
    if ($target_language == SourceOverviewForm::ALL) {
      $target_languages = array_keys(tmgmt_available_languages());
    }
    elseif ($target_language == SourceOverviewForm::MULTIPLE) {
      $target_languages = array_filter($form_state->getValue('target_languages'));
    }
    else {
      $target_languages = [$target_language];
    }

    $enforced_source_language = NULL;
    if ($form_state->getValue('source_language') != SourceOverviewForm::SOURCE) {
      $enforced_source_language = $form_state->getValue('source_language');
    }

    $skipped_count = 0;
    $job_items_by_source_language = [];
    // Group the selected items by source language.
    foreach (array_filter($form_state->getValue('items')) as $item_id) {
      $job_item = tmgmt_job_item_create($this->pluginId, $type, $item_id);
      $source_language = $enforced_source_language ? $enforced_source_language : $job_item->getSourceLangCode();
      if (in_array($source_language, $job_item->getExistingLangCodes())) {
        $job_items_by_source_language[$source_language][$item_id] = $job_item;
      }
      else {
        $skipped_count++;
      }
    }

    $jobs = [];
    $remove_job_item_ids = [];
    // Loop over all target languages, create a job for each source and target
    // language combination add add the relevant job items to it.
    foreach ($target_languages as $target_language) {
      foreach ($job_items_by_source_language as $source_language => $job_items) {
        // Skip in case the source language is the same as the target language.
        if ($source_language == $target_language) {
          continue;
        }

        $job = tmgmt_job_create($source_language, $target_language, \Drupal::currentUser()->id());
        $job_empty = TRUE;
        /** @var \Drupal\tmgmt\JobItemInterface $job_item */
        foreach ($job_items as $id => $job_item) {
          try {
            // As the same item might be added to multiple jobs, we need to
            // re-create them.
            $job->addItem($job_item->getPlugin(), $job_item->getItemType(), $job_item->getItemId());
            $remove_job_item_ids[$job_item->id()] = $job_item->id();
            $job_empty = FALSE;
          } catch (\Exception $e) {
            // If an item fails for one target language, then it is also going
            // to fail for others, so remove it from the array.
            unset($job_items_by_source_language[$source_language][$id]);
            $this->messenger()->addError($e->getMessage());
          }
        }
        if (!$job_empty) {
          $jobs[] = $job;
        }
      }
    }

    // Start the checkout process if any jobs were created.
    if ($jobs) {
      if ($enforced_source_language) {

        $this->messenger()->addWarning($this->t('You have enforced the job source language which most likely resulted in having a translation of your original content as the job source text. You should review the job translation received from the translator carefully to prevent the content quality loss.'));
        if ($skipped_count) {
          $languages = \Drupal::languageManager()->getLanguages();
          $this->messenger()->addStatus(
            \Drupal::translation()->formatPlural(
              $skipped_count, 'One item skipped as for the language @language it was not possible to retrieve a translation.',
              '@count items skipped as for the language @language it was not possible to retrieve a translations.', ['@language' => $languages[$enforced_source_language]->getName()]
            )
          );
        }
      }
      \Drupal::service('tmgmt.job_checkout_manager')->checkoutAndRedirect($form_state, $jobs);
    }
    else {
      $this->messenger()->addError($this->t('From the selection you made it was not possible to create any translation job.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hook_views_default_views() {
    return array();
  }

  /**
   * Builds search form for entity sources overview.
   *
   * @param array $form
   *   Drupal form array.
   * @param FormStateInterface $form_state
   *   Drupal form_state array.
   * @param string $type
   *   Entity type.
   *
   * @return array
   *   Drupal form array.
   */
  public function overviewSearchFormPart(array $form, FormStateInterface $form_state, $type) {
    // Add entity type and plugin_id value into form array
    // so that it is available in the form alter hook.
    $form_state->set('entity_type', $type);
    $form_state->set('plugin_id', $this->pluginId);

    // Add search form specific styling.
    $form['#attached']['library'][] = 'tmgmt/source_search_form';

    $form['search_wrapper'] = array(
      '#prefix' => '<div class="tmgmt-sources-wrapper">',
      '#suffix' => '</div>',
      '#weight' => -15,
    );
    $form['search_wrapper']['search'] = array(
      '#tree' => TRUE,
    );
    $form['search_wrapper']['search_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
      '#weight' => 90,
    );
    $form['search_wrapper']['search_cancel'] = array(
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#weight' => 100,
    );

    return $form;
  }

  /**
   * Gets languages form header.
   *
   * @return array
   *   Array with the languages for the header.
   */
  protected function getLanguageHeader() {
    $languages = array();
    foreach (\Drupal::languageManager()->getLanguages() as $langcode => $language) {
      $languages['langcode-' . $langcode] = array(
        'data' => $language->getName(),
      );
    }

    return $languages;
  }

  /**
   * Performs redirect with search params appended to the uri.
   *
   * In case of triggering element is edit-search-submit it redirects to
   * current location with added query string containing submitted search form
   * values.
   *
   * @param array $form
   *   Drupal form array.
   * @param FormStateInterface $form_state
   *   Drupal form_state array.
   * @param $type
   *   Entity type.
   *
   * @return bool
   *   Returns TRUE, if redirect has been set.
   */
  public function overviewSearchFormRedirect(array $form, FormStateInterface $form_state, $type) {
    if ($form_state->getTriggeringElement()['#id'] == 'edit-search-cancel') {
      $form_state->setRedirect('tmgmt.source_overview', array('plugin' => $this->pluginId, 'item_type' => $type));
      return TRUE;
    }
    elseif ($form_state->getTriggeringElement()['#id'] == 'edit-search-submit') {
      $query = array();

      foreach ($form_state->getValue('search') as $key => $value) {
        $query[$key] = $value;
      }
      $form_state->setRedirect('tmgmt.source_overview', array('plugin' => $this->pluginId, 'item_type' => $type), array('query' => $query));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Builds the translation status render array with source and job item status.
   *
   * @param int $status
   *   The source status: original, missing, current or outofdate.
   * @param \Drupal\tmgmt\JobItemInterface|NULL $job_item
   *   The existing job item for the source.
   *
   * @return array
   *   The render array for displaying the status.
   */
  function buildTranslationStatus($status, JobItemInterface $job_item = NULL) {
    switch ($status) {
      case 'original':
        $label = t('Original language');
        $icon = 'core/misc/icons/bebebe/house.svg';
        break;

      case 'missing':
        $label = t('Not translated');
        $icon = 'core/misc/icons/bebebe/ex.svg';
        break;

      case 'outofdate':
        $label = t('Translation Outdated');
        $icon = drupal_get_path('module', 'tmgmt') . '/icons/outdated.svg';
        break;

      default:
        $label = t('Translation up to date');
        $icon = 'core/misc/icons/73b355/check.svg';
    }

    $build['source'] = [
      '#theme' => 'image',
      '#uri' => $icon,
      '#title' => $label,
      '#alt' => $label,
    ];

    // If we have an active job item, wrap it in a link.
    if ($job_item) {
      $url = $job_item->toUrl();
      if ($job_item->isActive() && $job_item->getJob() && $job_item->getJob()->isUnprocessed()) {
        $url = $job_item->getJob()->toUrl();
      }

      $url->setOption('query', \Drupal::destination()->getAsArray());

      $item_icon = $job_item->getStateIcon();
      $item_icon['#title'] = $this->t('Active job item: @state', ['@state' => $item_icon['#title']]);
      $build['job_item'] = [
        '#type' => 'link',
        '#url' => $url,
        '#title' => $item_icon,
      ];
    }
    return $build;
  }

}

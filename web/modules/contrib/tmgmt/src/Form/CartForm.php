<?php

namespace Drupal\tmgmt\Form;

use Drupal\Core\Form\FormBase;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Source overview form.
 */
class CartForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_cart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin = NULL, $item_type = NULL) {
    $languages = tmgmt_available_languages();
    $options = array();
    $selected = [];
    foreach (tmgmt_cart_get()->getJobItemsFromCart() as $item) {
      $url = $item->getSourceUrl();
      $selected[$item->id()] = TRUE;
      $options[$item->id()] = array(
        $item->getSourceType(),
        $url ? \Drupal::l($item->label(), $url) : $item->label(),
        isset($languages[$item->getSourceLangCode()]) ? $languages[$item->getSourceLangCode()] : t('Unknown'),
      );
    }

    $form['items'] = array(
      '#type' => 'tableselect',
      '#header' => array(t('Type'), t('Content'), t('Language')),
      '#empty' => t('There are no items in your cart.'),
      '#options' => $options,
      '#default_value' => $selected,
    );

    $form['enforced_source_language'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enforce source language'),
      '#description' => t('The source language is determined from the item\'s source language. If you wish to enforce a different language you can select one after ticking this checkbox. In such case the translation of the language you selected will be used as the source for the translation job.')
    );

    $form['source_language'] = array(
      '#type' => 'select',
      '#title' => t('Source language'),
      '#description' => t('Select a language that will be enforced as the translation job source language.'),
      '#options' => $languages,
      '#states' => array(
        'visible' => array(
          ':input[name="enforced_source_language"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['target_language'] = array(
      '#type' => 'select',
      '#title' => t('Request translation into language/s'),
      '#multiple' => TRUE,
      '#options' => $languages,
      '#description' => t('If the item\'s source language will be the same as the target language the item will be ignored.'),
    );

    $form['request_translation'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Request translation'),
      '#submit' => array('::submitRequestTranslation'),
      '#validate' => array('tmgmt_cart_source_overview_validate'),
    );

    $form['remove_selected'] = array(
      '#type' => 'submit',
      '#button_type' => 'danger',
      '#value' => t('Remove selected item'),
      '#submit' => array('::submitRemoveSelected'),
      '#validate' => array('tmgmt_cart_source_overview_validate'),
    );

    $form['empty_cart'] = array(
      '#type' => 'submit',
      '#button_type' => 'danger',
      '#value' => t('Empty cart'),
      '#submit' => array('::submitEmptyCart'),
    );

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Form submit callback to remove the selected items.
   */
  function submitRemoveSelected(array $form, FormStateInterface $form_state) {
    $job_item_ids = array_filter($form_state->getValue('items'));
    tmgmt_cart_get()->removeJobItems($job_item_ids);
    \Drupal::entityTypeManager()->getStorage('tmgmt_job_item')->delete(JobItem::loadMultiple($job_item_ids));
    $this->messenger()->addStatus(t('Job items were removed from the cart.'));
  }

  /**
   * Form submit callback to remove the selected items.
   */
  function submitEmptyCart(array $form, FormStateInterface $form_state) {
    \Drupal::entityTypeManager()->getStorage('tmgmt_job_item')->delete(tmgmt_cart_get()->getJobItemsFromCart());
    tmgmt_cart_get()->emptyCart();
    $this->messenger()->addStatus(t('All job items were removed from the cart.'));
  }

  /**
   * Custom form submit callback for tmgmt_cart_cart_form().
   */
  function submitRequestTranslation(array $form, FormStateInterface $form_state) {
    $target_languages = array_filter($form_state->getValue('target_language'));
    $enforced_source_language = NULL;
    if ($form_state->getValue('enforced_source_language')) {
      $enforced_source_language = $form_state->getValue('source_language');
    }

    $skipped_count = 0;
    $job_items_by_source_language = array();
    // Group the selected items by source language.
    foreach (JobItem::loadMultiple(array_filter($form_state->getValue('items'))) as $job_item) {
      $source_language = $enforced_source_language ? $enforced_source_language : $job_item->getSourceLangCode();
      if (in_array($source_language, $job_item->getExistingLangCodes())) {
        $job_items_by_source_language[$source_language][$job_item->id()] = $job_item;
      }
      else {
        $skipped_count++;
      }
    }

    $jobs = array();
    $remove_job_item_ids = array();
    // Loop over all target languages, create a job for each source and target
    // language combination add add the relevant job items to it.
    foreach ($target_languages as $target_language) {
      foreach ($job_items_by_source_language as $source_language => $job_items) {
        // Skip in case the source language is the same as the target language.
        if ($source_language == $target_language) {
          continue;
        }


        $job = tmgmt_job_create($source_language, $target_language, $this->currentUser()->id());
        $job_empty = TRUE;
        /** @var \Drupal\tmgmt\JobItemInterface $job_item */
        foreach ($job_items as $id => $job_item) {
          try {
            // As the same item might be added to multiple jobs, we need to
            // re-create them and delete the old ones, after removing them from
            // the cart.
            $job->addItem($job_item->getPlugin(), $job_item->getItemType(), $job_item->getItemId());
            $remove_job_item_ids[$job_item->id()] = $job_item->id();
            $job_empty = FALSE;
          }
          catch (\Exception $e) {
            // If an item fails for one target language, then it is also going
            // to fail for others, so remove it from the array.
            unset($job_items_by_source_language[$source_language][$id]);
            $this->messenger()->addStatus($e->getMessage(), 'error');
          }
        }

        if (!$job_empty) {
          $jobs[] = $job;
        }
      }
    }

    // Remove job items from the cart.
    if ($remove_job_item_ids) {
      tmgmt_cart_get()->removeJobItems($remove_job_item_ids);
      entity_delete_multiple('tmgmt_job_item', $remove_job_item_ids);
    }

    // Start the checkout process if any jobs were created.
    if ($jobs) {
      if ($enforced_source_language) {

        $this->messenger()->addWarning(t('You have enforced the job source language which most likely resulted in having a translation of your original content as the job source text. You should review the job translation received from the translator carefully to prevent the content quality loss.'));

        if ($skipped_count) {
          $languages = \Drupal::languageManager()->getLanguages();
          $this->messenger()->addStatus(\Drupal::translation()->formatPlural($skipped_count, 'One item skipped as for the language @language it was not possible to retrieve a translation.',
            '@count items skipped as for the language @language it was not possible to retrieve a translations.', array('@language' => $languages[$enforced_source_language]->getName())));
        }
      }

      \Drupal::service('tmgmt.job_checkout_manager')->checkoutAndRedirect($form_state, $jobs);
    }
    else {
      $this->messenger()->addError(t('From the selection you made it was not possible to create any translation job.'));
    }
  }

}


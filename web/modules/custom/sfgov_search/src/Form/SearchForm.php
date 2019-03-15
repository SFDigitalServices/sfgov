<?php

namespace Drupal\sfgov_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SearchForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $keyword = \Drupal::request()->query->get('keyword');
    $form['#attributes'] = array(
      'class' => array(
        'sfgov-search-form',
        'sfgov-search-form-311',
      ),
    );

    $form['sfgov_search_input'] = array(
      '#type' => 'textfield',
      '#placeholder' => t('What are you looking for?'),
      '#attributes' => array(
        'class' => array(
          'sf-gov-search-input-class',
        ),
      ),
      '#suffix' => '<div id="sfgov-search-autocomplete"></div>',
    );

    $form['#prefix'] = '<div class="mobile-btn"><i class="fa fa-times"></i></div>';
    // $form['#suffix'] = '<div id="sfgov-search-autocomplete"></div>';
    $form['#attached']['library'][] = 'sfgov_search/search';

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keyword = $form_state->getValues()['sfgov_search_input'];
    $form_state->setRedirect('sfgov_search.content', ['keyword' => $keyword]);
  }
}
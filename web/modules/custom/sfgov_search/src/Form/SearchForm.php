<?php

namespace Drupal\sfgov_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchForm extends FormBase {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(LanguageManagerInterface $languageManager) {
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfgov_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $keyword = \Drupal::request()->query->get('keyword');
    $config = \Drupal::config('sfgov_search.settings');
    $form['#attributes'] = array(
      'class' => array(
        'sfgov-search-form',
        'sfgov-search-form-311',
      ),
      'role' => 'search',
      'novalidate' => 'true',
    );

    $suffix_markup = '<div id="sfgov-search-describedby" aria-hidden="true" class="visually-hidden">' . t('When autocomplete results are available use up and down arrows to review and enter to select, or type the value') . '</div>';
    $suffix_markup .= '<div id="sfgov-search-autocomplete" role="listbox" aria-label="' . t("Search autocomplete") . '"></div>';

    $form['sfgov_search_input'] = array(
      '#title' => 'Search',
      '#type' => 'textfield',
      '#placeholder' => t('Search'),
      '#attributes' => array(
        'class' => array(
          'sf-gov-search-input-class',
        ),
        'title' => t('Search'),
        'role' => t('combobox'),
        'aria-autocomplete' => t('both'),
        'aria-describedby' => 'sfgov-search-describedby',
        'aria-owns' => 'sfgov-search-autocomplete',
        'aria-activedescendant' => '',
      ),
      '#suffix' => $suffix_markup,
    );

    $form['#attached']['library'][] = 'sfgov_search/search';
    $form['#attached']['drupalSettings']['sfgovSearch']['collection'] = empty($config->get('search_collection')) ? null : $config->get('search_collection');
    $form['#attached']['drupalSettings']['sfgovSearch']['qie'] = empty($config->get('qie_influence')) ? null : $config->get('qie_influence');

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
    $form_state->setRedirect('sfgov_search.content', ['keyword' => $keyword], [
      'language' => $this->languageManager->getCurrentLanguage(),
    ]);
  }
}

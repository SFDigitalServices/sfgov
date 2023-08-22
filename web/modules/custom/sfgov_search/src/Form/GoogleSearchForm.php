<?php

namespace Drupal\sfgov_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GoogleSearchForm extends FormBase {

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
    return 'sfgov_google_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $keyword = \Drupal::request()->query->get('q');

    $config = \Drupal::config('sfgov_google_search.settings');

//    $form['#attributes'] = array(
//      'id' => 'sfgov-search-form',
//      'class' => array(
//        'sfgov-search-form',
//        'sfgov-search-form-311',
//      ),
//      'role' => 'search',
//      'novalidate' => 'novalidate',
//    );

//    $suffix_markup = '<div id="sfgov-search-describedby" aria-hidden="true" class="visually-hidden">' . t('When autocomplete results are available use up and down arrows to review and enter to select, or type the value') . '</div>';
//    $suffix_markup .= '<div id="sfgov-search-autocomplete" role="listbox" aria-label="' . t("Search autocomplete") . '"></div>';

    $form['q'] = array(
      '#title' => t('Search'),
      '#type' => 'textfield',
      '#placeholder' => t('Google Search'),
      '#id' => 'edit-sfgov-search-input',
      '#value' => $keyword,
      '#attributes' => array(
        'class' => array(
          'sf-gov-search-input-class',
        ),
        'title' => t('Google Search'),
        'role' => t('combobox'),
        'aria-autocomplete' => t('both'),
        'aria-describedby' => 'sfgov-search-describedby',
        'aria-expanded' => 'false',
        'aria-owns' => 'sfgov-search-autocomplete',
        'aria-activedescendant' => '',
        'name' => 'q',
      ),
//      '#suffix' => $suffix_markup,
    );

    $form['#attached']['library'][] = 'sfgov_search/google_search';
    //$form['#attached']['drupalSettings']['sfgovSearch']['collection'] = empty($config->get('search_collection')) ? null : $config->get('search_collection');
    //$form['#attached']['drupalSettings']['sfgovSearch']['qie'] = empty($config->get('qie_influence')) ? null : $config->get('qie_influence');

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Google Search'),
      '#button_type' => 'primary',
      '#attributes' => array(
        'class' => array(
          'btn', // button class from design system
        ),
      )
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search = $form_state->getValues()['q'];
    $form_state->setRedirect('sfgov_google_search.content', ['q' => $search], [
      'language' => $this->languageManager->getCurrentLanguage(),
    ]);
  }
}

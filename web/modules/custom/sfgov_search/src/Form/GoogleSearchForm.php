<?php

namespace Drupal\sfgov_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
    $keyword = \Drupal::request()->query->get('keys');

    $config = \Drupal::config('sfgov_google_search.settings');

    $form['#attributes'] = array(
      'id' => 'sfgov-search-form',
      'class' => array(
        'sfgov-search-form',
        'sfgov-search-form-311',
      ),
      'role' => 'search',
      'novalidate' => 'novalidate',
    );

      $form['keys'] = array(
        '#title' => t('Search'),
        '#type' => 'textfield',
        '#placeholder' => t('Search'),
        '#id' => 'edit-sfgov-search-input',
        '#default_value' => $keyword,
        '#attributes' => array(
          'class' => array(
            'sf-gov-search-input-class',
          ),
          'title' => t('Search'),
          'role' => t('combobox'),
          'aria-autocomplete' => t('both'),
          'aria-describedby' => 'sfgov-search-describedby',
          'aria-expanded' => 'false',
          'aria-owns' => 'sfgov-search-autocomplete',
          'aria-activedescendant' => '',
        ),
      );

      $form['actions']['#type'] = 'actions';

      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Search'),
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
    $config = \Drupal::config('sfgov_search.settings');
    $redirect_google = $config->get('redirect_google');
    $search = $form_state->getValues()['keys'];

    if ($redirect_google) {
      $google_search_url = \Drupal\Core\Url::fromUri('https://www.google.com/search', [
        'query' => ['q' => $search . ' site:sf.gov OR site:sfgov.org'],
      ])->toString();
      $response = new RedirectResponse($google_search_url);
      $response->send();      
    } else {
      $form_state->setRedirect('search.view_google_json_api_search', ['keys' => $search], [
        'language' => $this->languageManager->getCurrentLanguage(),
      ]);
    }

  }
}

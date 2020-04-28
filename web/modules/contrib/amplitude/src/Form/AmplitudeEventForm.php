<?php

namespace Drupal\amplitude\Form;

use Drupal\amplitude\Entity\AmplitudeEvent;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Form controller for Amplitude event edit forms.
 *
 * @ingroup amplitude
 */
class AmplitudeEventForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Event name"),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#title' => $this->t('ID'),
      '#machine_name' => [
        'exists' => '\Drupal\amplitude\Entity\AmplitudeEvent::load',
        'source' => ['label'],
      ],
    ];

    $form['properties'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Properties'),
      '#default_value' => $entity->get('properties'),
      '#description' => $this->t('The JSON-formatted properties associated to this event. You can use tokens in this field.'),
    ];

    $form['token_container']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => 'all',
      '#show_restricted' => TRUE,
      '#weight' => 90,
    ];

    $form += $this->getTriggerElements($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $properties = json_decode($form_state->getValue('properties'));
    $event_trigger_data_capture_properties = $form_state->getValue('event_trigger_data_capture_properties');
    if (!$properties) {
      $form_state->setErrorByName('properties', $this->t('Entered JSON is in invalid format!'));
    }
    if (!empty($event_trigger_data_capture_properties)) {
      if (!json_decode($event_trigger_data_capture_properties)) {
        $form_state->setErrorByName('event_trigger_data_capture_properties', $this->t('Entered JSON is in invalid format!'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $status = parent::save($form, $form_state);
    $this->showMessage($this->entity->label(), $status);

    $form_state->setRedirect('entity.amplitude_event.collection');
  }

  /**
   * Shows a message depending on save status.
   *
   * @param string $label
   *   The label of the entity.
   * @param int $status
   *   The saving status.
   */
  protected function showMessage(string $label, int $status) {
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Amplitude event.', [
          '%label' => $label,
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Amplitude event.', [
          '%label' => $label,
        ]));
    }
  }

  /**
   * Returns an array with the event trigger elements for this form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array with the event trigger elements for this form.
   */
  protected function getTriggerElements(array $form, FormStateInterface $form_state) {

    $event_trigger = $this->entity->get('event_trigger');
    $event_trigger_pages = $this->entity->get('event_trigger_pages');
    $event_trigger_other = $this->entity->get('event_trigger_other');
    $event_trigger_scroll_depths = $this->entity->get('event_trigger_scroll_depths');
    $event_trigger_selector = $this->entity->get('event_trigger_selector');
    $event_trigger_data_capture = $this->entity->get('event_trigger_data_capture');
    $event_trigger_data_capture_properties = $this->entity->get('event_trigger_data_capture_properties');

    $form['trigger_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Trigger settings'),
    ];

    $form['trigger_settings']['event_trigger_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.", [
        '%user-wildcard' => '/user/*',
        '%front' => '<front>',
      ]),
      '#default_value' => $event_trigger_pages,
    ];

    $form['trigger_settings']['event_trigger'] = [
      '#type' => 'select',
      '#options' => AmplitudeEvent::getTriggerOptions(),
      '#title' => $this->t('Event trigger'),
      '#default_value' => $event_trigger,
    ];

    $events_link = Link::fromTextAndUrl(
      $this->t('JQuery event'),
      Url::fromUri('https://api.jquery.com/category/events/')
    )->toString();
    $other_condition = [
      ':input[name="event_trigger"]' => ['value' => AmplitudeEvent::EVENT_TRIGGER_OTHER],
    ];
    $form['trigger_settings']['event_trigger_other'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event name'),
      '#description' => $this->t(
        'The name of the @events_link to trigger this event.',
        ['@events_link' => $events_link]
      ),
      '#states' => [
        'visible' => $other_condition,
        'required' => $other_condition,
      ],
      '#default_value' => $event_trigger_other,
    ];

    $selector_link = Link::fromTextAndUrl(
      $this->t('selector'),
      Url::fromUri('https://api.jquery.com/category/selectors/')
    )->toString();
    $selector_condition = [
      ':input[name="event_trigger"]' => [
        ['value' => AmplitudeEvent::EVENT_TRIGGER_CLICK],
        ['value' => AmplitudeEvent::EVENT_TRIGGER_SELECT],
        ['value' => AmplitudeEvent::EVENT_TRIGGER_OTHER]
      ]
    ];
    $form['trigger_settings']['event_trigger_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selector'),
      '#description' => $this->t(
        'The @selector_link for the element(s) triggering this event.',
        ['@selector_link' => $selector_link]
      ),
      '#states' => [
        'visible' => $selector_condition,
        'required' => $selector_condition,
      ],
      '#default_value' => $event_trigger_selector,
    ];

    $form['trigger_settings']['event_trigger_data_capture'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capture text of the event trigger <em>selector</em> element'),
      '#states' => [
        'visible' => $selector_condition,
      ],
      '#default_value' => $event_trigger_data_capture,
    ];

    $selector_data_capture_condition = [
      ':input[name="event_trigger_data_capture"]' => ['checked' => TRUE],
    ];
    $form['trigger_settings']['event_trigger_data_capture_properties'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selector for element containing desired text'),
      '#description' => $this->t(
        'The JSON-formatted properties associated to this event trigger.<br/>'.
        'Use a selector as the property value to capture the desired text relative to event trigger <em>selector</em>.<br/>' .
        'For example, if the <em>selector</em> field is <em>a[href]</em>, then <br/>' .
        '<ul>'.
        '  <li><em>{"prop":"h3"}</em> will capture the text for an element with the selector <em>a[href] h3</em>.</li>' .
        '  <li><em>{"prop":""}</em> will capture the text for the selector <em>a[href]</em>' .
        '</ul>'
      ),
      '#states' => [
        'visible' => $selector_data_capture_condition,
        'required' => $selector_data_capture_condition,
      ],
      '#default_value' => $event_trigger_data_capture_properties
    ];

    $selector_scroll_condition = [
      ':input[name="event_trigger"]' => ['value' => AmplitudeEvent::EVENT_TRIGGER_SCROLL],
    ];
    $form['trigger_settings']['event_trigger_scroll_depths'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page scroll depth(s)'),
      '#description' => $this->t(
        'A comma delimited list of scroll depth percentages that will send event properties to Amplitude (e.g. 25, 50, 75)'
      ),
      '#states' => [
        'visible' => $selector_scroll_condition,
        'required' => $selector_scroll_condition,
      ],
      '#default_value' => $event_trigger_scroll_depths,
    ];

    return $form;
  }

}

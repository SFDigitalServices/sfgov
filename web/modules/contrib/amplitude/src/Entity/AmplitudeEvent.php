<?php

namespace Drupal\amplitude\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Amplitude event entity.
 *
 * @ConfigEntityType(
 *   id = "amplitude_event",
 *   label = @Translation("Amplitude event"),
 *   label_collection = @Translation("Amplitude events"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\amplitude\AmplitudeEventListBuilder",
 *     "form" = {
 *       "add" = "Drupal\amplitude\Form\AmplitudeEventForm",
 *       "edit" = "Drupal\amplitude\Form\AmplitudeEventForm",
 *       "delete" = "Drupal\amplitude\Form\AmplitudeEventDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\amplitude\AmplitudeEventHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "amplitude_event",
 *   admin_permission = "administer amplitude settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/amplitude/amplitude_event/{amplitude_event}",
 *     "add-form" = "/admin/config/amplitude/amplitude_event/add",
 *     "edit-form" = "/admin/config/amplitude/amplitude_event/{amplitude_event}/edit",
 *     "delete-form" = "/admin/config/amplitude/amplitude_event/{amplitude_event}/delete",
 *     "collection" = "/admin/config/amplitude/amplitude_event"
 *   }
 * )
 */
class AmplitudeEvent extends ConfigEntityBase implements AmplitudeEventInterface {

  public const EVENT_TRIGGER_PAGE_LOAD = 'pageLoad';

  public const EVENT_TRIGGER_CLICK = 'click';

  public const EVENT_TRIGGER_SELECT = 'select';

  public const EVENT_TRIGGER_SCROLL = 'scroll';

  public const EVENT_TRIGGER_OTHER = 'other';

  /**
   * The Amplitude event ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Amplitude event label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Amplitude event properties.
   *
   * @var string
   */
  protected $properties;

  /**
   * The Amplitude event trigger.
   *
   * @var string
   */
  protected $event_trigger;

  /**
   * The Amplitude event pages.
   *
   * @var string
   */
  protected $event_trigger_pages;

  /**
   * The Amplitude event custom trigger.
   *
   * @var string
   */
  protected $event_trigger_other;

    /**
   * The Amplitude event scroll depths.
   *
   * @var string
   */
  protected $event_trigger_scroll_depths;

  /**
   * The Amplitude event selector.
   *
   * @var string
   */
  protected $event_trigger_selector;

  /**
   * The Amplitude event trigger data capture.
   *
   * @var string
   */
  protected $event_trigger_data_capture;

  /**
   * The Amplitude event trigger data capture properties.
   *
   * @var string
   */
  protected $event_trigger_data_capture_properties;

  /**
   * Returns the available trigger options for events.
   *
   * @return array
   *   The trigger options.
   */
  public static function getTriggerOptions(): array {
    return [
      self::EVENT_TRIGGER_PAGE_LOAD => t('Page load'),
      self::EVENT_TRIGGER_CLICK => t('On click'),
      self::EVENT_TRIGGER_SELECT => t('On select'),
      self::EVENT_TRIGGER_SCROLL => t('On scroll'),
      self::EVENT_TRIGGER_OTHER => t('Other events'),
    ];
  }

}

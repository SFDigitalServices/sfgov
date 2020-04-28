<?php

/**
 * @file
 * Contains \Drupal\mandrill_activity\Entity\MandrillActivity.
 */

namespace Drupal\mandrill_activity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\mandrill_activity\MandrillActivityInterface;

/**
 * Defines the MandrillActivity entity.
 *
 * @ingroup mandrill_activity
 *
 * @ConfigEntityType(
 *   id = "mandrill_activity",
 *   label = @Translation("Mandrill Activity"),
 *   fieldable = FALSE,
 *   handlers = {
 *     "list_builder" = "Drupal\mandrill_activity\Controller\MandrillActivityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mandrill_activity\Form\MandrillActivityForm",
 *       "edit" = "Drupal\mandrill_activity\Form\MandrillActivityForm",
 *       "delete" = "Drupal\mandrill_activity\Form\MandrillActivityDeleteForm"
 *     }
 *   },
 *   config_prefix = "mandrill_activity",
 *   admin_permission = "administer mandrill",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/mandrill/activity/{mandrill_activity}",
 *     "delete-form" = "/admin/config/services/mandrill/activity/{mandrill_activity}/delete"
 *   }
 * )
 */
class MandrillActivity extends ConfigEntityBase implements MandrillActivityInterface {

  /**
   * Unique Mandrill Activity entity ID.
   *
   * @var int
   */
  public $id;

  /**
   * The human-readable name of this mandrill_activity_entity.
   *
   * @var string
   */
  public $label;

  /**
   * The Drupal entity type (e.g. "node", "user").
   *
   * @var string
   */
  public $entity_type;

  /**
   * The Drupal bundle (e.g. "page", "user")
   *
   * @var string
   */
  public $bundle;

  /**
   * The path to view individual entities of the selected type.
   *
   * @var string
   */
  public $entity_path;

  /**
   * The property that contains the email address to track.
   *
   * @var string
   */
  public $email_property;

  /**
   * Whether or not this Mandrill activity stream is enabled.
   *
   * @var boolean
   */
  public $enabled;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

}

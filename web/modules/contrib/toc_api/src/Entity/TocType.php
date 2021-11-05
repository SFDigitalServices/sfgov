<?php

/**
 * @file
 * Contains Drupal\toc_api\Entity\TocType.
 */

namespace Drupal\toc_api\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\toc_api\TocTypeInterface;

/**
 * Defines the TOC type entity.
 *
 * @ConfigEntityType(
 *   id = "toc_type",
 *   label = @Translation("TOC type"),
 *   admin_permission = "administer toc_types",
 *   handlers = {
 *     "access" = "Drupal\toc_api\TocTypeAccessControlHandler",
 *     "list_builder" = "Drupal\toc_api\TocTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\toc_api\TocTypeForm",
 *       "edit" = "Drupal\toc_api\TocTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/toc/add",
 *     "edit-form" = "/admin/structure/toc/manage/{toc_type}",
 *     "delete-form" = "/admin/structure/toc/manage/{toc_type}/delete",
 *     "collection" = "/admin/structure/toc",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "options",
 *   }
 * )
 */
class TocType extends ConfigEntityBase implements TocTypeInterface {

  /**
   * The TOC type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The TOC type UUID.
   *
   * @var string
   */
  protected  $uuid;

  /**
   * The TOC type label.
   *
   * @var string
   */
  protected  $label;

  /**
   * The TOC type options.
   *
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

}

<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Interface OfficeHoursItemListInterface
 *
 * @package Drupal\office_hours
 */
interface OfficeHoursItemListInterface extends FieldItemListInterface {

  /**
   * @param int $time
   * @return bool
   */
  public function isOpen($time = NULL);

  /**
   * @param array $settngs
   * @param array $field_settngs
   * @param $time
   * @return mixed
   */
  public function getRows(array $settings, array $field_settings, $time = NULL);

}

<?php

namespace Drupal\sfgov_locations\Plugin\Validation\Constraint;

/**
 * Address format constraint.
 */
class AddressFormatConstraint extends AddressFormatConstraintBase {

  public $blankMessage = '@name field must be blank.';
  public $notBlankMessage = '@name field is required.';
  public $invalidMessage = '@name field is not in the right format.';

}

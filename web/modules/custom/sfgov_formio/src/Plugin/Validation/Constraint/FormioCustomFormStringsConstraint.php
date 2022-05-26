<?php

namespace Drupal\sfgov_formio\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a FormioCustomFormStrings constraint.
 *
 * @Constraint(
 *   id = "FormioCustomFormStrings",
 *   label = @Translation("FormioCustomFormStrings", context = "Validation"),
 * )
 */
class FormioCustomFormStringsConstraint extends Constraint {

  /**
   * The violation message.
   *
   * @var string
   */
  public $uniqueKey = 'The custom key %custom_key is already in use at entry %entry';

}

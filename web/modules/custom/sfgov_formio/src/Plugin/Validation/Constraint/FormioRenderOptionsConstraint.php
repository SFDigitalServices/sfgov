<?php

namespace Drupal\sfgov_formio\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a FormioRenderOptions constraint.
 *
 * @Constraint(
 *   id = "FormioRenderOptions",
 *   label = @Translation("FormioRenderOptions", context = "Validation"),
 * )
 */
class FormioRenderOptionsConstraint extends Constraint {

  /**
   * The violation message.
   *
   * @var string
   */
  public $invalidJson = 'The JSON data in "Form.io Render Options" is invalid';

}

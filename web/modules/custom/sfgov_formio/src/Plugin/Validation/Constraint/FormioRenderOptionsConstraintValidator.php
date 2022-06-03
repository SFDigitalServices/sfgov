<?php

namespace Drupal\sfgov_formio\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the FormioRenderOptions constraint.
 */
class FormioRenderOptionsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {

    if ($data = $value->value) {
      if (is_string($data)) {
        if (!json_decode($data)) {
          $this->context->addViolation($constraint->invalidJson);
        }
      }
    }

  }

}

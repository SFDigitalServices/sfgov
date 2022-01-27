<?php

namespace Drupal\sfgov_media\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PowerBiUrl constraint.
 */
class PowerBiUrlConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $item = $items->first();

    // Validate URL.
    if ($item && strpos($item->value, 'https://app.powerbigov.us/view?r=') !== 0) {
      $this->context->addViolation($constraint->message);
    }
  }
}

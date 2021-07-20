<?php

namespace Drupal\sfgov_media\Plugin\Validation\Constraint;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PowerBiSourceDataUrl constraint.
 */
class PowerBiSourceDataUrlConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $item = $items->first();

    // Validate URL.
    if ($item && !UrlHelper::isValid($item->value, TRUE)) {
      $this->context->addViolation($constraint->message);
    }
  }
}

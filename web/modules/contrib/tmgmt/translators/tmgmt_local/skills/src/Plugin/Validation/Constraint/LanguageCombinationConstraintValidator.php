<?php

namespace Drupal\tmgmt_language_combination\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ForumLeaf constraint.
 */
class LanguageCombinationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value->language_from == $value->language_to) {
      $this->context->addViolation($constraint->noDifferentMessage);
    }

    foreach ($value->getParent() as $combination) {
      if ($combination->language_from == $value->language_from && $combination->language_to == $value->language_to) {
        if ($value != $combination && $value->getName() > $combination->getName()) {
          $this->context->addViolation($constraint->uniqueMessage);
        }
      }
    }
  }

}

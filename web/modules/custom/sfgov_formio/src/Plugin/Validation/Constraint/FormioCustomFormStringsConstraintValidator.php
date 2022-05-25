<?php

namespace Drupal\sfgov_formio\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the CustomFormStrings constraint.
 */
class FormioCustomFormStringsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    $parent = $entity->getParent()->getEntity();

    $custom_keys = [];
    foreach($parent->field_custom_form_strings->getValue() as $entry) {
      $custom_keys[] = $entry['key'];
    }

    $existing_keys = [];
    foreach($parent->field_form_strings->getValue() as $entry) {
      if (in_array($entry['key'], $custom_keys)) {
        $this->context->addViolation($constraint->uniqueKey, [
          '%custom_key' => $entry['key'],
          '%entry' => $entry['label']
        ]);
      }
    }
  }

}

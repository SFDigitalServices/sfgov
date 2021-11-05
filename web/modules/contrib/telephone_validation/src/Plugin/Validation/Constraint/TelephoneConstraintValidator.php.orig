<?php

namespace Drupal\telephone_validation\Plugin\Validation\Constraint;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\telephone_validation\Validator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TelephoneConstraint constraint.
 */
class TelephoneConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Validator service.
   *
   * @var \Drupal\telephone_validation\Validator
   */
  protected $validator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('telephone_validation.validator'));
  }

  /**
   * Constructs a new TelephoneConstraintValidator.
   *
   * @param \Drupal\telephone_validation\Validator $validator
   *   Telephone number validation service.
   */
  public function __construct(Validator $validator) {
    $this->validator = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    try {
      $number = $value->getValue();
    }
    catch (\InvalidArgumentException $e) {
      return;
    }
    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = $value->getFieldDefinition();

    // Check field allows storing a third party settings.
    if (!$field instanceof ThirdPartySettingsInterface) {
      return;
    }

    $settings = $field->getThirdPartySettings('telephone_validation');
    // If no settings found we must skip validation.
    if (empty($settings)) {
      return;
    }
    // Validate number against validation settings.
    if (!$this->validator->isValid(
      $number['value'],
      $field->getThirdPartySetting('telephone_validation', 'format'),
      $field->getThirdPartySetting('telephone_validation', 'country')
    )) {
      $this->context->addViolation($constraint->message, ['@number' => $number['value']]);
    }
  }

}

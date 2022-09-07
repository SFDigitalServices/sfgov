<?php

namespace Drupal\sfgov_formio\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\sfgov_formio\FormioHelpers;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Validates the FormioDataSource constraint.
 */
class FormioDataSourceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The FormioHelpers service.
   *
   * @var \Drupal\sfgov_formio\FormioHelpers
   */
  protected $formioHelpers;

  /**
   * Constructs a FormioDataSourceConstraintValidator object.
   *
   * @param Drupal\sfgov_formio\FormioHelpers $formio_helpers
   *   The Formio helper functions.
   */
  public function __construct(FormioHelpers $formio_helpers) {
    $this->formioHelpers = $formio_helpers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sfgov_formio.helpers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    $parent = $entity->getParent()->getEntity();
    if ($parent->hasField('field_formio_data_source')) {
      if ($parent->field_formio_data_source->value) {

        $formio_helpers = $this->formioHelpers;
        $formio_helpers->setHelperData($parent);

        if (!$formio_helpers->isValidUrl()) {
          $this->context->addViolation($constraint->invalidUrl);
        }

        if (!$formio_helpers->isValidData()) {
          $this->context->addViolation($constraint->invalidJson, [
            '%error' => $formio_helpers->getDataError(),
          ]);
        }
      }
    }
  }

}

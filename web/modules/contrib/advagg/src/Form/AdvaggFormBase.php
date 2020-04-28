<?php

namespace Drupal\advagg\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * View AdvAgg information for this site.
 */
abstract class AdvaggFormBase extends ConfigFormBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    parent::__construct($config_factory);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * Checks if the form was submitted by AJAX.
   *
   * @return bool
   *   TRUE if the form was submitted via AJAX, otherwise FALSE.
   */
  protected function isAjax() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->query->has(FormBuilderInterface::AJAX_FORM_REQUEST)) {
      return TRUE;
    }
    return FALSE;
  }

}

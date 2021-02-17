<?php

namespace Drupal\sfgov_vaccine\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;

/**
 * Class VaccineController.
 */
class VaccineController extends ControllerBase {

  /**
   * Drupal\Core\Cache\Context\CacheContextInterface definition.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextInterface
   */
  protected $cacheContextIp;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->cacheContextIp = $container->get('cache_context.ip');
    return $instance;
  }

  public function makeTitle() {
    // @todo Create admin form.
    return t("COVID-19 vaccine sites");
  }

  public function makeFilters() {
    return \Drupal::formBuilder()->getForm('\Drupal\sfgov_vaccine\Form\FilterSitesForm');
  }

  public function makeResults() {

    $titles = ['Moscone', 'Laguna', 'Walgreens'];
    $results = [];

    foreach ($titles as $title) {
      $result = [
        'site_title' => $title,
    ];

      $results[] = $result;
    }

    return $results;
  }

  /**
   * Showresults.
   *
   * @return array
   *   Return Render Array.
   */
  public function showResults() {
    return [
      '#theme' => 'vaccine-widget',
      '#page_title' => $this->makeTitle(),
      '#filters' => $this->makeFilters(),
      '#results' => $this->makeResults(),
      ];
  }

}

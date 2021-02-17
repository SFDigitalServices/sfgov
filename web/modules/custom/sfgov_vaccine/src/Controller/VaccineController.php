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

    $path_to_file = drupal_get_path('module', 'sfgov_vaccine') . '/data/sites.json';
    $sites = json_decode(file_get_contents($path_to_file));

    $results = [];
    foreach ($sites as $site_id => $site_data ) {
      $result = [
        'site_title' => $site_data->site_title,
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

<?php

namespace Drupal\sfgov_data\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\sfgov_data\Service\JsonApiBuilder;

class DataController extends ControllerBase {

  private $jsonApiBuilder;

  public function __construct(JsonApiBuilder $jsonApiBuilder) {
    $this->jsonApiBuilder = $jsonApiBuilder;
  }

  public static function create(ContainerInterface $container) {
    $jsonApiBuilderService = $container->get('sfgov_data.jsonapi_builder');
    return new static ($jsonApiBuilderService);
  }

  public function getData($nid) {
    // $response = new Response();
    // $response->setContent($nid);
    // $response->setMaxAge(10);
    $data = $this->jsonApiBuilder->buildJsonApi(Node::load($nid));
    // error_log($data);

    // $data = [
    //   "nid" => $nid,
    // ];

    return new Response($data);
  }
}

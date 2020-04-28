<?php

namespace Drupal\simple_sitemap_engines\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\simple_sitemap\SimplesitemapManager;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process a queue of search engines to submit sitemaps.
 *
 * @QueueWorker(
 *   id = "simple_sitemap_engine_submit",
 *   title = @Translation("Sitemap search engine submission"),
 *   cron = {"time" = 30}
 * )
 *
 * @see simple_sitemap_engines_cron()
 */
class SitemapSubmitter extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The search engine entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $engineStorage;

  /**
   * The HTTP client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The sitemap manager service.
   *
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  protected $sitemapManager;

  /**
   * The simple sitemap logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $engine_storage
   *   The search engine entity storage.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client service.
   * @param \Drupal\simple_sitemap\SimplesitemapManager $sitemap_manager
   *   The sitemap manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The simple sitemap logger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $engine_storage, ClientInterface $http_client, SimplesitemapManager $sitemap_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->engineStorage = $engine_storage;
    $this->httpClient = $http_client;
    $this->sitemapManager = $sitemap_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('simple_sitemap_engine'),
      $container->get('http_client'),
      $container->get('simple_sitemap.manager'),
      $container->get('logger.factory')->get('simple_sitemap')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($engine_id) {
    /** @var \Drupal\simple_sitemap_engines\Entity\SearchEngine $engine */
    if ($engine = $this->engineStorage->load($engine_id)) {
      // Gather URLs for all sitemap variants.
      $sitemap_urls = [];
      foreach ($this->sitemapManager->getSitemapTypes() as $type_name => $type_definition) {
        $sitemap_generator = $this->sitemapManager->getSitemapGenerator($type_definition['sitemapGenerator']);
        $variants = $this->sitemapManager->getSitemapVariants($type_name, FALSE);
        if (!empty($variants)) {
          // Submit all variants that are enabled for this search engine.
          foreach ($variants as $id => $variant) {
            if (in_array($id, $engine->sitemap_variants)) {
              $sitemap_urls[$variant['label']] = $sitemap_generator->setSitemapVariant($id)->getSitemapUrl();
            }
          }
        }
      }

      // Submit all URLs.
      foreach ($sitemap_urls as $variant => $sitemap_url) {
        $submit_url = str_replace('[sitemap]', $sitemap_url, $engine->url);
        try {
          $this->httpClient->request('GET', $submit_url);
          // Log if submission was successful.
          $this->logger->info('Sitemap %sitemap submitted to @url', ['%sitemap' => $variant, '@url' => $submit_url]);
          // Record last submission time. This is purely informational; the
          // variable that determines when the next submission should be run is
          // stored in the global state.
          $engine->last_submitted = time();
        }
        catch (RequestException $e) {
          // Catch and log exceptions so this submission gets removed from the
          // queue whether or not it succeeded.
          // If the error was caused by network failure, it's fine to just wait
          // until next time the submission is queued to try again.
          // If the error was caused by a malformed URL, keeping the submission
          // in the queue to retry is pointless since it will always fail.
          watchdog_exception('simple_sitemap', $e);
        }
      }
      $engine->save();
    }
  }

}

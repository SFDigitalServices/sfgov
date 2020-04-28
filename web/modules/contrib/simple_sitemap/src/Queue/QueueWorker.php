<?php

namespace Drupal\simple_sitemap\Queue;

use Drupal\Component\Utility\Timer;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorBase;
use Drupal\simple_sitemap\SimplesitemapSettings;
use Drupal\simple_sitemap\SimplesitemapManager;
use Drupal\Core\State\StateInterface;
use Drupal\simple_sitemap\Logger;


class QueueWorker {

  use BatchTrait;

  const REBUILD_QUEUE_CHUNK_ITEM_SIZE = 5000;

  /**
   * @var \Drupal\simple_sitemap\SimplesitemapSettings
   */
  protected $settings;

  /**
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  protected $manager;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\simple_sitemap\Queue\SimplesitemapQueue
   */
  protected $queue;

  /**
   * @var \Drupal\simple_sitemap\Logger
   */
  protected $logger;

  /**
   * @var string|null
   */
  protected $variantProcessedNow;

  /**
   * @var string|null
   */
  protected $generatorProcessedNow;

  /**
   * @var array
   */
  protected $results = [];

  /**
   * @var array
   */
  protected $processedPaths = [];

  /**
   * @var array
   */
  protected $generatorSettings;

  /**
   * @var int|null
   */
  protected $maxLinks;

  /**
   * @var int|null
   */
  protected $elementsRemaining;

  /**
   * @var int|null
   */
  protected $elementsTotal;

  /**
   * QueueWorker constructor.
   * @param \Drupal\simple_sitemap\SimplesitemapSettings $settings
   * @param \Drupal\simple_sitemap\SimplesitemapManager $manager
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\simple_sitemap\Queue\SimplesitemapQueue $element_queue
   * @param \Drupal\simple_sitemap\Logger $logger
   */
  public function __construct(SimplesitemapSettings $settings,
                              SimplesitemapManager $manager,
                              StateInterface $state,
                              SimplesitemapQueue $element_queue,
                              Logger $logger) {
    $this->settings = $settings;
    $this->manager = $manager;
    $this->state = $state;
    $this->queue = $element_queue;
    $this->logger = $logger;
  }

  /**
   * @return $this
   */
  public function deleteQueue() {
    $this->queue->deleteQueue();
    SitemapGeneratorBase::purgeSitemapVariants(NULL, 'unpublished');
    $this->variantProcessedNow = NULL;
    $this->generatorProcessedNow = NULL;
    $this->results = [];
    $this->processedPaths = [];
    $this->state->set('simple_sitemap.queue_items_initial_amount', 0);
    $this->state->delete('simple_sitemap.queue_stashed_results');
    $this->elementsTotal = NULL;
    $this->elementsRemaining = NULL;

    return $this;
  }

  /**
   * @param array|null $variants
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function rebuildQueue($variants = NULL) {
    $all_data_sets = [];
    $sitemap_variants = $this->manager->getSitemapVariants();
    $type_definitions = $this->manager->getSitemapTypes();
    $this->deleteQueue();

    foreach ($sitemap_variants as $variant_name => $variant_definition) {

      // Skipping unwanted sitemap variants.
      if (NULL !== $variants && !in_array($variant_name, (array) $variants)) {
        continue;
      }

      $type = $variant_definition['type'];

      // Adding generate_sitemap operations for all data sets.
      foreach ($type_definitions[$type]['urlGenerators'] as $url_generator_id) {

        $data_sets = $this->manager->getUrlGenerator($url_generator_id)
          ->setSitemapVariant($variant_name)
          ->getDataSets();

        if (!empty($data_sets)) {
          $sitemap_variants[$variant_name]['data'] = TRUE;
          foreach ($data_sets as $data_set) {
            $all_data_sets[] = [
              'data' => $data_set,
              'sitemap_variant' => $variant_name,
              'url_generator' => $url_generator_id,
              'sitemap_generator' => $type_definitions[$type]['sitemapGenerator'],
            ];

            if (count($all_data_sets) === self::REBUILD_QUEUE_CHUNK_ITEM_SIZE) {
              $this->queueElements($all_data_sets);
              $all_data_sets = [];
            }
          }
        }
      }
    }

    if (!empty($all_data_sets)) {
      $this->queueElements($all_data_sets);
    }
    $this->getQueuedElementCount(TRUE);

    // todo: May not be clean to remove sitemap variants data when queuing elements.
    // todo: Add test.
    // Remove all sitemap variant instances where no results have been queued.
    $this->manager->removeSitemap(array_keys(array_filter($sitemap_variants, function($e) { return empty($e['data']); })));

    return $this;
  }

  protected function queueElements($elements) {
    $this->queue->createItems($elements);
    $this->state->set('simple_sitemap.queue_items_initial_amount', ($this->state->get('simple_sitemap.queue_items_initial_amount') + count($elements)));
  }

  /**
   * @param string $from
   * @return $this
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function generateSitemap($from = 'form') {

    $this->generatorSettings = [
      'base_url' => $this->settings->getSetting('base_url', ''),
      'xsl' => $this->settings->getSetting('xsl', TRUE),
      'default_variant' => $this->settings->getSetting('default_variant', NULL),
      'skip_untranslated' => $this->settings->getSetting('skip_untranslated', FALSE),
      'remove_duplicates' => $this->settings->getSetting('remove_duplicates', TRUE),
      'excluded_languages' => $this->settings->getSetting('excluded_languages', []),
    ];
    $this->maxLinks = $this->settings->getSetting('max_links');
    $max_execution_time = $this->settings->getSetting('generate_duration', 10000);
    Timer::start('simple_sitemap_generator');

    $this->unstashResults();

    if (!$this->generationInProgress()) {
      $this->rebuildQueue();
    }

    while ($element = $this->queue->claimItem()) {

      if (!empty($max_execution_time) && Timer::read('simple_sitemap_generator') >= $max_execution_time) {
        break;
      }

      try {
        if ($element->data['sitemap_variant'] !== $this->variantProcessedNow) {

          if (NULL !== $this->variantProcessedNow) {
            $this->generateVariantChunksFromResults(TRUE);
            $this->publishCurrentVariant();
          }

          $this->variantProcessedNow = $element->data['sitemap_variant'];
          $this->generatorProcessedNow = $element->data['sitemap_generator'];
          $this->processedPaths = [];
        }

        $this->generateResultsFromElement($element);

        if (!empty($this->maxLinks) && count($this->results) >= $this->maxLinks) {
          $this->generateVariantChunksFromResults();
        }
      }
      catch (\Exception $e) {
        watchdog_exception('simple_sitemap', $e);
      }

      $this->queue->deleteItem($element); //todo May want to use deleteItems() instead.
      $this->elementsRemaining--;
    }

    if ($this->getQueuedElementCount() === 0) {
      $this->generateVariantChunksFromResults(TRUE);
      $this->publishCurrentVariant();
    }
    else {
      $this->stashResults();
    }

    return $this;
  }

  /**
   * @param $element
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function generateResultsFromElement($element) {
    $results = $this->manager->getUrlGenerator($element->data['url_generator'])
      ->setSitemapVariant($this->variantProcessedNow)
      ->setSettings($this->generatorSettings)
      ->generate($element->data['data']);

    $this->removeDuplicates($results);
    $this->results = array_merge($this->results, $results);
  }

  /**
   * @param array $results
   */
  protected function removeDuplicates(&$results) {
    if ($this->generatorSettings['remove_duplicates'] && !empty($results)) {
      $result = $results[key($results)];
      if (isset($result['meta']['path'])) {
        if (isset($this->processedPaths[$result['meta']['path']])) {
          $results = [];
        }
        else {
          $this->processedPaths[$result['meta']['path']] = TRUE;
        }
      }
    }
  }

  /**
   * @param bool $complete
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function generateVariantChunksFromResults($complete = FALSE) {
    if (!empty($this->results)) {
      $generator = $this->manager->getSitemapGenerator($this->generatorProcessedNow)
        ->setSitemapVariant($this->variantProcessedNow)
        ->setSettings($this->generatorSettings);

      if (empty($this->maxLinks) || $complete) {
        $generator->generate($this->results);
        $this->results = [];
      }
      else {
        foreach (array_chunk($this->results, $this->maxLinks, TRUE) as $chunk_links) {
          if (count($chunk_links) === $this->maxLinks || $complete) {
            $generator->generate($chunk_links);
            $this->results = array_diff_key($this->results, $chunk_links);
          }
        }
      }
    };
  }

  protected function publishCurrentVariant() {
    if ($this->variantProcessedNow !== NULL) {
      $this->manager->getSitemapGenerator($this->generatorProcessedNow)
        ->setSitemapVariant($this->variantProcessedNow)
        ->setSettings($this->generatorSettings)
        ->generateIndex()
        ->publish();
    }
  }

  protected function stashResults() {
    $this->state->set('simple_sitemap.queue_stashed_results', [
      'variant' => $this->variantProcessedNow,
      'generator' => $this->generatorProcessedNow,
      'results' => $this->results,
      'processed_paths' => $this->processedPaths,
    ]);
    $this->results = [];
    $this->processedPaths = [];
    $this->generatorProcessedNow = NULL;
    $this->variantProcessedNow = NULL;
  }

  protected function unstashResults() {
    if (NULL !== $results = $this->state->get('simple_sitemap.queue_stashed_results')) {
      $this->state->delete('simple_sitemap.queue_stashed_results');
      $this->results = !empty($results['results']) ? $results['results'] : [];
      $this->processedPaths = !empty($results['processed_paths']) ? $results['processed_paths'] : [];
      $this->variantProcessedNow = $results['variant'];
      $this->generatorProcessedNow = $results['generator'];
    }
  }

  public function getInitialElementCount() {
    if (NULL === $this->elementsTotal) {
      $this->elementsTotal = (int) $this->state->get('simple_sitemap.queue_items_initial_amount', 0);
    }

    return $this->elementsTotal;
  }

  /**
   * @param bool $force_recount
   * @return int
   */
  public function getQueuedElementCount($force_recount = FALSE) {
    if ($force_recount || NULL === $this->elementsRemaining) {
      $this->elementsRemaining = $this->queue->numberOfItems();
    }

    return $this->elementsRemaining;
  }

  /**
   * @return int
   */
  public function getStashedResultCount() {
    return count($this->state->get('simple_sitemap.queue_stashed_results', ['results' => []])['results']);
  }

  /**
   * @return int
   */
  public function getProcessedElementCount() {
    $initial = $this->getInitialElementCount();
    $remaining = $this->getQueuedElementCount();

    return $initial > $remaining ? ($initial - $remaining) : 0;
  }

  /**
   * @return bool
   */
  public function generationInProgress() {
    return 0 < ($this->getQueuedElementCount() + $this->getStashedResultCount());
  }
}


<?php

namespace Drupal\simple_sitemap\Commands;

use Drupal\simple_sitemap\Simplesitemap;
use Drush\Commands\DrushCommands;

/**
 * Class SimplesitemapCommands
 * @package Drupal\simple_sitemap\Commands
 */
class SimplesitemapCommands extends DrushCommands {

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * SimplesitemapCommands constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   */
  public function __construct(Simplesitemap $generator) {
    $this->generator = $generator;
  }

  /**
   * Regenerate the XML sitemaps according to the module settings.
   *
   * @command simple-sitemap:generate
   *
   * @usage drush simple-sitemap:generate
   *   Regenerate the XML sitemaps according to the module settings.
   *
   * @validate-module-enabled simple_sitemap
   *
   * @aliases ssg, simple-sitemap-generate
   */
  public function generate() {
    $this->generator->generateSitemap('drush');
  }

  /**
   * Rebuild the sitemap queue for all sitemap variants.
   *
   * @command simple-sitemap:rebuild-queue
   *
   * @usage drush simple-sitemap:rebuild-queue
   *   Rebuild the sitemap queue for all sitemap variants.
   *
   * @validate-module-enabled simple_sitemap
   *
   * @aliases ssr, simple-sitemap-rebuild-queue
   */
  public function rebuildQueue() {
    $this->generator->rebuildQueue();
  }

}

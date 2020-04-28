<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator;

/**
 * Interface SitemapGeneratorInterface
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator
 */
interface SitemapGeneratorInterface {

  function setSitemapVariant($sitemap_variant);

  function setSettings(array $settings);

  function generate(array $links);

  function generateIndex();

  function publish();

  function remove();
}

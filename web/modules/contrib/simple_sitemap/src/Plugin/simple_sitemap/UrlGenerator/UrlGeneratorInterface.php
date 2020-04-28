<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

/**
 * Interface UrlGeneratorInterface
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 */
interface UrlGeneratorInterface {

  function setSettings(array $settings);

  function setSitemapVariant($sitemap_variant);

  function getDataSets();

  function generate($data_set);
}

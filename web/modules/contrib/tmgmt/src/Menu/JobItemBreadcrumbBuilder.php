<?php

namespace Drupal\tmgmt\Menu;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tmgmt\JobItemInterface;

/**
 * A custom Job item breadcrumb builder.
 */
class JobItemBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteName() == 'entity.tmgmt_job_item.canonical' && $route_match->getParameter('tmgmt_job_item') instanceof JobItemInterface) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addCacheContexts(['route']);

    /** @var JobItemInterface $job_item */
    $job_item = $route_match->getParameter('tmgmt_job_item');
    $breadcrumb->addCacheableDependency($job_item);

    // Add links to administration, translation, job overview and job to the
    // breadcrumb.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Administration'), 'system.admin'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Translation'), 'tmgmt.admin_tmgmt'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Job overview'), 'view.tmgmt_job_overview.page_1'));
    $breadcrumb->addLink(Link::createFromRoute($job_item->getJob()->label(), 'entity.tmgmt_job.canonical', array('tmgmt_job' => $job_item->getJobId())));

    return $breadcrumb;
  }

}

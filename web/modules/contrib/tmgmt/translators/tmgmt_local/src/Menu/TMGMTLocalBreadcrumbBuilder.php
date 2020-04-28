<?php

namespace Drupal\tmgmt_local\Menu;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tmgmt_local\LocalTaskInterface;
use Drupal\tmgmt_local\LocalTaskItemInterface;

/**
 * A custom Local task item breadcrumb builder.
 */
class TMGMTLocalBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if (strpos($route_match->getRouteName(), 'view.tmgmt_local_manage_translate_task') === 0 || strpos($route_match->getRouteName(), 'view.tmgmt_local_task_overview') === 0 ||
      $route_match->getParameter('tmgmt_local_task') instanceof LocalTaskInterface || $route_match->getParameter('tmgmt_local_task_item') instanceof LocalTaskItemInterface) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addCacheContexts(['route']);

    // Add links to administration, and translation to the breadcrumb.
    if (\Drupal::config('tmgmt_local.settings')->get('use_admin_theme')
      || strpos($route_match->getRouteObject()->getPath(), '/manage-translate') === 0) {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Administration'), 'system.admin'));
      $breadcrumb->addLink(Link::createFromRoute($this->t('Translation'), 'tmgmt.admin_tmgmt'));
    }

    if ($route_match->getParameter('tmgmt_local_task') instanceof LocalTaskInterface || $route_match->getParameter('tmgmt_local_task_item') instanceof LocalTaskItemInterface) {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Local Tasks'), 'view.tmgmt_local_task_overview.unassigned'));
      if ($route_match->getParameter('tmgmt_local_task_item') instanceof LocalTaskItemInterface) {
        /** @var LocalTaskItemInterface $local_task_item */
        $local_task_item = $route_match->getParameter('tmgmt_local_task_item');
        $breadcrumb->addCacheableDependency($local_task_item);

        $breadcrumb->addLink(Link::createFromRoute($local_task_item->getTask()->label(), 'entity.tmgmt_local_task.canonical', array('tmgmt_local_task' => $local_task_item->getTask()->id())));
      }
    }

    return $breadcrumb;
  }

}

/**
 * @file
 * Views UI helpers for Simple XML Sitemap display extender.
 */

(function ($, Drupal) {
  Drupal.simpleSitemapViewsUi = {};

  Drupal.behaviors.simpleSitemapViewsUiCheckboxify = {
    attach: function attach() {
      var $button = $('[data-drupal-selector="edit-index-button"]').once('simple-sitemap-views-ui-checkboxify');
      if ($button.length) {
        new Drupal.simpleSitemapViewsUi.Checkboxifier($button);
      }
    }
  };

  Drupal.behaviors.simpleSitemapViewsUiArguments = {
    attach: function attach() {
      var $arguments = $('.indexed-arguments').once('simple-sitemap-views-ui-arguments');
      var $checkboxes = $arguments.find('input[type="checkbox"]');
      if ($checkboxes.length) {
        new Drupal.simpleSitemapViewsUi.Arguments($checkboxes);
      }
    }
  };

  Drupal.simpleSitemapViewsUi.Checkboxifier = function ($button) {
    this.$button = $button;
    this.$parent = this.$button.parent('div.simple-sitemap-views-index');
    this.$input = this.$parent.find('input:checkbox');
    this.$button.hide();
    this.$input.on('click', $.proxy(this, 'clickHandler'));
  };

  Drupal.simpleSitemapViewsUi.Checkboxifier.prototype.clickHandler = function () {
    this.$button.trigger('click').trigger('submit');
  };

  Drupal.simpleSitemapViewsUi.Arguments = function ($checkboxes) {
    this.$checkboxes = $checkboxes;
    this.$checkboxes.on('change', $.proxy(this, 'changeHandler'));
  };

  Drupal.simpleSitemapViewsUi.Arguments.prototype.changeHandler = function (e) {
    var $checkbox = $(e.target), index = this.$checkboxes.index($checkbox);
    $checkbox.prop('checked') ? this.check(index) : this.uncheck(index);
  };

  Drupal.simpleSitemapViewsUi.Arguments.prototype.check = function (index) {
    this.$checkboxes.slice(0, index).prop('checked', true);
  };

  Drupal.simpleSitemapViewsUi.Arguments.prototype.uncheck = function (index) {
    this.$checkboxes.slice(index).prop('checked', false);
  };

})(jQuery, Drupal);
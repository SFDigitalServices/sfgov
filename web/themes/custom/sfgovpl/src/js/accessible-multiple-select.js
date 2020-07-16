/**
 * @file
 * Accessible Multi-select list.
 *
 * Adapted version https://codepen.io/rdmchenry/pen/OyzVEx as requested in
 * SG-1153.
 */
(function($, Drupal) {
  'use strict';

  const DataStatePropertyName = 'multiselect';
  const EventNamespace = '.multiselect';
  const PluginName = 'MultiSelect';

  var old = $.fn[PluginName];
  $.fn[PluginName] = plugin;
  $.fn[PluginName].Constructor = MultiSelect;
  $.fn[PluginName].noConflict = function() {
    $.fn[PluginName] = old;
    return this;
  };

  // Defaults
  $.fn[PluginName].defaults = {};

  // Static members
  $.fn[PluginName].EventNamespace = function () {
    return EventNamespace.replace(/^\./ig, '');
  };
  $.fn[PluginName].GetNamespacedEvents = function (eventsArray) {
    return getNamespacedEvents(eventsArray);
  };

  function getNamespacedEvents(eventsArray) {
    var event;
    var namespacedEvents = "";
    while (event = eventsArray.shift()) {
      namespacedEvents += event + EventNamespace + ' ';
    }
    return namespacedEvents.replace(/\s+$/g, '');
  }

  function plugin(option) {
    this.each(function () {
      var $target = $(this);
      var multiSelect = $target.data(DataStatePropertyName);
      var options = (typeof option === typeof {} && option) || {};

      if (!multiSelect) {
        $target.data(DataStatePropertyName, multiSelect = new MultiSelect(this, options));
      }

      if (typeof option === typeof '') {
        if (!(option in multiSelect)) {
          throw "MultiSelect does not contain a method named '" + option + "'";
        }
        return multiSelect[option]();
      }
    });
  }

  function MultiSelect(element, options) {
    this.$element = $(element);
    this.options = $.extend({}, $.fn[PluginName].defaults, options);
    this.destroyFns = [];

    this.$toggle = this.$element.children('.toggle');
    this.$toggle.attr('id', this.$element.attr('id') + '-multi-select-label');
    this.$backdrop = null;
    this.$allToggle = null;

    init.apply(this);
  }

  MultiSelect.prototype.open = open;
  MultiSelect.prototype.close = close;

  function init() {
    this.$element
      .addClass('multi-select')
      .attr('tabindex', 0);

    initAria.apply(this);
    initEvents.apply(this);
    updateLabel.apply(this);
    injectToggleAll.apply(this);

    this.destroyFns.push(function() {
      return '|'
    });
  }

  function injectToggleAll() {
    if (this.$allToggle && !this.$allToggle.parent()) {
      this.$allToggle = null;
    }

    var toggleAllLabel = Drupal.t('Select all');

    this.$allToggle = $('<li class="multi-select__list-item toggle-all"><label class="multi-select__label"><input type="checkbox" class="multi-select__input" /><span class="multi-select__label-text">' + toggleAllLabel + '</span></label><li>');

    this.$element
      .children('ul:first')
      .prepend(this.$allToggle)
      .find('li:empty').remove();
  }

  function initAria() {
    this.$element
      .attr('role', 'combobox')
      .attr('aria-multiselect', true)
      .attr('aria-expanded', false)
      .attr('aria-haspopup', false);
      // .attr('aria-labeledby', this.$element.attr('aria-labeledby') + " " + this.$toggle.attr('id'));

    this.$toggle
      .attr('aria-label', '');
  }

  function initEvents() {
    var that = this;
    this.$element
      .on(getNamespacedEvents(['click']), function($event) {
        if ($event.target !== that.$toggle[0] && !that.$toggle.has($event.target).length) {
          return;
        }

        if ($(this).hasClass('in')) {
          that.close();
        }
        else {
          that.open();
        }
      })
      .on(getNamespacedEvents(['keydown']), function($event) {
        var next = false;

        switch($event.keyCode) {
          // Enter.
          case 13:
            if ($(this).hasClass('in')) {
              that.close();
            }
            else {
              that.open();
            }
            break;

          // Tab.
          case 9:
            if ($event.target !== that.$element[0]) {
              $event.preventDefault();
            }

          // Esc.
          case 27:
            that.close();
            break;

          // Down arrow.
          case 40:
            next = true;

          // Up arrow.
          case 38:
            var $items = $(this).children('ul:first').find(':input, button, a');

            var foundAt = $.inArray(document.activeElement, $items);
            if (next && ++foundAt === $items.length) {
              foundAt = 0;
            }
            else if (!next && --foundAt < 0) {
              foundAt = $items.length - 1;
            }

            $($items[foundAt]).trigger('focus');
        }
      })
      .on(getNamespacedEvents(['focus']), 'a, button, :input', function() {
        $(this)
          .parents('li:last')
          .addClass('focused');
      })
      .on(getNamespacedEvents(['blur']), 'a, button, :input', function() {
        $(this)
          .parents('li:last')
          .removeClass('focused');
      })
      .on(getNamespacedEvents(['change']), ':checkbox', function() {
        if (that.$allToggle && $(this).is(that.$allToggle.find(':checkbox'))) {
          var allChecked = that.$allToggle.find(':checkbox').prop('checked');
          that.$element
            .find(':checkbox')
            .not(that.$allToggle.find(':checkbox'))
            .each(function() {
              $(this).prop('checked', allChecked);
              $(this)
                .parents('li:last')
                .toggleClass('selected', $(this).prop('checked'));
            });

            updateLabel.apply(that);
            return;
          }

          $(this)
            .parents('li:last')
            .toggleClass('selected', $(this).prop('checked'));

          var checkboxes = that.$element
            .find(':checkbox')
            .not(that.$allToggle.find(':checkbox'))
            .filter(':checked');

          that.$allToggle.find(':checkbox').prop('checked', checkboxes.length === checkboxes.end().length);

          updateLabel.apply(that);
      })
      .on(getNamespacedEvents(['mouseover']), 'ul', function() {
        $(this)
          .children('.focused')
          .removeClass('focused');
      });
  }

  function updateLabel() {
    var $checkboxes = this.$element.find('ul input[type=checkbox][value]');

      var countTotal = $checkboxes.length;
      var countChecked = $checkboxes.filter(':checked').length;
      var legend = this.$element.find('legend').text();
      var itemSingular = this.$element.attr('data-multiselect-item-singular') ? this.$element.attr('data-multiselect-item-singular') : 'item';
      var itemPlural = this.$element.attr('data-multiselect-item-plural') ? this.$element.attr('data-multiselect-item-plural') : 'items';

      var labels = {
        default: legend,
        toggleAll: Drupal.formatPlural(countChecked, 'All @itemSingular selected', 'All @itemPlural selected', {
          '@itemSingular': itemSingular,
          '@itemPlural': itemPlural,
        }),
        toggleOf: Drupal.formatPlural(countChecked, '@count @itemSingular selected', '@count @itemPlural selected', {
          '@itemSingular': itemSingular,
          '@itemPlural': itemPlural
        }),
        toggleOfTotal: Drupal.t('@checked of @total @item selected', {
          '@checked': countChecked,
          '@item': itemPlural,
          '@total': countTotal,
        }),
      };

      this.$toggle
        .children('label')
        .text(countChecked ? (countChecked === countTotal ? labels.toggleAll : labels.toggleOf) : labels.default);

      this.$element
        .children('ul')
        .attr('aria-label', labels.toggleOfTotal);
  }

  function ensureFocus() {
    this.$element
      .children('ul:first')
      .find(':input, button, a')
      .first()
      .trigger('focus')
      .end()
      .end()
      .find(':checked')
      .first()
      .trigger('focus');
  }

  function addBackdrop() {
    if (this.$backdrop) {
      return;
    }

    var that = this;
    this.$backdrop = $('<div class="multi-select-backdrop" />');
    this.$element.append(this.$backdrop);

    this.$backdrop.on('click', function() {
      $(this).off('click').remove();

      that.$backdrop = null;
      that.close();
    });
  }

  function open() {
    if (this.$element.hasClass('in')) {
      return;
    }

    this.$element.addClass('in');

    this.$element
      .attr('aria-expanded', true)
      .attr('aria-haspopup', true);

    addBackdrop.apply(this);
    //ensureFocus.apply(this);
  }

  function close() {
    this.$element
      .removeClass('in')
      .trigger('focus');

    this.$element
      .attr('aria-expanded', false)
      .attr('aria-haspopup', false);

    if (this.$backdrop) {
      this.$backdrop.trigger('click');
    }
  }

})(jQuery, Drupal);

$.fn.toMultiSelect = function() {
  const $element = $(this)
  const fieldset_id = $element.attr('id');
  const legend = $element.find('> legend').text().trim()

  const $list = $('<ul class="multi-select__list">')
  $element.find('.form-checkboxes .js-form-type-checkbox').each(function() {
    var $label = $(this).find('label')
      .clone()
      .addClass('multi-select__label')
      .wrapInner('<span class="multi-select__label-text"></span>')
      .prepend(
        $(this).find('input')
          .clone()
          .addClass('multi-select__input')
          .removeAttr('data-multiselect data-multiselect-item-singular data-multiselect-item-plural')
      );

    var $item = $('<li class="multi-select__list-item">').append($label);
    $list.append($item);
  });

  const $multiSelect = $('<fieldset class="multi-select">').attr({
    'id': fieldset_id,
    'data-multiselect-item-singular': $element.attr('data-multiselect-item-singular'),
    'data-multiselect-item-plural': $element.attr('data-multiselect-item-plural'),
  });

  $multiSelect.prepend('<legend class="visually-hidden" id="' + fieldset_id + '-multi-select-label">' + legend +'</legend>');
  $multiSelect.append('<span class="toggle"><label>' + legend + '</label><span class="icon"></span></span>');
  $multiSelect.append($list);

  $multiSelect.MultiSelect();

  $(this).replaceWith($multiSelect);
}

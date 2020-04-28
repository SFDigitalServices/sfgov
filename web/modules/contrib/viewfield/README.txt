CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Viewfield provides a field that holds a reference to a View and renders it
whenever the entity containing the field is displayed. Essentially Viewfield
provides a way to display a view as part of any entity.

 * For a full description and more information on the module visit
   https://www.drupal.org/node/1210138

 *  To submit bug reports and feature suggestions, or to track changes visit
   https://www.drupal.org/project/issues/viewfield


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Viewfield module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the Viewsfield module.
    2. Navigate to Administration > Structure > Content types > content type to
       edit] > Manage fields and add a new field. Select "Viewfield" from the
       dropdown, provide a label name, and Save and continue.
    3. From the "Field settings" tab choose the number of field values (distinct
       view displays) to be stored and Save field settings.

Assigning field values:
    1. Navigate to Administration > Structure > Content types > [content type to
       edit] > Manage fields and select the "Edit" tab. There is an opportunity
       to provide Help text.
    2. For each Viewfield, select a View and Display from the dropdown menus.
       in the "Arguments" field, a comma-delineated list of arguments
       (contextual filters) can be entered. The argument list may contain tokens.
    3. Selecting the "Always use default values" checkbox means the Viewfield
       will always use the provided default value(s) when rendering the field,
       and this field will be hidden in all entity edit forms, making it
       unnecessary to assign values individually to each piece of content. If
       this is checked, a default value must be provided.
    4. To restrict the views available for content authors, check one or more
       views from the "Allowed views" list. To allow all views, leave all the
       boxes empty.
    5. To restrict display types for content authors, check one or more boxes in
       the "Allowed display types" list. To allow all display types, leave all
       the boxes empty.

Manage Display settings:
    1. Navigate to Administration > Structure > Content types > [content type to
       edit] > Manage display.
    2. Select the edit gear for the viewfield. There are options to render the
       view display title, the options are: Above, Inline, Hidden, and Visually
       Hidden.
    3. There is an option to produce renderable output even if the view produces
       no results. This option may be useful for some specialized cases, e.g.,
       to force rendering of an attachment display even if there are no view
       results. Select "Always build output" to capture this feature.
    4. If "Always build output" is selected, there will be an option to output
       the view display title even when the view produces no results.The option
       are:
       Above, Inline, Hidden, or Visually Hidden. This option has an effect only
       when "Always build output" is selected.

Twig theming:
Viewfield provides default theming with the viewfield.html.twig file and
viewfield-item.html.twig templates, which may each be overridden. Enable Twig
debugging to view file name suggestions in the rendered HTML.

CSS styling:
In addition to the core field CSS classes, Viewfield adds "field__item__label"
for view titles. Because Drupal core does not provide default styling for
fields, Viewfield likewise does not provide any CSS styles. Themes must provide
their own styling for the "field__item__label" class.


MAINTAINERS
-----------

 * keithm - https://www.drupal.org/u/keithm
 * Daniel Kudwien (sun) - https://www.drupal.org/u/sun

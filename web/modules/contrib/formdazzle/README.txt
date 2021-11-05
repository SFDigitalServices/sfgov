Formdazzle!

Drupal form theming with less pain

Theming drupal forms can be difficult and time-consuming. This module provides a
set of utilities that make form theming easier.

Currently, this module provides theme suggestions for forms that are much more
useful than those provided by Drupal core.

- Theme suggestions for all form elements (including buttons)
- Theme suggestions for all form element labels
- All theme suggestions include the form ID and the form element name;
  e.g. [element-type]--[form-id]--[form-element-name].html.twig
- Twig debugging comments have been added to all forms for the hidden
  [form-id].html.twig template.

While Drupal core only provided these two theme suggestions:

  input.html.twig
  input--textfield.html.twig

Formdazzle adds the following two theme suggestions to the list:

  input--textfield--webform-contact.html.twig
  input--textfield--webform-contact--first-name.html.twig

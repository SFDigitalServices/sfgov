Telephone Validation
========================

This is basic module which brings validation to Telephone field. It uses
giggsey/libphonenumber-for-php library, port of google's libphonenumber library.
Module can automatically discover where the phone number comes from (which
country) and if it's valid or not.

# Dependencies

- telephone
- field_ui
- giggsey/libphonenumber-for-php

# Prerequisites

Use composer to download giggsey/libphonenumber-for-php lib. You have several
options here - just choose one which suits you best. IMO the easiest one is to
use composer for downloading this module.

Read more
https://www.drupal.org/docs/develop/using-composer


# Installation

Just enable the module. All telephone fields will have option to opt-in for
for validation.

# Credits

Jakub Piasecki @ Ny Media AS

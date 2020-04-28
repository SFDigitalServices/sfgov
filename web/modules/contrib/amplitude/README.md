CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usage
 * Maintainers


INTRODUCTION
------------

The module provides

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/amplitude

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/amplitude
   
 * Amplitude home: https://amplitude.com/simplified-homepage


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Amplitude module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Amplitude
       and enter your Amplitude API key.

USAGE
-----

**Managing events**

Navigate to Administration > Configuration > Amplitude > Amplitude Events to manage events.

The event fields are:

- Name: the name of the event.
- Pages: the pages where this event is going to be triggered.
- Event trigger: 
  - Page load: event is triggered as the user access the page.
  - On click and on select: event is triggered on click/select.
  - Other events: allow users to manually enter event name (e.g. dbclick).
- Selector: If Event trigger isn't page load, users must specify the selector
 for the HTML element(s) triggering the event.
- Event properties: The JSON-formatted properties associated to this event. You can use tokens in this field.

**Example event:**

- Name: **view node**
- Pages: **node/***
- Event trigger: **Page load**
- Event properties: 
```json
{
  "title": "[node:title]",
  "site_name": "[site:name]"
}  
```

This event will trigger the following JS on page load:

```javascript
amplitude.getInstance().logEvent(‘view node’,
    { "title": "My node title",
      "site_name": "My site"});
```

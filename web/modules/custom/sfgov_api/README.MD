# SF.gov API
This module builds and exposes json data for the wagtail site to consume.

## Calling the API
There is currently one route that provides entity data. You must provide the entity type and bundle
as arguments and it will return all of the entities of that type.

Examples:
 - all step_by_step nodes: `/sfgov-api/entity/node/en/step_by_step/`
 - all process_step paragraphs: `/sfgov-api/entity/paragraph/process_step`

Optional arguments for language and specific entity ids
 - all step_by_step nodes in spanish: `/sfgov-api/entity/node/es/step_by_step`
 - step_by_step node:610 in english: `/sfgov-api/entity/node/en/step_by_step/en/610`

**For an entity type to work it has to have a corresponding plugin in sfgov_api/src/Plugin/SfgApi**

## API structure
This module uses Drupal's plugin system to create a flexible and extendable variety of api endpoints.
Most of the work happens in `sfgov_api/src/SfgApiPluginBase.php` which pulls information from annotations/url arguments/plugins.
It then uses this data to build a list of entities (and translate them if necessary), then make representations
of them that can be sent as a json object.

The json object consists of three parts for each entity, and the data is set in different places
  - **drupal_data**: This is basic data to use for organizational purposes (id, entity type, bundle). Set by `SfgApiPluginBase`
  - **base_data**: This is where we would add in any metadata that wagtail expects. Set by the entity base plugin like `sfgov_api/src/SfgApiNodePluginBase.php`
  - **custom_data**: This is where we manipulate the data for the individual field values of the entity. Set by the bundle plugin like `sfgov_api/src/Plugin/SfgApi/Node/StepByStep.php`

## Building on the API
With this structure, every entity that needs to be exposed for the API will need its own plugin. At time of writing
there are example plugins in place for Nodes and Paragraphs.

You can easily generate new plugins with the command `drush generate sfgov:api-plugin`

When building a new plugin make sure to respect the
different layers of responsibility set out in the previous section. Each entity level plugin should focus on building
the actual field data that wagtail expects in the `setCustomData` function. The idea here is that they return an array
shaped like so

```
  wagtail_field_name_1: processed_drupal_data_1
  wagtail_field_name_2: processed_drupal_data_2
```

The part that processes the drupal data can simply pull it from the entity:
`$entity->get('field_description')->value`.

Or it can be more elaborate and wrap the data in some kind of function that processes the data further:
`$this->getReferencedData($entity->get('field_process_steps')->referencedEntities())`

Any helper functions that are created to process the data specifically for wagtail should be added to `sfgov_api/src/Plugin/SfgApi/ApiFieldHelperTrait.php`
so that they can be used in other plugins.

Note: The aforementioned `getReferencedData` function relies on there being a plugin for the entity type it is referencing. It
uses said plugin to map out the fields and data.

## Pushing to Wagtail
At time of writing there are two custom drush commands for pushing nodes into the Wagtail API. It only supports pushing to a
local version of the wagtail site.

**This approach might change in future iterations and is currently very rough and limited**

### Requirements
1. This setup assumes you're hosting your Drupal site through lando.
2. Install the wagtail site locally from sfgov's github
3. Force wagtail to accept external connections by adding the following to your local_settings.py in the Wagtail repo.
```
ALLOWED_HOSTS = ['localhost', 'host.docker.internal', '127.0.0.1']
```

### Usage
There are currently two functions, each one for sending individual entities/pages to the Wagtail API. The only difference between the two is how they gather their respective json payloads.
**pushLiteralData** Lets you set the literal field values being pushed. This is mainly for experimentation purposes.
**pushEntityById** Hooks into the larger plugin infrastructure built in this module to generate that json payload.

## Todo
### Features
- add proper pagination to `SfgApiPluginBase::prepareData()` (currently limited to 20 results)
- add proper security to the route(s)
- add interfaces

### Plugins
- Figure out correct values for baseData in `SfgApiNodePluginBase`
- Figure out correct values for baseData in `sfgov_api/src/Plugin/SfgApi/Media/Image.php`
- Figure out correct field names for custom data in `sfgov_api/src/Plugin/SfgApi/Paragraph/ProcessStep.php`
  and `sfgov_api/src/Plugin/SfgApi/Paragraph/ProcessStep.php`. currently using the Drupal field names for
  proof of concept.
- Add plugins for all required entity types

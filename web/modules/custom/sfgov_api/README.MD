# SF.gov API
This module builds and exposes json data for the wagtail site to consume.

## Calling the API
There is currently one route that provides entity data. You must provide the entity type and bundle
as arguments and it will return all of the entities of that type.

Examples:
 - all step_by_step nodes: `/sfgov-api/entity/node/step_by_step/`
 - all process_step paragraphs: `/sfgov-api/entity/paragraph/process_step`

Optional arguments for language and specific entity ids
 - all step_by_step nodes in spanish: `/sfgov-api/entity/node/step_by_step/es`
 - step_by_step node:610 in english: `/sfgov-api/entity/node/step_by_step/en/610`

**For an entity type to work it has to have a corresponding plugin in sfgov_api/src/Plugin/SfgovApi**

## API structure
This module uses Drupal's plugin system to create a flexible and extendable variety of api endpoints.
Most of the work happens in `sfgov_api/src/SfgovApiPluginBase.php` which pulls information from annotations/url arguments/plugins.
It then uses this data to build a list of entities (and translate them if necessary), then make representations
of them that can be sent as a json object.

The json object consists of three parts for each entity, and the data is set in different places
  - **drupal_data**: This is basic data to use for organizational purposes (id, entity type, bundle). Set by `SfgovApiPluginBase`
  - **base_data**: This is where we would add in any metadata that wagtail expects. Set by the entity base plugin like `sfgov_api/src/SfgovApiNodePluginBase.php`
  - **custom_data**: This is where we manipulate the data for the individual field values of the entity. Set by the bundle plugin like `sfgov_api/src/Plugin/SfgovApi/Node/StepByStep.php`

## Building on the API
With this structure, every entity that needs to be exposed for the API will need its own plugin. At time of writing
there are example plugins in place for Nodes and Paragraphs.

You can easily generate new plugins with the command `drush generate custom:api-plugin`

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

Any helper functions that are created to process the data specifically for wagtail should be added to `sfgov_api/src/Plugin/SfgovApi/ApiFieldHelperTrait.php`
so that they can be used in other plugins.

Note: The aforementioned `getReferencedData` function relies on there being a plugin for the entity type it is referencing. It
uses said plugin to map out the fields and data.

## Todo
### Features
- add proper pagination to `SfgovApiPluginBase::prepareData()` (currently limited to 20 results)
- add proper security to the route(s)

### Plugins
- Figure out correct values for baseData in `SfgovApiNodePluginBase`
- Figure out correct values for baseData in `sfgov_api/src/Plugin/SfgovApi/Media/Image.php`
- Figure out correct field names for custom data in `sfgov_api/src/Plugin/SfgovApi/Paragraph/ProcessStep.php`
  and `sfgov_api/src/Plugin/SfgovApi/Paragraph/ProcessStep.php`. currently using the Drupal field names for
  proof of concept.
- Add plugins for all required entity types

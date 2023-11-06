# SF.gov API
This module builds and exposes json data from the Drupal side and then provides means of pushing that data into Wagtail.

# Setup
## Local setup
1. This setup assumes you're hosting your Drupal site through lando.
2. Install the wagtail site locally from sfgov's github
3. Force wagtail to accept external connections by adding the following to your local_settings.py in the Wagtail repo.
```
ALLOWED_HOSTS = ['localhost', 'host.docker.internal', '127.0.0.1']
```

### Tips
- Make sure you've run a config import so that all of the settings at `/admin/config/system/wagtail-api-credentials` are properly set up.
- You're likely to have to reset wagtail many times during testing. Copy and paste the following into your terminal to do so.
```
dropdb ds_platform &&
createdb ds_platform &&
./manage.py migrate &&
./manage.py createsuperuser &&
./manage.py setup_locales &&
./manage.py loaddata home_translations &&
./manage.py runserver
```

## Live Setup
WIP

# Viewing API Data
There is currently one route that provides entity data. You must provide the entity type and bundle
as arguments and it will return some entities of that type displayed in the format that will be pushed to Wagtail.
This is very helpful for debugging. Note: you can also provide an entity id as an argument.

Examples:
 - all step_by_step nodes in english: `sfgov-api/entity/node/en/step_by_step`
 - all step_by_step nodes in spanish: `/sfgov-api/entity/es/node/step_by_step`
 - all process_step paragraphs in english: `/sfgov-api/entity/en/paragraph/process_step`

Optional arguments for specific entity ids
 - step_by_step node:610 in english: `/sfgov-api/entity/en/node/step_by_step/610`

**For an entity type to work it has to have a corresponding plugin in sfgov_api/src/Plugin/SfgApi**

## API structure
This module uses Drupal's plugin system to create a flexible and extendable variety of api endpoints.
Most of the work happens in `sfgov_api/src/SfgApiPluginBase.php` which pulls information from annotations/url arguments/plugins.
It then uses this data to build a list of entities (and translate them if necessary), then make representations
of them that can be viewed as a json object.

The json object consists of three parts for each entity, and the data is set in different places
  - **drupal_data**: This is basic data to use for organizational purposes (id, entity type, bundle). Set by `SfgApiPluginBase`
  - **base_data**: This is where we would add in any metadata that wagtail expects. Set by the entity base plugin like `sfgov_api/src/SfgApiNodePluginBase.php`
  - **custom_data**: This is where we manipulate the data for the individual field values of the entity. Set by the bundle plugin like `sfgov_api/src/Plugin/SfgApi/Node/StepByStep.php`

## Building on the API
With this structure, every entity that needs to be exposed for the API will need its own plugin. There are example
plugins in place for Nodes and Paragraphs.

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

Any helper functions that are created to process the data specifically for wagtail
should be added to `sfgov_api/src/Plugin/SfgApi/ApiFieldHelperTrait.php`
so that they can be used in other plugins.

Note: The aforementioned `getReferencedData` function relies on there being a plugin for the entity type it is referencing. It
uses said plugin to map out the fields and data.

# Pushing to Wagtail

## Migration Strategy
A successful push to the Wagtail API returns the wagtail id of the node created. Those ids then get stored in the
`drupal_wagtail_id_map` table next to their Drupal ID. This allows Drupal to have some idea of what has been migrated and where
The plan is to migrate all nodes in stub form first then update them to have their actual content. It has to be done this
way to make the entity relationships work.

### Entity Relationships
Wagtail is designed around hierarchical relationships, while relationships in Drupal are flat.
The current way around this are fields like related_content_agency which can store references to other
nodes but also need to be updated in the API by pushing to `/api/cms/sf.RelatedContentAgency`
- This is still WIP

### Translations
Wagtail treats each language of a node as a separate entity while Drupal does not. To keep track
of these ids there is one entry per language in the `drupal_wagtail_id_map` table.

Also, Wagtail differentiates translations by giving them a different parent page. Those parent pages
are automatically created as part of the wagtail setup with the `./manage.py setup_locales` and
`./manage.py loaddata home_translations` commands. Those parent pages have to be manually identified at
`/admin/config/system/wagtail-api-credentials`. Nodes will handle this translation in `SfgApiNodeBase`.

## Commands
There are two custom drush commands for pushing nodes into the Wagtail API.

- `sfgov_api:push_entity `(alias: pe): pushes a single entity into Wagtail.
e.g. `pe node step_by_step en 610` (push step-by-step node 610)
- `sfgov_api:push_entity_by_bundle` (alias: peb): pushes every entity of a provided bundle type.
e.g. `drush peb node step_by_step es` (push every spanish step by step)

**Note: At time of writing these are only designed to support pushing nodes and their attached paragraphs**

Both of these commands have the following optional parameters
- `--print`: Use this for debugging, it will print some useful error data to the console and add two files to
`web/modules/custom/sfgov_api/src/Drush/Errors`. One file is an exact print of the curl command used for the push, the
other is an html file of what went wrong in Wagtail.
- `--stub`: Use to push just the stub data of an entity (title and slug)
- `--update`: Update the data of an existing node in Wagtail

Both of these basically route to the `pushToWagtail` function which does the heavy lifting.

## Error handling
There are three primary types of errors that can come from this process. This list might expand
in the future.

**No Translation:** This simply means that there was no translation of the node available. Not really an error, but the
error logs and tables look confusing without explicitly calling this out.
**Wagtail API:** These are json errors that are returned from the API directly (e.g. missing required fields)
**Wagtail Errors:** Sometimes when you push it breaks Wagtail in a non-api way. Wagtail's response is to generate
an html page with the full error message (e.g. pushing a slug that already exists)

Every node that that is pushed registers data to the `drupal_wagtail_id_map` table. If it has an error then it will
record the error id which can then be looked up in the `drupal_wagtail_errors` table.

# Miscellaneous
**Unused Code**
There are some pieces of code that are currently not being used but might be useful in the future and/or have
been useful in previous iterations including:
  - Plugin's ability to set a wag_bundle
  - Most of the data in the `drupal_data` array when rendering entities
  - `getWagtailTime` and `generateRandomSlug` in the `ApiFieldHelperTrait`

**Shape of Wagtail Data**
If you need to see the shape of data in Wagtail, create that page, then go to its
corresponding API page. Ie, if you make a StepByStep of ID 8, you can see the shape
of the data by going to `http://127.0.0.1:8000/api/cms/sf.StepByStep/8`

# SF.gov API
This module builds and exposes json data from the Drupal side and then provides means of pushing that data into Wagtail.

# Setup
## Local setup
1. This setup assumes you're hosting your Drupal site through lando.
2. Install the wagtail site locally from sfgov's github
3. Install this module and run a config import so that all of the settings at `/admin/config/system/wagtail-api-credentials` are properly set up.
3. Force wagtail to accept external connections by adding the following to your local_settings.py in the Wagtail repo.
```
ALLOWED_HOSTS = ['localhost', 'host.docker.internal', '127.0.0.1']
```

## Live Setup
TBD

## API structure
This module uses Drupal's plugin system to create a flexible and extendable variety of api endpoints.
Most of the work happens in `sfgov_api/src/SfgApiPluginBase.php` which pulls information from annotations/url arguments/plugins.
It then uses this data to build a payload (`sfgov_api/Payload/Payload`) of that entity that can be viewed at the `/sfgov-api-viewer` path
and pushed to Wagtail using the `sfgov_api:push_entity` and `sfgov_api:push_entity_by_bundle` drush commands.

The payload consists of four main elements each of which are assembled in slightly different ways. Broadly speaking, the plugin system
pulls entity and field data into the Payload object which refines the data down to the following components.
  - **metadata**: This is basic data to use for organizational purposes (id, entity type, bundle, etc).
  - **stub data**: This is just the data needed to push a stub entity to Wagtail for entity referencing purpises (Note: only for nodes).
  - **errors**: Errors accumulated by the plugins on the way to the payload object. Look for the `addPluginError` function for examples.
  - **payload data**: The actual fields and values that will be json_encoded and sent to Wagtail. Consists of the following elements:
    - **base_data**: This is where we would add in any that wagtail expects for all entities of that type. Set by the entity base plugin like `sfgov_api/src/SfgApiNodePluginBase.php`
    - **custom_data**: This is where we manipulate the data for the individual field values of the entity. Set by the bundle plugin like `sfgov_api/src/Plugin/SfgApi/Node/StepByStep.php`

## Building on the API
With this structure, every entity that needs to be exposed for the API will need its own plugin. There are example
plugins in place for nodes, paragraphs, and media.

You can easily generate new plugins with the command `drush generate sfgov:api-plugin`

When building a new plugin make sure to respect the
different layers of responsibility set out in the previous section. Each entity level plugin should focus on building
the actual field data that wagtail expects in the `setCustomData` function. They return an array shaped like so

```
  wagtail_field_name_1: processed_drupal_data_1
  wagtail_field_name_2: processed_drupal_data_2
```

The part that processes the drupal data can simply pull it from the entity:
`$entity->get('field_description')->value`.

Or it can be more elaborate and wrap the data in some kind of function that processes the data further:
`$this->getReferencedData($entity->get('field_process_steps')->referencedEntities(), 'step')`

If you need to make small adjustments to the data that are only relevant to that entity, you can add
some functions to the entity plugin itself (see `sfgov_api\Plugin\SfgApi\Node\News::fixNewsType`)

Helper functions with broader applicability to more than one entity should be added to
`sfgov_api/src/Plugin/SfgApi/ApiFieldHelperTrait.php` so that they can be used in other plugins.

Note: The aforementioned `getReferencedData` function relies on there being a plugin for the entity type it is referencing. It
uses said plugin to map out the fields and data.

# Viewing API Data
There is currently one route that shows entity data. You must provide the entity type, language, bundle, and entity id
as arguments and it will display that entity in the format that will be pushed to Wagtail. This is very helpful for debugging
the plugins.

Examples:
 - node step_by_step:610 in english: `/sfgov-api-viewer/entity/en/node/step_by_step/610`
 - node step_by_step:610 in spanish: `/sfgov-api-viewer/entity/es/node/step_by_step/610`
 - node step_by_step:611 in spanish: `/sfgov-api-viewer/entity/es/node/step_by_step/611`
    - Gives an error because there is no spanish translation of this node.
 - media image:8825 in english `/sfgov-api-viewer/entity/en/media/image/8825`
 - paragraph process_step:1646 in english: `/sfgov-api-viewer/entity/en/paragraph/process_step/1646`

**For an entity type to work it has to have a corresponding plugin in sfgov_api/src/Plugin/SfgApi**

# Pushing to Wagtail

## Migration Strategy
A successful push to the Wagtail API returns the wagtail id of the page created. Those ids then get stored in the
`drupal_wagtail_node_id_map` table next to their Drupal ID. This allows Drupal to have some idea of what has been migrated and where
The plan is to migrate all nodes in stub form first then update them to have their actual content. It has to be done this
way to make the entity relationships work.

**Note:** Media follows the same standard but is stored at `drupal_wagtail_media_id_map`. The rest of this doc will only mention
the node table, but whatever applies to it also applies to the media table.

### Entity Relationships
Wagtail does normal entity reference fields by connecting them to a secondary table entity to create the relationship
(see `/api/cms/sf.RelatedContentAgency` in the Wagtail API for an example). This API will push normal entity reference
fields as the following, and Wagtail will figure it out from there (feature in progress).
```
  'drupal_id' = $entity_id;
  'wagtail_id' = $wagtail_id;
  'entity_type' = $entity_type;
  'bundle' = $bundle;
```
If the entity reference is in a streamfield (wagtail's answer to paragraphs) then Wagtail stores the relationship like
so where "value" is the page id. This API will push data in the same shape minus the id (feature in progress).
```
id: "{long-random-string}",
type: "transaction",
value: "2",
```

### Translations
Wagtail treats each language of a node as a separate entity while Drupal does not. To keep track
of these ids there is one entry per language in the `drupal_wagtail_id_node_map` table.

Also, Wagtail differentiates translations by giving them a different parent page. Those parent pages
are automatically created as part of the wagtail setup with the `./manage.py setup_locales` and
`./manage.py loaddata home_translations` commands. Those parent pages have to be manually identified at
`/admin/config/system/wagtail-api-credentials`. Nodes will handle this translation in `SfgApiNodeBase`.

**Note: All of the english nodes should be stubbed before its translations are pushed**

## Commands
There are two custom drush commands for pushing entities into the Wagtail API.

- `sfgov_api:push_entity `(alias: pe): pushes a single entity into Wagtail.
   - `pe node step_by_step en 610`: push step-by-step node 610 in english
   - `pe media image en 8825`: push image media 8825 in english
- `sfgov_api:push_entity_by_bundle` (alias: peb): pushes every entity of a provided bundle type.
  - `drush peb node step_by_step es` push every spanish step by step

**Note: At time of writing these are only designed to support pushing nodes and their attached paragraphs**

Both of these commands have the following optional parameters
- `--print`: Use this for debugging, it will print some useful error data to the console and add two files to
`web/modules/custom/sfgov_api/src/Drush/Errors`. One file is an exact print of the curl command used for the push, the
other is an html file of what went wrong in Wagtail.
- `--stub`: Use to push just the stub data of an entity (title, slug, parent page)
- `--update`: Update the data of an existing node in Wagtail

Both of these basically route to the `pushToWagtail` function which does the heavy lifting.

## Error handling
There are three primary types of errors that can come from this process. This list might expand
in the future.

- **No Translation:** This simply means that there was no translation of the node available. Not really an error, but the
error logs and tables look confusing without explicitly calling this out.
- **Wagtail API:** These are json errors that are returned from the API directly (e.g. missing required fields)
- **Wagtail Errors:** Sometimes when you push it breaks Wagtail in a non-api way. Wagtail's response is to generate
an html page with the full error message (e.g. pushing a slug that already exists)

Every node that that is pushed registers data to the `drupal_wagtail_node_id_map` table. If it has an error then it will
record the error id which can then be looked up in the `drupal_wagtail_errors` table.

# Wrangling Wagtail
**Shape of Wagtail Data**
If you need to see the shape of data in Wagtail, create that page, then go to its
corresponding API page. Ie, if you make a StepByStep of ID 8, you can see the shape
of the data by going to `http://127.0.0.1:8000/api/cms/sf.StepByStep/8`

**Delete an individual Wagtail Node**
A normal wagtail page will live at something like `http://localhost:8000/admin/pages/6/edit/`. If you want to delete just
that node go to its corresponding api page `http://localhost:8000/api/cms/sf.StepByStep/6` and click the red DELETE button.

**Nuking from Orbit**
You're likely to have to reset wagtail during testing. Copy and paste the following into your terminal to do so.
```
dropdb ds_platform &&
createdb ds_platform &&
./manage.py migrate &&
echo "from django.contrib.auth import get_user_model; User = get_user_model(); User.objects.create_superuser('admin', 'admin@example.com', 'admin')" | ./manage.py shell
./manage.py setup_locales &&
./manage.py loaddata home_translations &&
./manage.py runserver

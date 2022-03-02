README
---------------------
## Introduction
  This module provides structure and resources for using migrate API within the sfgov site

## Structure
- sfgov_migrate is the top level module that contains all of the submodules and
  mainly exists for organization and dependencies
- sfgov_update_fields provides a migration group and some examples for changing
  a field type by migrating the data between fields within the same node

## Tips
- Every time you change a migration file you need to run the following to import
  the changes into Drupal `lando migrate-import module_name`
- To reset a migration (stop, reset, rollback) run `lando migrate-reset migration_id`
- To reroll a migration (stop, reset, rollback, start) run `lando migrate-reroll migration_id`
- These shortcuts and others are configured in `scripts/migrate` and the tooling section of `.lando.yml`
- To migrate a specific entity, add the following to its migration.
  This would migrate ONLY the node with id 123
  ```php
    nid:
      plugin: skip_on_value
      source: tnid
      method: row
      not_equals: TRUE
      value: 123
  ```


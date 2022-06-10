README
---------------------
## Introduction
  This module provides structure and resources for using migrate API to move data between fields

## Details
- Normally migrations are done from one data source to another, but this one is within the same data source.
- Normally a migration will create a new node. However, by using the `overwrite_properties` key and providing a reference to a nid, migrate API will instead search for existing nodes and override that listed field value
- Migrate rollback doesn't work as usual in this instance because its designed to delete nodes that were created in a migration, not nodes that were already there.
- Because its hard to rollback a migration, I recommend working with a single node during development rather than the entire set.
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

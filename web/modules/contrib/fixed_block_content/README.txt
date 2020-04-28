FIXED CONTENT BLOCKS DRUPAL MODULE
==================================

CONTENTS OF THIS FILE
---------------------

 * Summary
 * Requirements
 * Installation
 * Configuration
 * Usage
 * Related modules
 * Contact


SUMMARY
-------

This module provides a way to create custom content blocks without broken
instances if the block does not exist.

A new block type act as a wrapper for the content block. If the custom block
disappears, this module will re-create it as a new empty block or with a
default content stored in config.

This module solves these typical scenarios:

 * Missing blocks in website staging
 
   No more "This block is broken or missing. You may be missing
   content or you might need to enable the original module.".

   Custom blocks created in your local environment are not ported to other site
   environments (like test, production ...), as a result any reference
   (instance) to them will ends in a "broken block".

   Fixed content blocks are part site configuration, so they are promoted in
   the process of staging.

 * Permanent content blocks
 
   Custom blocks, as content entities, can be deleted. If so, their instances
   will be lost. A fixed block content is always present in any layout
   placement, even if the linked custom block disappears.

Multiple instances of a single fixed block are allowed as usual in D8.


REQUIREMENTS
------------

This module depends on:
  * the custom block core module (block_content)
  * the HAL core module (hal) and serialization to store default block content
    in config


INSTALLATION
------------

Install as usual, see https://www.drupal.org/node/1897420 for further
information. You might rebuild the caches after (un)installation.


CONFIGURATION
-------------

Module configuration is available at Manage -> Structure -> Block layout ->
Custom block library -> Fixed blocks
(/admin/structure/block/block-content/fixed-block-content).


USAGE
-----

As simple as:

 * add a fixed block content
 * create instances when you need it

Set a default content by editing your custom block and going to such link
operation in the fixed blocks list.

Missing custom blocks are automatically created when needed, with default
content if present or empty if no default content established.


RELATED MODULES
---------------

Similar modules:

 * Simple Block
   (https://www.drupal.org/project/simple_block)
   Provides a way for creating static and simple blocks. Only a title and
   a body allowed, config entities are no "fieldable". Simple blocks are
   entirely stored in the site configuration, so cannnot be managed as
   content by editors or non-admin users.

   From the module description: "...lets you define simple exportable blocks
   that have only a title and a formatted text ... unlike the core Block Content
   (block_content), this module stores the blocks as config entities making the
   import/export trivial..."

 * Recreate Block Content
   (https://www.drupal.org/project/recreate_block_content)
   A simpler approach that also solves the broken block content instances. It
   simply creates a new empty custom block when missing with the same IDs but
   with no default content.

Recommended modules:

 * Default content
   (https://www.drupal.org/project/default_content)
   Exports any content entity within the website code. Any new installation will
   start with the exported content.

 * Page manager
   (https://www.drupal.org/project/page_manager)
   From module description: "It supports the creation of new pages, and allows
   placing blocks within that page." So if you need a kind of "fixed pages",
   this module provides that and much more.


CONTACT
-------

Current maintainers:
* Manuel Adan (manuel.adan) - https://www.drupal.org/user/516420

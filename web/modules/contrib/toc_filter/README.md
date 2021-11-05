
Contents of this file
---------------------

 * Introduction
 * Demo
 * Requirements
 * Features
 * Installation 
 * Similar Modules
 * Notes


Introduction
------------

Converts header tags into a hierarchical table of contents using Drupal's input
filter system.

The use case that the TOC filter module addresses is one of the simplest and 
most common  approaches for table of contents in Drupal. Basically, you just 
enable the 'TOC filter' module add a `[toc]` token to your HTML and it will be 
replaced with a responsive table of contents.


Demo
----

> Evaluate this project online using [simplytest.me](https://simplytest.me/project/toc_filter).


Requirements
------------

The TOC filter 8.x-2.x requires the [TOC API](https://www.drupal.org/project/toc_api) 
module which provides a framework for  creating table of contents (TOC) from an
HTML document's header tags.

 
Features
--------

Besides the flexibility, provided by the TOC API, for defining how a table of 
contents is displayed, the TOC filter module includes the ability to 

- Move table of contents to a block
- Customize an individual table of contents


Installation
------------

IMPORTANT:
Make sure your text formats are configured to support selected header tags.

1. Copy/upload the toc_filter.module and toc_api.module to the 
   modules directory of your Drupal installation.

2. Enable the 'TOC filter' module in 'Extend'. (/admin/modules)

3. Visit the 'Configuration > Content authoring > Text formats and editors'
   (/admin/config/content/formats). Click "configure" next to the input format 
   you want to enable the 'Table of Contents' filter on.

4. Enable (check) the 'Table of Contents' filter under the list of filters and save
   the configuration.

5. (optional) Visit the 'Configuration > Site Structure > Table of contents'
   (/admin/structure/toc). 

6. (optional) Place the TOC filter block on all pages.
   (/admin/structure/block)

     

Similar Modules
---------------

- [Comparison of Table of Contents / TOC modules](https://www.drupal.org/node/2278811)


Author/Maintainer
-----------------

- [Jacob Rockowitz](http://drupal.org/user/371407)

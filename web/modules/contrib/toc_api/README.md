
Table of Contents
-----------------

* Introduction
* Implementations
* Notes
* References


Introduction
------------

This module provides a framework for creating table of contents (TOC) from 
an HTML fragment's header tags. 

The TOC API consists of a several classes, services, and plugin:
            
- **Toc**: A class that parses the header tags from an HTML fragment and 
  stores a index of these headers that represent a hierarchical table 
  of contents.
  
- **TocType**: A configuration entity that contains options for customizing a 
  table of contents.

- **TocFormatter**: A service for formatting a table of content's headers, 
  numbering, and ids.

- **TocManager**: A service that creates and manages table of contents instances.

- **TocBuilder**: A service that builds and renders a table of contents and 
  update an HTML document's headers.

- **TocBlockBase**: A base block which displays the current TOC module's 
  TOC in a block.

The TOC API includes templates for formatting a table of contents as a
**hierarchical tree** or **jump menu**.  All TOC types included in the TOC API
are responsive with a hierarchical tree being displayed for desktop users 
and a jump menu displayed for mobile users (browser width less than 768px).

TOC Type features

- Only display TOC if the content contains a specified amount of HTML headers.
- Define min and max headers to be included in table of contents
- Set list style type and formatting for each header
- Set back to top appearance and labeling for each header
- Display entire path for hierarchical headers
- Limit allowed HTML tags with table of contents headers and links
- Provides the ability to move a TOC into a block.


Implementations
---------------

Note that this module does NOT directly expose any mechanisms for displaying a 
table of contents, you must create a custom module and/or install one of the 
below modules to implement a table of contents.

### Custom Module Implementation

Below is very simple example of a hook from the TOC API example module
(toc_api_example.module) that adds a TOC to all page and article nodes on a 
website.

    <?php
    /**
     * @file
     * Example of a custom implementation of the TOC API that adds a table of contents to specified content types.
     */
    
    use Drupal\node\NodeInterface;
    use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
    use Drupal\toc_api\Entity\TocType;
    
    /**
     * Implements hook_node_view().
     */
    function toc_api_example_node_view(array &$build, NodeInterface $node, EntityViewDisplayInterface $display, $view_mode) {
      // Add TOC to 'page' and 'article' content types that are being viewed as a full (page) with a body field.
      if (in_array($node->getType(), ['page', 'article']) && $view_mode == 'full' && isset($build['body'][0])) {
        // Get the completely render (and filtered) body value.
        $body = (string) \Drupal::service('renderer')->render($build['body'][0]);
    
        // Get 'default' TOC type options.
        /** @var Drupal\toc_api\TocTypeInterface $toc_type */
        $toc_type = TocType::load('default');
        $options = ($toc_type) ? $toc_type->getOptions() : [];
    
        // Create a TOC instance using the TOC manager.
        /** @var \Drupal\toc_api\TocManagerInterface $toc_manager */
        $toc_manager = \Drupal::service('toc_api.manager');
        /** @var \Drupal\toc_api\TocInterface $toc */
        $toc = $toc_manager->create('toc_filter', $body, $options);
    
        // If the TOC is visible (ie has more than X headers), replace the body
        // render array with the TOC and update body content using the TOC builder.
        if ($toc->isVisible()) {
          /** @var \Drupal\toc_api\TocBuilderInterface $toc_builder */
          $toc_builder = \Drupal::service('toc_api.builder');
          $build['body'][0] = [
            'toc' => $toc_builder->buildToc($toc),
            'content' => $toc_builder->buildContent($toc),
          ];
        }
      }
    }
   


### Contrib Modules using TOC API

- **[TOC filter](https://www.drupal.org/project/toc_filter)**  
  Converts header tags into a hierarchical table of contents using Drupal's input
  filter system.  
  

Notes
-----

- The TOC API is based on Drupal 8's OO architecture and patterns while the 
  supported feature set is based on the 
  [Table of contents](https://www.drupal.org/project/tableofcontents) module.
  
- Only TOC parsing and TOC building are included in the TOC API.
  JavaScript enhancements for hide/show logic and smooth scrolling are not being
  included and should be handled via custom code and/or a contrib module.
  
- Even though, the TOC API's module's namespace is 'toc_api', all classes, services, 
  and templates are using the 'toc' or 'Toc' namespace, because there is minimal 
  risk of namespace conflicts with the [TOC module](https://www.drupal.org/project/toc)
  because the TOC module does not implement any APIs. The TOC module just 
  generates a table of contents for specific page content using JavaScript.


References
----------

- [Comparison of Table of Contents / TOC modules](https://www.drupal.org/node/2278811)
- [TOC for D7 (and D8)](https://www.drupal.org/node/1424896)


Author/Maintainer
-----------------

- [Jacob Rockowitz](http://drupal.org/user/371407)

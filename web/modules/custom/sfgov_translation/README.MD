README
---------------------
# Introduction
  This module provides customizations on top of the existing drupal
  translate/tmgmt/lionbridge workflow to make translations easier for editors.

# Features
## Mark Outdated
  All nodes now have two new base fields, translation_outdated and translation_notes.
  The logic around these base fields allows editors to update an english node,
  then click a button to mark all of that nodes' translations as out of date and
  provide any notes that the translation editors might need. Both of these fields
  will only appear if a node has existing translations. The button to mark nodes
  as outdated will only appear on english nodes, and the checkbox that a node is outdated
  will only appear on non-english nodes. This field is also marked as false by
  default. All of this logic is in the module file.

## Custom view field
  This module adds a custom view field which uses the tmgmt translation status icons
  to display a node's translation status on a per language basis. It pulls data
  from both TMGMT and the 'mark outdated' feature above. It marks nodes as:
    * No translation (X)
    * Translated (Checkmark)
    * In progress (Hourglass)
    * Out of date (Orange circle)

## Translation Status Filter
  The 'Translation Status' filter is more complex than it looks. First, it only works properly
  when there is also a 'default translation' filter on the view. This filter allows you to filter by
  whether or not a node has a translation and is off by default. The 'Translation Status'
  filter does the following based on its state
    * - Any -: Shows all nodes, 'default translation' is off
    * Translation Outdated: Shows nodes that have been marked as outdated, 'default translation' is off
    * Translated: Shows all nodes with a translation. Ignores 'translation_outdated' status, 'default translation' is on

## Miscellaneous
 * This module hides the existing translation UI on nodes because it might get mixed up with the new functionalty.
 * The existing system to mark translations as outdated is limited by a node's workflow state, we wanted this
   functionalty to work independently of that system, so we built our own.
 * When a translation is completed by tmgmt, this module automatically unchecks the 'translation_outdated' checkbox.

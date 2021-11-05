CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usage Notes
 * Maintainers


INTRODUCTION
------------

The File Delete module adds the ability to easily delete files —both private 
and public— within Drupal administration.

It changes files from the "Permanent" status to the "Temporary" status. These 
files will be deleted by Drupal during its cron runs.

If a file is registered as being used somewhere, the Module will not allow it 
to be deleted.

 * For a full description of the module, visit the project page:  
   https://www.drupal.org/project/file_delete

 * To submit bug reports and feature suggestions, or to track changes:  
   https://www.drupal.org/project/issues/file_delete


REQUIREMENTS
------------

No special requirements.


INSTALLATION
------------

 * We recommend installing the module via Composer.
   ```
   composer require drupal/file_delete
   ```
   Otherwise, Install as you would normally install a contributed Drupal module.  
   See: https://www.drupal.org/node/1897420 for further information.
   

CONFIGURATION
-------------

 * Configure the user permissions in Administration » People » Permissions:

   - Delete files

     Users with this permission will gain access to delete file entities.

 * Add a Delete link to your Files View in Administration » Structure » Views »
   Files » Edit  
   A new "Link to delete File" field should be available.

 * A Delete link should now be visible for files in Administration » Content »
   Files
 

USAGE NOTES
-----------

#### File is set to 'Temporary' but not getting deleted after a cron run
In Drupal, Temporary files generally kept for some time — default 6 hours —
before being deleted.  
You can configure this time in 
Administration » Configuration » Media » File System


#### Working with Drupal Media
If you added an image to the website as a Drupal Media entity, you will have to
follow these steps.
1. **Important:** Confirm that this Media is not being used in your site.
2. Delete this Media entity in Administration » Content » Media
3. Now you can delete the file in Administration » Content » Files


##### Why is this the case?
Drupal's File Usage system still needs some work. It does not correctly track
all usages within Drupal. Most of the work related to this is being tracked in
[this issue](https://www.drupal.org/project/drupal/issues/2821423)

Specific to Drupal Media, the work is being tracked in 
[this issue](https://www.drupal.org/project/drupal/issues/2835840)  


MAINTAINERS
-----------

#### Current maintainers:
 * Jonathan Eom (jonnyeom) - https://www.drupal.org/u/jonnyeom

#ACKNOWLEDGEMENTS

This is a Drupal 8 rewrite of CER, which was in turn the next
generation of Corresponding Node References.

Credit goes to everyone who's worked on CER and CNR in the
past!

#DESCRIPTION

CER keeps reference fields in sync. If entity Alice references entity 
Bob, CER will make Bob back-reference Alice automatically, and it will 
continue to keep the two in sync if either one is changed or deleted. 
CER does this by way of “presets”, which are relationships you set up 
between reference-type fields.

By “reference-type fields”, I mean any kind of field that references 
an entity.

Out of the box, CER integrates with any core Entity Reference field. 
(More field integrations are planned).

#DEPENDENCIES

- Entity Reference (In core)

#CREATING PRESETS

CER won’t do anything until you create at least one preset. To create a 
preset, visit admin/config/content/cer and click “Add corresponding reference”.

1. Name your preset so you remember what it's for
2. Select the fields and entity bundles you would like to correspond to one another.
3. Save the Corresponding Reference, and you're all set!

#THINGS YOU SHOULD KNOW

* There is no upgrade path from previous versions of CER or CNR, but you shouldn't
really need one! CER for Drupal 8 doesn't store any additional information about each
referenced entity, so simply configure CER after you upgrade your site to D8, and it should
continue working.
* Everything CER does, it does in a normal security context. This can lead to unexpected 
behavior if you’re not aware of it. In other words, if you don’t have the permission to 
view a specific node, don’t expect CER to be able to reference it when logged in as you. Be 
mindful of your entity/field permissions!
  
#ROAD MAP

CER for D8 is still under heavy development. Some of the things you can help contribute to:
* A better configuration UI.
* Synchronization functionality for existing references.
* A plugin system for supporting more field types.
* Bug fixes!

#MAINTAINER

Questions, comments, etc. should be directed to bmcclure (ben.mcclure@gmail.com).

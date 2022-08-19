# SF.gov Untranslated Path Aliases

- [Introduction](#introduction)
- [Configuration](#configuration)
- [Testing](#testing)
- [Content](#content)

## Functionality
Drupal makes untranslated content available under a default "/[lang]/node/[id]" path. This modules alters the
alias manager to enable the untranslated content to have the same node alias as the original content.

Example:

- An original "en" node, say, "/node/5" with a node alias "/wingardium-leviosa".
- If the node has no translations:
  - Without this module, translations would only be available under "/[lang]/node/5".
  - With this module, the translations will be available under "/[lang]/wingardium-leviosa".

Furthermore, it provides the same "alias" functionality for all content, not just nodes (views, taxonomy terms, etc.),
essentially enabling a translation alias for all content that does not have an existing one.


## Notes
- This module was customized from the [Fake Path Alias](https://www.drupal.org/project/fake_path_alias) contrib module.
- When enabled, all untranslated content will get aliases, as they are looked-for on-the-fly.
- If disabling this module, consider that the untranslated aliases will no longer function, and work may be needed to
redirect traffic from non-existing aliases to the original URLs.

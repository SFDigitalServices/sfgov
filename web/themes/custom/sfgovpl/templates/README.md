# Making Templates Translation friendly.

## Problem:
 Gtranslate translates the entire page but we want referenced entities that have human/Drupal translations to use those translations instead of the machine translation on that part of the page.

## Solution:
Replace the translated node object in the render array.
@see sfgov_translation_preprocess_node()

## Rules for respecting translations:

1, The following variables are very reliable:

- `node.label`
- `url`
- `node.field_FIELD_NAME.value`

2. Variables that start with `content` will need to be tested for translation awareness.

3. When referencing entities, it's more reliable to render them than to daisy chain values (i.e., `content.field_NAME.entity.label`)

4. Full page and default templates (i.e., node--TYPE--full.html.twig) don't need to adhere to these rules since the whole page will either be translated by gtranslate or not.

5. Include the `attributes` array in the template so the `notranslate` class can be added.

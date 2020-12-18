# SF Gov Form.io

- [Introduction](#introduction)
- [Configuration](#configuration)
- [Testing](#testing)
- [Content](#content)

## Introduction

This module loads the dependencies needed for Form.io functionality on SF.gov. It also provides a settings form, and includes other general customizations. The two dependencies are:

- [Formio.js](https://github.com/formio/formio.js): The Form.io JavaScript library.
- [formio-sfds](https://github.com/SFDigitalServices/formio-sfds): The SFDS Form.io theme, which provides custom templates, styles, and facilitates other functionality, such as form translations.

## Configuration

The version of each dependency can be configured in the administrative backend, using the following instructions:

1. Log in as a privileged user, and visit `/admin/config/services/sfgov_formio`.
2. Enter the desired version for each dependency, e.g. `7.0.0-rc.1`. If left blank, the latest release will be loaded.
3. Save.

_Note: The configuration must be exported to code in order to persist through deployments. The values defined in the configuration settings file ([config/sfgov_formio.settings.yml](/config/sfgov_formio.settings.yml)), will overwrite database settings with each deployment._

## Testing

It's possible to test different versions, without having to edit the above settings, by using the following query parameters:

| Parameter name | Example Query String | Resulting Source |
| :--- | :--- | :---
| `formiojsVersion` | `/home?formiojsVersion=4.11.2` | `https://unpkg.com/formiojs@4.11.2/dist/formio.full.min.js` |
| `formio-sfdsVersion` | `/home?formio-sfdsVersion=7.0.2` | `https://unpkg.com/formio-sfds@7.0.2/dist/formio-sfds.standalone.js` |

## Content

Form.io forms are implemented via Paragraphs. There is a `form_io` Paragraph entity, which is exposed in two different entities:

| Type | Entity | URL | When to use | Examples |
| :--- | :--- | :--- | :--- | :--- |
| `form_page` | Content type | `/node/add/form_page` | Useful when the sole purpose of the page is to display the form. |  [/workers-families-first-preapproval-form](https://sf.gov/workers-families-first-preapproval-form), [/apply-for-mini-grant](https://sf.gov/apply-for-mini-grant) |
| `form` | Custom block type | `/block/add/form` | Useful for displaying a form on one or more pages. | Feedback form (bottom of all pages) |

### Adding Form Content

1. Decide which type you need and go to `/node/add/form_page` or `/block/add/form`.
2. Click the "Add Form.io" button to add the Form.io Paragraph.
3. Add the endpoint in the Data Source field, e.g. `https://sfds.form.io/name`
4. Add any options, such as localization, in the Form.io Render Options field, in JSON format.
5. Add a URL in the Confirmation Page URL, if desired.

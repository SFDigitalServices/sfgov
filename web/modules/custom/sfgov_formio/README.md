# SF Gov Form.io

This module stores the library definitions, and related code for Form.io forms.

## Form.io Content

Form.io forms are implemented via Paragraphs. There is a `form_io` Paragraph entity, which is exposed in two different entities:

| Type | Entity | URL | When to use | Examples |
| :--- | :--- | :--- | :--- | :--- |
| `form_page` | Content type | `/node/add/form_page` | Useful when the sole purpose of the page is to display the form. |  [/workers-families-first-preapproval-form](https://sf.gov/workers-families-first-preapproval-form), [/apply-for-mini-grant](https://sf.gov/apply-for-mini-grant) |
| `form` | Custom block type | `/block/add/form` | Useful for displaying a form on one or more pages. | Feedback form (bottom of all pages) |

### Adding Form Content

1. Decide which type you need and go to `/node/add/form_page` or `/block/add/form`.
2. Click the "Add Form.io" button to add the Form.io Paragraph.
3. Add the endpoint in the Data Source field, e.g. `https://sfds.form.io/name`
4. Add any options, such as localization, in the Form.io Render Options field.
5. Add a URL in the Confirmation Page URL, if desired.

## Library Definitions

The source files and dependencies are defined in [`sfgov_formio.libraries.yml`](./sfgov_formio.libraries.yml), which defines two libraries:

1. `sfgov_formio/formio`: The [Formio.js](https://github.com/formio/formio.js) library.
2. `sfgov_formio/formio_sfds`: The [formio-sfds](https://github.com/SFDigitalServices/formio-sfds) library, which provides SFDS-specific templates, customizations, and theme.

### Override Library Definitions in the Admin UI

This module has a settings page that can be used to override the source location for each library in the administrative UI. This allows for easy testing, without needing codebase changes. To use this feature:

1. Log in, and visit `/admin/config/services/sfgov_formio`.
2. Enter a source for the Formio.js library, e.g. `https://unpkg.com/formiojs@4.10.0-rc.6/dist/formio.full.min.js`, or leave blank to use default.
3. Enter a source for formio-sfds, e.g. `https://unpkg.com/formio-sfds@v4.2.0/dist/formio-sfds.standalone.js`, or leave blank to use default.
4. Save.

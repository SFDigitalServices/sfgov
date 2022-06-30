# Translations

This directory contains [gettext] portable object (`.po`) and portable object
_template_ (`.pot`) files for managing translations from English to all of
SF.gov's supported non-English languages:

| code | language
| :--- | :---
| `es` | Spanish
| `zh-hant` | Chinese (Traditional script)
| `fil` | Filipino

All `.po` files are expected to have the language code included in the filename
extension. For instance, assuming `forms.pot` is a collection of English form
strings, there should also be a `forms.es.po`, `forms.zh-hant.po`, and
`forms.fil.po`.

## Templates

The `.pot` template files list all of the English strings that need to be
translated, and can be used to generate the `.po` files for all languages. At
its most minimal, a `.pot` file looks like:

```pot
msgid "English string"
msgstr ""
```

These two lines are repeated for each unique string, with the `msgid` line
defining the English "source" string. The `msgstr` line should be empty.

### Generate `.po` files

Generating the `.po` files for each language is pretty straightforward if you
have the [gettext] command line tools installed. The [msginit] command creates a
`.po` for a given locale (language) from a `.pot` template. So, assuming `group`
is set to the basename of the `.pot` file, e.g. `group=forms` for `forms.pot`:

```sh
group=forms
for lang in es zh-hant fil; do
  msginit --locale=$lang --input=$group.pot --output=$group.$lang.po
done
```

Run `man msginit` or visit [the docs][msginit] for more information and options.

### Update `.po` files with new strings

If you've already got `.po` files and need to sync the list of strings from the
template (`.pot`), you can update the `.po` files with [msgmerge][]. Assuming
`group` is set to the basename of the `.pot` file, e.g. `group=forms` for
`forms.pot`:

```sh
group=forms
for lang in es zh-hant fil; do
  msgmerge -U --suffix=off $group.$lang.po $group.pot
done
```

Running `git diff` will show you any untranslated strings added to the `.po`
files. Run `man msgmerge` or visit [the docs][msgmerge] for more options.

## Manage Drupal user interface strings

You can use Drupal's [drush][] command line tool to export and import user
interface string translations. It's possible to run `drush` directly if you've
installed it globally on your machine, but it's _way_ easier to run it with
either:

- [Lando] in your local environment, via `lando drush`; or
- [Terminus] to interface with Pantheon environments via `terminus drush $env`,
  where `$env` is the Pantheon site ID and environment separated with `.`, i.e.
  `sfgov.dev`, `sfgov.test`, `sfgov.live` (production), or `sfgov.pr-xxxx` for a
  pull request multidev.

### Export strings

The drush [locale:export] command exports [user interface strings] from Drupal.
For instance, to export all of the "customized" string translations from
production (`sfgov.live`), you could run:

```sh
for lang in es zh-hant fil; do
  terminus drush sfgov.live locale:export "$lang" \
    --type=customized > "customized.$lang.po"
done
```

Doing this will output three _very_ large files that should not be committed
to git. Instead, it's best to start with a `.pot` that lists all of the English
strings that need to be translated, then generate the `.po` files from those:

```sh
# assuming a minimal English template
cat << EOF > lyrics.pot
msgid "Hey ya"
msgstr ""
EOF

# create lyrics.*.po with placeholders
for lang in es zh-hant fil; do
  msginit --locale=$lang --input=lyrics.pot --output=lyrics.$lang.po --no-translator
done
```

### Import strings

Importing strings is a bit trickier because `terminus drush` runs in Pantheon,
so the path you provide needs to be fully-qualified and exist on the Pantheon
filesystem. In other words:

**You can't use `terminus drush sfgov.live locale:import` to push translations
from your local filesystem to sf.gov**.

This limitation requires us to keep all of the `.po` files in git and run `drush
locale:import` with fully-qualified source repo paths prefixed with `/code/`. So,
to import all of the strings from git to the test environment, you would run:

```sh
for lang in es zh-hant fil; do
  for file in config/translations/**/*.$lang.po; do
    terminus drush sfgov.test locale:import "$lang" \
      --type=customized --override=customized "/code/$file"
  done
done
```

[gettext]: https://en.wikipedia.org/wiki/Gettext
[drush]: https://www.drush.org/latest/
[lando]: https://docs.lando.dev/
[locale:export]: https://www.drush.org/latest/commands/locale_export/
[locale:import]: https://www.drush.org/latest/commands/locale_import/
[msginit]: https://www.gnu.org/software/gettext/manual/html_node/msginit-Invocation.html
[msgmerge]: https://www.gnu.org/software/gettext/manual/html_node/msgmerge-Invocation.html#msgmerge-Invocation
[terminus]: https://pantheon.io/docs/terminus/
[user interface strings]: https://www.drupal.org/docs/multilingual-guide/translating-site-interfaces

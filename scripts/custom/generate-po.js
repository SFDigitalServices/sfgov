#!/usr/bin/env node
/* eslint-disable no-console, no-magic-numbers */
const { dirname, extname, join } = require('path')
const { readFileSync, writeFileSync } = require('fs')

if (process.argv.includes('--help') || process.argv.length < 4) {
  const script = process.argv[1]
  die(`
Usage:

  cat <tab-separated-input> | ${script} <basename>

Where <tab-separated-input> is tab-separated text (e.g. copied from Excel or Google Sheets)
in the following form:

  en     \tes     \tzh     \tfil
  English\tSpanish\tChinese\tFilipino

In other words:
- Each row is a string to be translated
- Each column is a language identified by its language code in the header

This script generates a gettext .po file for each language named:

  <basename>.<lang>.po

So, if you copy spreadsheet content in macOS and run:

  pbpaste | ${script} config/translations/custom

You should get files like:

  config/translations/custom.es.po
  config/translations/custom.zh.po
  config/translations/custom.fil.po
`)
}

const [
  base,
  description = ''
] = process.argv.slice(2)

const TAB = '\t'
const lines = readFileSync('/dev/stdin', 'utf8')
  .split(/[\r\n]+/)
  .map(line => line.trim())
  .filter(Boolean)

if (lines.length < 2) {
  die('You must provide at least two lines of tab-sepatated input')
}

const columns = lines.shift().split(TAB)
if (!columns.includes('en')) {
  die('One column must be the "en" language code')
} else if (columns.length < 2) {
  die('Only got one column; ')
}
const translations = lines.map(line => {
  const parts = line.split(TAB)
  return Object.fromEntries(
    columns.map((col, i) => [col, parts[i]])
  )
})

const entriesByLang = Object.fromEntries(
  columns.filter(col => col !== 'en').map(lang => [lang, {}])
)

for (const { en, ...rest } of translations) {
  for (const [lang, string] of Object.entries(rest)) {
    entriesByLang[lang][en] = string
  }
}

const dir = dirname(base)
const basename = extname(base)
for (const [lang, strings] of Object.entries(entriesByLang)) {
  const path = join(dir, `${basename}.${lang}.po`)
  const header = `# ${lang} translations for ${description || basename}
#
msgid ""
msgstr ""
"Content-Type: text/plain; charset=utf-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"`

  const entries = Object.entries(strings).map(([id, str]) => {
    return `msgid ${JSON.stringify(id)}\nmsgstr ${JSON.stringify(str)}`
  })
  writeFileSync(path, `${header}\n\n${entries.join('\n\n')}`)
}

function die (error) {
  console.error(error)
  process.exit(1)
}

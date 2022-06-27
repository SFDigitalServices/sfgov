/* eslint-disable no-magic-numbers */
const { danger, warn, fail } = require('danger')
const { sync: globbySync } = require('globby')
const { basename, join } = require('path')
const { locales } = require('./config/translations/locales.json')

const MIN_PR_DESC_LENGTH = 50
const JIRA_REF_PATTERN = /\b[A-Z]+-\d+\b/
const translationLangs = Object.keys(locales).filter(lang => lang !== 'en')

const { pr } = danger.github
const prDescription = pr.body || ''
if (!JIRA_REF_PATTERN.test(prDescription)) {
  warn('Please include at least one Jira ticket, e.g. SG-123 or DESSYS-123.')
} else if (prDescription.length < MIN_PR_DESC_LENGTH) {
  warn(`Your PR description is too short (&lt;${MIN_PR_DESC_LENGTH} characters). Please be more descriptive.`)
}

checkTranslations('config/translations')

function checkTranslations (cwd) {
  const potFiles = globbySync('**/*.pot', { cwd })
  const poFiles = globbySync('**/*.po', { cwd })
  const missingTemplates = []

  for (const poFile of poFiles) {
    const base = basename(poFile)
    const parts = base.split('.')
    const [lang] = parts.length > 2 ? parts.slice(-2) : []
    if (!lang) {
      const possibleNames = translationLangs.map(lang => `${base.replace('.po', `.${lang}.po`)} (${locales[lang]})`)
      warn(`Missing language code in filename: ${code(join(cwd, poFile))} should be named ${list(possibleNames.map(code), ', or ')}`)
    } else if (!translationLangs.includes(lang)) {
      warn(`Unexpected language code ${code(lang)} in ${code(join(cwd, poFile))} (expected ${list(translationLangs.map(code), ', or ')})`)
    } else {
      const potFile = poFile.replace(lang ? `.${lang}.po` : '.po', '.pot')
      if (!potFiles.includes(potFile) && !missingTemplates.includes(potFile)) {
        missingTemplates.push(potFile)
      }
    }
  }

  for (const potFile of missingTemplates) {
    warn(`Missing English template ${code(join(cwd, potFile))} for ${code(potFile.replace('.pot', '.*.po'))}`)
  }

  for (const potFile of potFiles) {
    const missingTranslations = translationLangs
      .map(lang => ({ lang, path: potFile.replace(/\.pot$/, `.${lang}.po`) }))
      .filter(({ path }) => !poFiles.includes(path))
    if (missingTranslations.length > 0) {
      const missingNames = missingTranslations.map(({ lang, path }) => `${code(path)} (${locales[lang]})`)
      warn(`Missing translations for ${code(join(cwd, potFile))}: ${list(missingNames, ', and ')}`)
    }
  }
}

function list (parts, lastGlue, glue = ', ') {
  const { length } = parts
  if (length < 2) return parts.join(glue)
  const last = length - 1
  const secondToLast = last - 1
  return parts.flatMap((part, i) => {
    return i === last ? part : [part, i === secondToLast ? lastGlue : glue]
  }).join('')
}

function code (str) {
  return `\`${str}\``
}

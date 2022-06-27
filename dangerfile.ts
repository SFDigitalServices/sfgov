import { danger, warn, fail } from 'danger'
import { globbySync } from 'globby'
import { basename } from 'path'
import { locales } from './config/translations/locales.json'

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

function checkTranslations (cwd: string) {

  const potFiles = globbySync('**/*.pot', { cwd })
  const poFiles = globbySync('**/*.po', { cwd })
  const missingTemplates = new Set<string>()

  for (const poFile of poFiles) {
    const base = basename(poFile)
    const parts = base.split('.')
    const [lang] = parts.slice(-2)
    if (!lang) {
      fail(`Missing language code in .po filename: ${cwd}/${base} (should be ${parts.splice(-2, 0, translationLangs.join('|')).join('.')})`)
    } else if (!translationLangs.includes(lang)) {
      fail(`Invalid language code in ${poFile}: "${lang}"; expected one of "${translationLangs.join('", "')}"`)
    }
    const potFile = poFile.replace(/\.po$/, '.pot')
    if (!potFiles.includes(potFile) && !missingTemplates.has(potFile)) {
      fail(`Missing English template ${potFile} to accompany ${cwd}/${parts.splice(-2, 0, '*').join('.')}`)
      missingTemplates.add(potFile)
    }
  }

  for (const potFile of potFiles) {
    for (const lang of translationLangs) {
      const poFile = potFile.replace(/\.pot$/, `.${lang}.po`)
      if (!poFiles.includes(poFile)) {
        fail(`Missing ${locales[lang]} translation for ${potFile}: expected ${poFile}`)
      }
    }
  }
}

import { danger, warn } from 'danger'

const MIN_PR_DESC_LENGTH = 50
const JIRA_REF_PATTERN = /\b[A-Z]+-\d+\b/
const SUPPORTED_LANGS = ['es', 'zh-hant', 'fil']
const LOCALES = {
  en: 'English',
  es: 'Spanish',
  'zh-hant': 'Chinese',
  fil: 'Filipino'
}

const {
  base,
  head,
  modified_files: modified,
  created_files: created,
  deleted_files: deleted
} = danger.git

const {
  api,
  pr,
  thisPR: { owner, repo }
} = danger.github

const prDescription = pr.body || ''
if (prDescription.length < MIN_PR_DESC_LENGTH) {
  warn(`Your PR description is too short (&lt;${MIN_PR_DESC_LENGTH} characters). Please be more descriptive.`)
} else if (!JIRA_REF_PATTERN.test(prDescription)) {
  warn('Please include at least one Jira ticket, e.g. SG-123 or DESSYS-123.')
}

const localeFiles = [...created, ...modified].filter(path => path.endsWith('.po'))
const localeGroups = new Map<string, string[]>()

for (const file of localeFiles) {
  const parts: string[] = file.split('/').pop()?.split('.') || []
  if (parts.length < 2) {
    warn('Locale file is missing the language code in its filename (before ".po").', file)
  }
  parts.pop()
  const lang = parts.pop() || ''
  if (!SUPPORTED_LANGS.includes(lang)) {
    warn(`Locale file has an unsupported language code: "${lang}" (should be one of "${SUPPORTED_LANGS.join('", "')}")`, file)
  }
  const group = parts.join('.')
  if (localeGroups.has(group)) {
    localeGroups.get(group)?.push(lang)
  } else {
    localeGroups.set(group, [lang])
  }
}

for (const [group, langs] of localeGroups.entries()) {
  for (const [lang, name] of Object.entries(LOCALES)) {
    if (!langs.includes(lang)) {
      warn(`Locale group "${group}" is missing ${name} translations (expected \`config/translations/${group}.${lang}.po\`)`)
    }
  }
}
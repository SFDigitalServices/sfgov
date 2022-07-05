/* eslint-disable no-magic-numbers */
const { danger, warn } = require('danger')
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

checkMonorepoDependencyVersions()

checkTranslations('config/translations')

function checkMonorepoDependencyVersions () {
  const rootPkg = require('./package.json')
  const { workspaces = [] } = rootPkg
  const allRootDeps = Object.assign({}, rootPkg.dependencies, rootPkg.devDependencies)

  const packages = globbySync(workspaces.map(dir => `${dir}/package.json`), { })
  for (const packageJson of packages) {
    const pkg = require(`./${packageJson}`)
    const allPkgDeps = Object.assign({}, pkg.dependencies, pkg.devDependencies)
    for (const [name, version] of Object.entries(allPkgDeps)) {
      if (allRootDeps[name] && allRootDeps[name] !== version) {
        warn(`Dependency version mismatch: ${code(`${name}@${version}`)} does not match root ${code(allRootDeps[name])}`, packageJson)
      }
    }
  }
}

function checkTranslations (cwd) {
  const potFiles = globbySync('**/*.pot', { cwd })
  const poFiles = globbySync('**/*.po', { cwd })
  const missingTemplates = []

  for (const poFile of poFiles) {
    const path = join(cwd, poFile)
    const base = basename(poFile)
    const parts = base.split('.')
    const [lang] = parts.length > 2 ? parts.slice(-2) : []
    if (!lang) {
      const possibleNames = translationLangs.map(lang => `${code(base.replace('.po', `.${lang}.po`))} (${locales[lang]})`)
      warn(`Missing language code in filename (should be named ${list(possibleNames, ', or ')})`, path)
    } else if (!translationLangs.includes(lang)) {
      warn(`Unexpected language code ${code(lang)} (expected ${list(translationLangs.map(code), ', or ')})`, path)
    } else {
      const potFile = poFile.replace(lang ? `.${lang}.po` : '.po', '.pot')
      if (!potFiles.includes(potFile) && !missingTemplates.includes(potFile)) {
        missingTemplates.push(potFile)
      }
    }
  }

  for (const potFile of missingTemplates) {
    warn(`Missing English template ${code(join(cwd, potFile))} for ${code(join(cwd, potFile.replace('.pot', '.*.po')))}`)
  }

  for (const potFile of potFiles) {
    const missingTranslations = translationLangs
      .map(lang => ({
        lang,
        path: potFile.replace(/\.pot$/, `.${lang}.po`)
      }))
      .filter(file => !poFiles.includes(file.path))
    if (missingTranslations.length > 0) {
      const missingNames = missingTranslations.map(({ lang, path }) => `${code(join(cwd, path))} (${locales[lang]})`)
      const path = join(cwd, potFile)
      warn(`Missing translations for ${code(path)}: ${list(missingNames, ', and ')}`, path)
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

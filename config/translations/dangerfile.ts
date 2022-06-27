import { fail, warn } from 'danger'
import { basename } from 'path'
import glob from 'node-glob'
import { locales } from './locales.json'

export const translationLangs = Object.keys(locales)
  .filter(lang => lang !== 'en')

export default function checkTranslations() {
  const cwd = __dirname
  const potFiles = glob('**/*.pot', { cwd })
  const poFiles = glob('**/*.po', { cwd })
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

import { danger, warn, fail,  } from 'danger'
import checkTranslations from './config/translations/dangerfile'

const MIN_PR_DESC_LENGTH = 50
const JIRA_REF_PATTERN = /\b[A-Z]+-\d+\b/

const { pr } = danger.github
const prDescription = pr.body || ''
if (!JIRA_REF_PATTERN.test(prDescription)) {
  warn('Please include at least one Jira ticket, e.g. SG-123 or DESSYS-123.')
} else if (prDescription.length < MIN_PR_DESC_LENGTH) {
  warn(`Your PR description is too short (&lt;${MIN_PR_DESC_LENGTH} characters). Please be more descriptive.`)
}

checkTranslations()
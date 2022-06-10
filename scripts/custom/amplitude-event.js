const actions = require('@actions/core')
const { context } = require('@actions/github')
const amplitude = require('@amplitude/node')

const { AMPLITUDE_API_KEY } = process.env
if (!AMPLITUDE_API_KEY) {
  actions.setFailed('Missing AMPLITUDE_API_KEY')
}

const {
  eventName,
  action = context.payload.deployment_status?.state,
  actor,
  sha,
  ref
} = context

const eventType = [
  'github',
  eventName,
  action
].filter(Boolean).join('.')

const userProperties = {
  user_id: `github:${actor}`,
  github: {
    user: actor,
    href: `https://github.com/${actor}`
  }
}

const eventProperties = {
  name: eventName,
  sha,
  ref,
  ...context.payload
}

const amp = amplitude.init(AMPLITUDE_API_KEY)

amp.logEvent({
  event_type: eventType,
  ...userProperties,
  event_properties: eventProperties
})

amp.flush()

/* eslint brace-style: ['error', '1tbs'] */
;(function () { // eslint-disable-line no-extra-semi
  const el = document.getElementById('formio-form')
  const confirmationURL = el.getAttribute('data-confirmation-url')
  const options = safeJSONParse(el.getAttribute('data-options')) || {}
  options.i18n = safeJSONParse(el.getAttribute('data-translations')) || {}

  let url = el.getAttribute('data-source')
  const params = new URL(location).searchParams
  const submissionId = params.get('submission')
  if (submissionId) {
    url += `/submission/${submissionId}`
  }

  // set these vars for all future measurements
  measure({
    'form.url': url
  })
  measure('create')

  // eslint-disable-next-line no-undef
  Formio.createForm(el, url, options)
    .then(form => {
      // include form language in future measurements
      measure({
        'form.language': form.language,
        'form.page': 0
      })
      measure('load')

      form.on('nextPage', event => {
        measure('nextPage')
        measure({ 'form.page': event.page })
      })
      form.on('prevPage', event => {
        measure('prevPage')
        measure({ 'form.page': event.page })
      })

      form.on('saveDraft', submission => {
        measure('saveDraft')
      })

      form.on('submit', (submission, saved) => {
        measure('submit', {
          'submission.state': submission.state
        })
      })

      // What to do when the submit begins.
      form.on('submitDone', submission => {
        measure('submitDone')
        // we want to navigate to the confirmation page only on final submission.
        // saving a draft also triggers the submitDone event, but we want to keep
        // the user on the current page in that case.
        if (confirmationURL && submission.state !== 'draft') {
          const formLanguageMap = {
            zh: 'zh-hant',
            'zh-TW': 'zh-hant'
          }
          // see: <https://github.com/formio/formio.js/pull/3592> for more details
          let lang = form.language
          lang = formLanguageMap[lang] || lang
          const actualUrl = lang && lang !== 'en'
            ? confirmationURL.replace('{lang}', lang)
            : confirmationURL.replace('{lang}/', '')
          measure('redirect', {
            reason: 'confirmation',
            url: actualUrl
          })
          window.location = actualUrl
        }
      })

      // measure file uploads
      form.on('fileUploadingStart', () => measure('fileUploadStart'))
      form.on('fileUploadingEnd', () => measure('fileUploadEnd'))

      const IGNORE_ERROR_TYPES = [
        // these are component-level validation errors that fire every time a
        // component is marked as invalid, dispatched for each component
        // individually, and for text inputs on each keystroke
        'componentError',
        'componentChange'
      ]

      form.onAny((type, event, ...rest) => {
        // remove the 'formio.' prefix
        const subtype = type.replace('formio.', '')
        if (IGNORE_ERROR_TYPES.includes(subtype)) {
          // do nothing
        } else if (subtype.match(/error/i)) {
          measure('error', {
            errorType: type,
            message: getErrorMessage(event)
          })
        }
        /**
         * Uncomment this for local development to see messages for events that
         * haven't been handled explicitly
         */
        // if (![type, subtype].some(t => form.events.listeners(t).length)) {
        //   console.debug('ignoring event', type, ...rest)
        // }
      })
    })
    .catch(error => {
      measure('error', {
        message: error.message,
        stack: error.stack
      })
    })

  // add an event and/or measurement variables to the GA data layer
  function measure (event, vars) {
    if (typeof event === 'object') {
      vars = event
    } else if (typeof event === 'string') {
      vars = Object.assign(
        { event: `form.${event}` },
        vars
      )
    }
    console.debug('measure', vars)
    window.dataLayer.push(vars)
  }

  function getErrorMessage (error) {
    return typeof error === 'string'
      ? error
      : Array.isArray(error)
        ? `${error.length} validation error${(error.length === 1 ? '' : 's')}`
        : error instanceof Object ? error.message : null
  }

  function safeJSONParse (str) {
    try {
      return JSON.parse(str)
    } catch (error) {
      return str
    }
  }
})()

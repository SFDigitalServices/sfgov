/* eslint-disable no-console */
/* eslint brace-style: ['error', '1tbs'] */
;(function () { // eslint-disable-line no-extra-semi
  const { Formio, gtag } = window
  const MEASURE_PARAMS = {}

  /**
   * Create a formio.js plugin with hooks for different types of requests:
   * (see: https://help.form.io/developers/fetch-plugin-api)
   */
  Formio.registerPlugin({
    // requests made before the form is loaded, including the form schema
    wrapStaticRequestPromise: wrapRequest,
    // requests made once the form is loaded, except file uploads
    wrapRequestPromise: wrapRequest,
    // file upload (and download) requests
    wrapFileRequestPromise: wrapFileRequest,
    // finally, hook into every fetch response so that we can raise more
    // meaningful errors when they fail
    requestResponse: wrapResponseError
  }, 'sfgov.measurement')

  const el = document.getElementById('formio-form')
  const confirmationURL = el.getAttribute('data-confirmation-url')
  const options = safeJSONParse(el.getAttribute('data-options')) || {}
  options.i18n = safeJSONParse(el.getAttribute('data-translations')) || {}

  const formURL = el.getAttribute('data-source')
  let url = formURL
  const params = new URL(location).searchParams
  const submissionId = params.get('submission')
  if (submissionId) {
    url += `/submission/${submissionId}`
  }

  // set these parameters for all future measurements
  measureParams({ form_url: url })
  measure('create')

  // eslint-disable-next-line no-undef
  Formio.createForm(el, url, options)
    .then(form => {
      // include form language in future measurements
      measureParams({ form_language: form.language })
      measure('load')

      /**
       * Define form event handlers in a mapping so that we don't have to call
       * form.on('event', handler) for each one, and so that we can check in the
       * onAny() callback for whether there's an explicit handler for the event
       */
      const handlers = {
        nextPage (event) {
          measure('next_page', { form_page: event.page })
        },

        prevPage (event) {
          measure('prev_page', { form_page: event.page })
        },

        // the signature of this event is different from nextPage + prevPage:
        // https://github.com/formio/formio.js/blob/4.19.x/src/Wizard.js#L406
        wizardPageSelected (page, index) {
          measure('select_page', { form_page: index })
        },

        submit (submission) {
          measure('submit')
        },

        saveDraft () {
          measure('save_draft')
        },

        submitDone (submission) {
          measure('submit_done')

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
              redirect_reason: 'confirmation',
              redirect_url: actualUrl
            })
            window.location = actualUrl
          }
        },

        // 'error' events are validation errors
        error (event) {
          measure('validation_error', {
            validation_count: Array.isArray(event) ? event.length : 1,
            validation_message: Array.isArray(event)
              ? event.map(err => err.message).join('; ')
              : event.message
          })
        }
      }

      for (const [type, handler] of Object.entries(handlers)) {
        form.on(type, handler)
      }

      /**
       * Uncomment this for local development to see messages for events that
       * haven't been handled explicitly
       */
      // form.onAny((type, event, ...rest) => {
      //   if (!handlers[type.replace(/^formio\./, '')]) {
      //     console.debug('unhandled event: "%s":', type, event, ...rest)
      //   }
      // })
    })
    .catch(error => {
      measure('error', getFormErrorVars(error))
    })

  // add an event and/or measurement variables to the GA data layer
  function measure (event, params) {
    event = `form_${event}`
    console.debug('measure', event, params)
    gtag('event', event, Object.assign({}, MEASURE_PARAMS, params))
  }

  function measureParams (params) {
    Object.assign(MEASURE_PARAMS, params)
  }

  /**
   * Attempt to turn a formio.js error or error message into useful measurement
   * variables.
   *
   * @param {any} error
   * @returns {Map<string, any>}
   */
  function getFormErrorVars (error) {
    if (error instanceof Error) {
      return {
        error_message: error.message,
        error_stack: error.stack
      }
    } else if (!error || error === 'Invalid alias') {
      return {
        error_message: `Form schema failed to load (${JSON.stringify(error)})`
      }
    }
    return { error_message: error.message || error }
  }

  /**
   * formio.js does some funky stuff with failed responses that obfuscate the
   * cause of the failure. Throwing an error with a meaningful message here
   * causes the request promise to reject, in which case Formio.request()
   * includes the thrown error's message in the following:
   *
   * `Could not connect to API server (${err.message}): ${url}`
   * https://github.com/formio/formio.js/blob/4.20.x/src/Formio.js#L1064
   *
   * This text is then displayed as-is on the page, rather than the text of the
   * response, which is not typically useful: "Invalid alias" for forms that
   * have moved, or possibly an empty string if the request was blocked at the
   * network level.
   *
   * @param {Response} response
   * @param {any} _Formio
   * @param {Map<string, any>} data
   * @returns {void}
   */
  function wrapResponseError (response) {
    if (!response.ok) {
      throw new Error(`${response.status} ${response.statusText}`)
    }
    return response
  }

  /**
   * @typedef {function} RequestWrapper
   * @param {Promise<any>} promise
   * @param {Map<string, string>} requestArgs
   * @returns {Promise<any>}
   */

  /** @type {RequestWrapper} */
  function wrapRequest (promise, { url, method, type }) {
    const t = Date.now()
    const vars = {
      request_url: url,
      request_method: method,
      request_type: type
    }
    measure('request_start', vars)
    return promise
      .then(value => {
        measure('request_end', { ...vars, request_time: Date.now() - t })
        return value
      }, error => {
        measure('request_error', {
          ...vars,
          request_time: Date.now() - t
        })
        throw error || `Unable to load URL: ${url}`
      })
  }

  /**
   * File requests have different data in their requestArgs object,
   * and we want to dispatch different events for these.
   *
   * @type {RequestWrapper}
   */
  function wrapFileRequest (promise, { file, fileName, provider }) {
    const t = Date.now()
    const { size, type } = file || {}
    const vars = {
      file_size: size,
      file_type: type,
      file_name: fileName,
      file_provider: provider
    }
    measure('file_upload_start', vars)
    return promise
      .then(value => {
        measure('file_upload_end', { ...vars, request_time: Date.now() - t })
        return value
      }, error => {
        measure('file_upload_error', {
          ...vars,
          error: error.message,
          request_time: Date.now() - t
        })
        throw error
      })
  }

  function safeJSONParse (str) {
    try {
      return JSON.parse(str)
    } catch (error) {
      return str
    }
  }
})()

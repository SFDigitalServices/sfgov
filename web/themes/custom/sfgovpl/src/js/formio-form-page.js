/* eslint brace-style: ['error', '1tbs'] */
;(function () { // eslint-disable-line no-extra-semi
  const { Formio } = window

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
    wrapFileRequestPromise: wrapFileRequest
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
        'form.language': form.language
      })
      measure('load')

      const sfoptions = options.sfoptions || {}
      // perform sfoptions, hide certain elements
      if (sfoptions.hide instanceof Object) {
        customHideElements(sfoptions.hide)
      }
      // add css class to element(by id)
      if (sfoptions.addClass) {
        customAddCssClassById(sfoptions.addClass)
      }
      // if cookies are defined, populate the form field with cookie value.
      if (sfoptions.cookies) {
        manageFormCookies(sfoptions.cookies, form, true)
      }

      /**
       * Define form event handlers in a mapping so that we don't have to call
       * form.on('event', handler) for each one, and so that we can check in the
       * onAny() callback for whether there's an explicit handler for the event
       */
      const handlers = {
        nextPage (event) {
          measure('nextPage', { page: event.page })
          // set form cookies, if there are any
          if (sfoptions.cookies) {
            manageFormCookies(sfoptions.cookies, form, false)
          }
        },

        prevPage (event) {
          measure('prevPage', { page: event.page })
        },

        submit (submission) {
          measure('submit', {
            'submission.state': submission.state
          })
        },

        saveDraft () {
          measure('saveDraft')
        },

        submitDone (submission) {
          measure('submitDone')
          // custom options defined in Form.io render options field
          if (options.redirects instanceof Object) {
            if (sfoptions.hide instanceof Object) {
              customHideElements(sfoptions.hide)
            }
            for (const key in options.redirects) {
              const value = options.redirects[key]
              // only one "redirect" should be "yes", this is set in the form.io form
              if (submission.data[key] === 'yes') {
                measure('redirect', {
                  reason: 'sfoptions.redirects',
                  url: value
                })
                window.location = value
                break
              }
            }
          }

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
        },

        // 'error' events are validation errors
        error (event) {
          measure('validationError', {
            count: Array.isArray(event) ? event.length : 1,
            message: event?.message || null
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
      console.error('form load error:', error)
      /**
       * Errors initializing the form (either in our code or in formio-sfds)
       * should throw an Error object that we can do something with. formio.js
       */
      measure('error', error instanceof Error
        ? {
            message: error.message || error,
            stack: error.stack
          }
        : {
            error
          })
    })

  // add an event and/or measurement variables to the GA data layer
  function measure (event, vars) {
    try {
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
    } catch (error) {
      console.error('unable to measure(', vars, '):', error)
    }
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
      'request.url': url,
      'request.method': method,
      'request.type': type
    }
    measure('requestStart', vars)
    return promise
      .then(value => {
        measure('requestEnd', { ...vars, 'request.time': Date.now() - t })
        return value
      }, error => {
        measure('requestError', { ...vars, 'request.time': Date.now() - t })
        throw error
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
      'upload.url': `${formURL}/storage/${provider}`,
      'upload.provider': provider,
      'file.size': size,
      'file.type': type,
      'file.name': fileName
    }
    measure('fileUploadStart', vars)
    return promise
      .then(value => {
        measure('fileUploadStart', { ...vars, 'request.time': Date.now() - t })
        return value
      }, error => {
        measure('fileUploadError', {
          ...vars,
          error: error.message,
          'request.time': Date.now() - t
        })
        throw error
      })
  }

  /**
   * @param {Map<string, string>} classes
   */
  function customAddCssClassById (classes) {
    for (const key in classes) {
      const el = document.getElementById(key)
      if (el) {
        el.classList.add(classes[key])
      }
    }
  }

  /**
   * @param {string[]} klasses
   */
  function customHideElements (klasses) {
    for (const klass of klasses) {
      const hide = document.getElementsByClassName(klass)
      for (let i = 0; i < hide.length; i++) {
        hide[i].style.display = 'none'
      }
    }
  }

  /**
   * @param {string} name
   * @param {string} value
   */
  function setCookie (name, value) {
    // now + plus 90 days
    // eslint-disable-next-line no-magic-numbers
    const expires = 90 * 24 * 3600 * 1000
    document.cookie = [
      `${name}=${encodeURIComponent(value)}`,
      'path=/',
      `expires=${new Date(Date.now() + expires).toGMTString()}`
    ].join('; ')
  }

  /**
   * @param {string} name
   * @returns {string?}
   */
  function getCookie (name) {
    const match = document.cookie.match(new RegExp(name + '=([^;]+)'))
    return match ? decodeURIComponent(match[1]) : null
  }

  function manageFormCookies (cookies, form, populateAll) {
    for (const item of cookies) {
      let field = document.querySelector(`input[name*="${item}"]`)
      if (!field) {
        return
      }
      const fieldtype = field.type

      // special type: select, because formio renders select as divs
      const selectField = document.querySelector(`select[name*="${item}"]`)
      if (selectField) {
        field = selectField
      }
      if (field || populateAll) {
        const cookieval = getCookie(item)
        if (cookieval) {
          // set submission data, form validation needs this
          form._submission.data[item] = cookieval
        }

        if (field) { // populate fields with cookie values
          if (fieldtype === 'radio' || fieldtype === 'checkbox') {
            field.checked = cookieval?.includes(field.value)
          } else {
            field.value = cookieval
          }
        }
        /* XXX: this condition never hits */
        // else if (field) {
        //   // set cookie for first time
        //   if (fieldtype === 'radio' || fieldtype === 'checkbox') {
        //     setCookie(item, field.checked)
        //   }
        //   else {
        //     setCookie(item, field.value)
        //   }
        // }

        // updates cookie if value changed
        field.addEventListener('change', function () {
          // console.log(`setting cookie for ${item} with value ${this.value}`)
          setCookie(item, this.value)
        })
      }
    }
  }

  function safeJSONParse (str) {
    try {
      return JSON.parse(str)
    } catch (error) {
      return str
    }
  }
})()

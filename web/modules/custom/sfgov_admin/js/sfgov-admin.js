/**
 * @file
 * General tweaks for the administrative user interface. This file is loaded on
 * all pages where the administrative theme is in use.
 */
(function ($, Drupal, document, window) {
  /**
   * Prevents the Administrative Toolbar from popping out as a sidebar when
   * screen width is reduced.
   */
  Drupal.behaviors.sfgovAdminToolbarReizeTweak = {
    attach (context) {
      window.matchMedia('(min-width: 975px)').addListener(event => {
        event.matches
          ? $('#toolbar-item-administration', context).click()
          : $('.toolbar-item.is-active', context).click()
      })
    }
  }

  /**
   * Adds a button that Toggles the Node edit form sidebar.
   */
  Drupal.behaviors.sfgovAdminSidebarToggle = {
    attach (context) {
      $('.layout-region-node-secondary', context)
        .once('sidebarToggle')
        .wrapInner('<div id="sidebar-toggle-content"/>')
        .prepend(
          '<button id="sidebar-toggle" class="sidebar-toggle"><span class="sidebar-toggle__text">' +
            Drupal.t('Toggle sidebar') +
            '</span><span class="sidebar-toggle__icon"></span></button>'
        )

      $('#sidebar-toggle', context)
        .once()
        .on('click', event => {
          event.preventDefault()
          const $container = $('.layout-node-form', context)
          const $sidebar = $('#sidebar-toggle-content', context)

          if ($container.hasClass('sidebar-toggle-active')) {
            $sidebar.show()
            $container.removeClass('sidebar-toggle-active')
          }
          else {
            $sidebar.hide()
            $container.addClass('sidebar-toggle-active')
          }
        })

      // This media query maps to the Seven theme's breakpoint for when the
      // sidebar appears in the right column. When the screen resized from a
      // large width, to a smaller width, and the sidebar appears below the
      // content, this initiates a click on the toggle to show the sidebar.
      window
        .matchMedia(
          'screen and (max-width: 780px), (max-device-height: 780px) and (orientation: landscape)'
        )
        .addListener(event => {
          if (
            event.matches &&
            $('#sidebar-toggle-content', context).not(':visible')
          ) {
            $('#sidebar-toggle', context).click()
          }
        })
    }
  }

  /**
   * Enables Dropbutton functionality on the custom "Add Drop Button" widget.
   */
  Drupal.behaviors.sfgovParagraphAddDropbutton = {
    attach (context) {
      // The add button is just a placeholder for the add buttons. Clicking it should have no affect.
      $('.sfgov-admin-paragraph-add-link', context)
        .once()
        .on('click', e => {
          e.preventDefault()
          $(e.target).closest('.dropbutton-wrapper').toggleClass('open')
        })
    }
  }

  /**
   * Add SVG icon to tabledrag handle.
   */
  Drupal.behaviors.sfgovTabledragHandle = {
    attach (context, settings) {
      $(
        '.form-item-custom-paragraph div.handle, .form-item-custom-autocomplete div.handle',
        context
      )
        .once('tabledrag-handle-override')
        .each(function (index, el) {
          $(this).html(
            '<svg class="paragraphs-tabledrag-handle" width="32" height="32" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M14.752,4 L1.25,4 C0.56,4 0,4.37333333 0,4.83333333 L0,5.16666667 C0,5.626 0.56,6 1.25,6 L14.752,6 C15.441,6 16.002,5.626 16.002,5.16666667 L16.002,4.83333333 C16.002,4.37333333 15.441,4 14.752,4 L14.752,4 L14.752,4 Z M1.25,0 C0.56,0 0,0.373333333 0,0.833333333 L0,1.16666667 C0,1.62666667 0.56,2 1.25,2 L14.752,2 C15.441,2 16.002,1.62666667 16.002,1.16666667 L16.002,0.833333333 C16.002,0.373333333 15.441,0 14.752,0 L1.25,0 L1.25,0 Z M14.752,8 L1.25,8 C0.56,8 0,8.374 0,8.83333333 L0,9.16666667 C0,9.626 0.56,10 1.25,10 L14.752,10 C15.441,10 16.002,9.626 16.002,9.16666667 L16.002,8.83333333 C16.002,8.374 15.441,8 14.752,8 L14.752,8 L14.752,8 Z"></path></svg>'
          )
        })
    }
  }

  Drupal.behaviors.sfgovPersonContentTypeName = {
    attach (context) {
      $('.node-person-form #edit-submit, .node-person-edit-form').click(
        () => {
          $('#edit-title-0-value').val(
            $('#edit-field-first-name-0-value').val() +
              ' ' +
              $('#edit-field-last-name-0-value').val()
          )
          $('form.node-person-form, form.node-person-edit-form').submit(
            () => {
              $('#edit-title-0-value').val(
                $('#edit-field-first-name-0-value').val() +
                  ' ' +
                  $('#edit-field-last-name-0-value').val()
              )
              return true
            }
          )
        }
      )
    }
  }

  // Enforce telephone numbers have format XXX-XXX-XXXX
  Drupal.behaviors.sfgovFormatTelephone = {
    attach (context, settings) {
      $('#edit-submit')
        .once()
        .on('click', e => {
          $('.form-tel').each(function () {
            const phoneField = $(this)
            if (phoneField.length > 0) {
              const regex = /\d+/g

              if (phoneField.val().length > 0) {
                const number = phoneField.val().match(regex).join('')

                if (number.length === 10) {
                  phoneField.val(
                    number.slice(0, 3) +
                      '-' +
                      number.slice(3, 6) +
                      '-' +
                      number.slice(6)
                  )
                }
              }
            }
          })
        })
    }
  }

  // populate the sort title for transactions based on the title (excluding stop words at the beginning of a title)
  // for example, sort title for "Apply for a loan to repair your home" would be "Loan to repair your home"
  // this sort title is used in the services a-z view to group and sort the transaction titles
  // refer to:
  //  - transaction content type
  //  - services page view a-z
  Drupal.behaviors.sfgovTransactionSortTitle = {
    attach (context, settings) {
      const stripStopWords = function (str) {
        const strArray = str.split(' ')
        const stopWords = [
          'a',
          'an',
          'apply',
          'as',
          'be',
          'find',
          'for',
          'get',
          'have',
          'register',
          'the',
          'to',
          'your'
        ]
        while ($.inArray(strArray[0].toLowerCase(), stopWords) >= 0) {
          strArray.splice(0, 1)
        }
        strArray[0] =
          strArray[0].charAt(0).toUpperCase() + strArray[0].slice(1)
        return strArray.join(' ')
      }
      const isTransactionEdit =
        !!($('form.node-transaction-edit-form').length > 0 ||
        $('form.node-transaction-form').length > 0)
      if (isTransactionEdit) {
        $('#edit-submit').on('click', () => {
          const sortTitle = stripStopWords($('#edit-title-0-value').val())
          $('#edit-field-sort-title-0-value').val(sortTitle)
        })
      }
    }
  }

  Drupal.behaviors.sfgovDeptReqRecords = {
    attach (context, settings) {
      const associations = {
        phone: 'edit-field-req-public-records-phone-wrapper',
        link: 'edit-field-req-public-records-link-wrapper',
        email: 'edit-field-req-public-records-email-wrapper'
      }
      const reqPublicRecords = function () {
        // a key value pair of radio buttons values and wrapper id's for show/hide
        $('#edit-field-req-public-records-none').parent().hide()
        $('#edit-field-req-public-records input[type="radio"]')
          .once()
          .on('click', function () {
            for (const key in associations) {
              $('#' + associations[key]).hide()
            }
            const radioVal = $(this).val().toLowerCase()
            $('#' + associations[radioVal]).show()
          })
      }
      const checkedVal = $(
        'input[name="field_req_public_records"]:checked'
      ).val()
      if (checkedVal) {
        $('#' + associations[checkedVal.toLowerCase()]).show()
      }
      reqPublicRecords()
    }
  }

  Drupal.behaviors.sfgovEventLocation = {
    attach (context, settings) {
      const inPersonCheck = $('#edit-field-location-in-person-value')
      const inPersonAddressLabel = $('#edit-field-address > summary')
      const onlineCheck = $('#edit-field-location-online-value')
      const onlineHelpText = $('#edit-field-location-online-value--description')

      if (inPersonCheck.is(':checked')) {
        $(inPersonAddressLabel).addClass('form-required')
      }

      if (!onlineCheck.is(':checked')) {
        $(onlineHelpText).hide()
      }

      $(inPersonCheck).click(() => {
        $(inPersonAddressLabel).addClass('form-required')
      })

      $(onlineCheck).click(function () {
        const checked = $(this).is(':checked')
        $(onlineHelpText).toggle(checked)
      })
    }
  }

  // Set the Meeting archive date to have the required icon, if the
  // meeting archive url is filled in. Field becomes required in backend
  // and in states api, but because of how the date field is built, the
  // required icon doesn't get set in the form states api.
  Drupal.behaviors.sfgovPublicBodyRequiredMeetingArchiveDate = {
    attach (context, settings) {
      const meetingArchiveUrl = $('#edit-field-meeting-archive-url-0-uri')
      const meetingArchiveDateLabel = $(
        '#edit-field-meeting-archive-date-wrapper h4'
      )

      const checkRequiredState = function () {
        if (meetingArchiveUrl.val() !== '') {
          if (
            !meetingArchiveDateLabel.hasClass('js-form-required form-required')
          ) {
            $(meetingArchiveDateLabel).addClass(
              'js-form-required form-required'
            )
          }
        }
        else {
          $(meetingArchiveDateLabel, meetingArchiveUrl).removeClass(
            'js-form-required form-required'
          )
        }
      }

      $(document).ready(checkRequiredState)
      $(meetingArchiveUrl).on('click input change', checkRequiredState)
    }
  }
})(jQuery, Drupal, document, window)

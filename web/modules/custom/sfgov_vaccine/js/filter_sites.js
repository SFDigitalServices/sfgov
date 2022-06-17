(function ($, Drupal) {
  Drupal.behaviors.filterSites = {
    attach (context, settings) {
      // @todo Banish the jquery!
      const R = 3958.754641 // radius of the earth in miles

      // Set media query and register event listener.
      const mediaQuery = window.matchMedia('(min-width: 768px)')
      mediaQuery.addEventListener('change', layoutChange)

      // Elements.
      const sectionCount = $('.vaccine-count-container')
      const leftColumn = $('.group--left')
      const sitesWrapper = $('.vaccine-filter__sites')
      const submitButton = $('.vaccine-filter-form #edit-submit', context)
      const editLocation = $('#edit-location')
      const invalidAddressAlert = $('[data-role=invalid-address]')
      const wheelchairCheckbox = $('[name=wheelchair]')
      const pedAgeSelect = $('[name=pediatric]')
      const languageField = $('[name=language]')
      const locationField = $('[name=location]')
      const radiusInput = $('[name=radius]')

      const allSites = $('.vaccine-site')
        .each(function () {
          const site = getSiteData(this)
          $(this).data('site', site)
        })

      // Other variables.
      const speed = 'slow'

      // On load.
      displaySites()
      layoutChange(mediaQuery)

      // On Click.
      submitButton.on('click', event => {
        event.preventDefault()
        leftColumn.fadeOut(0)

        // Clear GPS data, check for autocomplete entry
        if (!editLocation.attr('data-place-changed')) {
          // Remove previous GPS, ***BUG*** not working for invalid address handling
          editLocation
            .removeAttr('data-lat')
            .removeAttr('data-lng')
        }

        // eslint-disable-next-line no-undef, promise/catch-or-return
        locationSubmit()
          .then(displaySites)
          // eslint-disable-next-line no-console
          .catch(error => console.error(error))
          .finally(() => {
            leftColumn.fadeIn(speed)
            if (locationField.attr('data-lat') && locationField.attr('data-lng')) {
              scrollUp(speed)
            } else if ($('.vaccine-filter__sites .included').length > 0) {
              scrollUp(speed)
            }
          })
      })

      function filterVaccineSites () {
        const filters = []
        const userLocation = !!locationField.val()

        // filterByAvailability = $('[name=available]').is(':checked')

        if (wheelchairCheckbox.is(':checked') === true) {
          filters.push(site => site.access.wheelchair === true)
        }

        const langSelected = languageField.val().trim()
        if (langSelected && langSelected !== 'all') {
          /**
           * SPECIAL CASE: ASL isn't always available via remote translation.
           * `site.access.languages.asl` will be true if the remote language
           * services include "ASL"; otherwise, we can assume that remote
           * services (LanguageLine) offer spoken language translation.
           */
          const langOtherTest = langSelected === 'asl'
            ? () => false
            : site => site.access.remote_translation.available === true
          filters.push(site => site.access.languages[langSelected] || langOtherTest(site))
        }

        const ageRangeString = pedAgeSelect.val()
        if (ageRangeString) {
          const ageRange = ageRangeString.split('-').map(str => parseFloat(str))
          if (ageRange.every(n => !isNaN(n))) {
            filters.push(site => site.dosages.some(dosage => rangesOverlap(ageRange, dosage.ages)))
          } else {
            // console.warn('Bad age range:', ageRange)
          }
        }

        const maxDistance = parseFloat(radiusInput.val())
        const origin = {
          lat: parseFloat(locationField.attr('data-lat')),
          lng: parseFloat(locationField.attr('data-lng'))
        }
        const showDistance = userLocation && [maxDistance, origin.lat, origin.lng].every(n => !isNaN(n))
        if (showDistance) {
          filters.push(site => site.distance <= maxDistance)
        }

        // Test and filter.
        allSites
          .hide()
          .removeClass('included')
          .filter(function () {
            const $site = $(this)
            const site = $site.data('site')

            if (showDistance) {
              const distance = getDistance(origin, site.location)
              site.distance = distance
              $site
                .attr('data-distance', distance)
                .find('[data-role=distance]')
                .text(formatDistance(distance))
            } else {
              $site
                .removeAttr('data-distance')
                .find('[data-role=distance]').text('')
            }

            return filters.every(test => test(site))
          })
          .sort((a, b) => {
            // FIXME sort by comparing distance, then order:
            // (a.distance - b.distance) || (a.order - b.order)
            const orderA = parseInt(a.getAttribute('data-order'))
            const orderB = parseInt(b.getAttribute('data-order'))

            // Sort by distance and then order if location is entered.
            if (showDistance) {
              const distA = parseFloat(a.getAttribute('data-distance'))
              const distB = parseFloat(b.getAttribute('data-distance'))
              return sortAscending(distA, distB) || sortDescending(orderA, orderB)
            }

            return sortDescending(orderA, orderB)
          })
          .show()
          .addClass('included')
          .appendTo(sitesWrapper)
      }

      function showNoResultsMessage () {
        $('.vaccine-filter__empty').show()
      }

      function hideNoResultsMessage () {
        $('.vaccine-filter__empty').hide()
      }

      function showCount (speed) {
        invalidAddressAlert.hide()
        const count = $('.vaccine-site.included').length
        sectionCount.find('span').text(count)
        sectionCount.show()
      }

      function hideCount () {
        sectionCount.hide()
      }

      function showSites () {
        sitesWrapper.show()
      }

      function hideSites () {
        sitesWrapper.hide()
      }

      // This is the main function.
      function displaySites () {
        // Check for long/lat coords from user location
        filterVaccineSites()

        if (locationField.attr('data-lat') && locationField.attr('data-lat')) {
          // Location found, check results and show if valid
          if (
            // If there are no results.
            $('.vaccine-filter__sites .included').length === 0
          ) {
            hideCount()
            hideSites()
            showNoResultsMessage()
          } else if (
            (locationField.attr('data-lng') < -122.93 ||
            locationField.attr('data-lng') > -121.54) &&
            (locationField.attr('data-lat') < 37.0000 ||
            locationField.attr('data-lat') > 38.0200)
          ) {
            // GPS coords out of bounds
            hideCount()
            hideSites()
            invalidAddressAlert.show()
          } else {
            // If "Only show sites with available appointments" is not checked and
            // there are sites that meet the selected criteria.
            hideNoResultsMessage()
            showSites()
            showCount()
          }
        } else if (editLocation.val()) {
          // Invalid location entered, alert user
          hideSites()
          hideCount()
          sitesWrapper.hide()
          invalidAddressAlert.show()
        } else {
          // No location entered, show results
          if (
            // If there are no results.
            $('.vaccine-filter__sites .included').length === 0
          ) {
            hideCount()
            hideSites()
            showNoResultsMessage()
          } else {
            // If "Only show sites with available appointments" is not checked and
            // there are sites that meet the selected criteria.
            hideNoResultsMessage()
            showSites()
            showCount()
          }
        }
      }

      // Responsive layout.
      function layoutChange (e) {
        if (e.matches) {
          $('.vaccine-filter__filters').appendTo('.group--right')
        } else {
          $('.vaccine-filter__filters').appendTo(
            '.vaccine-filter__filter-top > div'
          )
        }
      }

      // Scroll to Top.
      function scrollUp (speed) {
        const newPosition = sectionCount.offset().top - 150
        $('html, body').animate({ scrollTop: newPosition }, speed)
      }

      function getSiteData (el) {
        return el.hasAttribute('data-site')
          ? tryParse(el.getAttribute('data-site')) || {}
          : {}
      }

      function tryParse (str) {
        try {
          return JSON.parse(str)
        } catch (error) {
          return undefined
        }
      }

      function formatDistance (distance) {
        return isNaN(distance)
          ? ''
          : Math.round(distance * 10) / 10 + 'mi'
      }

      // @see https://en.wikipedia.org/wiki/Haversine_formula
      // @see https://simplemaps.com/resources/location-distance
      function getDistance ({ lat: lat1, lng: lng1 }, { lat: lat2, lng: lng2 }) {
        lat1 = deg2rad(lat1)
        lat2 = deg2rad(lat2)
        const latOff = lat2 - lat1
        const lngOff = deg2rad(lng2 - lng1)
        const a = (
          Math.pow(Math.sin(latOff / 2), 2) +
          Math.cos(lat1) * Math.cos(lat2) * Math.pow(Math.sin(lngOff / 2), 2)
        )
        const d = 2 * R * Math.asin(Math.sqrt(a))
        return Math.round(d * 10) / 10 // round to one decimal point
      }

      function deg2rad (deg) {
        return deg * Math.PI / 180
      }

      function sortDescending (a, b) {
        return a > b ? -1 : a === b ? 0 : 1
      }

      function sortAscending (a, b) {
        return a < b ? -1 : a === b ? 0 : 1
      }

      function rangesOverlap ([x1, x2], [y1, y2]) {
        return x1 <= y2 && y1 <= x2
      }
    }
  }
})(jQuery, Drupal)

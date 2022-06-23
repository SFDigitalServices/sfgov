// Reference: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete

function locationAutocomplete () {
  const input = document.getElementById('edit-location')
  const neBound = new google.maps.LatLng(38.015782, -121.5566)
  const swBound = new google.maps.LatLng(37.003, -122.928461)
  const bayAreaBounds = new google.maps.LatLngBounds(swBound, neBound)
  const options = {
    componentRestrictions: { country: 'US' },
    fields: ['geometry', 'name'],
    strictBounds: false,
    bounds: bayAreaBounds
  }
  const autocomplete = new google.maps.places.Autocomplete(input, options)
  const infoWindow = new google.maps.InfoWindow()

  autocomplete.addListener('place_changed', () => {
    infoWindow.close()
    const place = autocomplete.getPlace()
    if (place.geometry) {
      input.setAttribute('data-lat', place.geometry.location.lat())
      input.setAttribute('data-lng', place.geometry.location.lng())
      input.setAttribute('data-place-changed', true)
    }
  })
}

/**
 * locationSubmit is a fallback for when a user does not select a suggested place from locationAutocomplete, the place_changed event
 * is never triggered, so data-lat and data-lng attributes are never set for successful execution of filter_sites.js
 * @returns Promise
 */
function locationSubmit () {
  if (typeof google === 'undefined') {
    console.warn('google maps is not defined! hiding the location search UI')
    $('#edit-distance-from').hide()
    return Promise.resolve({
      status: 'ok',
      msg: 'Google Maps is not loaded'
    })
  } else {
    $('#edit-distance-from').show()
  }

  return new Promise((resolve, reject) => {
    const input = document.getElementById('edit-location')

    // if an autocomplete place was selected, resolve and return early
    if (input.getAttribute('data-place-changed')) {
      resolve({ status: 'ok', msg: 'user selected place, further resolution not necessary' })
      return
    } else if (!input.value) {
      resolve({ status: 'ok', msg: 'no place selected, further resolution not necessary' })
      return
    }

    const neBound = new google.maps.LatLng(38.015782, -121.5566)
    const swBound = new google.maps.LatLng(37.003, -122.928461)
    const bayAreaBounds = new google.maps.LatLngBounds(swBound, neBound)

    // use the AutocompleteService to programmatically retrieve predictions
    const acService = new google.maps.places.AutocompleteService()
    const acRequest = {
      input: input.value,
      bounds: bayAreaBounds,
      componentRestrictions: { country: 'US' }
    }

    acService.getPlacePredictions(acRequest, (predictions, status) => {
      if (status !== google.maps.places.PlacesServiceStatus.OK || !predictions) {
        // Modify reject directive so we can handle bad addresses
        // reject(new Error('no predictions from AutocompleteService'))
        resolve({ status: 'done', msg: 'no predictions from AutocompleteService' })
        return
      }

      const firstPlaceId = predictions[0].place_id // use the first place from predictions (too assuming?)
      const pdService = new google.maps.places.PlacesService(document.createElement('div')) // PlacesService requires an html element
      pdService.getDetails({ placeId: firstPlaceId }, (placeResult, status) => {
        if (status !== google.maps.places.PlacesServiceStatus.OK || !placeResult) {
          reject(new Error('no place details from PlacesService'))
        } else {
          input.setAttribute('data-lat', placeResult.geometry.location.lat())
          input.setAttribute('data-lng', placeResult.geometry.location.lng())
          resolve({ status: 'done', msg: 'lat lng attributes set on input via AutocompleteService and PlacesService' })
        }
      })
    })
  })
}

($ => {
  // $url = 'https://search.sf311.org/s/search.json?query=birth+certificate&collection=sf-prod-search-meta&SM=qb&qsup=&start_rank=1&num_ranks=10';
  function Search311 (collectionName) {
    const _this = this

    this.inputSelector = '#edit-sfgov-search-input'
    this.topSearchContainerSelector = '#sfgov-top-search-suggestions-container'
    this.autocompleteContainerSelector = '#sfgov-search-autocomplete'

    this.props = {
      protocol: 'https',
      domain: 'search.sf311.org',
      path: '/s/search.json',
      parameters: {
        query: '',
        collection: 'sfgov-meta-prod',
        'cool.4': 0.1,
        SM: 'both',
        qsup: '',
        start_rank: 1,
        num_ranks: 10,
        callback: 'drupalSettings.sfgovSearch.search311.processSearchResults'
      }
    }

    $('body').click(e => {
      const clickTarget = $(e.target)
      if (clickTarget.attr('id') !== 'edit-sfgov-search-input') {
        $(_this.autocompleteContainerSelector).hide()
        $(_this.inputSelector).attr('aria-expanded', false)
        $(_this.topSearchContainerSelector).hide()
      }
    })

    $(this.inputSelector).attr({
      autocomplete: 'off',
      'aria-expanded': false
    })

    this.topSearchSuggestions = function () {
      const topSearchSuggsSelector = '.sfgov-top-search-suggestion'
      const topSearchSuggs = $(topSearchSuggsSelector)

      if (topSearchSuggs.length > 0) {
        const containerId = this.topSearchContainerSelector.replace('#', '')
        const containerSelector = '#' + containerId

        $(topSearchSuggs[0]).before('<div id="' + containerId + '" aria-label="' + Drupal.t('Search top suggestions') + '" role="listbox"><h4>' + Drupal.t('Top searches:') + '</h4></div>')
        $(this.topSearchContainerSelector).hide()

        $(topSearchSuggs).each(function (idx) {
          $(this).find('a').attr('id', 'aria-ts-opt-' + idx)
          $(this).find('a').attr('role', 'option')
          $(_this.topSearchContainerSelector).append($(this))
        })

        $('.sfgov-search-form-311').append($(containerSelector))

        $(this.inputSelector).focus(function () {
          if ($(this).val().length <= 0) {
            $(_this.topSearchContainerSelector).show()
            $(topSearchSuggsSelector).show()
          }
        })

        $(this.inputSelector).keyup(function () {
          if ($(this).val().length <= 0) {
            $(_this.topSearchContainerSelector).show()
          }
          else {
            $(_this.topSearchContainerSelector).hide()
          }
        })
      }
    }

    this.autocomplete = function () {
      $('#edit-keyword, #edit-sfgov-search-input')
        .attr('autocomplete', 'off')
      $(this.inputSelector).on('keyup click', event => {
        const searchKeyword = $(_this.inputSelector).val()
        const key = event.keyCode
        if (searchKeyword.length >= 3 || key === 40) {
          $.ajax({
            url: _this.props.protocol + '://' + _this.props.domain + '/s/suggest.json' + '?' + 'collection=' + _this.props.parameters.collection + '&partial_query=' + searchKeyword + '&show=10&sort=0&alpha=.5&fmt=json++&profile=_default',
            dataType: 'jsonp',
            success (data) {
              const autocompletes = data
              if (autocompletes.length > 0) {
                $(_this.autocompleteContainerSelector).show()
                $(_this.inputSelector).attr('aria-expanded', true)
                let autocompleteHtml = ''
                const searchPathPrefix = drupalSettings.sfgov_search_form_block.language_prefix + '/search?keyword='
                for (let i = 0; i < autocompletes.length; i++) {
                  autocompleteHtml += '<a role="option" id="aria-ac-opt-' + i + '" href="' + searchPathPrefix + autocompletes[i].disp + '">' + autocompletes[i].disp.replace(searchKeyword.toLowerCase(), '<strong>' + searchKeyword + '</strong>') + '</a>'
                }
                $(_this.autocompleteContainerSelector).html(autocompleteHtml)
              }
              else {
                $(_this.autocompleteContainerSelector).hide()
                $(_this.inputSelector).attr('aria-expanded', false)
              }
              $(_this.autocompleteContainerSelector + ' a').click(function () {
                $(_this.inputSelector).val($(this).text())
              })
            }
          })
        }
        else {
          $(_this.autocompleteContainerSelector).hide()
          $(_this.inputSelector).attr('aria-expanded', false)
        }
      })
    }

    this.makeRequest = function () {
      $('#sfgov-search-overlay').show()
      $('#sfgov-search-loading').css({ top: ($(document).scrollTop() + ($(window).height() / 2)) + 'px' }).show()
      let options = ''
      for (const key in this.props.parameters) {
        options += key + '=' + this.props.parameters[key] + '&'
      }
      options = options.substring(0, options.length - 1) // remove last &
      const url = this.props.protocol + '://' + this.props.domain + this.props.path + '?' + options
      $.ajax({
        url,
        dataType: 'jsonp'
      })
    }

    this.renderSearchResults = function (results, resultsSummary, highlightRegex, elem, isSfGov) {
      let html = ''
      if (results.length > 0) {
        const hr = new RegExp(highlightRegex.replace('(?i)', ''), 'gi')
        for (let i = 0; i < results.length; i++) {
          const result = results[i]
          let deptContactInfoHtml = ''
          const isDeptSearchResult = !!result.liveUrl.match(/\/departments\//)
          const isTopicSearchResult = !!result.liveUrl.match(/\/topics\//)
          let searchResultClass = 'sfgov-search-result'
          let searchResultContainerClass = 'sfgov-search-result--container'
          let title = ''

          if (result.metaData.sfgovTitle) {
            title = result.metaData.sfgovTitle
          }
          else {
            title = result.title
          }

          let resultSummary = ''
          let truncatedSummary = ''
          if (result.metaData.sfgovSummary) {
            resultSummary = result.metaData.sfgovSummary
          }
          else if (result.metaData.c) {
            resultSummary = result.metaData.c
          }
          else if (result.summary) {
            resultSummary = result.summary
          }
          else {
            resultSummary = 'No result summary'
          }

          if (resultSummary.length > 200) {
            truncatedSummary = resultSummary.substr(0, 200)
            truncatedSummary = truncatedSummary.substr(0, truncatedSummary.lastIndexOf(' ')) + ' ...'
          }
          else {
            truncatedSummary = resultSummary
          }

          // Exclude certain strings from translation.
          // @see web/themes/custom/sfgovpl/src/js/exclude-from-gtranslate.js
          // if (typeof window.SfGovExcludeFromTranslate === 'function') {
          //   truncatedSummary = window.SfGovExcludeFromTranslate(truncatedSummary)
          // }

          truncatedSummary = truncatedSummary.replace(hr, '<strong>$&</strong>')

          if (isDeptSearchResult) {
            searchResultClass += ' dept-search-result'
            searchResultContainerClass = 'dept-search-result-container'
          }

          if (isTopicSearchResult) {
            searchResultClass += ' topic-search-result'
            searchResultContainerClass = 'topic-search-result-container'
          }

          html += '<div class="' + searchResultClass + '" data-result-page-num=' + Math.ceil(resultsSummary.currStart / resultsSummary.numRanks) + '>'
          html += '  <div class="' + searchResultContainerClass + ' sfgov-fb-search-result">'

          if (isDeptSearchResult) {
            html += '<div class="content-type dept">' + Drupal.t('Department') + '</div>'

            const phone = result.metaData.dp ? result.metaData.dp : null
            const address = result.metaData.da ? result.metaData.da : null

            if (phone || address) {
              deptContactInfoHtml = '<div class="phone-address--container">'
              if (phone) {
                deptContactInfoHtml += '' +
              '  <div class="phone--container">' +
              '    <i class="sfgov-icon-phone"></i>' +
              '    <span class="phone">' +
              '      <a href="tel:+1-' + phone + '">' + phone + '</a>' +
              '    </span>' +
              '  </div>'
              }
              if (address) {
                deptContactInfoHtml += '' +
              '  <div class="address--container">' +
              '    <i class="sfgov-icon-location"></i>' +
              '    <span class="address">' + address +
              '    </span>' +
              '  </div>'
              }
              deptContactInfoHtml += '</div>'
            }
          }
          if (isTopicSearchResult) {
            html += '<div class="content-type topic">' + Drupal.t('Topic') + '</div>'
          }

          html += '    <a class="title-url" href="' + result.liveUrl + '" title="' + title + '"><h4>' + title + '</h4></a>'
          html += '    <div class="sfgov-search-result-body">'
          html += '      <p>' + Drupal.t(truncatedSummary) + '</p>'
          html += '      <a href="' + result.liveUrl + '" title="' + title + '">' + result.liveUrl + '</a>'
          html += deptContactInfoHtml
          html += '    </div>'
          html += '  </div>'
          html += '</div>'
        }

        $('#sfgov-search-overlay').hide()
        $('#sfgov-search-loading').hide()

        if ($('#sfgov-search-results').hasClass('sfgov-search-mobile-results')) {
          $(elem).html($(elem).html() + html)
        }
        else {
          $(elem).html(html)
        }

        $('#sfgov-search-results').removeClass('add-height')
      }
    }

    this.processSearchResults = function (data) {
      const resultsDiv = $('#sfgov-search-results')
      const messagesDiv = $('#sfgov-search-messages')
      let emptyResultSet = false

      if (data.response.resultPacket) {
        const spell = !!data.response.resultPacket.spell
        const error = !!data.response.resultPacket.error
        const results = data.response.resultPacket.results
        const resultsSummary = data.response.resultPacket.resultsSummary
        const highlightRegex = data.response.resultPacket.queryHighlightRegex

        if (!error) {
          if (spell && getQueryParam('si') !== 'true') { // misspelled word
            messagesDiv.prepend('<div class="sfgov-search-misspelled"><span>' + Drupal.t('Showing results for') + ' </span> <a href="/search?keyword=' + Drupal.t(data.response.resultPacket.spell.text) + '" class="sfgov-spelled-keyword">' + Drupal.t(data.response.resultPacket.spell.text) + '</a><br><div class="sfgov-search-instead">Search instead for <a href="/search?keyword=' + Drupal.t(data.question.query) + '&si=true">' + Drupal.t(data.response.resultPacket.query) + '</a></div></div>')
            // make a request for the correctly spelled word
            search311.setParam('query', data.response.resultPacket.spell.text)
            search311.makeRequest()
          }
          else {
            if (results.length === 0) {
              emptyResultSet = true
            }
            else {
              _this.renderSearchResults(results, resultsSummary, highlightRegex, resultsDiv, true)
              if (!$('.sfgov-search-pagination').hasClass('has-nav')) {
                _this.paginate(data)
                $('.sfgov-search-pagination').addClass('has-nav')
                $('#sfgov-search-results-container').removeClass('no-search-results')
              }
              // show number of results
              this.updateCountSummary(resultsSummary.totalMatching, resultsSummary.currStart, (resultsSummary.nextStart ? resultsSummary.nextStart - 1 : resultsSummary.totalMatching))
            }
          }
        }
        else {
          messagesDiv.prepend(Drupal.t('There was an error retrieving search results.  Please try again later.'))
          $('#sfgov-search-overlay').hide()
          $('#sfgov-search-loading').hide()
        }
      }
      else {
        emptyResultSet = true
      }
      if (emptyResultSet) {
        resultsDiv.html('<div class="no-search-results--container">' +
      '<h2>' + Drupal.t('We don\'t have anything yet that matches your search.') + '</h2>' +
      '<p>' + Drupal.t('Try looking through our ') + '<a href="https://sf.gov/services" target="_blank" rel="noopener noreferrer">list of services</a>.</p>' +
      '</div>')
        $('#sfgov-search-overlay').hide()
        $('#sfgov-search-loading').hide()
        $('#sfgov-search-results').removeClass('add-height')
        $('#sfgov-search-results-container').addClass('no-search-results')
      }
    }

    this.updateCountSummary = function (total, current, next) {
      $('#sfgov-search-results-count').html(current + ' - ' + next + ' of ' + total.toLocaleString() + ' results')
    }

    // add pagination for search results
    this.paginate = function (data) {
      const numPagesToShow = 5

      const updatePagination = function (currentPage) {
        const pageLinks = $('.sfgov-search-pagination-nav .page-num')
        const numPages = pageLinks.length
        let start = 1
        let end = 1

        for (let i = 0; i < pageLinks.length; i++) {
          $(pageLinks[i]).addClass('hide-page').removeClass('more-prev')
            .removeClass('more-next')
        }

        if (currentPage <= 2) {
          start = 1
          end = numPagesToShow
        }
        else if (currentPage > (numPages - 2)) {
          start = (numPages - numPagesToShow) + 1
          end = numPages
        }
        else {
          start = currentPage - 2
          end = currentPage + 2
          if (start > 1) {
            $(pageLinks[start - 1]).addClass('more-prev')
          }
        }

        if (end !== numPages) {
          $(pageLinks[end - 1]).addClass('more-next')
        }

        for (let i = start; i <= end; i++) {
          const index = i - 1
          $(pageLinks[index]).removeClass('hide-page')
        }
      }

      const resultsSummary = data.response.resultPacket.resultsSummary
      if (resultsSummary) {
        const totalResults = resultsSummary.totalMatching
        const resultsPerPage = resultsSummary.numRanks
        const numPages = Math.ceil(totalResults / resultsPerPage)
        const paginateHtml = $('<ul class="sfgov-search-pagination-nav"></ul>')
        $(paginateHtml).append('<li class="previous" style="display:none"><a href="javascript:void(0)" title="' + Drupal.t('Previous search results page') + '">' + Drupal.t('Previous') + '</a></li>')
        for (let i = 1; i <= numPages; i++) {
          let classname = ''
          if (i === 1) {
            classname += ' first current'
          }
          if (i === numPages) {
            classname += ' last'
          }
          const listItem = $('<li class="' + classname + ' page-num"></li>')
          const pageLink = $('<a href="javascript:void(0)" data-page-num="' + i + '" data-next-start="' + (((i - 1) * resultsPerPage) + 1) + '" title="' + Drupal.t('Search results page ') + i + '"></a>')
          $(listItem).append(pageLink)
          $(pageLink).click(function () {
            $('.sfgov-search-pagination-nav .current').removeClass('current')
            $(this).parent().addClass('current')
            const pageNum = parseInt($(this).attr('data-page-num'))
            const first = parseInt($('.sfgov-search-pagination-nav .first a').attr('data-page-num'))
            const last = parseInt($('.sfgov-search-pagination-nav .last a').attr('data-page-num'))
            const nextStart = $(this).attr('data-next-start')

            if (pageNum === last) {
              $('.sfgov-search-pagination-nav .next').hide()
            }
            else {
              $('.sfgov-search-pagination-nav .next').show()
            }

            if (pageNum === first) {
              $('.sfgov-search-pagination-nav .previous').hide()
            }
            else {
              $('.sfgov-search-pagination-nav .previous').show()
            }

            if (!$('#sfgov-search-results').hasClass('sfgov-search-mobile-results')) {
              $(document).scrollTop($('.sfgov-search-311-block').offset().top)
            }

            _this.setParam('start_rank', nextStart)
            _this.makeRequest()
            updatePagination(pageNum)
          })
          $(pageLink).append(i)
          $(paginateHtml).append(listItem)
        }

        $('.sfgov-search-pagination').prepend(paginateHtml)

        if (numPages > 1) {
          $(paginateHtml).append('<li class="next"><a href="javascript:void(0)" title="' + Drupal.t('Next search results page') + '">' + Drupal.t('Next') + '</a></li>')
        }

        // next click
        $('.sfgov-search-pagination-nav .next').click(() => {
          const current = parseInt($('.sfgov-search-pagination-nav .current a').attr('data-page-num'))
          const nextPage = current + 1
          // const last = $('.sfgov-search-pagination-nav .last a').attr('data-page-num')
          $('a[data-page-num="' + nextPage + '"]').click()
        })

        // prev click
        $('.sfgov-search-pagination-nav .previous').click(() => {
          const current = parseInt($('.sfgov-search-pagination-nav .current a').attr('data-page-num'))
          const prevPage = current - 1
          $('a[data-page-num="' + prevPage + '"]').click()
        })

        updatePagination(1)
      }
    }

    this.setProp = function (prop, value) {
      this.props[prop] = value
    }

    this.setParam = function (param, value) {
      this.props.parameters[param] = value
    }

    this.getProps = function () {
      return this.props
    }
  }

  function getQueryParam (queryParam) {
    const qs = window.location.search.substring(1)
    const params = qs.split('&')
    for (let i = 0; i < params.length; i++) {
      const pair = params[i].split('=')
      const param = pair[0]
      const value = pair[1]
      if (param === queryParam) {
        return value
      }
    }
    return null
  }

  function attachMobileEvents () {
    const containerSelector = '.head-right--container #block-sfgovsearchblock'
    $(containerSelector + ' .mobile-btn').click(function () {
      if ($(this).hasClass('close')) {
        $(this).removeClass('close')
        $(containerSelector).removeClass('mobile-open')
      }
      else {
        $(this).addClass('close')
        $(containerSelector).addClass('mobile-open')
      }
    })
    $('.sfgov-search-mobile-more').click(() => {
      $('.sfgov-search-pagination .next').click()
    })
  }

  function doMobile () {
    const currentPage = $('.sfgov-search-pagination-nav .current a').attr('data-page-num')
    const width = $(window).width()
    if (width <= 770) {
      $('#sfgov-search-results').addClass('sfgov-search-mobile-results')
      $('#sfgov-search-results .sfgov-search-result.views-row').show()
    }
    else {
      $('#sfgov-search-results').removeClass('sfgov-search-mobile-results')
      $('#sfgov-search-results .sfgov-search-result.views-row:not([data-result-page-num="' + currentPage + '"])').hide()
    }
  }

  const search311 = new Search311()
  // drupalSettings.sfgovSearch is an object created by this custom module at /src/Controller/SearchController.php
  drupalSettings.sfgovSearch.search311 = search311

  $(document).ready(() => {
    $('.head-right--container #edit-sfgov-search-input').attr('placeholder', 'Search')
    const kw = getQueryParam('keyword') ? getQueryParam('keyword') : ''
    $('.sf-gov-search-input-class').val(decodeURIComponent(kw))
    if (drupalSettings && drupalSettings.sfgovSearch) {
      if (drupalSettings.sfgovSearch.collection) {
        search311.setParam('collection', drupalSettings.sfgovSearch.collection)
      }
      if (drupalSettings.sfgovSearch.qie) {
        search311.setParam('cool.4', drupalSettings.sfgovSearch.qie)
      }
    }
    search311.setParam('query', kw)
    search311.makeRequest()
    search311.autocomplete()
    search311.topSearchSuggestions()
    attachMobileEvents()
    doMobile()
    $(window).resize(() => {
      doMobile()
    })

    $(document).on('keydown', e => {
      const autosuggestVisible = $('#sfgov-search-autocomplete').is(':visible')
      const topSearchesVisible = $('#sfgov-top-search-suggestions-container').is(':visible')
      let itemLength = 0
      let selector = ''
      if (autosuggestVisible) {
        selector = '#sfgov-search-autocomplete > a'
      }
      else if (topSearchesVisible) {
        selector = '#sfgov-top-search-suggestions-container > .sfgov-top-search-suggestion a'
      }
      itemLength = selector.length > 0 ? $(selector).length : 0
      const keyPressed = e.keyCode
      let focusIndex = 0
      if ((autosuggestVisible || topSearchesVisible) && (keyPressed === 40 || keyPressed === 38)) {
        if (keyPressed === 40) { // arrow down
          if ($('#edit-sfgov-search-input').is(':focus')) { // input is focused, set focus on first element
            focusIndex = 0
          }
          else {
            focusIndex = (focusIndex === itemLength - 1) ? (itemLength - 1) : focusIndex + 1
          }
        }
        if (keyPressed === 38) {
          focusIndex--
          if (focusIndex <= 0) {
            focusIndex = 0
          }
        }
        $(selector)[focusIndex].focus()
        $('#edit-sfgov-search-input').attr('aria-activedescendant', $(selector + ':focus').attr('id'))
        if ((keyPressed === 40 && focusIndex !== itemLength - 1) || (keyPressed === 38 && focusIndex !== 0)) {
          e.preventDefault()
        }
      }
    })
  })
})(jQuery)

// $url = 'https://search.sf311.org/s/search.json?query=birth+certificate&collection=sf-prod-search-meta&SM=qb&qsup=&start_rank=1&num_ranks=10';
$ = jQuery;
function Search311() {
  this.props = {
    "protocol": "https",
    "domain": "search.sf311.org",
    "path": "/s/search.json",
    "parameters": {
      "query": "",
      "collection": "sfgov-meta-prod",
      // "collection": "sf-dev-crawl",
      "SM": "both",
      "qsup": "",
      "start_rank": 1,
      "num_ranks": 10,
      "callback": "search311.processSearchResults"
    }
  };

  this.makeRequest = function() {
    $('#sfgov-search-overlay').show();
    $('#sfgov-search-loading').css({'top':($(document).scrollTop() + ($(window).height()/2))+'px'}).show();
    var options = '';
    for(var key in this.props.parameters) {
      options += key + '=' + this.props.parameters[key] + '&';
    }
    options = options.substring(0, options.length-1); // remove last &
    var url = this.props.protocol + '://' + this.props.domain + this.props.path + '?' + options;
    $.ajax({
      url: url, 
      dataType: 'jsonp'
    });
  };

  this.renderSearchResults = function(results, resultsSummary, highlightRegex, elem, isSfGov) {
    var html = '';
    if(results.length > 0) {
      var hr = new RegExp(highlightRegex.replace('(?i)', ''), 'gi');
      for(var i=0; i<results.length; i++) {
        var result = results[i];
        var deptContactInfoHtml = '';
        var isDeptSearchResult = result.liveUrl.match(/\/departments\//) ? true : false;
        var isTopicSearchResult = result.liveUrl.match(/\/topics\//) ? true : false;
        var searchResultClass = 'transaction-search-result';
        var searchResultContainerClass = 'sfgov-transaction-search--container';
        var title = '';

        if(result.metaData.sfgovTitle) {
          title = result.metaData.sfgovTitle;
        } else {
          title = result.title;
        }
  
        var resultSummary = '';
        var truncatedSummary = '';
        if(result.metaData.sfgovSummary) {
          resultSummary = result.metaData.sfgovSummary;
        } else if(result.metaData.c) {
          resultSummary = result.metaData.c;
        } else if(result.summary) {
          resultSummary = result.summary;
        } else {
          resultSummary = 'No result summary';
        }

        if(resultSummary.length > 200) {
          truncatedSummary = resultSummary.substr(0, 200);
          truncatedSummary = truncatedSummary.substr(0, truncatedSummary.lastIndexOf(' ')) + ' ...';
        } else {
          truncatedSummary = resultSummary;
        }

        truncatedSummary = truncatedSummary.replace(hr, '<strong>$&</strong>');
  
        if(isDeptSearchResult) {
          searchResultClass = 'department-search-result';
          searchResultContainerClass = 'department-search-result--container';
        }
  
        if(isTopicSearchResult) {
          searchResultClass = 'topic-search-result';
          searchResultContainerClass = 'topic-search-result--container';
        }
  
        html += '<div class="sfgov-search-result views-row" data-result-page-num=' + Math.ceil(resultsSummary.currStart/resultsSummary.numRanks) + '>';
        html += '  <div class="' + searchResultClass + '">';
        html += '  <div class="' + searchResultContainerClass + ' sfgov-fb-search-result">';
    
        if(isDeptSearchResult) {
          html += '<div class="content-type"><i class="sfgov-icon-department"></i><span>' + Drupal.t('Department') + '</span></div>';
          
          var phone = result.metaData.dp ? result.metaData.dp : null;
          var address = result.metaData.da ? result.metaData.da : null;
  
          if(phone || address) {
            deptContactInfoHtml = '<div class="phone-address--container">';
            if(phone) {
              deptContactInfoHtml += '' +  
              '  <div class="phone--container">' +
              '    <i class="sfgov-icon-phone"></i>' +
              '    <span class="phone">' +
              '      <a href="tel:+1-' + phone + '">' + phone + '</a>' +
              '    </span>' +
              '  </div>';
            }
            if(address) {
              deptContactInfoHtml += '' +
              '  <div class="address--container">' +
              '    <i class="sfgov-icon-location"></i>' +
              '    <span class="address">' + address +
              '    </span>' +
              '  </div>';
            }
            deptContactInfoHtml += '</div>';
          }
        }
        if(isTopicSearchResult) {
          html += '<div class="content-type"><i class="sfgov-icon-filefilled"></i><span>Topic</span></div>';
        }

        
        
        html += '    <a class="title-url" href="' + result.liveUrl + '" title="' + title + '"><h4>' + title + '</h4></a>';
        html += '    <div class="body-container">';
        // html += '      <div class="related-dept"></div>';
        html += '      <p class="body">' + Drupal.t(truncatedSummary) + '</p>';
        html += '      <a href="' + result.liveUrl + '" title="' + title + '">' + result.liveUrl + '</a>';
        html += deptContactInfoHtml;
        html += '    </div>';
        html += '  </div>';
        html += '  </div>';
        html += '</div>';
      }

      $('#sfgov-search-overlay').hide();
      $('#sfgov-search-loading').hide();

      if($('#sfgov-search-results').hasClass('sfgov-search-mobile-results')) {
        $(elem).html($(elem).html() + html);
      } else {
        $(elem).html(html);
      }

      $('#sfgov-search-results').removeClass('add-height');
    }
  }

  this.processSearchResults = function(data) {
    var resultsDiv = $('#sfgov-search-results');
    var messagesDiv = $('#sfgov-search-messages');
    var emptyResultSet = false;

    if(data.response.resultPacket) {
      var spell = data.response.resultPacket.spell ? true : false;
      var error = data.response.resultPacket.error ? true : false;
      var results = data.response.resultPacket.results;
      var resultsSummary = data.response.resultPacket.resultsSummary;
      var highlightRegex = data.response.resultPacket.queryHighlightRegex;
    
      if(!error) {
        console.log('here');
        if(spell && getQueryParam('si') !== 'true') { // misspelled word
          messagesDiv.prepend('<div class="sfgov-search-misspelled"><span>' + Drupal.t('Showing results for') + ' </span><a href="/search?keyword=' + Drupal.t(data.response.resultPacket.spell.text) + '" class="sfgov-spelled-keyword">' + Drupal.t(data.response.resultPacket.spell.text) + '</a><br><div class="sfgov-search-instead">Search instead for <a href="/search?keyword=' + Drupal.t(data.question.query) + '&si=true">' + Drupal.t(data.response.resultPacket.query) + '</a></div></div>');
          // make a request for the correctly spelled word
          search311.setParam('query', data.response.resultPacket.spell.text);
          search311.makeRequest();
        } else {
          if(results.length == 0) {
            emptyResultSet = true;
          } else {
            _this.renderSearchResults(results, resultsSummary, highlightRegex, resultsDiv, true);
            if(!$('.sfgov-search-pagination').hasClass('has-nav')) {
              _this.paginate(data);
              $('.sfgov-search-pagination').addClass('has-nav');
            }
            // show number of results
            this.updateCountSummary(resultsSummary.totalMatching, resultsSummary.currStart, (resultsSummary.nextStart ? resultsSummary.nextStart-1 : resultsSummary.totalMatching));
          }
        }
      }
      else {
        messagesDiv.prepend(Drupal.t('There was an error retrieving search results.  Please try again later.'));
      }
    } else {
      emptyResultSet = true;
    }
    if(emptyResultSet) {
      resultsDiv.html('<div class="no-search-results--container">' +
      '<h2>' + Drupal.t('We don\'t have anything yet that matches your search.') + '</h2>' +
      '<p>' + Druapl.t('Try searching our main website') + ', <a href="https://sfgov.org/all-pages-docs" target="_blank" rel="noopener noreferrer">sfgov.org</a>.</p>' + 
      '</div>');
      $('#sfgov-search-overlay').hide();
      $('#sfgov-search-loading').hide();
      $('#sfgov-search-results').removeClass('add-height');
    }
  }

  this.updateCountSummary = function(total, current, next) {
    $('#sfgov-search-results-count').html(current + ' - ' + next + ' of ' + total.toLocaleString() + ' results');
    $('#sfgov-search-results-count').show();
  }

  // add pagination for search results
  this.paginate = function(data) {
    var numPagesToShow = 5;

    var updatePagination = function(currentPage) {
      var pageLinks = $('.sfgov-search-pagination-nav .page-num');
      var numPages = pageLinks.length;
      var start = 1;
      var end = 1;

      for(var i=0; i<pageLinks.length; i++) {
        $(pageLinks[i]).addClass('hide-page').removeClass('more-prev').removeClass('more-next');
      }

      if(currentPage <= 2) {
        start = 1;
        end = numPagesToShow;
      } else if(currentPage > (numPages-2)) {
        start = (numPages - numPagesToShow) + 1;
        end = numPages;
      } else {
        start = currentPage - 2;
        end = currentPage + 2;
        if(start > 1) $(pageLinks[start-1]).addClass('more-prev');
      }

      if(end != numPages) $(pageLinks[end-1]).addClass('more-next');

      for(var i=start; i<=end; i++) {
        var index = i-1;
        $(pageLinks[index]).removeClass('hide-page');
      }

    };

    var resultsSummary = data.response.resultPacket.resultsSummary;
    if(resultsSummary) {
      var totalResults = resultsSummary.totalMatching;
      var resultsPerPage = resultsSummary.numRanks;
      var numPages = Math.ceil(totalResults/resultsPerPage);
      var paginateHtml = $('<ul class="sfgov-search-pagination-nav"></ul>');
      $(paginateHtml).append('<li class="previous" style="display:none"><a href="javascript:void(0)" title="' + Drupal.t('Previous search results page') + '">' + Drupal.t('Previous') + '</a></li>');
      for(var i=1; i<=numPages; i++) {
        var classname = '';
        if(i==1) classname += ' first current';
        if(i==numPages) classname += ' last';
        var listItem = $('<li class="' + classname + ' page-num"></li>');
        var pageLink = $('<a href="javascript:void(0)" data-page-num="' + i + '" data-next-start="' + (((i-1) * resultsPerPage) + 1) + '" title="' + Drupal.t('Search results page ') + i + '"></a>');
        $(listItem).append(pageLink);
        $(pageLink).click(function() {
          $('.sfgov-search-pagination-nav .current').removeClass('current');
          $(this).parent().addClass('current');
          var pageNum = parseInt($(this).attr('data-page-num'));
          var first = parseInt($('.sfgov-search-pagination-nav .first a').attr('data-page-num'));
          var last = parseInt($('.sfgov-search-pagination-nav .last a').attr('data-page-num'));
          var nextStart = $(this).attr('data-next-start');

          if(pageNum == last) {
            $('.sfgov-search-pagination-nav .next').hide();
          } else {
            $('.sfgov-search-pagination-nav .next').show();
          }
          
          if(pageNum == first) {
            $('.sfgov-search-pagination-nav .previous').hide();
          } else {
            $('.sfgov-search-pagination-nav .previous').show();
          }

          if(!$('#sfgov-search-results').hasClass('sfgov-search-mobile-results')) {
            $(document).scrollTop($('#block-sfgovsearchblock-2').offset().top);
          }

          _this.setParam('start_rank', nextStart);
          _this.makeRequest();
          updatePagination(pageNum);
        });
        $(pageLink).append(i);
        $(paginateHtml).append(listItem);
      }

      $('.sfgov-search-pagination').prepend(paginateHtml);

      if(numPages > 1) $(paginateHtml).append('<li class="next"><a href="javascript:void(0)" title="' + Drupal.t('Next search results page') + '">' + Drupal.t('Next') + '</a></li>');

      // next click
      $('.sfgov-search-pagination-nav .next').click(function() {
        var current = parseInt($('.sfgov-search-pagination-nav .current a').attr('data-page-num'));
        var nextPage = current + 1;
        var last = $('.sfgov-search-pagination-nav .last a').attr('data-page-num');
        $('a[data-page-num="' + nextPage + '"]').click();
      });

      // prev click
      $('.sfgov-search-pagination-nav .previous').click(function() {
        var current = parseInt($('.sfgov-search-pagination-nav .current a').attr('data-page-num'));
        var prevPage = current - 1;
        $('a[data-page-num="' + prevPage + '"]').click();
      });

      updatePagination(1);
    }
  }

  this.splitSearchResults = function(results) {
    var sfDotGovRegex = /sf\.gov/;
    var splitResults = {
      sfdotgov: [],
      other: []
    }
    for(var i=0; i<results.length; i++) {
      var result = results[i];
      if(result.liveUrl.match(sfDotGovRegex) || result.metaData.sfgovSummary) {
        splitResults.sfdotgov.push(result);
      } else {
        splitResults.other.push(result);
      }
    }
    return splitResults;
  }

  this.setProp = function(prop, value) {
    this.props[prop] = value;
  }

  this.setParam = function(param, value) {
    this.props.parameters[param] = value;
  };

  this.getProps = function() {
    return this.props;
  }

  _this = this;
}

function getQueryParam(queryParam) {
  var qs = window.location.search.substring(1);
  var params = qs.split('&');
  for(var i=0; i<params.length; i++) {
    var pair = params[i].split('=');
    var param = pair[0];
    var value = pair[1];
    if(param == queryParam) {
      return value;
    }
  }
  return null;
}

function attachMobileEvents() {
  var containerSelector = '.head-right--container #block-sfgovsearchblock';
  $(containerSelector + ' .mobile-btn').click(function() {
    if($(this).hasClass('close')) {
      $(this).removeClass('close');
      $(containerSelector).removeClass('mobile-open');
    } else {
      $(this).addClass('close');
      $(containerSelector).addClass('mobile-open');
    }
  });
  $('.sfgov-search-mobile-more').click(function() {
    $('.sfgov-search-pagination .next').click();
  });
}

function doMobile() {
  var currentPage = $('.sfgov-search-pagination-nav .current a').attr('data-page-num');
  var width = $(window).width();
  if(width <= 770) {
    $('#sfgov-search-results').addClass('sfgov-search-mobile-results');
    $('#sfgov-search-results .sfgov-search-result.views-row').show();
  } else {
    $('#sfgov-search-results').removeClass('sfgov-search-mobile-results');
    $('#sfgov-search-results .sfgov-search-result.views-row:not([data-result-page-num="' + currentPage + '"])').hide();
  }
};

var search311 = new Search311();
$(document).ready(function() {
  var kw = getQueryParam('keyword');
  if(drupalSettings.sfgovSearch) {
    $('.sf-gov-search-input-class').val(decodeURIComponent(kw));
    search311.setParam('query', kw);
    search311.makeRequest();
  }
  attachMobileEvents();
  doMobile();
  $(window).resize(function() {
    doMobile();
  });
});
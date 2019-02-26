// $url = 'https://search.sf311.org/s/search.json?query=birth+certificate&collection=sf-prod-search-meta&SM=qb&qsup=&start_rank=1&num_ranks=10';
$ = jQuery;
function Search311() {
  this.props = {
    "protocol": "https",
    "domain": "search.sf311.org",
    "path": "/s/search.json",
    "parameters": {
      "query": "",
      "collection": "sf-dev-crawl",
      "SM": "both",
      "qsup": "",
      "start_rank": 1,
      "num_ranks": 10,
      "callback": "search311.processSearchResults"
    }
  };

  this.makeRequest = function() {
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

  this.renderSearchResults = function(results, highlightRegex, elem, isSfGov) {
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
        if(result.metaData.sfgovSummary) {
          resultSummary = result.metaData.sfgovSummary;
        } else if(result.metaData.c) {
          resultSummary = result.metaData.c;
        } else if(result.summary) {
          resultSummary = result.summary;
        } else {
          resultSummary = 'No result summary';
        }
        resultSummary = resultSummary.replace(hr, '<strong>$&</strong>');
  
        if(isDeptSearchResult) {
          searchResultClass = 'department-search-result';
          searchResultContainerClass = 'department-search-result--container';
        }
  
        if(isTopicSearchResult) {
          searchResultClass = 'topic-search-result';
          searchResultContainerClass = 'topic-search-result--container';
        }
  
        // console.log(result);
  
        html += '<div class="sfgov-search-result views-row">';
        // html += '  <div style="font-size:22px;font-weight:bold">' + (i+1) + '</div>';
        html += '  <div class="' + searchResultClass + '">';
        html += '  <div class="' + searchResultContainerClass + ' sfgov-fb-search-result">';
    
        if(isDeptSearchResult) {
          html += '<div class="content-type"><i class="sfgov-icon-department"></i><span>Department</span></div>';
          
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
        
        html += '    <a class="title-url" href="' + result.liveUrl + '"><h4>' + title.replace(' | San Francisco', '') + '</h4></a>';
        html += '    <div class="body-container">';
        html += '      <div class="related-dept"></div>';
        // html += '      <p class="body">' + result.summary + '</p>';
        html += '      <p class="body">' + resultSummary + '</p>';
        html += '      <a href="' + result.liveUrl + '">' + result.liveUrl + '</a>';
        html += deptContactInfoHtml;
        html += '    </div>';
        html += '  </div>';
        html += '  </div>';
        html += '</div>';
      }
      $(elem).html(html);
    }
  }

  this.processSearchResults = function(data) {
    var spell = data.response.resultPacket.spell ? true : false;
    var error = data.response.resultPacket.error ? true : false;
    var results = data.response.resultPacket.results;
    var resultsSummary = data.response.resultPacket.resultsSummary;
    var highlightRegex = data.response.resultPacket.queryHighlightRegex;
    var resultsDiv = $('#sfgov-search-results');
    var resultsOtherDiv = $('#other-sfgov-search-results');
    var messagesDiv = $('#sfgov-search-messages');
  
    if(!error) {
      if(spell && getQueryParam('si') !== 'true') { // misspelled word
        messagesDiv.prepend('<div class="sfgov-search-misspelled"><span>Showing results for:</span><span class="sfgov-spelled-keyword">' + data.response.resultPacket.spell.text + '</span><br><div class="sfgov-search-instead">Search instead for: <a href="/search_?keyword=' + drupalSettings.sfgovSearch.keyword + '&si=true">' + data.response.resultPacket.query + '</a></div></div>');
        // make a request for the correctly spelled word
        search311.setParam('query', data.response.resultPacket.spell.text);
        search311.makeRequest();
      } else {
        if(results.length == 0) {
          resultsDiv.html('<div class="no-search-results--container">' +
          '<h2>We don\'t have anything yet that matches your search.</h2>' +
          '<p>Try searching our main website, <a href="https://sfgov.org/all-pages-docs" target="_blank" rel="noopener noreferrer">sfgov.org</a>.</p>' + 
          '</div>');
        } else {
          _this.renderSearchResults(results, highlightRegex, resultsDiv, true);
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
      messagesDiv.prepend('There was an error retrieving search results.  Please try again later.');
    }
  }

  this.updateCountSummary = function(total, current, next) {
    $('#sfgov-search-results-count').html(current + ' - ' + next + ' of ' + total + ' results');
    $('#sfgov-search-results-count').show();
  }

  // add pagination for search results
  this.paginate = function(data) {
    var resultsSummary = data.response.resultPacket.resultsSummary;
    if(resultsSummary) {
      var totalResults = resultsSummary.totalMatching;
      var resultsPerPage = resultsSummary.numRanks;
      var numPages = Math.ceil(totalResults/resultsPerPage);
      var paginateHtml = $('<ul class="sfgov-search-pagination-nav"></ul>');
      $(paginateHtml).append('<li class="previous" style="display:none"><a href="javascript:void(0)">previous</a></li>');
      for(var i=1; i<=numPages; i++) {
        var classname = '';
        if(i==1) classname += ' first current';
        if(i==numPages) classname += ' last';
        var listItem = $('<li class="' + classname + '"></li>');
        var pageLink = $('<a href="javascript:void(0)" data-page-num="' + i + '" data-next-start="' + (((i-1) * resultsPerPage) + 1) + '"></a>');
        $(listItem).append(pageLink);
        $(pageLink).click(function() {
          $('.sfgov-search-pagination-nav .current').removeClass('current');
          $(this).parent().addClass('current');
          var pageNum = parseInt($(this).attr('data-page-num'));
          var first = parseInt($('.sfgov-search-pagination-nav .first a').attr('data-page-num'));
          var last = parseInt($('.sfgov-search-pagination-nav .last a').attr('data-page-num'));
          var nextStart = $(this).attr('data-next-start');
          
          console.log('pageNum:' + pageNum);
          console.log('last:' + last);
          console.log('first:' + first);

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

          _this.setParam('start_rank', nextStart);
          _this.makeRequest();
        })
        $(pageLink).append(i);
        $(paginateHtml).append(listItem);
      }
      $('.sfgov-search-pagination').prepend(paginateHtml);
      $(paginateHtml).append('<li class="next"><a href="javascript:void(0)">next</a></li>');

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

function doMobile() {
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
}

var search311 = new Search311();
$(document).ready(function() {
  console.log('sfgov_search.js');
  if(drupalSettings.sfgovSearch) {
    $('.sf-gov-search-input-class').val(drupalSettings.sfgovSearch.keyword);
    search311.setParam('query', drupalSettings.sfgovSearch.keyword);
    search311.makeRequest();
  }
  doMobile();
});
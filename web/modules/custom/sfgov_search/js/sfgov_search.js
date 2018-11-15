// $url = 'https://search.sf311.org/s/search.json?query=birth+certificate&collection=sf-prod-search-meta&SM=qb&qsup=&start_rank=1&num_ranks=10';
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
      "callback": "processSearchResults"
    }
  };

  this.makeRequest = function() {
    var options = '';
    for(var key in this.props.parameters) {
      options += key + '=' + this.props.parameters[key] + '&';
    }
    options = options.substring(0, options.length-1); // remove last &
    var url = this.props.protocol + '://' + this.props.domain + this.props.path + '?' + options;
    jQuery.ajax({
      url: url, 
      dataType: 'jsonp'
    });
  };

  this.setProp = function(prop, value) {
    this.props[prop] = value;
  }

  this.setParam = function(param, value) {
    this.props.parameters[param] = value;
  };

  this.getProps = function() {
    return this.props;
  }
}

var search311 = new Search311();

function renderSearchResults(results, elem, isSfGov) {
  var html = '';
  if(results.length > 0) {
    if(isSfGov) {
      elem.append('<div class="sfgov-search-domain-label sfgov-search-result views-row">Results from sf.gov</div>');
    } else {
      elem.append('<div class="sfgov-search-domain-label sfgov-search-result views-row">Results from other city departments</div>');
    }
    for(var i=0; i<results.length; i++) {
      var result = results[i];
      console.log(result);
      var deptContactInfoHtml = '';
      var isDeptSearchResult = result.liveUrl.match(/\/departments\//) ? true : false;
      var isTopicSearchResult = result.liveUrl.match(/\/topics\//) ? true : false;
      var searchResultClass = 'transaction-search-result';
      var searchResultContainerClass = 'sfgov-transaction-search--container'

      var resultSummary = result.metaData.c ? result.metaData.c : result.summary;
      
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
      
      html += '    <a class="title-url" href="' + result.liveUrl + '"><h4>' + result.title.replace(' | San Francisco', '') + '</h4></a>';
      html += '    <div clas="body-container">';
      html += '      <div class="related-dept"></div>';
      // html += '      <p class="body">' + result.summary + '</p>';
      html += '      <p class="body">' + resultSummary + '</p>';
      html += deptContactInfoHtml;
      html += '    </div>';
      html += '  </div>';
      html += '  </div>';
      html += '</div>';
    }
    elem.html(elem.html() + html);
  }
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

function splitSearchResults(results) {
  var sfDotGovRegex = /sf\.gov/;
  var splitResults = {
    sfdotgov: [],
    other: []
  }
  for(var i=0; i<results.length; i++) {
    var result = results[i];
    if(result.liveUrl.match(sfDotGovRegex)) {
      splitResults.sfdotgov.push(result);
    } else {
      splitResults.other.push(result);
    }
  }
  return splitResults;
}

function processSearchResults(data) {
  var spell = data.response.resultPacket.spell ? true : false;
  var error = data.response.resultPacket.error ? true : false;
  var results = data.response.resultPacket.results;
  var resultsDiv = jQuery('#sfgov-search-results');
  var resultsOtherDiv = jQuery('#other-sfgov-search-results');
  var messagesDiv = jQuery('#sfgov-search-messages');

  if(!error) {
    if(spell && getQueryParam('si') !== 'true') { // misspelled word
      messagesDiv.prepend('<div class="sfgov-search-misspelled">Showing results for <strong><em>' + data.response.resultPacket.spell.text + '</em></strong><br><div class="sfgov-search-instead">Search instead for <a href="/search_?keyword=' + drupalSettings.sfgovSearch.keyword + '&si=true">' + data.response.resultPacket.query + '</a></div></div>');
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
        var splitResults = splitSearchResults(results);
        renderSearchResults(splitResults.sfdotgov, resultsDiv, true);
        renderSearchResults(splitResults.other, resultsOtherDiv, false);
      }
    }
  }
  else {
    messagesDiv.prepend('There was an error retrieving search results.  Please try again later.');
  }
}

jQuery(document).ready(function() {
  jQuery('#edit-sfgov-search-input').val(drupalSettings.sfgovSearch.keyword);
  search311.setParam('query', drupalSettings.sfgovSearch.keyword);
  search311.makeRequest();
});
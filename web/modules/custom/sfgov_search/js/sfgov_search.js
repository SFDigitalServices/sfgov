(function($) {
  console.log($('#sfgov_search_results'));
  console.log(drupalSettings.sfgovSearch.keyword);
  var protocol = 'https';
  var domain = 'search.sf311.org';
  var path = '/s/search.json?query=';
  var collection = 'sf-dev-crawl';
  var options = 'SM=qb&qsup=&start_rank=1&num_ranks=10';
  // $url = 'https://search.sf311.org/s/search.json?query=birth+certificate&collection=sf-prod-search-meta&SM=qb&qsup=&start_rank=1&num_ranks=10';
  var url = protocol + '://' + domain + path + encodeURI(drupalSettings.sfgovSearch.keyword) + '&collection=' + collection + '&' + options;
  url += '&callback=processSearchResults'
  console.log(url);
  $.ajax({
    url: url, 
    dataType: 'jsonp'
  });
})(jQuery);

function processSearchResults(data) {
  var spell = data.response.resultPacket.spell ? true : false;
  var error = data.response.resultPacket.error ? true : false;
  var results = data.response.resultPacket.results;
  var resultsDiv = jQuery('#sfgov_search_results');

  if(spell) { // misspelled word
    resultsDiv.prepend('<div class="misspelled">Did you mean ' + data.response.resultPacket.spell.text + '?</div>');
  } else {
    var html = '';
    for(var i=0; i<results.length; i++) {
      var result = results[i];
      html += '<div class="sfgov-search-result views-row">';
      html += '  <div class="sfgov-transaction-search--container sfgov-fb-search-result">';

      if(result.liveUrl.match(/\/departments\//)) {
        html += '<div class="content-type"><i class="sfgov-icon-department"></i><span>Department</span></div>';
      }
      if(result.liveUrl.match(/\/topics\//)) {
        html += '<div class="content-type"><i class="sfgov-icon-filefilled"></i><span>Topic</span></div>';
      }
      
      html += '    <a class="title-url" href="' + result.liveUrl + '"><h4>' + result.title.replace(' | San Francisco', '') + '</h4></a>';
      html += '    <div clas="body-container">';
      html += '      <div class="related-dept"></div>';
      html += '      <p class="body">' + result.summary + '</p>';
      html += '    </div>';
      html += '  </div>';
      html += '</div>';
    }
    resultsDiv.html(html);
  }
}

(function($) {
  console.log($('#sfgov_search_results'));
  console.log(drupalSettings.sfgovSearch.keyword);
  var protocol = 'https';
  var domain = 'search.sf311.org';
  var path = '/s/search.json?query=';
  var collection = 'sf-prod-search-meta';
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
  console.log(data);
}

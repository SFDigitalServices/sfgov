
/*
* the purpose of this little bit of js is to take into account the sort title 
* of a transaction (sort title is a field on the transaction content type)
* and highlight the sort title on the actual title.
* For example, on the title "Apply for a loan to repair your home", and sort title "Loan to repair your home",
* this js should replace the title with "Apply for a <strong>loan to repair your home</strong>"
* the sort title is an attribute (data-sort-title) on each link rendered by
* the drupal view Services on the "Services (page):A-Z" display
* refer to /web/modules/custom/sfgov_admin/js/src/sfgov-admin.js to see how sort titles are created
*/
(function($) {
  // function to check if a single character is alphanumeric
  function isAlphaNumeric(char) {
    return char.length == 1 && char.match(/([a-zA-Z]|\d)/i);
  }

  // function to escape special characters in a string
  function replaceSpecialChars(str) {
    var specialChars = ['(', ')', '[', ']', '{', '}', '|', '\\', '?', '+', '$', '^', '*', '!'];
    var specialCharsRegexStr = '[\\' + specialChars.join('\\') + ']';
    // var specialCharsRegex = /[\(\)\[\]\{\}\|\\\?\+\$\^\*\!]/g;
    var specialCharsRegex = new RegExp(specialCharsRegexStr, 'g');
    var specialCharsMatch = str.match(specialCharsRegex);
    var replacedStr = str;
    if(specialCharsMatch) {
      for(var i=0; i<specialCharsMatch.length; i++) {
        replacedStr = replacedStr.replace(specialCharsMatch[i], '\\' + specialCharsMatch[i]);
      }
    }
    return replacedStr;
  }

  $('.sfgov-service-a-z').each(function() {
    var title = $(this).html();
    var sortTitle = $(this).attr('data-sort-title').split(' ');
    
    var regexStr = '';
    // loop through the sort title words and create a regular expression to match and replace for highlighting the sorted phrase in the actual title
    // ex: Apply for a loan to repair your home
    // should result in the following regex: (L|l)oan(\s+)?(T|t)o(\s+)?(R|r)epair(\s+)?(Y|y)our(\s+)?(H|h)ome(\s+)?
    for(var i=0; i<sortTitle.length; i++) {
      var firstLetter = sortTitle[i].charAt(0);
      if(isAlphaNumeric(firstLetter)) {
        regexStr += '(' + firstLetter.toUpperCase() + '|' + firstLetter.toLowerCase() + ')' + replaceSpecialChars(sortTitle[i].slice(1)) + '(\\s+)?';
      } else {
        regexStr += replaceSpecialChars(sortTitle[i]) + '(\\s+)?';
      }
    }

    try {
      var regex = new RegExp(regexStr);
      var match = $(this).html().match(regex);
      if(match.length > 0) {
        var replacedTitle = $(this).html().replace(match[0], '<strong>' + match[0] + '</strong>');
        $(this).html(replacedTitle);
      }
    } catch(e) {
      console.log('skipped: ' + $(this).html());
    }
  });
})(jQuery);
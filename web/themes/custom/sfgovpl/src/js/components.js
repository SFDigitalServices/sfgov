(function($) {
  console.log('components.js');
  var toggles = document.querySelectorAll('[data-toggle-container]');
  for(var i=0; i<toggles.length; i++) {
    (function(n) {
      var trigger = toggles[i].querySelector('[data-toggle-trigger]');
      $(trigger).click(function(e) { // TODO: remove jquery dependency, roll our own method of adding event listeners
        var triggerLink = e.target;
        if(toggles[n].hasAttribute('data-toggle-show')) {
          toggles[n].removeAttribute('data-toggle-show');
          triggerLink.innerHTML = 'Show more';
        } else {
          toggles[n].setAttribute('data-toggle-show', '');
          triggerLink.innerHTML = 'Show less';
        }
      });
    })(i);
  }
})(jQuery);
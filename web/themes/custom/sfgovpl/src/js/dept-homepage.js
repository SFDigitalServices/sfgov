(function($) {
  $('body.page-node-type-department').each(function() {
    // an array of selectors for the dept homepage sections
    var sections = [
      {"selector": "#sfgov-dept-services", "label":"Services"},
      {"selector": "#sfgov-dept-news", "label":"News"},
      {"selector": "#sfgov-dept-events", "label":"Events"},
      {"selector": "#sfgov-dept-resources", "label":"Resources"},
      {"selector": "#sfgov-dept-about", "label":"About"},
      {"selector": "#sfgov-dept-contact", "label":"Contact"},
    ];
    var $inPageMenuContainer = $('#sfgov-dept-in-this-page');
    var $inPageMenuList = $('#sfgov-dept-in-this-page ul');
    var $scrollElem = $('html, body');
    var scrollTo = function(elemSelector) {
      $scrollElem.animate({
        scrollTop: $(elemSelector).offset().top
      }, 300);
      return false;
    }

    // create the elements
    for(var i=0; i<sections.length; i++) {
      var elem = $(sections[i].selector);
      // console.log(sections[i]);
      if(elem.length > 0) {
        $inPageMenuContainer.show();
        var li = document.createElement('li');
        var a = document.createElement('a');
        $(a).attr('href', '#'+sections[i].label.toLowerCase()).attr('data-section', sections[i].selector).text(sections[i].label);
        $(li).append(a);
        $inPageMenuList.append(li);
      }
    }

    var $links = $inPageMenuList.find('a');
    $links.click(function() {
      var scrollToSelector = 'a[name="' + $(this).attr('href').replace('#', '') + '"]';
      scrollTo(scrollToSelector);
    });

    $(document).ready(function() {
      if(window.location.hash) {
        var selector = 'a[name="' + window.location.hash.replace('#','') + '"]';
        scrollTo(selector);
      }
    });
  });
})(jQuery);

(function($) {
  var aurl = 'https://antk.github.io/birthday/assets/';
  var cbg1 = aurl + 'confetti-bg.png';
  var cbg2 = aurl + 'rain-confetti.gif';
  var fw = aurl + 'fireworks.gif';
  var bn = aurl+ 'balloons.gif';
  var bs = aurl + 'birthday.mp3';
  var clickCount = 0;
  var trigger = document.createElement('div');
  $('body').append(trigger);
  $(trigger).css({
    position:'absolute', 
    bottom:0, 
    right:0, 
    height:'100px', 
    width:'100px', 
    background: 'rgba(255, 255, 255, 0.02)'
  });
  var intervalId = window.setInterval(function() {
    clickCount = 0;
  }, 4000);
  $(trigger).click(function() {
    clickCount++;
    if(clickCount >= 5) {
      clickCount = 0;
      $('html, body').css({overflow:'hidden'});
      happyBirthday();
      window.clearInterval(intervalId);
    }
  });
  function happyBirthday() {
    hideElems();
    $.ajax({
      url: "//api.giphy.com/v1/gifs/search?api_key=OB4hiQ16LN99kAgP2oAFBsX550GD223X&q=birthday&limit=100&offset=0&rating=G&lang=en"
    }).done(function(data) {
      if(data) {
        hideElems();
        var gifs = data.data;
        var overlay = document.createElement('div');
        var birthdayElem = document.createElement('div');
        $('body').append(overlay).append(birthdayElem);
        $(overlay).css({
          position: 'absolute',
          top: '0',
          left: '0',
          height: $(window).height() + 'px',
          width: '100%',
          background: '#333',
          opacity: '1'
        });
        $(birthdayElem).css({
          position: 'absolute',
          top: $(window).scrollTop(),
          left: '0',
          height: $(window).height() + 'px',
          width: '100%',
          background: 'rgba(255,255,255,0.8)'
        });
        var birthdayHtml = '' + 
        '<div id="birthday-bg" style="background:url(' + cbg1 + ') no-repeat top center;width:100%;height:100%;background-size:cover;position:relative;">' +
        ' <div style="background:url(' + cbg2 + ') no-repeat top center; width:100%; height:100%;background-size:cover;">' +
        '  <div id="birthday-gif" style="width:100%;height:' + ($(window).height()) + 'px"></div>' +
        ' </div>';
        var elemHtml = '<img class="fireworks" data-src="' + fw + '" style="position:absolute;top:0;left:0;"/>';
        birthdayHtml += elemHtml;
        birthdayHtml += '</div>';
        $(birthdayElem).html(birthdayHtml);
        birthdaySong();
        mouseBalloon();
        cycleGifs(gifs);
        $('#bplay-btn').click();
        $('body').click(function() {
          $('#baudio')[0].play();
        });
      }
    });

    function mouseBalloon() {
      var evt = 'ontouchstart' in document.documentElement ? 'touchstart' : 'click';
      $('body').on(evt, function(e) {
        var div = $(document.createElement('div'));
        div.css({
          background: 'url(' + bn + ') no-repeat',
          'background-size': 'cover',
          'background-position': Math.floor(Math.random() * 2) == 0 ? '96px 0px' : '-106px 0px',
          width: '200px',
          height: '200px',
          position: 'absolute',
          top: (e.pageY-100) + 'px',
          left: (e.pageX-100) + 'px',

        });
        $('body').append(div);
        setTimeout(function() {
          $(div).css({
            transition: '3s ease-in-out',
            transform: 'translateY(-1000px)',
          });
        }, 10);
      })
    }

    function birthdaySong() {
      var html = '<audio id="baudio" loop controls style="width:0px"><source src="' + bs + '"></audio>';
      html += '<a id="bplay-btn" href="#">play</a>';
      $('body').append(html);
      $('#bplay-btn').click(function() {
        $('#baudio')[0].play();
      });
    }

    function cycleGifs(gifs) {
      var birthdayGif = $('#birthday-gif');
      birthdayGif.css({background: 'url(' + gifs[Math.floor(Math.random() * gifs.length)].images.original.url + ') no-repeat center center', 'background-size':'contain'});
      setInterval(function() {
        var index = Math.floor(Math.random() * gifs.length);
        birthdayGif.css({background: 'url(' + gifs[index].images.original.url + ') no-repeat center center', 'background-size':'contain', 'opacity':'0.9'});
      }, 3000);
      var fireworks = $('.fireworks');
      var fwhMin = 250;
      var fwhMax = 500;
      var topMin = 1;
      var topMax = 60;
      var leftMin = 1;
      var leftMax = 60;

      setInterval(function() {
        var randomTop = Math.random() * (topMax - topMin) + topMin;
        var randomLeft = Math.random() * (leftMax - leftMin) + leftMin;
        var randomFwDimensions = Math.random() * (fwhMax - fwhMin) + fwhMax;
        var src = $(fireworks).attr('data-src');
        $(fireworks).css({top:randomTop + '%', left:randomLeft + '%', width:randomFwDimensions + 'px', height: randomFwDimensions + 'px'});
        $(fireworks).attr('src', src);
        $(fireworks).show();
        setTimeout(function() {
          $(fireworks).hide();
          $(fireworks).removeAttr('src');
        }, 1500)
      }, 2000)
    }

    function hideElems() {
      $('.dialog-off-canvas-main-canvas').hide();
    }
  }

})(jQuery);
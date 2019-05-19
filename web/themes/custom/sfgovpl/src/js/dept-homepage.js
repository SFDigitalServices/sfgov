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
  var clickCount = 0;
  var trigger = document.createElement('div');
  $('body').append(trigger);
  $(trigger).css({
    position:'absolute', 
    bottom:0, 
    left:0, 
    height:'50px', 
    width:'50px', 
    background: 'rgba(255, 255, 255, 0.02)'
  });
  var intervalId = window.setInterval(function() {
    clickCount = 0;
  }, 2000);
  $(trigger).click(function() {
    clickCount++;
    if(clickCount >= 5) {
      window.clearInterval(intervalId);
      clickCount = 0;

      $('body').css({overflow:'hidden'});
      happyBirthday();
    }
  });
  function happyBirthday() {
    var overlay = document.createElement('div');
    var birthdayElem = document.createElement('div');
    $('body').append(overlay).append(birthdayElem);
    $(overlay).css({
      position: 'absolute',
      top: '0',
      left: '0',
      height: $(document).height() + 'px',
      width: '100%',
      background: '#333',
      opacity: '0.4'
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
    '<div id="birthday-bg" style="background:url(https://i.ibb.co/6JFCL81/confetti-bg.png) no-repeat top center;width:100%;height:100%;background-size:cover;position:relative;">' +
    ' <div style="background:url(https://i.ibb.co/wNjg4yt/rain-transparent-confetti-1.gif) no-repeat top center; width:100%; height:100%;background-size:cover;">' +
    '  <div id="birthday-gif" style="width:100%;height:' + ($(window).height()) + 'px"></div>' +
    ' </div>';
    var elemHtml = '<img class="fireworks" data-src="https://i.ibb.co/kHpTH9w/fireworks.gif" style="position:absolute;top:0;left:0;"/>';
    birthdayHtml += elemHtml;
    birthdayHtml += '</div>';

    $.ajax({
      url: "//api.giphy.com/v1/gifs/search?api_key=OB4hiQ16LN99kAgP2oAFBsX550GD223X&q=birthday&limit=100&offset=0&rating=G&lang=en"
    }).done(function(data) {
      if(data) {
        var gifs = data.data;
        $(birthdayElem).html(birthdayHtml);
        birthdaySong();
        mouseBalloon();
        setTimeout(function() {
          cycleGifs(gifs);
        },2500);
      }
    });

    function mouseBalloon() {
      $('body').click(function(e) {
        var img = document.createElement('img');
        $(img).attr('src', 'https://i.ibb.co/wC05L40/balloons.gif');
        $(img).css({
          position:'absolute',
          width:'200px',
          top:(e.pageY-100)+'px',
          left:(e.pageX-100)+'px',
          'clip-path': Math.floor(Math.random() * 2) == 0 ? 'inset(45px 96px 0px 0px)' : 'inset(0 29px 0px 106px)',
        });
        $('body').append(img);
        setTimeout(function() {
          $(img).css({
            transition: '3s ease-in-out',
            transform: 'translateY(-1000px)'
          });
        }, 10);
      })
    }

    function birthdaySong() {
      var iframeHtml = '<iframe width="560" height="315" src="https://www.youtube.com/embed/8zgz2xBrvVQ?autoplay=1&t=1&loop=1&playlist=8zgz2xBrvVQ&end=120" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
      $('body').append(iframeHtml);
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
  }

})(jQuery);
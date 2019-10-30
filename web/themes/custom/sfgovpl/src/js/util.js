var SFGOV = {};
SFGOV.util = {
  getParam: function(name) {
    var result = null,
        tmp = [];
    location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
          tmp = item.split("=");
          if (tmp[0] === name) result = decodeURIComponent(tmp[1]);
        });
    return result;
  }
}
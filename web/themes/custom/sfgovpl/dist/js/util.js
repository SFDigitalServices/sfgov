"use strict";

var SFGOV = {};
SFGOV.util = {
  getParam: function getParam(name) {
    var result = null,
        tmp = [];
    location.search.substr(1).split("&").forEach(function (item) {
      tmp = item.split("=");
      if (tmp[0] === name) result = decodeURIComponent(tmp[1]);
    });
    return result;
  }
};
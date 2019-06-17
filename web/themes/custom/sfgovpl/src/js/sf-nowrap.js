"use strict";
(function($) {
    var nowrap = function(oldPhase, newPhase){
        $('body :not(script)').contents().filter(function(){
                return this.nodeType === 3;
        }).replaceWith(function() {
            return this.nodeValue.replace(oldPhase, newPhase);
        });      
    }
    $('document').ready(function(){
        nowrap('San Francisco', 'San&nbsp;Francisco');
    });
})(jQuery);
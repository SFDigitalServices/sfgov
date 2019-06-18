"use strict";
(function($) {
    var nowrap = function(oldPhrase){
        var newPhrase = oldPhrase.replace(' ', '\xa0');
        $('body :not(script)').contents().filter(function(){
                return this.nodeType === 3;
        }).each(function(){
            if(this.nodeValue.indexOf(oldPhrase)>=0){
                this.nodeValue = this.nodeValue.replace(oldPhrase, newPhrase);
            }
        });
    }
    $('document').ready(function(){
        nowrap('San Francisco');
    });
})(jQuery);

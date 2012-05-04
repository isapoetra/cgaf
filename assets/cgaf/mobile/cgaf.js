(function($,cgaf){
	$(document).bind("mobileinit", function(){
	  $.mobile.defaultPageTransition = 'pop';
	  $.mobile.allowCrossDomainPages = true;
	});
	cgaf.defaults.ismobile=true;
})(jQuery,cgaf)
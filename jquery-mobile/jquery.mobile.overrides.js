/* 
  override core jquery-mobile to support 'normal' web page view on mobile pages
  when viewed on e.g iPad landscape and above
  and other behaviour changes
 */

$(document).bind('mobileinit', function(){
  $.extend(  $.mobile , {
    defaultPageTransition: "none"
  });
  $.mobile.selectmenu.prototype.options.initSelector = ".mobileSelect"; // for selects and flip switches
  $.mobile.activeBtnClass = 'unused'; // stop footer bar buttons remaining active in ajaxy pages
});



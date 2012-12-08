/*! jQuery noSelect plugin (from Flexigrid - v1.1)
* http://code.google.com/p/flexigrid/
* Copyright (c) 2008 by Paulo P. Marinas; Licensed MIT, GPLv2 */
(function($) {$.fn.noSelect = function () { return this.each(function () {if ($.browser.msie || $.browser.safari) $(this).bind('selectstart', function () {return false;});else if ($.browser.mozilla) {$(this).css('MozUserSelect', 'none');$('body').trigger('focus');} else if ($.browser.opera) $(this).bind('mousedown', function () {return false;});else $(this).attr('unselectable', 'on');});};})(jQuery);

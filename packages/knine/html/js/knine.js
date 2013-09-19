(function($) {
	/**
	* jQuery Knine plugin :
	* Allows to browse dynamically a knowledge article
	*
	*/

	$.fn.knine = function(arg) {
		var default_conf = {
			article_id: 0,
			values: {},
			level: '',
			depth: 0,
			autonum: true,
			lang: 'en',
			lang_summary: 'Summarize', 
			lang_details: 'Read more'			
		};

		var methods = {
			/*
			*	This method allows to give live to a static object (i.e. server-side generated)
			*/
			dynamize: function($elem, conf) {
				// for all visible links
				var $details_links = $elem.find('.details_link');
				var $summary_links = $elem.find('.summary_link');
				
				// handle click events
				$details_links.bind('click', function() {
					// get related DOM objects
					var $details_link = $(this);
					var $article = $details_link.parent().parent();					
					var $level = $article.find('.level').first();					
					// unfold content
					var $new_article = $('<div/>').addClass('article').attr('id', $article.attr('id'));				
					var new_level = $level.text();
					if(new_level.length > 0) new_level = new_level + '.';
					$new_article.knine({
						article_id: $article.attr('id'),
						level: new_level,
						depth: 1,
						lang: conf.lang,						
						lang_summary: conf.lang_summary,
						lang_details: conf.lang_details						
					});
					$article.replaceWith($new_article.children());
				});
				$summary_links.bind('click', function() {
					// get related DOM objects	
					var $summary_link = $(this);
					var $details_link = $summary_link.parent().find('.details_link').last();		
					var $summary = $summary_link.parent().parent().find('.summary').first();
					var $content = $summary_link.parent().parent().find('.content').first();
					// hide/show what must be
					$summary.show();
					$content.hide();
					$details_link.show();
					$summary_link.hide();						
				});			
			},
			
			/*
			*	This method fills a DOM object with an article at specified level and adds related event handlers
			*/
			load: function($elem, conf) {
				// load article (or get it from conf)
				if($.isEmptyObject(conf.values)) {
					var result = browse('knine\\Article', [conf.article_id], ['id', 'modified', 'title', 'summary', 'content', 'children_ids'], conf.lang);
					// check that the article does actualy exist						
					if(typeof result[conf.article_id] == 'undefined') return; 
					conf.values = result[conf.article_id];
				}
				// 1) First, we build article DOM structure
				var $article = $('<div/>').addClass('article').attr('id', conf.article_id)
				if(conf.autonum) $article.append($('<div/>').addClass('level').text(conf.level));
				var $title = $('<div/>').addClass('title').appendTo($article);	
				if(conf.autonum) $title.text(conf.level + ' ' + conf.values['title']);
				else $title.text(conf.values['title']);
				var $summary = $('<div/>').addClass('summary').html(conf.values['summary']).appendTo($article);
				var $content = $('<div/>').addClass('content').appendTo($article);

				// add authoring information
				if(conf.level == '') {				
					var result = browse('knine\\Article', [conf.article_id], ['authors_ids'], conf.lang);
					result = browse('knine\\User', result[conf.article_id]['authors_ids'], ['firstname','lastname'], conf.lang);
					var authors = '';
					var res_len = Object.keys(result).length, j = 0;
					$.each(result, function(id, item) {
						if(authors.length) { 
							if(j == res_len-1) authors += ' et ';
							else authors += ', ';
						}
						authors += item.firstname+' '+item.lastname;
						++j;
					});
					var date = new Date();
					date.setFullYear(conf.values['modified'].substring(0,4));
					date.setMonth(conf.values['modified'].substring(5,7));	
					date.setDate(conf.values['modified'].substring(8,10));
					$title.html($title.html()+'<p style="font-size: 12px;">&nbsp;&nbsp; par '+authors+'</p><p style="font-size: 12px;">&nbsp;('+date.toLocaleDateString()+')</p>');
					$title.addClass('main');						
				}
				//add show/hide links
				var $details_link = $('<a/>').addClass('details_link').text(conf.lang_details);
				var $summary_link = $('<a/>').addClass('summary_link').text(conf.lang_summary);
				$article.append($('<div/>').addClass('display_button').append($details_link).append($summary_link));

				// 2) Then we add the dynamics
				
				// handle content display
				$content.bind('unfold', function() {
						if(!$content.hasClass('loaded')) {
							if($.isEmptyObject(conf.values['children_ids'])) {
								$content.html(conf.values['content']);
							}
							else {
								var result = browse('knine\\Article', conf.values['children_ids'], ['created', 'title', 'summary', 'content', 'children_ids'], conf.lang);
								if(typeof result == 'object') {
									var i = 0;
									$.each(result, function(id, item){									
										var next_level = new Number(i)+1;
										if(conf.level.length) next_level = conf.level + next_level;
										methods.load($content, {
														article_id: id,
														level: next_level+'.',
														depth: conf.depth-1,
														values: result[id],
														lang: conf.lang,
														lang_summary: conf.lang_summary,
														lang_details: conf.lang_details
													});
										++i;
									});
									$content.addClass('loaded');
								}
							}
						}
					}
				);	
				
				// handle click events
				$details_link.bind('click', function() {
					$content.trigger('unfold');
					$summary.hide();
					$content.show();
					$details_link.hide();
					$summary_link.show();
				});
				$summary_link.bind('click', function() {
					$summary.show();
					$content.hide();
					$details_link.show();
					$summary_link.hide();						
				});
			
				// check if there is something more to do for content part (i.e. recurse to a deeper level)
				if(conf.depth <= 0) {
					$content.hide();
					$summary_link.hide();
				}
				else {
					$content.trigger('unfold');
					$summary.hide();
					$details_link.hide();
				}
				// append article DOM object
				$elem.append($article);			
			}
		};
		
		return this.each(function() {
			(function ($elem, conf) {	
				// has not yet been instancialized
				if($elem.data('knine') == undefined) {
					if($elem.hasClass('loaded')) {
						// we just need to add the dynamics (i.e. to handle events)
						methods.dynamize($elem, conf);						
					}
					else {
						// load content via ajax and listen to events
						methods.load($elem, conf);
						// mark elem as loaded
						$elem.addClass('loaded');						
					}
					// keep track of the instancialization
					$elem.data('knine', true);
				}
			})($(this), $.extend(true, default_conf, arg));
		});
	};
})(jQuery);
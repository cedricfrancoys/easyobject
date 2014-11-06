// fix for browsers that don't natively support Object.keys
Object.keys=Object.keys||function(o,k,r){r=[];for(k in o)r.hasOwnProperty.call(o,k)&&r.push(k);return r};

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

		// we use a semaphore to check the loading status, when it reaches back 0, we mark the article as ready
		var loading = 0;
		
		var methods = {
			/*
			*	This method allows to give live to a static object (i.e. server-side generated)
			*/
			dynamize: function($elem, conf) {
				// handle click events for details links
				$elem.find('.details_link').on('click', function() {
					// get related DOM objects
					var $article = $(this).parent().parent();					
					var article_id = $article.attr('id');
					var new_level = $article.find('.level').first().text();
					if(new_level.length > 0) new_level += '.';
					// display loader
					var $loader = $('<div />').addClass('loader').text('Loading ...');
					$article.replaceWith($loader);
					// unfold content
					$('<div/>').addClass('article').attr('id', article_id)
					.knine({
						article_id: article_id,
						level: new_level,
						depth: 1,
						lang: conf.lang,
						lang_summary: conf.lang_summary,
						lang_details: conf.lang_details,
						autonum: conf.autonum
					})
					.on('ready', function() {
						// replace loader
						$article = $(this).children();
						$loader.replaceWith($article);						
						$('html, body').animate({scrollTop: $article.offset().top}, 1);
					});	
				});
				// handle click events for summary links
				$elem.find('.summary_link').on('click', function() {
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
				// update semaphore
				if(typeof conf.values['children_ids'] != 'undefined' && conf.depth > 0) {
					loading += Object.keys(conf.values['children_ids']).length;
				}
				// 1) First, we build article DOM structure
				var $article = $('<div/>').addClass('article').attr('id', conf.article_id)
				var $title = $('<div/>').addClass('title').appendTo($article);
				if(conf.autonum && conf.level != '') {
					$article.append($('<div/>').addClass('level').text(conf.level));
					$title.text(conf.level + ' ' + conf.values['title']);
				}
				else $title.text(conf.values['title']);
				var $summary = $('<div/>').addClass('summary').html(conf.values['summary']).appendTo($article);
				var $content = $('<div/>').addClass('content').appendTo($article);

				// add authoring information
				if(conf.level == '') {
					$title.addClass('main');
					loading++;
					$.when(easyObject.browse({
						class_name: 'knine\\Article',
						ids: [conf.article_id],
						fields: ['authors_ids'], 
						lang: conf.lang,
						async: true
					})).done(function(result) {
						if(typeof result == 'object' && typeof result[conf.article_id] != 'undefined' && !$.isEmptyObject(result[conf.article_id]['authors_ids'])) {
							$.when(easyObject.browse({
								class_name: 'knine\\User',
								ids: result[conf.article_id]['authors_ids'],
								fields: ['firstname','lastname'], 
								lang: conf.lang,
								async: true
							})).done(function(result) {
								if(typeof result == 'object') {
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
									var date = conf.values['created'];
									year = date.substring(0,4);
									month = date.substring(5,7);
									day = date.substring(8,10);
									$title.html($title.html()+'<br /><span style="font-size: 12px;">'+authors+'&nbsp;('+day+'/'+month+'/'+year+')</span>');
								}
								if(!loading) $elem.trigger('ready');
								else --loading;
							});						
						}
						else {
							if(!loading) $elem.trigger('ready');
							else --loading;
						}
					});						
				}

				//add show/hide links
				var $details_link = $('<a/>').addClass('details_link').text(conf.lang_details);
				var $summary_link = $('<a/>').addClass('summary_link').text(conf.lang_summary);

				if(conf.depth > 0 && $.isEmptyObject(conf.values['children_ids']) && conf.values['content'].length == 0) conf.depth = 0;
				
				if( (conf.depth == 0 && (!$.isEmptyObject(conf.values['children_ids']) || conf.values['content'].length > 0))
					||
					(conf.depth > 0 && conf.values['summary'].length > 0) )
					$article.append($('<div/>').addClass('display_button').append($details_link).append($summary_link));

				// 2) Then we add the dynamics

				// handle content display
				$content.on('unfold', function() {
						if(!$content.hasClass('loaded')) {
							if($.isEmptyObject(conf.values['children_ids'])) {
								$content.html(conf.values['content']);
							}
							else {
								$.when(easyObject.browse({
									class_name: 'knine\\Article',
									ids: conf.values['children_ids'],
									fields: ['created', 'title', 'summary', 'content', 'children_ids'], 
									lang: conf.lang,
									async: true
								})).done(function(result) { 
									if(typeof result == 'object') {
										var i = 0;
										$.each(result, function(id, item){
											var next_level = new Number(i)+1;
											if(conf.level.length) next_level = conf.level + next_level;
											methods.load($content, {
															article_id: id,
															level: next_level+'.',
															depth: conf.depth-1,
															autonum: conf.autonum,
															values: result[id],
															lang: conf.lang,
															lang_summary: conf.lang_summary,
															lang_details: conf.lang_details
														});
											++i;
										});
									}								
								});								
							}							
						}
					}
				);

				// handle click events
				$details_link.bind('click', function() {
					$content.trigger('unfold');					
					$details_link.hide();
					$summary.hide();
					$content.show();
					$summary_link.show();
					$('html, body').animate({scrollTop: $article.offset().top}, 1000);
				});
				$summary_link.bind('click', function() {
					$summary.show();
					$content.hide();
					$details_link.show();
					$summary_link.hide();
				});

				// check if there is something more to do for content part (i.e. recurse to a deeper level)
				if(conf.depth > 0 && (!$.isEmptyObject(conf.values['children_ids']) || conf.values['content'].length > 0)) {
					$content.trigger('unfold');
					$summary.hide();
					$details_link.hide();				
				}
				else {
					$content.hide();
					$summary_link.hide();				
				}
				// append article DOM object
				$elem.append($article);
				// mark elem as loaded
				$elem.addClass('loaded');				
				// check/update semaphore				
				if(!loading) $elem.trigger('ready');
				else loading--;
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
						// load content and listen to events
						if($.isEmptyObject(conf.values)) {
							$.when(easyObject.browse({
								class_name: 'knine\\Article',
								ids: [conf.article_id],
								fields: ['id', 'created', 'title', 'summary', 'content', 'children_ids'], 
								lang: conf.lang,
								async: true
							})).done(function(result) { 
								if(typeof result == 'object' && typeof result[conf.article_id] != 'undefined') {
									conf.values = result[conf.article_id];
									methods.load($elem, conf);
								}
							});						
						}
						else methods.load($elem, conf);
					}
					// keep track of the instancialization
					$elem.data('knine', true);					
				}
			})($(this), $.extend(true, default_conf, arg));
		});
	};
})(jQuery);
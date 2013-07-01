/* 
 * Global vars definition
 *
 */

/**
 This object defines how nutrients are presented to the user
 Nutrients may be nested into a category (or a subcategory) and/or can represent severel food items (for instance omega_3 is the sum of all n-3 polyunsaturated fatty acids)
 Thus, this is a recursive object with final values stored in arrays containing one or more DB ids (from the table 'food_des')
 Note: any key (including categories and subcategories) must be unique as they are associated to a UI_term (for translation)
*/
var nutrients_categories={Lipids:{Total_fat:[204],Monounsaturated:[645],Polyunsaturated:{Total_PU_fat:[646],Omega_3:[621,629,631,851,852],Omega_6:[672,675,685,853,855]},Sterols:{Total_sterols:[601,636,638,639,641],Cholesterol:[601],Phytosterol:[636]}},Protids:{Protein:[203],Amino_acids:[501,502,503,504,505,506,507,508,509,510,511,512,513,514,515,516,517,518,521]},Fibres:{Total_fibres:[291]},Methylxanthins:{Caffeine:[262],Theobromine:[263]},Water:{Total_water:[255]},Alcohol:{Ethanol:[221]},Energy:{Total_energy:[208]},Minerals:{Calcium:[301],Iron:[303],Magnesium:[304],Phosphorus:[305],Potassium:[306],Sodium:[307],Zinc:[309],Copper:[312],Manganese:[315],Selenium:[317],Fluoride:[313]},Glucids:{Carbohydrate:[205],Starch:[209],Saccharides:{Total_saccharides:[269],Monosaccharides:{Glucose:[211],Fructose:[212],Galactose:[287]},Disaccharides:{Sucrose:[210],Lactose:[213],	Maltose:[214]}}},Vitamins:{C:[401],B1:[404],B2:[405],B3:[406],B5:[410],B6:[415],B9:[431],B12:[418],A:[319],E:[323],D:[328],K:[430]}};

/**
 RDI stands for Recommended Dietary Intakes and may vary from one population category to another. 
 In the app, RDI is used to compare the amounts of nutrients from a meal against official recommendations.
 This object defines the RDI values for (some of) the nutrients defined in 'nutrients_categories' for various population categories.
*/
var nutrients_RDI = {
	// infants from 0 to 6 months
	baby_0_6: {},
	// infants from 7 to 12 months	
	baby_7_12: {Total_fat:30,Omega_3:0.5,Omega_6:4.6,Protein:13.5,Total_water:800,Calcium:0.27,Iron:0.011,Magnesium:0.075,Phosphorus:0.275,Potassium:0.7,Sodium:0.37,Zinc:0.003,Copper:2.2E-4,Manganese:6.0E-4,Selenium:2.0E-5,Carbohydrate:95,C:0.05,B1:3.0E-4,B2:4.0E-4,B3:0.004,B5:0.0018,B6:3.0E-4,B9:8.0E-5,B12:5.0E-7,A:5.0E-4,E:0.005,D:5.0E-6,K:2.5E-6},
	// infants from 1 to 3 years
	baby_13_36: {}
};

/**
 This object contains translations of the UI terms (ie: 'label' tags as well as the names of the nutrients and nutrients categories)
 The terms to use are defined by the value of the 'lang' parameter (defined above)
 Notes : 
	- As those objects are quite small (less than 3ko), they are not put in separate files
	- If you modify this, ensure to maintain keys unicity
*/
var UI_terms = {
		'en': {compose:'Composition',result:'Result',composition:'Description of the meal',food: 'Food',quantity: 'Quantity (g):',remove: 'Remove',display_nutr: 'show nutrients',hide_nutr: 'hide  nutrients',add_food_item:'Add food item',save_list:'Export list',load_list:'Import list',content:'Nutrients contained in the meal',Lipids:'Lipids',Total_fat:'Total fat',Monounsaturated:'Monounsaturated fatty acids',Polyunsaturated:'Polyunsaturated fatty acids',Omega_3:'Omega 3 (n-3)',Omega_6:'Omega 6 (n-6)',Total_PU_fat:'Total',Sterols:'Sterols',Total_sterols:'Total',Cholesterol:'Cholest\u00e9rol',Phytosterol:'Phytost\u00e9rol',Protids:'Protids',Protein:'Proteins',Amino_acids:'Amino acids',Fibres:'Fibres',Total_fibres:'Total',Methylxanthins:'Methylxanthins',Caffeine:'Caffeine',Theobromine:'Theobromine',Water:'Liquides',Total_water:'Water',Alcohol:'Alcohol',Ethanol:'Ethanol',Energy:'Energy',Total_energy:'Total',Minerals:'Minerals',Calcium:'Calcium (Ca)',Iron:'Iron (Fe)',Magnesium:'Magnesium (Mg)',Phosphorus:'Phosphorus (P)',Potassium:'Potassium (K)',Sodium:'Sodium (Na)',Zinc:'Zinc (Zn)',Copper:'Copper (Cu)',Manganese:'Manganese (Mn)',Selenium:'Selenium (Se)',Fluoride:'Fluoride',Glucids:'Glucids',Carbohydrate:'Carbohydrate',Saccharides:'Saccharides',Total_saccharides:'Total',Monosaccharides:'Monosaccharides',Glucose:'Glucose',Fructose:'Fructose',Galactose:'Galactose',Disaccharides:'Disaccharides',Sucrose:'Sucrose',Lactose:'Lactose',Maltose:'Maltose',Starch:'Starch',Vitamins:'Vitamins',C:'Vitamin C (ascorbic acid)',B1:'Vitamin B1 (Thiamin)',B2:'Vitamin B2 (Riboflavin)',B3:'Vitamin B3 (Niacin)',B5:'Vitamin B5 (Pantothenic acid)',B6:'Vitamin B6 (Pyridoxal phosphat)',B9:'Vitamin B9 (Folic acid)',B12:'Vitamin B12 (Cobalamin)',A:'Vitamin A (Retinol)',E:'Vitamin E (alpha-tocopherol)',D:'Vitamin D (calciferol)',K:'Vitamin K (phylloquinone)'},
		'fr': {compose: 'Composition',result: 'Résultat',composition: 'Composition du repas',food: 'Aliment',quantity: 'Quantité (g):',remove: 'Supprimer',display_nutr: 'afficher les nutriments',hide_nutr: 'masquer les nutriments',add_food_item: 'Ajouter un aliment',save_list: 'Sauvegarder la liste',load_list:'Importer une liste',content: 'Contenance en nutriments du repas',Lipids: 'Lipides',Total_fat: 'Matières grasses',Monounsaturated: 'Acides gras mono-insaturés',Polyunsaturated: 'Acides gras poly-insaturés',Omega_3: 'Omega 3 (n-3)',Omega_6: 'Omega 6 (n-6)',Total_PU_fat: 'Total',Sterols: 'Stérols',Total_sterols: 'Total',Cholesterol: 'Cholestérol',Phytosterol: 'Phytostérol',Protids: 'Protides',Protein: 'Protéines',		Amino_acids: 'Acides aminés',Fibres: 'Fibres',Total_fibres: 'Total',Methylxanthins: 'Méthylxanthines',Caffeine: 'Caféine',Theobromine: 'Théobromine',Water: 'Liquides',Total_water: 'Eau',Alcohol: 'Alcool',Ethanol: 'Ethanol',Energy: 'Énergie',Total_energy: 'Total',Minerals: 'Oligo-éléments',Calcium: 'Calcium (Ca)',Iron: 'Fer (Fe)',Magnesium: 'Magnesium (Mg)',Phosphorus: 'Phosphore (P)',Potassium: 'Potassium (K)',Sodium: 'Sodium (Na)',Zinc: 'Zinc (Zn)',Copper: 'Cuivre (Cu)',Manganese: 'Manganèse (Mn)',Selenium: 'Selenium (Se)',Fluoride: 'Fluor',Glucids: 'Glucides',Carbohydrate: 'Hydrates de carbone',Saccharides: 'Saccharides',	Total_saccharides: 'Total',Monosaccharides: 'Monosaccharides',Glucose: 'Glucose',Fructose: 'Fructose',Galactose: 'Galactose',Disaccharides: 'Disaccharides',Sucrose: 'Saccharose',Lactose: 'Lactose',Maltose: 'Maltose',Starch: 'Amidon',Vitamins: 'Vitamines',C: 'Vitamine C (acide ascorbic)',B1: 'Vitamine B1 (Thiamine)',B2: 'Vitamine B2 (Riboflavine)',B3: 'Vitamine B3 (Niacine)',B5: 'Vitamine B5 (Acide pantothenique)',B6: 'Vitamine B6 (Phosphate pyridoxal)',B9: 'Vitamine B9 (Acide folique)',B12: 'Vitamine B12 (Cobalamine)',A: 'Vitamine A (Retinol)',E: 'Vitamine E (alpha-tocopherol)',D: 'Vitamine D (calciferole)',K: 'Vitamine K (phylloquinone)'}
};


/*
 * Main closure
 * Here are defined all methods and events
 *
 */
(function($) {	
	// adjust the renderItem method in the jQuery autocomplete prototype so html tags are not escaped
	$[ "ui" ][ "autocomplete" ].prototype["_renderItem"] = function( ul, item) {
	return $( "<li></li>" ) 
	  .data( "item.autocomplete", item )
	  .append( $( "<a></a>" ).html( item.label ) )
	  .appendTo( ul );
	};
	// we'll cache some results to minimize the number of server requests
	var nutrients_cache = {};
	var result_refresh = false;
	
	var treeView = function(data, $container, quantity, highlight) {
		var is_empty = ($container.children().length > 0)?0:1;	
		var i = 0;
		if(is_empty) {
			$('<div/>').addClass('NodeItem')
// todo : translation			
			.append($('<div/>').addClass('ItemTitle').css({'font-weight': 'bold', 'margin-left': '20px'}).html('Nutriment'))
			.append($('<div/>').addClass('ItemValue').css({'font-weight': 'bold'}).html('Value'))
			.append($('<div/>').addClass('ItemRDI').css({'font-weight': 'bold'}).html('RDI'))
			.append($('<div/>').addClass('ItemRatio').css({'font-weight': 'bold'}).html('% poids'))
			.appendTo($container);		
		}

		var easy_to_read = function (value) {
			var units = 'g';
			if(value < 1) {
				if(value < 0.001) {
					value *= 1000000;
					units = 'µg';
				}
				else {
					value *= 1000;
					units = 'mg';				
				}
			}
			var delta = value - Math.floor(value);
			if(delta >= 0.01) value = (new Number(value)).toFixed(2);
			else value = (new Number(value)).toFixed(0);
			return value+' '+units;
		}

		var build = function(data) {
			var result = $('<div/>');			
			$.each(data, function(id, value) {
				var $node;
				if(typeof value == 'number') {
					if(is_empty) {
						var $node = $('<div />').addClass('NodeItem')
							.append($('<div/>').addClass('ItemTitle').html(UI_terms[config.lang][id]))
							.append($('<div/>').addClass('ItemValue').addClass(id))
							.append($('<div/>').addClass('ItemRatio'))
							.append($('<div/>').addClass('ItemRDI'));
						if(i%2 == 0) 
							$node.addClass('odd');
					}
					else {						
						var $node = $container.find('div.'+id).parent();
					}
					if(id == 'Total_energy') $node.find('div.ItemValue').html((new Number(value)).toFixed(0)+' Kcal');
					else {
						$node.find('div.ItemValue').html(easy_to_read(value));
						if(typeof quantity != 'undefined') 
							$node.find('div.ItemRatio').html((new Number(100*value/quantity)).toFixed(2)+'%');
						if(typeof nutrients_RDI[config.rdi_type][id] != 'undefined') {
							var rdi_ratio = (new Number(100*value/nutrients_RDI[config.rdi_type][id])).toFixed(2);
							$node.find('div.ItemRDI').html(rdi_ratio+'%');
							if(typeof highlight != 'undefined' && rdi_ratio < 100) 
								$node.find('div.ItemValue').css({'color':'red'});
							else $node.find('div.ItemValue').css({'color':'black'});
						}
					}
					++i;
				}
				if(typeof value == 'object') {
					if(is_empty) {	
						$node = $('<div/>').addClass(id).addClass('category').html(UI_terms[config.lang][id]).css({'margin-top': 5})
						.append($('<div/>').addClass('expandNode').addClass('expand'))
						.append($('<div/>').addClass('NodeContents').append(build(value)));

						$node.children('.expandNode').on('click', function() {
							var contents = $(this).parent().children(".NodeContents");
							contents.toggle();
							if(contents.css('display') != "none") $(this).attr("class", "expandNode collapse");
							else $(this).attr("class", "expandNode expand");
						});
					}
					else build(value);
				}
				if(is_empty) result.append($node);				
			});
			return result;
		}		
		$container.append(build(data));
	}
		
	var create_result_table = function(categories, data, quantity) {
		var result;
		if($.isArray(categories)) {
			result = 0.0;
			$.each(categories, function(id, value){	
				if(typeof data[value] != 'undefined') {
					// we convert everything in grams
					var ratio = 1.0;
					if(data[value][1] == 'µg') ratio = 0.000001;
					if(data[value][1] == 'mg') ratio = 0.001;
					result += parseFloat(data[value][0])*ratio*(quantity*0.01);
				}
			});		
		}
		// categories is an object (i.e. group)
		else {
			result = {};
			$.each(categories, function(id, value){	
				result[id] = create_result_table(categories[id], data, quantity);
			});		
		}
		return result;	
	}

	var add_result_table = function(res1, res2) {
		var result = {};
		$.each(res1, function(id, value){	
			if(typeof value == 'number') result[id] = value + res2[id];
			if(typeof value == 'object') result[id] = add_result_table(value, res2[id]);
		});
		return result;
	}

	/**
	* 
	* Application init
	*
	*/
	$(window).load(function() {	
	
// todo : extend config with URL params (lang, food_groups, rdi_type)

		$('#main').tabs().bind('tabsselect', function(event, ui) {
			// we re-compute the meal result when the user switch to the "result" tab
			if(ui.index == 1) {
				setTimeout(function (){	
					$('#result').trigger('refresh');
				}, 125);
			}
		});		

		$('#result').bind('refresh', function() {
			if(!result_refresh) return true;
			result_refresh = false;
			var result_table = {}, i = 0;
			var total_qty = 0.0;
			$('#list').find('div.aliment').each(function(id, elem) {		
				var $elem = $(elem);
				var elem_res = $elem.data('result_table');			
				var elem_qty = $elem.find('input.quantity').val();
				total_qty += (new Number(elem_qty));
				if(typeof elem_res != 'undefined') {
					if(i == 0) $.extend(true, result_table, elem_res);
					else result_table = add_result_table(result_table, elem_res);
				}
				++i;
			});
			if(i <= 0) $('#result').empty();
			treeView(result_table, $('#result'), total_qty, true);
		});

		$('#export').on('click', function() {
			var result = '';
			$('#list').find('div.aliment').each(function(id, elem) {		
				var $elem = $(elem);
				result += $elem.find('input.identifier').val()+';'+ $elem.find('input.food').val()+';'+$elem.find('input.quantity').val()+"\r\n";
			});
			$content = $('<div />').attr('title', 'export').append($('<textarea />').css({'width': '100%', 'min-height': '150px'}).html(result));
			$content.dialog({ 
				buttons: { 
							"Ok": function() { 
								$(this).dialog("close");
							}
				}		
			});
			
		});

		$('#import').on('click', function() {
			$content = $('<div />').attr('title', 'import').append($('<textarea />').css({'width': '100%', 'min-height': '150px'}));

			$content.dialog({ 
				buttons: { 
							"Ok": function() {
								var content = $(this).find('textarea').val();								
								$.each(content.split("\n"), function(i, value) {
									if(value.length > 1) {
										var res = value.split(";");
										var new_id = add_item(res[0], res[1], res[2]);
										$('#alim-'+new_id).parent().find('div.details').trigger('refresh');
									}
								});
								$(this).dialog("close");
							}
				}		
			});
		});
		
		var add_item = function(food_id, label, quantity) {
			var new_label, new_quantity;			
			var new_id = $('#list').children().length;
			if(typeof food_id == 'undefined') food_id = '';			
			if(typeof label != 'undefined') new_label = label;
			else new_label = '';
			if(typeof quantity != 'undefined') new_quantity = quantity;
			else new_quantity = 100;
			
			var $new_item = $('<div>').addClass('aliment')
				.append($('<label/>').attr('for', 'food'))
				.append($('<input>').attr('id', 'alim-'+new_id).val(new_label).addClass('food').addClass('autocomplete').css({'margin-right': '15px'})
					.autocomplete({
						delay: 500,
						minLength: 4,
						source: [],
						select: function(event, ui) {
							// we intercept the selection in the autocomplete list in order to display the label 
							// and to store the id in a hidden input
							$(event.target).val(ui.item.label.replace(/(<b>)*(<\/b>)*/gi, ''));
							$('#'+$(event.target).attr('id')+'-id').val(ui.item.value);
							$('#'+$(event.target).attr('id')+'-qte').focus();
							// on food-item change : modify the nutrients list
							// delay the refresh so dropdown list is removed
							var $details = $(this).parent().find('div.details');
							setTimeout(function (){	
								$details.trigger('refresh');
							}, 125);
							return false;
						},
						search: function(event, ui) {
							$.ajax({
								type: 'GET',
								url: '?get=dietwatch_food',
								async: false,
								dataType: 'json',
								data: {	
									lang: config.lang,
									content: event.target.value,
									groups: config.food_groups
								},					
								contentType: 'application/json; charset=utf-8',
								success: function(json_data){
										$(event.target).autocomplete( "option", "source",  json_data);
								},
								error: function(e){console.log('error: '+e);}
							});
							return true;
						}
					})
				)
				.append($('<input type="hidden">').addClass('identifier').attr('id', 'alim-'+new_id+'-id').val(food_id))
				.append($('<label/>').attr('for', 'quantity'))
				.append($('<input>').attr('id', 'alim-'+new_id+'-qte').addClass('quantity').val(new_quantity)
					.on('change', function() {
						// on quantity change : modify the nutrients list
						$(this).parent().find('div.details').trigger('refresh');
					})
				)
				.append($('<button typ="button" />').append($('<label/>').attr('for', 'remove')).on('click', function() {
						$(this).parent().remove();
					})
				)
				.append($('<div/>').addClass('show_nutr')
					.append($('<a/>').addClass('display_link').append($('<label/>').attr('for', 'display_nutr'))
						.on('click', function() {
							var $this = $(this);
							var $parent = $this.parent().parent();
							$this.toggle();
							$this.parent().find('a.hide_link').toggle();
							$details = $parent.find('div.details')
							$details.toggle();
						})
					)
					.append($('<a/>').addClass('hide_link').append($('<label/>').attr('for', 'hide_nutr')).css({'display': 'none'})
						.on('click', function() {
							var $this = $(this);
							var $parent = $this.parent().parent();
							$this.toggle();
							$this.parent().find('a.display_link').toggle();
							$parent.find('div.details').toggle();
						})
					)
				)
				.append($('<div/>').addClass('details').css({'display':'none'}).bind('refresh', function() {
					var $this = $(this);
					var $parent = $this.parent();
					var food_quantity = $parent.find('input.quantity').val();
					$parent.find('input.identifier').each(function(i, elem) {
						var food_id = $(elem).val();
						if(typeof food_id == 'undefined' || !food_id.length) return;
						var $details = $parent.find('div.details');										
						// first, we try to use the cache
						if(typeof nutrients_cache[food_id] == 'object') {
							res = create_result_table(nutrients_categories, nutrients_cache[food_id], food_quantity);
							// associate result table with the jQuery object
							$parent.data('result_table', res);
							// re-generate the food item details treeview
							treeView(res, $details, food_quantity);
							// result tab needs a refresh
							result_refresh = true;							
						}
						// if nutrients for this food item are not yet cached, then we ask the server
						else {
							$.ajax({
								type: 'GET',
								url: '?get=dietwatch_nutrients',
								async: true,
								dataType: 'json',
								data: {	
									food_id: food_id
								},					
								contentType: 'application/json; charset=utf-8',
								success: function(json_data){
									nutrients_cache[food_id] = {};
									$.extend(true, nutrients_cache[food_id], json_data);
									res = create_result_table(nutrients_categories, nutrients_cache[food_id], food_quantity);
									// associate result table with the jQuery object
									$parent.data('result_table', res);
									// re-generate the food item details treeview
									treeView(res, $details, food_quantity);
									// result tab needs a refresh
									result_refresh = true;									
								},
								error: function(e){console.log(e);}
							});							
						}
					});
				}))
			.appendTo($('#list'));
			
			$new_item.find('label').each(function(i, elem) {
				$elem = $(elem);
				$elem.html(UI_terms[config.lang][$elem.attr('for')]);				
			});

			return new_id;
		}
		
		$('#add').on('click', function() {
			var new_id = add_item();
			$('#'+'alim-'+new_id).focus();
		});

		// translate UI terms
		$('label').each(function(i, elem) {
			$elem = $(elem);
			$elem.html(UI_terms[config.lang][$elem.attr('for')]);				
		});
		
		// display body content (now that the browser has loaded UI styles and images)
		$('#loader').hide();
		$('#main').css({'visibility': 'visible'});
	});	
})(jQuery);
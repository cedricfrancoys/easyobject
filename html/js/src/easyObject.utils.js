// easyObject.utils.js

// JS equivalents to convenient PHP functions
function rtrim(value) {
	while(value.charAt(value.length-1) == ' ') value = value.slice(0,-1);
	return value;
}

function ucfirst(value) {
	if(typeof(value) == 'string') return value.charAt(0).toUpperCase() + value.substr(1);
	return '';
}

function lcfirst(value) {
	if(typeof(value) == 'string') return value.charAt(0).toLowerCase() + value.substr(1);
	return '';
}

function explode(delimiter, value) {
	var result = [];
	var start = 0, length = value.length;
	while(start < length) {
		pos = value.indexOf(delimiter, start);
		if(pos == -1) {
			result.push(value.slice(start));
			break;
		}
		result.push(value.slice(start, pos));
		start = pos+1;
	}
	return result;
}

// todo : use jQuery inArray method
function in_array(needle, haystack) {
	var result = false;
	for(i = 0, j = haystack.length; i < j && !result; ++i) {
		if(haystack[i] == needle) result = true;
	}
	return result;
}

// other utility functions
function remove_value(list, value) {
	var result = [];
	for(i in list) if(list[i] != value) result.push(list[i]);
	return result;	
}

function add_value(list, value) {
	var result = remove_value(list, value);
	result.push(value);
	return result;
}

function merge_domains(domA, domB) {
	result = [];
	for (x in domA) {
		for (y in domB) {	
			x.push(y);
			result.push([x]);		
		}
	}
	return result;
}

// todo : deprecate
function domain_to_string(domain) {
	var str_domain = '[';
	for (i in domain) {
		if(str_domain.length > 1) str_domain += ',';
		if(typeof domain[i] == 'object') str_domain += domain_to_string(domain[i]);
		else str_domain += domain[i];
	}
	str_domain += ']';					
	return str_domain;
}
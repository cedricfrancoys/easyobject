<?php
/**
*    This file is part of the easyObject project.
*    http://www.cedricfrancoys.be/easyobject
*
*    Copyright (C) 2012  Cedric Francoys
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
* file: apps/blog/display-static.php
*
* Displays a post entry according to the template design.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// we'll need to format some dates
load_class('utils/DateFormatter');

// you may use this to mak post_id parameter mandatory
// check_params(array('post_id'));

// get the value of the post_id parameter (set it to 1 if not present), and put it in the $params array
$params = get_params(array('post_id'=>1));

/*
* A small html parser that replaces 'var' tags with their associated content.
*
* @param string $template    the full html code of a page, containing var tags to be replaced by content
* @param function $decorator    the function to use in order to return html code matching a var tag
*/
function decorate_template($template, $decorator) {
    $previous_pos = 0;
    $html = '';
    // use regular expression to locate all 'var' tags in the template
    preg_match_all("/<var([^>]*)>.*<\/var>/iU", $template, $matches, PREG_OFFSET_CAPTURE);
    // replace 'var' tags with their associated content
    for($i = 0, $j = count($matches[1]); $i < $j; ++$i) {
        // 1) get tag attributes
        $attributes = array();
        $args = explode(' ', ltrim($matches[1][$i][0]));
        foreach($args as $arg) {
            if(!strlen($arg)) continue;
            list($attribute, $value) = explode('=', $arg);
            $attributes[$attribute] = str_replace('"', '', $value);
        }
        // 2) get content pointed by var tag, replace tag with content and build resulting html
        $pos = $matches[0][$i][1];
        $len = strlen($matches[0][$i][0]);
        $html .= substr($template, $previous_pos, ($pos-$previous_pos)).$decorator($attributes);
        $previous_pos = $pos + $len;
    }
    // add trailer
    $html .= substr($template, $previous_pos);
    return $html;
}
/**
* Returns html part specified by $attributes (from a 'var' tag) and associated with current post id
* (here come the calls to easyObject API)
*
* @param array $attributes
*/
$get_html = function ($attributes) {
    global $params;
    $html = '';
    switch($attributes['id']) {
        case 'content':
            if(is_int($post_values = &browse('blog\Post', array($params['post_id']), array('id', 'created', 'title', 'content')))) break;
            $title = $post_values[$params['post_id']]['title'];
            $content = $post_values[$params['post_id']]['content'];
            $dateFormatter = new DateFormatter();
            $dateFormatter->setDate($post_values[$params['post_id']]['created'], DATE_TIME_SQL);
            $date = ucfirst(strftime("%A %d %B %Y", $dateFormatter->getTimestamp()));
            $html = "
                <h2 class=\"title\">$title</h2>
                <div class=\"meta\"><p>$date</p></div>
                <div class=\"entry\">$content</div>
            ";        
            break;
        case 'recent_posts':
            $ids = search('blog\Post', array(array(array())), 'created', 'desc', 0, 5);
            $recent_values = &browse('blog\Post', $ids, array('id', 'title'));
            foreach($recent_values as $values) {
                $title = $values['title'];
                $id = $values['id'];
                $html .= "<li><a href=\"index.php?show=blog_display&post_id={$id}\">$title</a></li>";
            }
            break;            
    }
    return $html;
};

// if we got the post_id and if the template file can be found, read the template and decorate it with current post values 
if(!is_null($params['post_id']) && file_exists('packages/blog/html/template.html')) print(decorate_template(file_get_contents('packages/blog/html/template.html'), $get_html));
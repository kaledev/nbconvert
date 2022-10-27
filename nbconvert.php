<?php
   /*
   Plugin Name: NbConvert
   Description: A plugin to add ipynb files to a blog post or page using nbviewer
   Version: 1.1
   Author: Original code by Andrew Challis, edits by Mike Kale
   Author URI: http://www.andrewchallis.co.uk
   License: MIT
   */

function nbconvert_handler($atts) {
  //run function that actually does the work of the plugin
  $nb_output = nbconvert_function($atts);
  //send back text to replace shortcode in post
  return $nb_output;
}

function nbconvert_get_most_recent_git_change_for_file_from_api($url) {

  $url_list = explode('/', $url);

  $owner = $url_list[3];
  $repo = $url_list[4];
  $branch = $url_list[6];
  $path = implode("/", array_slice($url_list, 7));

  $request_url = 'https://api.github.com/repos/'.$owner.'/'.$repo.'/commits/'.$branch.'?path='. $path.'&page=1';

  $context_params = array(
    'http' => array(
      'method' => 'GET',
      'user_agent' => 'Bogus user agent',
      'timeout' => 1
    )
  );

  
  $res = file_get_contents($request_url, FALSE, stream_context_create($context_params));

  $datetime = json_decode($res, true)['commit']['committer']['date'];

  $max_datetime = strtotime($datetime);
  $max_datetime_f = date('m/d/Y H:i:s', $max_datetime);

  return $max_datetime_f;
}

function nbconvert_function($atts) {
  //process plugin
  extract(shortcode_atts(array(
        'url' => "",
     ), $atts));

  $clean_url = preg_replace('#^https?://github.com#', '', rtrim($url,'/'));
  $html = file_get_contents("https://nbviewer.jupyter.org/github" . $clean_url . "?flush_cache=true");
  $nb_output = nbconvert_getHTMLByID('notebook-container', $html);

  $last_update_date_time = nbconvert_get_most_recent_git_change_for_file_from_api($url);

  $converted_nb = '<div class="notebook">
    <div class="nbconvert-labels">
      <label class="github-link">
        <a href="'.$url.'" target="_blank">Check it out on github</a>
        <label class="github-last-update"> Last updated: '.$last_update_date_time.'</label>
      </label>
      </div>
    <div class="nbconvert">'.$nb_output.'
    </div>
  </div>';

  //send back text to calling function
  return $converted_nb;
}

function nbconvert_innerHTML(DOMNode $elm) {
  $innerHTML = '';
  $children  = $elm->childNodes;

  foreach($children as $child) {
    $innerHTML .= $elm->ownerDocument->saveHTML($child);
  }

  return $innerHTML;
}

function nbconvert_getHTMLByID($id, $html) {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    $node = $dom->getElementById($id);
    if ($node) {
        $inner_output = nbconvert_innerHTML($node);
        return $inner_output;
    }
    return FALSE;
}

function nbconvert_enqueue_style() {
	wp_enqueue_style( 'NbConvert', plugins_url( '/css/nbconvert.css', __FILE__ ));
}
add_action( 'wp_enqueue_scripts', 'nbconvert_enqueue_style' );
add_shortcode("nbconvert", "nbconvert_handler");
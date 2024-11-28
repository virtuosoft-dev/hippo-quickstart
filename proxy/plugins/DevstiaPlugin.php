<?php
/**
 * Based on the original ProxifyPlugin, this plugin is optimized just for Devstia.
 */

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;
use Proxy\Config;
use Proxy\Html;

class DevstiaPlugin extends AbstractPlugin {
	private $orig_url = 'https://devstia.com/';
	public $proxy_url = '';
	private $base_url = '';

	public function __construct(){
		$this->proxy_url = 'https://' . trim( shell_exec( 'hostname -f' ) ). ':8083/pluginable.php';
		if ( strpos( $this->proxy_url, '?' ) !== false ) {
			$this->proxy_url .= '&q=';
		}else{
			$this->proxy_url .= '?q=';
		}
	}
	
	private function css_url($matches){
		
		$url = trim($matches[1]);
		if(starts_with($url, 'data:')){
			return $matches[0];
		}
		
		return str_replace($matches[1], proxify_url($matches[1], $this->base_url), $matches[0]);
	}
	
	// this.params.logoImg&&(e="background-image: url("+this.params.logoImg+")")
	private function css_import($matches){
		return str_replace($matches[2], proxify_url($matches[2], $this->base_url), $matches[0]);
	}

	// replace src= and href=
	private function html_attr($matches){
		
		// could be empty?
		$url = trim($matches[2]);
		
		$schemes = array('data:', 'magnet:', 'about:', 'javascript:', 'mailto:', 'tel:', 'ios-app:', 'android-app:');
		if(starts_with($url, $schemes)){
			return $matches[0];
		}
		
		return str_replace($url, proxify_url($url, $this->base_url), $matches[0]);
	}

	private function form_action($matches){
		
		// sometimes form action is empty - which means a postback to the current page
		// $matches[1] holds single or double quote - whichever was used by webmaster
		
		// $matches[2] holds form submit URL - can be empty which in that case should be replaced with current URL
		if(!$matches[2]){
			$matches[2] = $this->base_url;
		}
		
		$new_action = proxify_url($matches[2], $this->base_url);
		
		// what is form method?
		$form_post = preg_match('@method=(["\'])post\1@i', $matches[0]) == 1;
		
		// take entire form string - find real url and replace it with proxified url
		$result = str_replace($matches[2], $new_action, $matches[0]);
		
		// must be converted to POST otherwise GET form would just start appending name=value pairs to your proxy url
		if(!$form_post){
		
			// may throw Duplicate Attribute warning but only first method matters
			$result = str_replace("<form", '<form method="POST"', $result);
			
			// got the idea from Glype - insert this input field to notify proxy later that this form must be converted to GET during http
			$result .= '<input type="hidden" name="convertGET" value="1">';
		}
		
		return $result;
	}
	
	public function onBeforeRequest(ProxyEvent $event){
		
		$request = $event['request'];
		
		// check if one of the POST pairs is convertGET - if so, convert this request to GET
		if($request->post->has('convertGET')){
			
			// we don't need this parameter anymore
			$request->post->remove('convertGET');
			
			// replace all GET parameters with POST data
			$request->get->replace($request->post->all());
			
			// remove POST data
			$request->post->clear();
			
			// This is now a GET request
			$request->setMethod('GET');
			
			$request->prepare();
		}
		$request->headers->set('X-Devstia-Proxy', 'true');
	}
	
	private function meta_refresh($matches){
		$url = $matches[2];
		return str_replace($url, proxify_url($url, $this->base_url), $matches[0]);
	}
	
	// <title>, <base>, <link>, <style>, <meta>, <script>, <noscript>
	private function proxify_head($str){
		
		// let's replace page titles with something custom
		if(Config::get('replace_title')){
			$str = preg_replace('/<title[^>]*>(.*?)<\/title>/is', '<title>'.Config::get('replace_title').'</title>', $str);
		}
		
		
		// base - update base_url contained in href - remove <base> tag entirely
		//$str = preg_replace_callback('/<base[^>]*href=
		
		// link - replace href with proxified
		// link rel="shortcut icon" - replace or remove
		
		// meta - only interested in http-equiv - replace url refresh
		// <meta http-equiv="refresh" content="5; url=http://example.com/">
		$str = preg_replace_callback('/content=(["\'])\d+\s*;\s*url=(.*?)\1/is', array($this, 'meta_refresh'), $str);
		
		return $str;
	}
	
	// The <body> background attribute is not supported in HTML5. Use CSS instead.
	private function proxify_css($str){
		
		// The HTML5 standard does not require quotes around attribute values.
		
		// if {1} is not there then youtube breaks for some reason
		$str = preg_replace_callback('@[^a-z]{1}url\s*\((?:\'|"|)(.*?)(?:\'|"|)\)@im', array($this, 'css_url'), $str);
		
		// https://developer.mozilla.org/en-US/docs/Web/CSS/@import
		// TODO: what about @import directives that are outside <style>?
		$str = preg_replace_callback('/@import (\'|")(.*?)\1/i', array($this, 'css_import'), $str);
		
		return $str;
	}

	public function onCompleted(ProxyEvent $event) {

		// to be used when proxifying all the relative links
		$this->base_url = $event['request']->getUri();
		
		$url_host = parse_url($this->base_url, PHP_URL_HOST);
		
		$response = $event['response'];
		$content_type = $response->headers->get('content-type');
		$str = $response->getContent();
		
		// remove JS from urls
		$js_remove = (array)Config::get('js_remove');
		foreach($js_remove as $pattern){
			if(strpos($url_host, $pattern) !== false){
				$str = Html::remove_scripts($str);
			}
		}
		
		// add html.no-js
		
		// let's remove all frames?? does not protect against the frames created dynamically via javascript
		$str = preg_replace('@<iframe[^>]*>[^<]*<\\/iframe>@is', '', $str);
		
		$str = $this->proxify_head($str);
		$str = $this->proxify_css($str);
		
		// If content type starts with text/html 
		if (starts_with($content_type, 'text/html') && $str != '') {
		
			// Use DOMDocument to manipulate the HTML content
			$dom = new \DOMDocument();
			libxml_use_internal_errors(true); // Suppress warnings for invalid HTML
			$dom->loadHTML($str, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		
			// Get all anchor tags
			$anchors = $dom->getElementsByTagName('a');
		
			// Loop through each anchor tag
			foreach ($anchors as $anchor) {
				$href = $anchor->getAttribute('href');
				$target = $anchor->getAttribute('target');
		
				// Check if the href does not contain '.css' or '.js'
				if ( strpos($href, '.css') === false &&	
					 strpos($href, '.js') === false &&
					 $target != '_blank' ) {

					// Check that the href contains the original domain
					if ( strpos($href, rtrim( $this->orig_url, "/") ) !== false ) {
						
						// Modify the href to use the proxy
						$new_href = $this->proxy_url . urlencode($href);
					}else{
						// Force the href to open in a new tab
						$anchor->setAttribute('target', '_blank');
						$new_href = $href;
					}

					// Restore # in the href
					$new_href = str_replace('%23', '#', $new_href);
					$anchor->setAttribute('href', $new_href);
				}
			}

			// Force script src to use proxy if it contains one of the following
			$force_js_proxy = [
				'wp-includes/js/dist/script-modules/block-library/navigation/view.min.js',
				'wp-includes/js/dist/script-modules/interactivity/index.min.js',
				'wp-includes/blocks/navigation/view.min.js',
				'wp-includes/js/jquery/jquery.min.js'
			];

			// Get all script tags
			$scripts = $dom->getElementsByTagName('script');

			// Loop through each script tag
			foreach ($scripts as $script) {
				$src = $script->getAttribute('src');

				// Check if the src contains any of the force_js_proxy strings
				foreach ($force_js_proxy as $force_js) {
					if (strpos($src, $force_js) !== false) {

						// Modify the src to use the proxy
						$new_src = $this->proxy_url . urlencode($src);
						$script->setAttribute('src', $new_src);
					}
				}
			}

			// Get all link tags
			$links = $dom->getElementsByTagName('link');

			// Loop through each link tag
			foreach ($links as $link) {
				$href = $script->getAttribute('href');

				// Check if the href contains any of the force_js_proxy strings
				foreach ($force_js_proxy as $force_js) {
					if (strpos($href, $force_js) !== false) {

						// Modify the href to use the proxy
						$new_href = $this->proxy_url . urlencode($href);
						$script->setAttribute('href', $new_href);
					}
				}
			}

			libxml_clear_errors();
			$str = $dom->saveHTML();

			// Go through each line in str
			$lines = explode("\n", $str);
			$new_str = '';
			foreach ($lines as $line) {

				// If line contains '/blueprints/' and '/screenshot' or admin-ajax.php
				if ( strpos( $line, 'screenshot' ) !== false && strpos( $line, '.txt' ) !== false ||
					strpos( $line, 'admin-ajax.php' ) !== false ) {

					// Replace original domain with proxy domain
					$line = str_replace( trim( json_encode( $this->orig_url ), '"' ), trim( json_encode( $this->proxy_url ), '"' ) . trim( json_encode( $this->orig_url ), '"' ), $line);
				}
				$new_str .= $line . "\n";
			}
			$str = $new_str;
		}

		// If content type starts with application/json
		if (starts_with($content_type, 'application/json') && $str != '') {
			
			// Replace original domain with proxy domain and original url query
			$str = str_replace( 
				trim( json_encode( $this->orig_url ), '"' ), 
				trim( json_encode( $this->proxy_url ), '"' ) . trim( json_encode( $this->orig_url ), '"' ), $str);
		}
		
		// form
		$str = preg_replace_callback('@<form[^>]*action=(["\'])(.*?)\1[^>]*>@i', array($this, 'form_action'), $str);
		
		// Tell client we're using the devstia proxy
		$str = str_replace( '<head>', '<head><meta name="devstia-proxy" content="true">', $str );
		$response->setContent($str);
	}
}

?>

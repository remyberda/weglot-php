<?php
namespace Weglot;

define('WEGLOT_VERSION', '1.0.0');

class WG
{
	protected $api_key;
	protected $original_l;
	protected $destination_l;
	protected $o_request_uri;
	protected $home_url;
	protected $current_l;
	protected $buttonOptions;
	protected $exclude_url;
	protected $exclude_blocks;

	const API_URL = 'https://weglot.com/api/v2/translate';
	
	private function __construct($options) {
		

		$this->api_key 			= $options['api_key'];
		$this->original_l 		= $options['original_l'];
		$this->destination_l 	= $options['destination_l'];
		$this->o_request_uri 	= $_SERVER['REQUEST_URI'];
		$this->home_url 		= (isset($options['home_url']) && $options['home_url']!="") ? '/'.trim(rtrim($options['home_url'],'/'),'/'): null;
		$this->buttonOptions 	= isset($options['buttonOptions']) ? $options['buttonOptions']: null;
		$this->exclude_url 		= isset($options['exclude_url']) ? $options['exclude_url']: null;
		$this->exclude_blocks 	= isset($options['exclude_blocks']) ? $options['exclude_blocks']: null;
		
        if ($this->api_key == null || mb_strlen($this->api_key) == 0) {
            throw new WeglotException('Weglot requires an api_key.');
        }
		
		$this->current_l = $this->getCurrentLang();
		if($this->current_l!=$this->original_l) {
			$_SERVER['REQUEST_URI'] = str_replace('/'.$this->current_l,'',$this->o_request_uri);	
		}
		ob_start(array(&$this,'treatPage'));
	}
	
	public static function Instance($options = "")
	{
		static $inst = null;
		if($inst == null)
		{
			$inst = new WG($options);
		}
		return $inst;
	}
	
	public function treatPage($final) {

		if($this->isEligibleURL($_SERVER['REQUEST_URI']) && WGUtils::is_HTML($final)) {
			if($this->current_l!=$this->original_l) {
				try {
					$l =  $this->current_l;
					$final = $this->translatePageTo($final,$l);
				}
				catch(\Weglot\WeglotException $e) {
					$final .= "<!--Weglot error : ".$e->getMessage()."-->";
				}
				catch(\Exception $e) {
					$final .= "<!--Weglot error : ".$e->getMessage()."-->";
				}	
			}
			
			/* Adds HrefLang */
			$dest = explode(",",$this->destination_l);
			
			$full_url = ($this->current_l!=$this->original_l) ?  str_replace('/'.$this->current_l.'/','/',$this->full_url($_SERVER)):$this->full_url($_SERVER);
			$hrefs = '<link rel="alternate" hreflang="'.$this->original_l.'" href="'.$full_url.'" />'."\n";
			foreach($dest as $d) {
				$hrefs.= '<link rel="alternate" hreflang="'.$d.'" href="'.$this->replaceUrl($full_url,$d).'" />'."\n";
			}
			
			

			$css = '<link rel="stylesheet" id="font-awesome-css" href="//maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css?ver=4.5.2" type="text/css" media="all"><link rel="stylesheet" href="https://d3m55resnjf8ja.cloudfront.net/cd_wg_css.css" type="text/css" media="all">';
			$js = '<script type="text/javascript" src="https://d3m55resnjf8ja.cloudfront.net/cd_wg_js.js"></script>';
			$final = str_replace('</head>',$hrefs.$css.$js.'</head>',$final);
			
			
			//Place the button if we see short code
			if (strpos($final,'<div id="weglot_here"></div>') !== false) {
				
				$button = $this->returnWidgetCode();
				$final = str_replace('<div id="weglot_here"></div>',$button,$final);
			}
			
			//Place the button if not in the page			
			if (strpos($final,'class="wgcurrent') === false) {
				
				$button = $this->returnWidgetCode(true);
				$button = WGUtils::str_lreplace('<aside id="weglot_switcher" wg-notranslate class="','<aside id="weglot_switcher" wg-notranslate class="wg-default ',$button);
				$final = (strpos($final, '</body>') !== false) ? WGUtils::str_lreplace('</body>',$button.' </body>',$final):WGUtils::str_lreplace('</footer>',$button.' </footer>',$final);
			}
			$length = strlen($final);
			header('Content-Length: '.$length);
			return $final;
		}
		else {
			return $final;
		}
	}
	
	
	function translatePageTo($final,$l) { 
		
		$translator = $this->api_key ? new \Weglot\WGClient($this->api_key):null;

		$translatedPage = $translator->translateDomFromTo($final,$this->original_l,$l,$this->exclude_blocks); 
		
	
		preg_match_all('/<a([^\>]+?)?href=(\"|\')([^\s\>]+?)(\"|\')/',$translatedPage,$out, PREG_PATTERN_ORDER);	
		for($i=0;$i<count($out[0]);$i++) {
			$sometags = $out[1][$i];
			$current_url = $out[3][$i];
			$parsed_url = parse_url($current_url);
			
			if((($current_url[0] == 'h' && $parsed_url['host']==$_SERVER['HTTP_HOST']) || ($current_url[0] =='/' && $current_url[1] !='/')) 
				 && !WGUtils::endsWith($current_url,'.jpg') && !WGUtils::endsWith($current_url,'.jpeg') && !WGUtils::endsWith($current_url,'.png') && !WGUtils::endsWith($current_url,'.pdf')
				&& $this->isEligibleURL($current_url) && strpos($sometags,'wg-notranslate') === false) 
			{
				$translatedPage = preg_replace('/<a'.preg_quote($sometags,'/').'href='.preg_quote($out[2][$i].$current_url.$out[4][$i],'/').'/','<a'.$sometags.'href='.$out[2][$i].$this->replaceUrl($current_url,$l).$out[4][$i],$translatedPage);
			}
		}
		
	
		preg_match_all('/<form (.*?)?action=(\"|\')([^\s\>]+?)(\"|\')/',$translatedPage,$out, PREG_PATTERN_ORDER);	
		for($i=0;$i<count($out[0]);$i++) {
			$sometags = $out[1][$i];
			$current_url = $out[3][$i];
			$parsed_url = parse_url($current_url);
			if((($current_url[0] == 'h' && $parsed_url['host']==$_SERVER['HTTP_HOST']) || ($current_url[0] =='/' && $current_url[1] !='/')) 
				 && !WGUtils::endsWith($current_url,'.jpg') && !WGUtils::endsWith($current_url,'.jpeg') && !WGUtils::endsWith($current_url,'.png') && !WGUtils::endsWith($current_url,'.pdf')
				&& $this->isEligibleURL($current_url) && strpos($sometags,'wg-notranslate') === false) 
			{
				$translatedPage = preg_replace('/<form '.preg_quote($sometags,'/').'action='.preg_quote($out[2][$i].$current_url.$out[4][$i],'/').'/','<form '.$sometags.'action='.$out[2][$i].$this->replaceUrl($current_url,$l).$out[4][$i],$translatedPage);
			}
		}
		preg_match_all('/<option (.*?)?(\"|\')((https?:\/\/|\/)[^\s\>]*?)(\"|\')(.*?)?>/',$translatedPage,$out, PREG_PATTERN_ORDER);
		for($i=0;$i<count($out[0]);$i++) {
			$sometags = $out[1][$i];
			$current_url = $out[3][$i];
			$parsed_url = parse_url($current_url);
			if((($current_url[0] == 'h' && $parsed_url['host']==$_SERVER['HTTP_HOST']) || $current_url[0] =='/') 
				  && !WGUtils::endsWith($current_url,'.jpg') && !WGUtils::endsWith($current_url,'.jpeg') && !WGUtils::endsWith($current_url,'.png') && !WGUtils::endsWith($current_url,'.pdf')
				&& $this->isEligibleURL($current_url) && strpos($sometags,'wg-notranslate') === false) 
			{
				$translatedPage = preg_replace('/<option '.preg_quote($sometags,'/').preg_quote($out[2][$i].$current_url.$out[5][$i],'/').'(.*?)?>/','<option '.$sometags.$out[2][$i].$this->replaceUrl($current_url,$l).$out[5][$i].'$2>',$translatedPage);
			}
		}
		preg_match_all('/<link rel="canonical"(.*?)?href=(\"|\')([^\s\>]+?)(\"|\')/',$translatedPage,$out, PREG_PATTERN_ORDER);
		for($i=0;$i<count($out[0]);$i++) {
			$sometags = $out[1][$i];
			$current_url = $out[3][$i];
			$parsed_url = parse_url($current_url);
			if((($current_url[0] == 'h' && $parsed_url['host']==$_SERVER['HTTP_HOST']) || $current_url[0] =='/') 
				  && !WGUtils::endsWith($current_url,'.jpg') && !WGUtils::endsWith($current_url,'.jpeg') && !WGUtils::endsWith($current_url,'.png') && !WGUtils::endsWith($current_url,'.pdf')
				&& $this->isEligibleURL($current_url) && strpos($sometags,'wg-notranslate') === false) 
			{
				$translatedPage = preg_replace('/<link rel="canonical"'.preg_quote($sometags,'/').'href='.preg_quote($out[2][$i].$current_url.$out[4][$i],'/').'/','<link rel="canonical"'.$sometags.'href='.$out[2][$i].$this->replaceUrl($current_url,$l).$out[4][$i],$translatedPage);
			}
		}		
		preg_match_all('/<meta property="og:url"(.*?)?content=(\"|\')([^\s\>]+?)(\"|\')/',$translatedPage,$out, PREG_PATTERN_ORDER);
		for($i=0;$i<count($out[0]);$i++) {
			$sometags = $out[1][$i];
			$current_url = $out[3][$i];
			$parsed_url = parse_url($current_url);
			if((($current_url[0] == 'h' && $parsed_url['host']==$_SERVER['HTTP_HOST']) || $current_url[0] =='/') 
				 && !WGUtils::endsWith($current_url,'.jpg') && !WGUtils::endsWith($current_url,'.jpeg') && !WGUtils::endsWith($current_url,'.png') && !WGUtils::endsWith($current_url,'.pdf')
				&& $this->isEligibleURL($current_url) && strpos($sometags,'wg-notranslate') === false) 
			{
				$translatedPage = preg_replace('/<meta property="og:url"'.preg_quote($sometags,'/').'content='.preg_quote($out[2][$i].$current_url.$out[4][$i],'/').'/','<meta property="og:url"'.$sometags.'content='.$out[2][$i].$this->replaceUrl($current_url,$l).$out[4][$i],$translatedPage);
			}
		}
		
		$translatedPage = preg_replace('/<html (.*?)?lang=(\"|\')(\S*)(\"|\')/','<html $1lang=$2'.$l.'$4',$translatedPage);
		$translatedPage = preg_replace('/property="og:locale" content=(\"|\')(\S*)(\"|\')/','property="og:locale" content=$1'.$l.'$3',$translatedPage);
		return $translatedPage;
	}
	
	public function url_origin($s, $use_forwarded_host=false) {
		$ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
		$sp = strtolower($s['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $s['SERVER_PORT'];
		$port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
		$host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
		$host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
		return $protocol . '://' . $host;
	}
	public function full_url($s, $use_forwarded_host=false) {
	   return $this->url_origin($s, $use_forwarded_host) . $this->o_request_uri;
	}		
	public function URLToRelative($url) {
		
		$home_dir = $this->home_url;
		if($home_dir)
			$url = str_replace($home_dir ,'',$url);
		
		if ((substr($url, 0, 7) == 'http://') || (substr($url, 0, 8) == 'https://')) {
			// the current link is an "absolute" URL - parse it to get just the path
			$parsed = parse_url($url);
			$path     = isset($parsed['path']) ? $parsed['path'] : ''; 
			$query    = isset($parsed['query']) ? '?' . $parsed['query'] : ''; 
			$fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : ''; 
			return $path.$query.$fragment;
		}
		else {
			return ($url=="") ? "/":$url;
		}
	}
	public function replaceUrl($url,$l) {
		
		if($l=='')
			return $url;
			
		
		
		$home_dir = $this->home_url;
		if($home_dir) {
			return str_replace($home_dir,$home_dir."/$l",$url);
		}
		else {
			$parsed_url = parse_url($url);
			$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
			$host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
			$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
			$user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
			$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
			$pass     = ($user || $pass) ? "$pass@" : ''; 
			$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '/'; 
			$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
			$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 

			return (strlen($path)>2 && substr($path,0,4)=="/$l/") ? "$scheme$user$pass$host$port$path$query$fragment":"$scheme$user$pass$host$port/$l$path$query$fragment";
		}
	}

	public function isEligibleURL($url) {
		$url = $this->URLToRelative($url);
		
		$exclusions = preg_replace('#\s+#',',',$this->exclude_url);
		$exclusions = $exclusions=="" ? "/amp":$exclusions.",/amp";
		$regex = explode(",",$exclusions);  

		if($exclusions!="") {
			foreach($regex as $ex) { 
				if(preg_match('/'.str_replace('/', '\/',$ex).'/',$url)==1)
					return false;
			}
			return true;
		}
		else
			return true;
	}
	
	public function getCurrentLang() {
		
		$home_trimed = $this->home_url ? trim(rtrim($this->home_url,'/'),'/')."\/":"";
		if(preg_match('/^\/'.$home_trimed.'([a-z]{2})(\/(.*))?/',$this->o_request_uri,$matches)) { 
			$languages = explode(",",$this->destination_l);
			if(in_array($matches[1],$languages))
				return $matches[1];
		}
		return $this->original_l;
	}

	public function returnWidgetCode($forceNoMenu = false) { 
		$original = $this->original_l;
		

		$url = 	$_SERVER['REQUEST_URI'];

		$buttonOptions = $this->buttonOptions;
		$full = isset($buttonOptions['fullname']) ? $buttonOptions['fullname']:true;
		$withname = isset($buttonOptions['with_name']) ? $buttonOptions['with_name']:true;
		$is_dropdown = isset($buttonOptions['is_dropdown']) ? $buttonOptions['is_dropdown']:true;
		$flag_class = (isset($buttonOptions['with_flags'])) ? ($buttonOptions['with_flags'] ? 'wg-flags ':''): 'wg-flags ';    
		$type_flags = isset($buttonOptions['type_flags']) ? $buttonOptions['type_flags']:0;
		
		$flag_class .= $type_flags==0 ? '':'flag-'.$type_flags.' ';
		
		$current = $this->current_l;
		$list = $is_dropdown ? "<ul>":"";
		$destEx = explode(",",$this->destination_l);
		array_unshift($destEx,$original);
		foreach($destEx as $d) { 
			if($d!=$current) {
				$link = (($d!=$original) ? $this->replaceUrl($url,$d):$this->replaceUrl($url,''));
				$list .= '<li class="wg-li '.$flag_class.$d.'"><a wg-notranslate href="'.$link.'">'.($withname ? ($full? WGUtils::getLangNameFromCode($d,false):strtoupper($d)):"").'</a></li>';
			}
		}
		$list .= $is_dropdown ? "</ul>":"";	
		$tag =  $is_dropdown ? "div":"li";

		$moreclass = $is_dropdown ? 'wg-drop ':'wg-list ';
		
		$aside1 = '<aside id="weglot_switcher" wg-notranslate class="'.$moreclass.'country-selector closed" onclick="openClose(this);" >';
		$aside2 = '</aside>';
		
		$button = '<!--Weglot '.WEGLOT_VERSION.'-->'.$aside1.'<'.$tag.' wg-notranslate class="wgcurrent wg-li '.$flag_class.$current.'"><a href="javascript:void(0);">'.($withname ? ($full? WGUtils::getLangNameFromCode($current,false):strtoupper($current)):"").'</a></'.$tag.'>'.$list.$aside2;
		return $button;
	}
	
	
	
}

?>

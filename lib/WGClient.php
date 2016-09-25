<?php
namespace Weglot;

require(dirname(__FILE__) . '/simple_html_dom.php');

class WGClient 
{
	protected $api_key;

	const API_BASE = 'https://weglot.com/api/';
	//const API_BASE = 'http://weglot/api/';
	
	function __construct($key) {
		$this->api_key = $key;
		
        if ($this->api_key == null || mb_strlen($this->api_key) == 0) {
            return null;
        }
	}
	
	public function hasAncestorAttribute($node,$attribute) {
		
		$currentNode = $node;

		while($currentNode->parent() && $currentNode->parent()->tag!="html") {

			if(isset($currentNode->parent()->$attribute))
				return true;
			else
				$currentNode = $currentNode->parent();
		}
		return false;
	}
	
	public function translateDomFromTo($dom,$l_from,$l_to,$exceptions_string) { 

		$html = str_get_html($dom, true, true, WG_DEFAULT_TARGET_CHARSET, false, WG_DEFAULT_BR_TEXT, WG_DEFAULT_SPAN_TEXT);
		
		
		$exceptions = explode(",",$exceptions_string);
		foreach ($exceptions as $exception) {
			foreach ($html->find($exception) as $k => $row) 
			{ 
				$attribute = "wg-notranslate";
				$row->$attribute="";
			}
		}
		
		
		
		$words = array();
		$nodes = array();

		foreach ($html->find('text') as $k => $row) 
		{
			if($this->full_trim($row->outertext)!="" && $row->parent()->tag!="script" && $row->parent()->tag!="style" && !is_numeric($this->full_trim($row->outertext)) && !preg_match('/^\d+%$/',$this->full_trim($row->outertext)) 
				&& !$this->hasAncestorAttribute($row,'wg-notranslate'))
			{ 
				if(strpos($row->outertext,'[vc_') !== false) {
					
				}
				else {
					array_push($words,array("t"=>"1","w"=>$row->outertext)); 
					array_push($nodes,array('node'=>$row,'type'=>'text'));
				}
			}
		}
		
		foreach ($html->find('input[type=\'submit\'],input[type=\'button\']') as $k => $row) 
		{
			if($this->full_trim($row->value)!="" && !is_numeric($this->full_trim($row->value)) && !preg_match('/^\d+%$/',$this->full_trim($row->value)) 
				&& !$this->hasAncestorAttribute($row,'wg-notranslate'))
			{
				array_push($words,array("t"=>"2","w"=>html_entity_decode($row->value)));
				array_push($nodes,array('node'=>$row,'type'=>'submit'));
			}
		}
		foreach ($html->find('input[type=\'text\'],input[type=\'password\'],input[type=\'search\'],input[type=\'email\'],input:not([type]),textarea') as $k => $row) 
		{
			if($this->full_trim($row->placeholder)!=""
				&& !is_numeric($this->full_trim($row->placeholder)) && !preg_match('/^\d+%$/',$this->full_trim($row->placeholder)) 
				&& !$this->hasAncestorAttribute($row,'wg-notranslate'))
			{
				array_push($words,array("t"=>"3","w"=>html_entity_decode($row->placeholder)));
				array_push($nodes,array('node'=>$row,'type'=>'placeholder'));
			}
		}
		foreach ($html->find('meta[name="description"],meta[property="og:title"],meta[property="og:description"],meta[property="og:site_name"],meta[name="twitter:title"],meta[name="twitter:description"]') as $k => $row) 
		{
			if($this->full_trim($row->content)!="" && !is_numeric($this->full_trim($row->content)) && !preg_match('/^\d+%$/',$this->full_trim($row->content)) 
				&& !$this->hasAncestorAttribute($row,'wg-notranslate'))
			{
				array_push($words,array("t"=>"4","w"=>$row->content));
				array_push($nodes,array('node'=>$row,'type'=>'meta_desc'));
			}
		}
		foreach ($html->find('iframe') as $k => $row) 
		{
			if($this->full_trim($row->src)!="" && strpos($this->full_trim($row->src),'.youtube.') !== false	&& !$this->hasAncestorAttribute($row,'wg-notranslate'))
			{
				array_push($words,array("t"=>"5","w"=>$row->src));
				array_push($nodes,array('node'=>$row,'type'=>'iframe_src'));
			}
		}
		foreach ($html->find('img') as $k => $row) 
		{
			if($this->full_trim($row->src)!="" && !$this->hasAncestorAttribute($row,'wg-notranslate'))
			{
				array_push($words,array("t"=>"6","w"=>$row->src));
				array_push($nodes,array('node'=>$row,'type'=>'image_src'));
			}
			if($this->full_trim($row->alt)!="" && !$this->hasAncestorAttribute($row,'wg-notranslate'))
			{
				array_push($words,array("t"=>"7","w"=>$row->alt));
				array_push($nodes,array('node'=>$row,'type'=>'image_alt'));
			}
		}
		foreach ($html->find('a') as $k => $row) 
		{
			if($this->full_trim($row->href)!="" && substr($this->full_trim($row->href),-4)==".pdf" &&  !$this->hasAncestorAttribute($row,'wg-notranslate'))
			{
				array_push($words,array("t"=>"8","w"=>$row->href));
				array_push($nodes,array('node'=>$row,'type'=>'a_pdf'));
			}
		}
		
		
		$title = "";
		foreach ($html->find('title') as $k => $row) {
			$title = $row->innertext;
		}
		
		$absolute_url = $this->full_url($_SERVER);
		if(strpos($absolute_url,'admin-ajax.php') !== false) {
				$absolute_url = $_SERVER['HTTP_REFERER'];
				$title = "Ajax data";
		}
		
		$bot = $this->bot_detected();	
		$parameters = array("l_from"=>$l_from,"l_to"=>$l_to,"title"=>$title,"request_url"=>$absolute_url,"bot"=>$bot,"words"=>$words);
		$results = $this->doRequest(self::API_BASE."v2/translate?api_key=".$this->api_key,$parameters);
		$json = json_decode($results,true); 
		if(json_last_error() == JSON_ERROR_NONE) 
		{
			if(isset($json['succeeded']) && ($json['succeeded']==0 || $json['succeeded']==1)) {
				if($json['succeeded']==1) {
					if(isset($json['answer'])) {
						$answer = $json['answer'];
						if(isset($answer['to_words'])) {
							$translated_words = $answer['to_words'];
							if(count($nodes)==count($translated_words)) {
								for($i=0;$i<count($nodes);$i++) {
									if($nodes[$i]['type']=='text') {
										$nodes[$i]['node']->outertext =  $translated_words[$i];
									}
									if($nodes[$i]['type']=='submit') {
										$nodes[$i]['node']->setAttribute('value',$translated_words[$i]);
									}
									if($nodes[$i]['type']=='placeholder') {
										$nodes[$i]['node']->setAttribute('placeholder',$translated_words[$i]);
									}
									if($nodes[$i]['type']=='meta_desc') {
										$nodes[$i]['node']->content =  $translated_words[$i];
									}
									if($nodes[$i]['type']=='iframe_src') {
										$nodes[$i]['node']->src =  $translated_words[$i];
									}
									if($nodes[$i]['type']=='image_alt') {
										$nodes[$i]['node']->alt =  $translated_words[$i];
									}
									if($nodes[$i]['type']=='image_src') {
										$nodes[$i]['node']->src =  $translated_words[$i];
										if($nodes[$i]['node']->hasAttribute("srcset") && $nodes[$i]['node']->srcset !=  "" && $translated_words[$i]!=$words[$i]['w']) {
											$nodes[$i]['node']->srcset = "";
										}
									}
									if($nodes[$i]['type']=='a_pdf') {
										$nodes[$i]['node']->href =  $translated_words[$i];
									}
								}
								return $html->save();
							}
							else
								throw new WeglotException('Unknown error with Weglot Api (0006)');
						}
						else
							throw new WeglotException('Unknown error with Weglot Api (0005)');
					}
					else
						throw new WeglotException('Unknown error with Weglot Api (0004)');
				}	 
				else {
					$error = isset($json['error']) ? $json['error']:'Unknown error with Weglot Api (0003)';
					throw new WeglotException($error);
				}	
			}
			else
				throw new WeglotException('Unknown error with Weglot Api (0002) : '.$json);
		}
		else
			throw new WeglotException('Unknown error with Weglot Api (0001) : '.json_last_error());
	}
	
	public function getUserInfo() {
		$results = $this->doRequest(self::API_BASE."user-info?api_key=".$this->api_key,null);
		$json = json_decode($results,true); 
		if(json_last_error() == JSON_ERROR_NONE) 
		{
			if(isset($json['succeeded']) && ($json['succeeded']==0 || $json['succeeded']==1)) {
				if($json['succeeded']==1) {
					if(isset($json['answer'])) {
						$answer = $json['answer'];
						return $answer;
					}
					else
						throw new WeglotException('Unknown error with Weglot Api (0004)');
				}
				else {
					$error = isset($json['error']) ? $json['error']:'Unknown error with Weglot Api (0003)';
					throw new WeglotException($error);
				}	
			}
			else
				throw new WeglotException('Unknown error with Weglot Api (0002) : '.$json);
		}
		else
			throw new WeglotException('Unknown error with Weglot Api (0001) : '.json_last_error());
	}
	
	public function doRequest($url,$parameters) {

		$payload = json_encode($parameters);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  

		$server_output = curl_exec ($ch);
		curl_close ($ch);

		return $server_output;
	}
	

	
	function bot_detected() 
	{
		$ua = $_SERVER['HTTP_USER_AGENT'];
		if (isset($ua))
		{
			if (preg_match('/bot|favicon|crawl|facebook|slurp|spider/i', $ua)) 
			{
				if (strpos($ua, 'Google') !== false) {
					return 2;
				}
				elseif (strpos($ua, 'bing') !== false) {
					return 3;
				}
				elseif (strpos($ua, 'yahoo') !== false) {
					return 4;
				}
				elseif (strpos($ua, 'Baidu') !== false) {
					return 5;
				}
				elseif (strpos($ua, 'Yandex') !== false) {
					return 6;
				}
				else {
					return 1;
				}
					
			}
			else 
			{
				return 0;
			}
		}
		else
		{
			return 1;
		}
	}
	
	function url_origin($s, $use_forwarded_host=false)
	{
		$ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
		$sp = strtolower($s['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $s['SERVER_PORT'];
		$port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
		$host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
		$host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
		return $protocol . '://' . $host;
	}
	function full_url($s, $use_forwarded_host=false)
	{
		return $this->url_origin($s, $use_forwarded_host) . $s['REQUEST_URI'];
	}
	function full_trim($word) {
		return trim($word," \t\n\r\0\x0B\xA0 ");
	}
}

?>

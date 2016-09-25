<?php 
namespace Weglot;
class WGUtils {

	public static function getLangNameFromCode($code,$english) {
		switch ($code) {
			case "sq":
				return $english ? "Albanian":"Shqip";	
			case "en":
				return $english ? "English":"English";
			case "ar":
				return $english ? "Arabic":"‏العربية‏";
			case "hy":
				return $english ? "Armenian":"հայերեն";			
			case "az":
				return $english ? "Azerbaijani":"Azərbaycan dili";	
			case "af":
				return $english ? "Afrikaans":"Afrikaans";
			case "eu":
				return $english ? "Basque":"Euskara";	
			case "be":
				return $english ? "Belarusian":"Беларуская";	
			case "bg":
				return $english ? "Bulgarian":"български";	
			case "bs":
				return $english ? "Bosnian":"Bosanski";	
			case "cy":
				return $english ? "Welsh":"Cymraeg";				
			case "vi":
				return $english ? "Vietnamese":"Tiếng Việt";				
			case "hu":
				return $english ? "Hungarian":"Magyar";				
			case "ht":
				return $english ? "Haitian":"Kreyòl ayisyen";				
			case "gl":
				return $english ? "Galician":"Galego";
			case "nl":
				return $english ? "Dutch":"Nederlands";		
			case "el":
				return $english ? "Greek":"Ελληνικά";			
			case "ka":
				return $english ? "Georgian":"ქართული";
			case "da":
				return $english ? "Danish":"Dansk";
			case "he":
				return $english ? "Hebrew":"עברית";
			case "id":
				return $english ? "Indonesian":"Bahasa Indonesia";				
			case "ga":
				return $english ? "Irish":"Gaeilge";	
			case "it":
				return $english ? "Italian":"Italiano";	
			case "is":
				return $english ? "Icelandic":"Íslenska";	
			case "es":
				return $english ? "Spanish":"Español";			
			case "kk":
				return $english ? "Kazakh":"Қазақша";				
			case "ca":
				return $english ? "Catalan":"Català";	
			case "ky":
				return $english ? "Kyrgyz":"кыргызча";	
			case "zh":
				return $english ? "Simplified Chinese":"中文 (简体)";
			case "tw":
				return $english ? "Traditional Chinese":"中文 (繁體)";
			case "ko":
				return $english ? "Korean":"한국어";
			case "lv":
				return $english ? "Latvian":"Latviešu";	
			case "lt":
				return $english ? "Lithuanian":"Lietuvių";	
			case "mg":
				return $english ? "Malagasy":"Malagasy";	
			case "ms":
				return $english ? "Malay":"Bahasa Melayu";
			case "mt":
				return $english ? "Maltese":"Malti";	
			case "mk":
				return $english ? "Macedonian":"Македонски";	
			case "mn":
				return $english ? "Mongolian":"Монгол";	
			case "de":
				return $english ? "German":"Deutsch";
			case "no":
				return $english ? "Norwegian":"Norsk";
			case "fa":
				return $english ? "Persian":"فارسی";	
			case "pl":
				return $english ? "Polish":"Polski";
			case "pt":
				return $english ? "Portuguese":"Português";		
			case "ro":
				return $english ? "Romanian":"Română";	
			case "ru":
				return $english ? "Russian":"Русский";		
			case "sr":
				return $english ? "Serbian":"Српски";	
			case "sk":
				return $english ? "Slovak":"Slovenčina";	
			case "sl":
				return $english ? "Slovenian":"Slovenščina";	
			case "sw":
				return $english ? "Swahili":"Kiswahili";	
			case "tg":
				return $english ? "Tajik":"Тоҷикӣ";	
			case "th":
				return $english ? "Thai":"ภาษาไทย";	
			case "tl":
				return $english ? "Tagalog":"Tagalog";	
			case "tt":
				return $english ? "Tatar":"Tatar";
			case "tr":
				return $english ? "Turkish":"Türkçe";
			case "uz":
				return $english ? "Uzbek":"O'zbek";	
			case "uk":
				return $english ? "Ukrainian":"Українська";	
			case "fi":
				return $english ? "Finnish":"Suomi";
			case "fr":
				return $english ? "French":"Français";	
			case "hr":
				return $english ? "Croatian":"Hrvatski";	
			case "cs":
				return $english ? "Czech":"Čeština";	
			case "sv":
				return $english ? "Swedish":"Svenska";	
			case "et":
				return $english ? "Estonian":"Eesti";	
			case "ja":
				return $english ? "Japanese":"日本語";
			case "hi":
				return $english ? "Hindi":"हिंदी";
			case "ur":
				return $english ? "Urdu":"اردو";
		}
	}

	public static function str_lreplace($search, $replace, $subject) {
		$pos = strrpos($subject, $search);
		
		if($pos !== false)
		{
			$subject = substr_replace($subject, $replace, $pos, strlen($search));
		}
		return $subject;
	}

	public static function is_HTML($string) {
		return ((preg_match("/<head/",$string,$m) != 0) && !(preg_match("/<xsl/",$string,$m) != 0));
	}
	
	public static function is_AJAX_HTML($string) {
		$r = preg_match_all("/<(a|div|span|p|i|aside|input|textarea|select|h1|h2|h3|h4|meta|button|form|li|strong|ul)/",$string,$m,PREG_PATTERN_ORDER);
		if($string[0]!='{' && $r && $r>=2)
			return true;
		else
			return false;
	}
	
	public static function endsWith($haystack, $needle) {
		// search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
	
	public static function isLanguageRTL($code) {
		$rtls = array("ar","he","fa");
		if(in_array($code,$rtls)) {
			return true;
		}
		return false;
	}
	
	public static function hasLanguageRTL($arrayOfCode) {
		foreach($arrayOfCode as $code) {
			if(WGUtils::isLanguageRTL($code))
				return true;
		}
		return false;
	}
	
	public static function hasLanguageLTR($arrayOfCode) {	
		foreach($arrayOfCode as $code) {
			if(!WGUtils::isLanguageRTL($code))
				return true;
		}
		return false;
	}
}

# Weglot PHP
The library to integrate Weglot translation to a PHP website


## Getting Started

### Install

Install the package via [Composer](https://getcomposer.org/doc/00-intro.md):

```bash
composer require weglot/weglot-php
```

(If you don't use Composer, you can copy the `weglot.php` file and the `lib` directory to your project).

### Initialize
To initialize Weglot, you need your API Key. You can find it on [your Weglot account](https://weglot.com/register?s=git).

Enter Weglot initialization code at the beginning of the execution (Usually index.php or app.php)

```php
// composer autoload
require __DIR__ . '/vendor/autoload.php';
// if you are not using composer: require_once 'path/to/weglot.php';

new \Weglot\WG(array(
	"api_key" =>"YOUR API KEY", // The api key, you can get one on https://weglot.com/register
	"original_l" =>"en", // the original language of your website
	"destination_l" =>"fr,de", // the languages you want to translate your website into
));
```

### Check it works !
Now, when you go on your website, you should see a country selector with flags at the bottom right of your website.

## Customize

### Button position
By default, the language button appears as fixed, at the bottom right of your website.
But you can make it appear wherever you want in your HTML page. Just enter `<div id="weglot_here"></div>` in your HTML wherever you want the button to be.
You can also customize it by adding some CSS rules on the button's element.


### Parameters

#### Required
- `api_key` A string that gives you access to Weglot sevices. You can get one by [creating an account](https://weglot.com/register)
- `original_l` The language of your original website. Enter the two letter code. The list of code is available [here](https://weglot.com/translation-api)
- `destination_l` The languages you want to translate into. Enter the two letter codes separated by commas.

#### Optional
- `buttonOptions` (array) An array of paramters to customize the language button design
	- `is_dropdown`  (bool, default `true`) `true` if the button is a dropdown list, `false` to show all languages as a list 
	- `with_name` (bool, default `true`) `true` to show the name of the language in the button
	- `fullname` (bool, default `true`) `true` to show the full name of the language in the button (English, Français,...) , `false` to show the language code (EN, FR,...)
	- `with_flags` (bool, default `true`) `true` to show the flags, `false` to not show flags
	- `type_flags` (int, default `0`)  The design of the flags
	 - `0` rectangular mate
	 - `1` rectangular bright
	 - `2` square
	 - `3` circle

- `exclude_blocks` (string, default "") : comma separated list of CSS selectors. You can exclude part of your website from being translated.

- `exclude_url` (string,  default "") : comma separated list of **relative** URLs. You can exclude URL of your website from being translated.

- `home_url` (string,  default "") : Enter the subdirectory if your website is not at the root. For instance, if your website is at `http://localhost/website/` , then enter `/website`


#### Example
Here is an example of initialization code

```php
// Example : Your website is in French, and you want it also in English, German, Japanese

new \Weglot\WG(array(
	"api_key" =>"YOUR API KEY", // The api key, you can get one on https://weglot.com/register
	"original_l" =>"fr", 
	"destination_l" =>"en,de,ja", 
	"buttonOptions" => array("fullname"=>false,"with_name"=>true,"is_dropdown"=>false,"with_flags"=>true,"type_flags"=>1),
	"exclude_blocks" => ".logo,nav #brand"
	"exclude_url" => "/terms-conditions,/privacy-policy"
));
```

## Troubleshooting
Once you save the initialization code, you should see the language button appear at the bottom right of your website.

If that is not the case, it means the Weglot code is not running. Check if you have PHP errors

If you see the flags but when you switch languages, you see a 404 /Not found, it means Weglot code is not running or not at the beginning. Weglot needs to run before the request is processed so make sure it is included at the beginning of the PHP code.

Also, make sure that your rewrite rules are configured so that the PHP code is run on a 404 page.

And of course, finally, contact us at support@weglot.com or on the live chat on our website, we answer pretty fast :)

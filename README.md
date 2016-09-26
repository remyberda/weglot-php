# Weglot PHP
The library to integrate Weglot translation to a PHP website


## Getting Started

### Install

Install the package via [Composer](https://getcomposer.org/doc/00-intro.md):

```bash
composer require "weglot/weglot-php":"dev-master"
```

If you don't use Composer, you can copy the `weglot.php` file and the `lib` directory to your project).

### Initialize
To initialize Weglot, you need your API Key. You can find it on [your Weglot account](https://weglot.com/account).

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

### Parameters

#### Required
`api_key` - A string that gives you access to Weglot sevices. You can get one by [creating an account](https://weglot.com/register)
`original_l` - The language of your original website. Enter the two letter code. The list of code is available [here](https://weglot.com/translation-api)
`destination_l` - The languages you want to translate into. Enter the two letter codes separated by commas.

#### Optional
`buttonOptions` - An array of paramters to customize the language button design
	`is_dropdown` - 
	`with_name`
	`fullname`
	`with_flags`
	`type_flags`
	
#### Examples

# GoogleShoppingAPI v2

## Magento Module GoogleShoppingAPI

This module is based on the official Magento GoogleShopping module and enhances
the original module features with APIv2 support (APIv1 support removed),
OAuth2 support and serveral additional features from the original 
EnhancedGoogleShopping module.

## Features


* update item expiration date on sync
* option to renew not listed items on sync
* option to remove disabled items on sync
* convert html entities in description to UTF-8 chars
* strip tags from description
* make sales price available in countries outside the US
* possibility to define a separate google shopping image with base image fallback
* adds Google Analytics source to product link (utm_source=GoogleShopping)
* adds Austria as target country
* ability to set Google product category in Magento product details

## Installation

As the Google ApiClient must be installed in addition, it is recommendet to 
install using composer.

Create or adapt the composer.json file in your Magento root directory with the 
following content:

```json
{
	"require": {
		"bluevisiontec/googleshoppingapi": "*",
		"magento-hackathon/magento-composer-installer": "*",
		"google/apiclient": "*"
	},
	"repositories": [
		{
			"type": "composer",
			"url": "http://packages.firegento.com"
		},
		{
				"type": "vcs",
				"url": "https://github.com/bluevisiontec/GoogleShoppingApi"
		}
	],
	"extra": {
		"magento-root-dir": "./",
		"magento-deploystrategy": "copy"
	}
}
```

### Install composer
```bash
mkdir bin
curl -s https://getcomposer.org/installer | php -- --install-dir=bin
php bin/composer.phar install
```

## Configuration

As the module has to use Google OAuth2 a ClientId and ClientSecret for Google
Content API is required. This can be generated in the 
http://console.developers.google.com/
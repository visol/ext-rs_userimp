TYPO3 Extension rs_userimp
================================

This extension is a User Importer that imports users from a XLSX, XLS or CSV file to the fe_users or tt_address table.

Installation
----------

Since we are not the owner of the extension key, you have to download the extension from Github or clone it using Git:

    git clone https://github.com/visol/ext-rs_userimp.git rs_userimp
    
You can also load it using Composer (but not via Packagist or TYPO3 TER) as follows:

    {
    	"repositories": [
    		{
    			"type": "composer",
    			"url": "https://composer.typo3.org/"
    		},
    		{
    			"type": "git",
    			"url": "https://github.com/visol/ext-rs_userimp.git"
    		}
    	],
    	"license": "GPL-2.0+",
    	"config": {
    		"vendor-dir": "Packages/Libraries",
    		"bin-dir": "bin"
    	},
    	"require": {
    		"typo3/cms": "~6.2",
    		"visol/rs-userimp": "~2.1"
        }
    }

Configuration
-----------
*to be written*

Requirements
-------------

The extension is currently compatible with TYPO3 6.2. It might work in 7 LTS, but it is not tested.

Authors
-----
The extension was last maintained by Rainer Sudhölter.

This friendly fork is maintained by visol digitale Dienstleistungen GmbH, Luzern.
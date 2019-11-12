# Facebook XML Product Catalog

Allows the creation of a valid XML file for use with Facebook Product Catalog.

## Features
* Creates a valid XML file
* Option to exclude certain categories from the feed
* Option to include or exclude out of stock products
* Customizable messages for stock availability
* Customizable XML feed output location
* Products without images are automatically excluded
* This module **_IS_** suitable for fashion products

## How to use
#### Step 1
After installing the module, go into the module configuration and set the following:

* XML feed location
* Store name and url.
* Categories to exclude, whether to include out of stock products or not
* Messages for stock availability.

### Step 5
Use cron to visit <your-magento-shop>/facebookfeed as frequently as you need in order to generate the feed

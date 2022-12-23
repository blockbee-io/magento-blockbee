[<img src="https://blockbee.io/static/assets/images/blockbee_logo_nospaces.png" width="300"/>](image.png)

# BlockBee Payment Gateway for Magento
Accept cryptocurrency payments on your Magento store

### Requirements:

```
Magento >= 2.4
Magento >= 2.3.5
```

### Description

Accept payments in Bitcoin, Bitcoin Cash, Litecoin, Ethereum, Monero and IOTA directly to your crypto wallet.

#### Allow users to pay with crypto directly on your store

The BlockBee extension enables your Magento store to get receive payments in cryptocurrency, with a simple setup.

#### Accepted cryptocurrencies & tokens include:

* (BTC) Bitcoin
* (ETH) Ethereum
* (BCH) Bitcoin Cash
* (LTC) Litecoin
* (XMR) Monero
* (TRX) Tron
* (BNB) Binance Coin
* (USDT) USDT

BlockBee plugin will attempt to automatically convert the value you set on your store to the cryptocurrency your customer chose.
Exchange rates are fetched every 5 minutes.

### Supported currencies for automatic exchange rates are:

* (USD) United States Dollar
* (EUR) Euro
* (GBP) Great Britain Pound
* (JPY) Japanese Yen
* (CNY) Chinese Yuan
* (INR) Indian Rupee
* (CAD) Canadian Dollar
* (HKD) Hong Kong Dollar
* (BRL) Brazilian Real
* (DKK) Danish Krone
* (MXN) Mexican Peso
* (AED) United Arab Emirates Dirham

If your Magento's currency is none of the above, the exchange rates will default to USD.
If you're using Magento in a different currency not listed here and need support, please [contact us](https://blockbee.io/contacts/) via our live chat.

#### Why choose BlockBee?

BlockBee has no setup fees, no monthly fees and no hidden costs!

BlockBee has a low 1% fee on the transactions processed.
For more info on our fees [click here](https://blockbee.io/fees/)

### Installation

1. Upload code to the folder app/code/Blockbee/Blockbee
2. Enter following commands to install module:
```bash
php bin/magento module:enable Blockbee_Blockbee
php bin/magento setup:upgrade 
php bin/magento setup:di:compile 
php bin/magento setup:static-content:deploy -f 
php bin/magento cache:flush 
php bin/magento cache:enable blockbee_cryptocurrencies
```

4. Enable and configure BlockBee in Magento Admin under Stores -> Configuration-> Sales -> Payment Methods -> BlockBee


### Configuration


1. Access your Magento Admin Panel 
2. Go to Stores -> Configuration -> Sales -> Payment Methods -> BlockBee 
3. Activate the payment method (if inactive) 
4. Set the name you wish to show your users on Checkout (for example: "Cryptocurrency") 
5. Paste your API Key in the API Key field
6. Select which cryptocurrencies you wish to accept (control + click to select many) 
7. Input your addresses to the cryptocurrencies you selected. This is where your funds will be sent to, so make sure the addresses are correct. 
8. Click "Save Changes" 
9. All done!


### Frequently Asked Questions

#### Do I need an API key?

Yes. To use our service you will need to register at our [dashboard](https://dash.blockbee.io/) and create a new API Key.

#### How long do payments take before they're confirmed?

This depends on the cryptocurrency you're using. Bitcoin usually takes up to 11 minutes, Ethereum usually takes less than a minute.

#### Is there a minimum for a payment?

Yes, the minimums change according to the chosen cryptocurrency and can be checked [here](https://blockbee.io/get_started/#fees).
If the Magento order total is below the chosen cryptocurrency's minimum, an error is raised to the user.

#### Where can I find more documentation on your service?

You can find more documentation about our service on our [website](https://blockbee.io/), our [technical documentation](https://docs.blockbee.io/) page or our [e-commerce](https://blockbee.io/ecommerce/) page.
If there's anything else you need that is not covered on those pages, please get in touch with us, we're here to help you!

#### Where can I get support?

The easiest and fastest way is via our live chat on our [website](https://blockbee.io) or via our [contact form](https://blockbee.io/contacts/).

### Changelog 

#### 1.0
* Initial release.

### Upgrade Notice
* No breaking changes.

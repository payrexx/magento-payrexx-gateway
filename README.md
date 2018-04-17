# Payrexx PaymentGateway Module for Magento2

A Magento plugin to accept payments with Payrexx. 


## Support

This module supports Magento 2 versions **2.2.***. It might work on more recent versions, but we cannot make any guarantees.

## Preparation

The usage of this module requires that you have obtained Payrexx REST API credentials. Please visit  [Payrexx](https://www.payrexx.com/) and retrieve your credentials.
## 1. Installation
### Manual install
##### Clone this repository
1. Locate the **/app/code** directory which should be under the magento root installation.
 
2. If the **code** folder is not there, create it.

3. Change to the **code** folder and clone the Git repository (https://github.com/payrexx/magento-payrexx-gateway.git) into **code** specifying the local repository folder structure to be **Payrexx/PaymentGateway** 
    ```bash
    $ git clone https://github.com/payrexx/magento-payrexx-gateway.git Payrexx/PaymentGateway
    ```

##### Download the module as "zip" archive

1. Locate the **/app/code** directory which should be under the magento root installation.

2. If the **code** folder is not there, create it.

3. Create the folder structure **Payrexx/PaymentGateway/** inside the **code** folder. 

4. Download the package from the github site (https://github.com/payrexx/magento-payrexx-gateway.git).

5. Extract the zip contents to the **PaymentGateway** folder you just created. The README.md and all other files and folders should be under the **PaymentGateway** folder.

## 2. Requirements
This extension requires the [Payrexx API library for PHP.](https://github.com/payrexx/payrexx-php)

When using composer this will be installed automatically. To install manually, enter the following command in your Magento 2 root folder:

```
$ composer require payrexx/payrexx
```

## 3. Magento Setup
   Run the commands from the Magento root directory.

```sh
$ php bin/magento module:enable Payrexx_PaymentGateway
$ php bin/magento setup:upgrade
$ php bin/magento setup:di:compile
```

If Magento is running in production mode, deploy static content:

```bash
$ php bin/magento setup:static-content:deploy
   ```

## 4.Configuration
### Magento
 To configure the module, log in to your Magento Admin panel.
1. Go to **Stores** -> **Configuration** -> **Sales** -> **Payment methods** 

2. Find and click on **Payrexx** Settings under **OTHER PAYMENT METHODS**

3. To configure your Payrexx Module Configuration, using the **Instance Name** and **API Secret**  obtained earlier.

4. Click Save config to save the configuration values. 

### Payrexx

 To Configure the webhook URL in Payrexx, Log in your Payrexx account.

1. Go to **settings** -> **API** --> Find **Webhook URL**

2. Insert the URL to your shop and add /payrexx/payment/webhook
 (e.g. If your shop `http://www.example.com`, the Webhook URL is `http://www.example.com/payrexx/payment/webhook`)
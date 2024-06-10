# Payrexx PaymentGateway Module for Magento2

A Payrexx plugin to accept payments in Magento.


## Support

This module supports Magento versions **2.2.\*** - **2.4.\***  
*Note: It may work on future Magento releases, but performance cannot be guaranteed.*

## Preparation

The usage of this module requires Payrexx REST API credentials. To obtain Payrexx REST API, please create your account in [Payrexx](https://www.payrexx.com/?ref=magento).
## Integration
### 1. Installation

##### Download the module as "zip" archive

1. Locate the **/app/** directory which should be under the Magento root installation.

2. Create the folder structure **code/Payrexx/PaymentGateway/** inside the **app** folder.

   (i.e) **app-> code->Payrexx->PaymentGateway**

3. Download the package from the github site (https://github.com/payrexx/magento-payrexx-gateway.git).

4. Extract the zip contents to the **PaymentGateway** folder you just created. The README.md and all other files and folders is stored in **PaymentGateway** folder.

### 2. Requirements
 This extension requires the [Payrexx API library for PHP.](https://github.com/payrexx/payrexx-php)

If you are not using Composer already, then you should read the installation guide http://getcomposer.org/download/

Enter the following command in your Magento root folder:

 ```
 $ composer require payrexx/payrexx
 ```

**Note:**
If Composer needs username and password, refer http://devdocs.magento.com/guides/v2.2/install-gde/prereq/connect-auth.html

While running Composer, If you can't login to `repo.magento.com`, rename the file **auth.json.sample** into **auth.json** which is present inside Magento root directory and insert your Public Key & Private Key in the  **auth.json**.

### 3. Magento Setup

#### Enable Extension Via Admin Web Setup Wizard

Log in to your Magento Admin Panel.

1. Go to **SYSTEM** -> **Web Setup Wizard** -> Choose **Module Manager**

2. Find **Payrexx_PaymentGateway** and select **Enable** from the Select drop-down menu.

3. Complete the Step 1: Readiness Check, Step 2: Create Backup, Step 3: Enable Module  
   Please wait until the process is completed.

#### Enable Extension via command line.

Go to Magento root directory and execute the following commands

```
$ bin/magento setup:upgrade

$ bin/magento setup:di:compile

$ bin/magento cache:clean
```

**Note:** It takes sometime for the process to complete.

### 4. Configuration in Magento

 To configure the module, log in to your Magento Admin panel.
1. Go to **Stores** -> **Configuration** -> **Sales** -> **Payment methods** 

2. Find and click on **Payrexx** under **OTHER PAYMENT METHODS**

3. To configure your Payrexx Module Configuration, use the **Instance Name** and **API Secret**  obtained earlier while creating your account in Payrexx.
   (e.g. If you have registered the url as  https://testing123.payrexx.com your instance will be  ‘testing123’).

4. Click **Save Config** to save the configuration values.

### 5. Payrexx Configuration

 To Configure the webhook URL in Payrexx, Log in your Payrexx account.

1. Go to **settings** -> **API** --> Find **Webhook URL**

2. Insert the URL to your shop and add /payrexx/payment/webhook
 (e.g. If your shop url is `http://www.example.com`, the Webhook URL will be `http://www.example.com/payrexx/payment/webhook`)

## Update

To make use of a new update of the module the following steps need to completed.

1. Upload the module code to the directory app/code/Payrexx/PaymentGateway of your Magento 2 installation

2. Execute the following commands for the update to take affect:
```
$ bin/magento setup:upgrade

$ bin/magento setup:di:compile

$ bin/magento cache:clean
```


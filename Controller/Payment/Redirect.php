<?php
/**
 * Payrexx Payment Gateway Module
 *
 * @author      Payrexx <support@payrexx.com>
 * @copyright   Payrexx AG
 * @package     magento2
 * @subpackage  payrexx_payment_gateway
 */
namespace Payrexx\PaymentGateway\Controller\Payment;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Sales\Model\Order;
use Payrexx\PaymentGateway\Model\PaymentMethod;

/**
 * Class \Payrexx\PaymentGateway\Controller\Payment\Redirect
 * The Redirect controller is accessing from frontend
 */
class Redirect extends \Payrexx\PaymentGateway\Controller\AbstractAction
{
    /**
     * Return result based on the request
     */
    public function execute()
    {
        // Get current order detail from checkoutsession object.
        $orderId = $this->checkoutSession->getLastRealOrderId();
        if (empty($orderId)) {
            $this->redirectToCheckoutCart();
            return;
        }

        $order = $this->getOrderDetailByOrderId($orderId);
        if (!$order) {
            $this->redirectToCheckoutCart();
            return;
        }

        // Create payrexx gateway using Payrexx Api
        $response = $this->createPayrexxGateway($order);
        if ($response) {
            $this->setPaymentAdditionalInfo($order->getPayment(), $response);
            $this->_redirect($response->getLink());
            return;
        }

        // If any exception occured cancel the current order
        // Add the ordered product into cart
        $this->executeCancelAction();
    }

    /**
     * Create Payrexx Gateway
     *
     * @param  \Magento\Sales\Model\Order $order The order related details
     * @return \Payrexx\Models\Response\Gateway|null
     */
    private function createPayrexxGateway($order)
    {
        // Create payrexx gateway object
        $gateway = ObjectManager::getInstance()->create(
            '\Payrexx\Models\Request\Gateway'
        );

        $gateway->setSkipResultPage(true);
        $gateway->setPsp([]);
        $gateway->setValidity(15);
        $gateway->setAmount((int)(string)($order->getGrandTotal() * 100));
        $gateway->setCurrency($order->getOrderCurrencyCode());

        // Set order id as the reference id
        $gateway->setReferenceId($order->getRealOrderId());
        // Set success page url to redirect after successfull payment
        $gateway->setSuccessRedirectUrl(
            $this->_url->getUrl('checkout/onepage/success')
        );
        // Set failure page url to redirect after unsuccessfull payment
        $gateway->setFailedRedirectUrl(
            $this->_url->getUrl('payrexx/payment/failure')
        );
        // Set failure page url to redirect after unsuccessfull payment
        $gateway->setCancelRedirectUrl(
            $this->_url->getUrl('payrexx/payment/failure')
        );

        $baskets = $this->getBaskets($order);
        // verify basket items amount equal to grand total
        $basketAmount = 0;
        foreach ($baskets as $basket) {
            $basketAmount += $basket['quantity'] * $basket['amount'];
        }
        if ($basketAmount === $order->getGrandTotal() * 100) {
            $gateway->setBasket($baskets);
        } else {
            $purpose = $this->createPurposeByBasket($baskets);
            $gateway->setPurpose($purpose);
        }

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        // Contact information which should be stored along with payment
        $fields = [
            'forename' => $billingAddress->getFirstname(),
            'surname'  => $billingAddress->getLastname(),
            'company'  => $billingAddress->getCompany(),
            'street'   => implode(',', $billingAddress->getStreet()),
            'postcode' => $billingAddress->getPostcode(),
            'place'    => $billingAddress->getCity(),
            'country'  => $billingAddress->getCountryId(),
            'phone'    => $billingAddress->getTelephone(),
            'email'    => $billingAddress->getEmail(),
            'delivery_forename' => $shippingAddress->getFirstname(),
            'delivery_surname'  => $shippingAddress->getLastname(),
            'delivery_company'  => $shippingAddress->getCompany(),
            'delivery_street'   => implode(',', $shippingAddress->getStreet()),
            'delivery_postcode' => $shippingAddress->getPostcode(),
            'delivery_place'    => $shippingAddress->getCity(),
            'delivery_country'  => $shippingAddress->getCountryId(),
        ];

        // Add contact information
        foreach ($fields as $type => $value) {
            $gateway->addField($type, $value);
        }

        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethod();
        if ($paymentMethod != PaymentMethod::PAYMENT_METHOD_PAYREXX_CODE) {
            $pm = preg_replace('/^(payrexx_payment_)*(.*)/s', '$2', $paymentMethod);
            $pm = str_replace('_', '-', $pm);
            $gateway->setPm([$pm]);
        }
        $lang = $this->getLang();
        if (!empty($lang)) {
            $gateway->setLanguage($lang);
        }

        try {
            $payrexx = $this->getPayrexxInstance();
            $metaData = $this->getMetaData();
            if (!empty($metaData)) {
                $payrexx->setHttpHeaders($metaData);
            }
            return $payrexx->create($gateway);
        } catch (\Payrexx\PayrexxException $e) {
            $this->logger->error(
                'Payrexx Gateway creation : ' . json_encode($e->getMessage())
            );
            return null;
        }
    }

    /**
     * Get language.
     *
     * @return string
     */
    private function getLang(): string
    {
        $resolver = ObjectManager::getInstance()->get(
            '\Magento\Framework\Locale\ResolverInterface'
        );

        $locale = $resolver->getLocale();
        if (empty($locale)) {
            $locale = $resolver->getDefaultLocale();
        }
        return strstr($resolver->getLocale(), '_', true);
    }

    /**
     * Set payment related information as additional info
     *
     * @param \Magento\Payment\Model\Info       $payment Payment related info
     * @param \Payrexx\Models\Response\Gateway  $gateway Payrexx gateway
     */
    private function setPaymentAdditionalInfo($payment, $gateway)
    {
        // Generate security hash based on hash alogorithm.
        $hash = hash_hmac(
            'sha1',
            $gateway->getHash(),
            $this->getPayrexxConfig()['api_secret'],
            false
        );

        $payment->setAdditionalInformation(
            static::PAYMENT_GATEWAY_ID,
            $gateway->getId()
        );

        $payment->setAdditionalInformation(
            static::PAYMENT_SECURITY_HASH,
            $hash
        );
        $payment->save();
    }

    private function getMetaData(): array
    {
        $objectManager = ObjectManager::getInstance();
        try {
            $productMetadata = $objectManager->get(ProductMetadataInterface::class);
            $moduleList = $objectManager->get(ModuleListInterface::class);
            $moduleName = 'Payrexx_PaymentGateway';
            $info = $moduleList->getOne($moduleName);
            return [
                'X-Shop-Version'   => (string) $productMetadata->getVersion(),
                'X-Plugin-Version' => (string) $info['setup_version'],
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getBaskets(Order $order): array
    {
        $baskets = [];
        foreach ($order->getAllItems() as $product) {
            if ($product->getPrice() <= 0) {
                continue;
            }
            $baskets[] = [
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'quantity' => $product->getQtyOrdered(),
                'amount' => $product->getPrice() * 100,
                'sku' => $product->getSku(),
            ];
        }

        $shippingAmount = $order->getShippingAmount();
        if ($shippingAmount > 0) {
            $baskets[] = [
                'name' => 'Shipping',
                'quantity' => 1,
                'amount' => $shippingAmount * 100,
            ];
        }

        $discountAmount = abs($order->getDiscountAmount());
        if ($discountAmount > 0) {
            $baskets[] = [
                'name' => 'Discount',
                'quantity' => 1,
                'amount' => $discountAmount * -100,
            ];
        }

        $taxAmount = $order->getTaxAmount();
        if ($taxAmount > 0) {
            $baskets[] = [
                'name' => 'Tax',
                'quantity' => 1,
                'amount' => $taxAmount * 100,
            ];
        }
        return $baskets;
    }

    private function createPurposeByBasket(array $products): string
    {
        $desc = [];
        foreach ($products as $product) {
            $desc[] = implode(' ', [
                $product['name'],
                $product['quantity'],
                'x',
                number_format($product['amount'] / 100, 2, '.'),
            ]);
        }
        return implode('; ', $desc);
    }
}

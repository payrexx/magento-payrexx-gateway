<?php
/**
 * Payrexx Payment Gateway
 *
 * Copyright © 2018 PAYREXX AG (https://www.payrexx.com)
 * See LICENSE.txt for license details.
 *
 * @author SoftSolutions4U <info@softsolutions4u.com>
 */
namespace Payrexx\PaymentGateway\Controller\Payment;

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
            // Fix the redirect URL
            $payrexxUrl = $this->resolvePayrexxUrl($response->getLink());
            $this->_redirect($payrexxUrl);
            return;
        }

        // If any exception occured cancel the current order
        // Add the ordered product into cart
        $this->context->getMessageManager()->addError(
            __('An error occurred while processing your payment. Please try again later.')
        );
        $this->executeCancelAction();
    }

    /**
     * Create Payrexx Gateway
     *
     * @param  \Magento\Sales\Model\Order $order The order related details
     * @return \Payrexx\Models\Response\Gateway|null
     */
    public function createPayrexxGateway($order)
    {
        // Create payrexx gateway object
        $gateway = new \Payrexx\Models\Request\Gateway();

        $gateway->setPsp([]);
        $gateway->setAmount($order->getGrandTotal() * 100);
        $gateway->setCurrency($order->getOrderCurrencyCode());

        // Set order id as the reference id
        $gateway->setReferenceId($order->getRealOrderId());
        // Set success page url to redirect after successfull payment
        $gateway->setSuccessRedirectUrl(
            $this->_url->getUrl('checkout/onepage/success')
        );
        // Set failure page url to redirect after unsuccessfull payment
        $gateway->setFailedRedirectUrl(
            $this->_url->getUrl('checkout/onepage/failure')
        );

        $billingAddress = $order->getBillingAddress();

        // Contact information which should be stored along with payment
        $fields = [
            'forename' => $billingAddress->getFirstname(),
            'surname'  => $billingAddress->getLastname(),
            'company'  => $billingAddress->getCompany(),
            'street'   => implode(',', $billingAddress->getStreet()),
            'postcode' => $billingAddress->getPostcode(),
            'place'    => $billingAddress->getRegion(),
            'country'  => $billingAddress->getCountryId(),
            'phone'    => $billingAddress->getTelephone(),
            'email'    => $billingAddress->getEmail()
        ];

        // Add contact information
        foreach ($fields as $type => $value) {
            $gateway->addField($type, $value);
        }

        try {
            // Create payrexx instance
            $payrexx = $this->getPayrexxInstance();
            // Create payrexx gateway
            return $payrexx->create($gateway);
        } catch (\Payrexx\PayrexxException $e) {
            $this->logger->addError(
                'Payrexx Gateway creation : ' . json_encode($e->getMessage())
            );
            return;
        }
    }

    /**
     * Get language based URL.
     *
     * @param  string $url payment link
     * @return string
     */
    public function resolvePayrexxUrl($url)
    {
        $resolver = $this->_objectManager->get(
            '\Magento\Framework\Locale\ResolverInterface'
        );

        $locale = $resolver->getLocale();
        if (empty($locale)) {
            $locale = $resolver->getDefaultLocale();
        }
        $lang = strstr($resolver->getLocale(), '_', true);

        // Add current/default language into URL
        return preg_replace(
            '/^(https:\/\/[a-z0-9.]+)\/(.*)$/',
            '$1/'. $lang .'/$2',
            $url
        );
    }

    /**
     * Set payment related information as additional info
     *
     * @param \Magento\Payment\Model\Info       $payment  Payment related info
     * @param \Payrexx\Models\Response\Gateway  $response Gateway response
     */
    public function setPaymentAdditionalInfo($payment, $response)
    {
        // Generate security hash based on hash alogorithm.
        $hash = hash_hmac(
            'sha1',
            $response->getHash(),
            $this->getPayrexxConfig()['api_secret'],
            false
        );

        $payment->setAdditionalInformation(
            static::PAYMENT_GATEWAY_ID,
            $response->getId()
        );

        $payment->setAdditionalInformation(
            static::PAYMENT_SECURITY_HASH,
            $hash
        );
        $payment->save();
    }
}

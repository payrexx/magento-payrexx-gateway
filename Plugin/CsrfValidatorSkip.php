<?php

/**
 * Payrexx Magento2 Module using \Magento\Payment\Model\Method\AbstractMethod
 * Copyright (C) 2021 payrexx.com
 *
 * This file is part of Payrexx/PaymentGateway.
 */

namespace Payrexx\PaymentGateway\Plugin;

/**
 * Description of CsrfValidatorSkip
 *
 * @author vinoth <vinoth@payrexx.com>
 */
class CsrfValidatorSkip {
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        if ("{$request->getModuleName()}/{$request->getActionName()}" == 'payrexx/webhook') {
            return; // Skip CSRF check
        }
        $proceed($request, $action); // Proceed Magento 2 core functionalities
    }

}
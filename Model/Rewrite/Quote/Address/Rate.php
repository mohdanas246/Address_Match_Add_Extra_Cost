<?php

namespace Codilar\ShippingAddress\Model\Rewrite\Quote\Address;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Address\RateResult\AbstractResult;
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Checkout\Model\Session as CheckoutSession;

class Rate extends \Magento\Quote\Model\Quote\Address\Rate
{
    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CheckoutSession $checkoutSession
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,

        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @param AbstractResult $rate
     * @return $this|Rate
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */

    public function importShippingRate(AbstractResult $rate)
    {
        $additionalPrice = (float)$this->scopeConfig->getValue('Codilar_ShippingAddress/products_settings/extra_shipping_cost');
        $textToMatch = $this->scopeConfig->getValue('Codilar_ShippingAddress/products_settings/text');
        $finalPrice = $rate->getPrice();

// Check if the shipping address matches the specified text to add extra shipping cost
        $shippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();
        if ($shippingAddress->getStreetLine(1) && str_contains($shippingAddress->getStreetLine(1), $textToMatch)) {
            $finalPrice += $additionalPrice;
        }

        if ($rate instanceof Error) {
            $this->setCode(
                $rate->getCarrier() . '_error'
            )->setCarrier(
                $rate->getCarrier()
            )->setCarrierTitle(
                $rate->getCarrierTitle()
            )->setErrorMessage(
                $rate->getErrorMessage()
            );
        } elseif ($rate instanceof Method) {
            $this->setCode(
                $rate->getCarrier() . '_' . $rate->getMethod()
            )->setCarrier(
                $rate->getCarrier()
            )->setCarrierTitle(
                $rate->getCarrierTitle()
            )->setMethod(
                $rate->getMethod()
            )->setMethodTitle(
                $rate->getMethodTitle()
            )->setMethodDescription(
                $rate->getMethodDescription()
            )->setPrice(
                $finalPrice
            );
        }
        return $this;
    }
}

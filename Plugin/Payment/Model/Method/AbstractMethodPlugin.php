<?php
/**
 * Created by PhpStorm.
 * User: Andrew Stepanchuk
 * Date: 08.08.2017
 * Time: 21:10
 */

namespace Netzexpert\PaymentMethodMap\Plugin\Payment\Model\Method;

use Magento\Framework\Serialize\Serializer\Json;

class AbstractMethodPlugin
{
    /** @var \Magento\Checkout\Model\Cart  */
    private $cart;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    private $scopeConfig;

    /** @var \Magento\Framework\Module\Manager  */
    private $moduleManager;

    /** @var \Magento\Framework\ObjectManagerInterface  */
    private $objectManager;

    /** @var  array */
    private $methodsMap;

    /** @var Json  */
    private $json;

    /**
     * AbstractMethodPlugin constructor.
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Json $json
     */
    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Json $json
    ) {
        $this->cart             = $cart;
        $this->scopeConfig      = $scopeConfig;
        $this->moduleManager    = $moduleManager;
        $this->objectManager    = $objectManager;
        $this->json             = $json;
    }

    /**
     * @param \Magento\Payment\Model\Method\AbstractMethod $method
     * @param boolean $result
     * @return boolean
     */
    public function afterIsAvailable(
        \Magento\Payment\Model\Method\AbstractMethod $method,
        $result
    ) {
        if (!$result) {
            return $result;
        }
        if (!$this->scopeConfig->getValue(
            'shipping/paymentmethodmap/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return $result;
        }
        $shippingMethod = $this->cart->getQuote()->getShippingAddress()->getShippingMethod();
        if (!$shippingMethod) {
            return $result;
        }

        // for matrixrate shipping methods support
        if ($this->moduleManager->isEnabled('WebShopApps_MatrixRate')) {
            if (strpos($shippingMethod, 'matrixrate')!==false) {
                /** @var \WebShopApps\MatrixRate\Model\ResourceModel\Carrier\Matrixrate\Collection $methodCollection */
                $methodCollection = $this->objectManager
                    ->get('\WebShopApps\MatrixRate\Model\ResourceModel\Carrier\Matrixrate\CollectionFactory')->create();
                list($string, $id) = preg_split('[matrixrate_matrixrate_]', $shippingMethod);
                $shippingMethod = 'matrixrate_' .
                    $methodCollection->addFieldToFilter('pk', $id)
                        ->getFirstItem()->getData('shipping_method');
            }
        }
        $map = $this->getMethodsMap();
        if (isset($map[$shippingMethod])) {
            $result = in_array($method->getCode(), $map[$shippingMethod]);
        }
        return $result;
    }

    private function getMethodsMap()
    {
        if (!$this->methodsMap) {
            $config = $this->json->unserialize(
                $this->scopeConfig->getValue(
                    'shipping/paymentmethodmap/map',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            );
            $this->methodsMap = [];
            foreach ($config as $configValue) {
                $this->methodsMap[$configValue['shipping_method']] = $configValue['payment_methods'];
            }
        }

        return $this->methodsMap;
    }
}

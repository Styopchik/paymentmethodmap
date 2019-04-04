<?php
/**
 * Created by PhpStorm.
 * User: Andrew Stepanchuk
 * Date: 09.08.2017
 * Time: 13:04
 */

namespace Netzexpert\PaymentMethodMap\Plugin\Payment\Model\Method;

use \Magento\Framework\Serialize\Serializer\Json;

class AdapterPlugin
{
    /** @var \Magento\Checkout\Model\Cart  */
    protected $_cart;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $_scopeConfig;

    /** @var \Magento\Framework\Module\Manager  */
    protected $_moduleManager;

    /** @var \Magento\Framework\ObjectManagerInterface  */
    protected $_objectManager;

    /** @var  array */
    private $_methodsMap;

    /** @var \Magento\Framework\Serialize\Serializer\Json  */
    private $serialize;

    /**
     * AbstractMethodPlugin constructor.
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Json $serialize
     */
    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Json $serialize
    ) {
        $this->_cart = $cart;
        $this->_scopeConfig = $scopeConfig;
        $this->_moduleManager = $moduleManager;
        $this->_objectManager = $objectManager;
        $this->serialize = $serialize;
    }

    /**
     * @param \Magento\Payment\Model\Method\Adapter $method
     * @param boolean $result
     * @return boolean
     */
    public function afterCanUseCheckout(
        \Magento\Payment\Model\Method\Adapter $method,
        $result
    ) {
        if (!$this->_scopeConfig->getValue('shipping/paymentmethodmap/enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return $result;
        }
        $shippingMethod = $this->_cart->getQuote()->getShippingAddress()->getShippingMethod();
        if (!$shippingMethod) {
            return $result;
        }

        // for matrixrate shipping methods support
        if ($this->_moduleManager->isEnabled('WebShopApps_MatrixRate')) {
            if (strpos($shippingMethod, 'matrixrate')!==false) {
                /** @var \WebShopApps\MatrixRate\Model\ResourceModel\Carrier\Matrixrate\Collection $methodCollection */
                $methodCollection = $this->_objectManager->get('\WebShopApps\MatrixRate\Model\ResourceModel\Carrier\Matrixrate\CollectionFactory')->create();
                list($string, $id) = preg_split('[matrixrate_matrixrate_]', $shippingMethod);
                $shippingMethod = 'matrixrate_' . $methodCollection->addFieldToFilter('pk', $id)->getFirstItem()->getData('shipping_method');
            }
        }
        $map = $this->_getMethodsMap();
        if (isset($map[$shippingMethod])) {
            $result = in_array($method->getCode(), $map[$shippingMethod]);
        }
        return $result;
    }

    private function _getMethodsMap()
    {
        if (!$this->_methodsMap) {
            $config = $this->serialize->unserialize($this->_scopeConfig->getValue('shipping/paymentmethodmap/map', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
            $this->_methodsMap = array();
            foreach ($config as $configValue) {
                $this->_methodsMap[$configValue['shipping_method']] = $configValue['payment_methods'];
            }
        }

        return $this->_methodsMap;
    }
}

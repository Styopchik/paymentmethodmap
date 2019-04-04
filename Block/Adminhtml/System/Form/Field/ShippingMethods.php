<?php
/**
 * Created by PhpStorm.
 * User: Andrew Stepanchuk
 * Date: 08.08.2017
 * Time: 11:03
 */

namespace Netzexpert\PaymentMethodMap\Block\Adminhtml\System\Form\Field;

class ShippingMethods extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * Shipping methods cache
     *
     * @var array
     */
    private $_methods;

    /** @var \Magento\Shipping\Model\Config  */
    protected $_shippingConfig;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Shipping\Model\Config $shippingConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_shippingConfig = $shippingConfig;
    }

    protected function _getActiveMethods()
    {
        if (!$this->_methods) {
            $activeCarriers = $this->_shippingConfig->getActiveCarriers();
            $this->_methods = array();
            foreach ($activeCarriers as $carrierCode => $carrierModel) {
                if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                    foreach ($carrierMethods as $methodCode => $method) {
                        $code = $carrierCode . '_' . $methodCode;
                        $this->_methods[] = array('value' => $code, 'label' => $method);
                    }
                }
            }
        }
        return $this->_methods;
    }

    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getActiveMethods() as $method) {
                $this->addOption($method['value'], addslashes($method['label']));
            }
        }
        return parent::_toHtml();
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}

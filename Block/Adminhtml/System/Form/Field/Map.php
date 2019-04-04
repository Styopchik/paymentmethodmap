<?php
/**
 * Created by PhpStorm.
 * User: Andrew Stepanchuk
 * Date: 08.08.2017
 * Time: 10:48
 */

namespace Netzexpert\PaymentMethodMap\Block\Adminhtml\System\Form\Field;

class Map extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /** @var array  */
    protected $_columns = [];

    /** @var \Netzexpert\PaymentMethodMap\Block\Adminhtml\System\Form\Field\ShippingMethods */
    protected $_shippingRenderer;

    /** @var  \Netzexpert\PaymentMethodMap\Block\Adminhtml\System\Form\Field\PaymentMethods */
    protected $_paymentRenderer;

    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->_shippingRenderer        = null;
        $this->_searchFieldRenderer = null;
        $this->addColumn(
            'shipping_method',
            ['label' => __('Shipping Method'), 'renderer' => $this->_getShippingRenderer()]
        );
        $this->addColumn(
            'payment_methods',
            ['label' => __('Payment Methods'), 'renderer' => $this->_getPaymentRenderer()]
        );
        $this->_addAfter            = false;
    }

    protected function _getShippingRenderer()
    {
        if (!$this->_shippingRenderer) {
            $this->_shippingRenderer = $this->getLayout()->createBlock(
                'Netzexpert\PaymentMethodMap\Block\Adminhtml\System\Form\Field\ShippingMethods',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->_shippingRenderer->setClass('shipping_method_select');
        }
        return $this->_shippingRenderer;
    }

    protected function _getPaymentRenderer()
    {
        if (!$this->_paymentRenderer) {
            $this->_paymentRenderer = $this->getLayout()->createBlock(
                'Netzexpert\PaymentMethodMap\Block\Adminhtml\System\Form\Field\PaymentMethods',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->_paymentRenderer->setClass('payment_method_select');
            $this->_paymentRenderer->setExtraParams('multiple');
        }
        return $this->_paymentRenderer;
    }

    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];

        $optionExtraAttr['option_' . $this->_getShippingRenderer()->calcOptionHash($row->getData('shipping_method'))] =
            'selected="selected"';
        foreach ($row->getData('payment_methods') as $method) {
            $optionExtraAttr['option_' . $this->_getPaymentRenderer()->calcOptionHash($method)] =
                'selected="selected"';
        }

        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }
}

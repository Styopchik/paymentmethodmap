<?php
/**
 * Created by PhpStorm.
 * User: Andrew Stepanchuk
 * Date: 08.08.2017
 * Time: 14:23
 */

namespace Netzexpert\PaymentMethodMap\Block\Adminhtml\System\Form\Field;

class PaymentMethods extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * Payment methods cache
     *
     * @var array
     */
    private $methods;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Methods constructor.
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Payment\Model\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Payment\Model\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentConfig = $config;
    }

    protected function _getPaymentMethods()
    {
        if ($this->methods === null) {
            $this->methods = $this->paymentConfig->getActiveMethods();
        }
        return $this->methods;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getPaymentMethods() as $paymentCode => $paymentModel) {
                $paymentTitle = $this->_scopeConfig->getValue('payment/'.$paymentCode.'/title');
                $this->addOption($paymentCode, addslashes($paymentTitle));
            }
        }
        if (!$this->_beforeToHtml()) {
            return '';
        }

        $html = '<select name="' .
            $this->getName() .
            '[]" id="' .
            $this->getId() .
            '" class="' .
            $this->getClass() .
            '" title="' .
            $this->getTitle() .
            '" ' .
            $this->getExtraParams() .
            '>';

        $values = $this->getValue();
        if (!is_array($values)) {
            $values = (array)$values;
        }

        $isArrayOption = true;
        foreach ($this->getOptions() as $key => $option) {
            $optgroupName = '';
            if ($isArrayOption && is_array($option)) {
                $value = $option['value'];
                $label = (string)$option['label'];
                $optgroupName = isset($option['optgroup-name']) ? $option['optgroup-name'] : $label;
                $params = !empty($option['params']) ? $option['params'] : [];
            } else {
                $value = (string)$key;
                $label = (string)$option;
                $isArrayOption = false;
                $params = [];
            }

            if (is_array($value)) {
                $html .= '<optgroup label="' . $label . '" data-optgroup-name="' . $optgroupName . '">';
                foreach ($value as $keyGroup => $optionGroup) {
                    if (!is_array($optionGroup)) {
                        $optionGroup = ['value' => $keyGroup, 'label' => $optionGroup];
                    }
                    $html .= $this->_optionToHtml($optionGroup, in_array($optionGroup['value'], $values));
                }
                $html .= '</optgroup>';
            } else {
                $html .= $this->_optionToHtml(
                    ['value' => $value, 'label' => $label, 'params' => $params],
                    in_array($value, $values)
                );
            }
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Calculate CRC32 hash for option value
     *
     * @param string $optionValue Value of the option
     * @return string
     */
    public function calcOptionHash($optionValue)
    {
        return sprintf('%u', crc32($this->getName() . $this->getId() . $optionValue));
    }
}

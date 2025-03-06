<?php

namespace Blockbee\Blockbee\Block\Adminhtml\Sales\Order;

use Magento\Framework\View\Element\Template;

class Fee extends Template
{
    protected $config;

    protected $order;

    protected $source;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Tax\Model\Config                        $taxConfig,
        array                                            $data = []
    )
    {
        $this->_config = $taxConfig;
        parent::__construct($context, $data);
    }

    public function displayFullSummary()
    {
        return true;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getStore()
    {
        return $this->order->getStore();
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->order = $parent->getOrder();
        $this->source = $parent->getSource();
        $fee = new \Magento\Framework\DataObject(
            [
                'code' => 'blockbee_fee',
                'strong' => false,
                'value' => $this->order->getData('blockbee_fee'),
                'label' => __('Service Fee'),
            ]
        );
        $parent->addTotal($fee, 'blockbee_fee');
        return $this;
    }
}

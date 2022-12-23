<?php

namespace Blockbee\Blockbee\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\DataObject\Copy;

class QuoteSubmitBefore implements ObserverInterface
{
    protected $objectCopyService;
    protected $logger;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface     $orderRepository,
        \Magento\Sales\Model\ResourceModel\Order        $orderResourceModel,
        \Blockbee\Blockbee\Model\Method\BlockbeePayment $payment,
        \Psr\Log\LoggerInterface                        $logger,
        Copy                                            $objectCopyService
    ) {
        $this->orderResourceModel = $orderResourceModel;
        $this->orderRepository = $orderRepository;
        $this->payment = $payment;
        $this->objectCopyService = $objectCopyService;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {

        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();

        if ($paymentMethod === 'blockbee') {
            $order =$observer->getOrder();
            $order->setData('blockbee_fee', (float)$quote->getData('fee'));
        }

    }
}

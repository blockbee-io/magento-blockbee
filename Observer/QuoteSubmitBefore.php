<?php

namespace Blockbee\Blockbee\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\DataObject\Copy;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Blockbee\Blockbee\Model\Method\BlockbeePayment;
use Psr\Log\LoggerInterface;

class QuoteSubmitBefore implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Order
     */
    protected $orderResourceModel;

    /**
     * @var BlockbeePayment
     */
    protected $payment;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Copy
     */
    protected $objectCopyService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Order $orderResourceModel,
        BlockbeePayment $payment,
        LoggerInterface $logger,
        Copy $objectCopyService
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
            // Assign the fee from the quote to the order.
            $order->setData('blockbee_fee', (float)$quote->getData('blockbee_fee'));
        }
    }
}

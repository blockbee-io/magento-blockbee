<?php

namespace Blockbee\Blockbee\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AfterSuccess implements ObserverInterface
{
    /**
     * @var \Blockbee\Blockbee\Helper\Data
     */
    protected $helper;

    /**
     * @var \Blockbee\Blockbee\Model\Method\BlockbeePayment
     */
    protected $payment;

    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $redirect;

    public function __construct(
        \Blockbee\Blockbee\Helper\Data $helper,
        \Blockbee\Blockbee\Model\Method\BlockbeePayment $payment,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\Response\Http $redirect
    ) {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
        $this->logger = $logger;
        $this->productMetadata = $productMetadata;
        $this->redirect = $redirect;
    }

    public function execute(Observer $observer)
    {
        $version_check = 1;

        if ($this->productMetadata->getVersion() >= 2.3 && $this->productMetadata->getVersion() < 2.4) {
            $version_check = 0;
        }

        if (empty($version_check)) {
            return false;
        }

        $order = $this->payment->getOrder();
        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();

        if ($paymentMethod === 'blockbee') {
            $metaData = json_decode($this->helper->getPaymentResponse($order->getQuoteId()), true);
            $redirectOrder = $metaData['blockbee_payment_url'];

            $this->responseFactory->create()->setRedirect($redirectOrder)->sendResponse();
            return $this->redirect->setRedirect($redirectOrder);
        }
    }
}

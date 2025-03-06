<?php

namespace Blockbee\Blockbee\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Callback implements HttpGetActionInterface
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
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;

    public function __construct(
        \Blockbee\Blockbee\Helper\Data                     $helper,
        \Blockbee\Blockbee\Model\Method\BlockbeePayment    $payment,
        \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http                $request,
        \Magento\Framework\App\Response\Http               $response
    ) {
        $this->helper          = $helper;
        $this->payment         = $payment;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig     = $scopeConfig;
        $this->request         = $request;
        $this->response        = $response;
    }

    public function execute()
    {
        $data = $this->request->getParams();

        $order = $this->orderRepository->get($data['order_id']);
        $orderId = $order->getQuoteId();

        $currencySymbol = strtolower($order->getOrderCurrencyCode());
        $fiat_currency = $data['currency'];

        if ($currencySymbol !== $fiat_currency) {
            return $this->response->setBody("*ok*");
        }

        $metaData = json_decode($this->helper->getPaymentResponse($orderId), true);

        if ($this->payment->hasBeenPaid($order) || $data['nonce'] != $metaData['blockbee_nonce']) {
            return $this->response->setBody("*ok*");
        }

        $paid = floatval($data['paid_amount']);
        $paid_fiat = floatval($data['paid_amount_fiat']);
        $is_paid = $data['is_paid'];
        $history = json_decode($metaData['blockbee_history'], true);

        $update_history = true;

        foreach ($history as $txid => $item) {
            if ($txid === $data['txid']) {
                $update_history = false;
            }
        }

        if ($update_history) {
            $history[$data['txid']] = [
                'timestamp' => time(),
                'value_paid' => $paid,
                'value_paid_fiat' => $paid_fiat
            ];
        } else {
            $history[$data['txid']]['pending'] = $data['pending'];
        }

        $this->helper->updatePaymentData($orderId, 'blockbee_history', json_encode($history));

        if ($is_paid) {
            $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $order->setState($state);
            $order->setStatus($status);
            $order->setTotalPaid($order->getGrandTotal());
            $order->save();
            return $this->response->setBody("*ok*");
        }

        return $this->response->setBody("*ok*");
    }
}

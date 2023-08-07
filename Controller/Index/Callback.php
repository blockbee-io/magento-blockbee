<?php

namespace Blockbee\Blockbee\Controller\Index;

use Blockbee\Blockbee\lib\BlockbeeHelper;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Callback implements HttpGetActionInterface
{
    protected $helper;
    protected $payment;
    protected $orderFactory;

    public function __construct(
        \Blockbee\Blockbee\Helper\Data                     $helper,
        \Blockbee\Blockbee\Model\Method\BlockbeePayment    $payment,
        \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http                $request,
        \Magento\Framework\App\Response\Http               $response
    )
    {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->response = $response;
    }

    public function execute()
    {
        $params = $this->request->getParams();

        $data = BlockbeeHelper::process_callback($params);

        $order = $this->orderRepository->get($data['order_id']);
        $orderId = $order->getQuoteId();

        $currencySymbol = $order->getOrderCurrencyCode();

        $apiKey = $this->scopeConfig->getValue('payment/blockbee/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $metaData = json_decode($this->helper->getPaymentResponse($orderId), true);

        if ($data['coin'] !== $metaData['blockbee_currency']) {
            return $this->response->setBody("*ok*");
        }

        if ($this->payment->hasBeenPaid($order) || $data['nonce'] != $metaData['blockbee_nonce']) {
            return $this->response->setBody("*ok*");
        }

        $paid = floatval($data['value_coin']);

        $min_tx = floatval($metaData['blockbee_min']);

        $history = json_decode($metaData['blockbee_history'], true);

        $update_history = true;

        foreach ($history as $uuid => $item) {
            if ($uuid === $data['uuid']) {
                $update_history = false;
            }
        }

        if ($update_history) {
            $fiat_conversion = BlockbeeHelper::get_conversion($metaData['blockbee_currency'], $currencySymbol, $paid, $this->scopeConfig->getValue('payment/blockbee/disable_conversion', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), $apiKey);

            $history[$data['uuid']] = [
                'timestamp' => time(),
                'value_paid' => BlockbeeHelper::sig_fig($paid, 6),
                'value_paid_fiat' => $fiat_conversion,
                'pending' => $data['pending']
            ];
        } else {
            $history[$data['uuid']]['pending'] = $data['pending'];
        }

        $this->helper->updatePaymentData($orderId, 'blockbee_history', json_encode($history));

        $metaData = json_decode($this->helper->getPaymentResponse($orderId), true);

        $history = json_decode($metaData['blockbee_history'], true);

        $calc = $this->payment::calcOrder($history, $metaData);

        $remaining = $calc['remaining'];
        $remaining_pending = $calc['remaining_pending'];

        if ($remaining_pending <= 0) {
            if ($remaining <= 0) {
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $order->setState($state);
                $order->setStatus($status);
                $order->setTotalPaid($order->getGrandTotal());
                $order->save();
            }
            return $this->response->setBody("*ok*");
        }

        if ($remaining_pending <= $min_tx) {
            $this->helper->updatePaymentData($orderId, 'blockbee_qr_code_value', BlockbeeHelper::get_static_qrcode($metaData['blockbee_address'], $metaData['blockbee_currency'], $min_tx, $this->scopeConfig->getValue('payment/blockbee/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), $this->scopeConfig->getValue('payment/blockbee/qrcode_size', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))['qr_code']);
        } else {
            $this->helper->updatePaymentData($orderId, 'blockbee_qr_code_value', BlockbeeHelper::get_static_qrcode($metaData['blockbee_address'], $metaData['blockbee_currency'], $remaining_pending, $this->scopeConfig->getValue('payment/blockbee/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), $this->scopeConfig->getValue('payment/blockbee/qrcode_size', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))['qr_code']);
        }

        return $this->response->setBody("*ok*");
    }
}

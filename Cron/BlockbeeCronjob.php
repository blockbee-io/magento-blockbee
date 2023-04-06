<?php

namespace Blockbee\Blockbee\Cron;

use Blockbee\Blockbee\lib\BlockbeeHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Blockbee\Blockbee\Helper\Data;


class BlockbeeCronjob
{

    public function __construct(
        ScopeConfigInterface                            $scopeConfig,
        CollectionFactory                               $orderCollectionFactory,
        Data                                            $helper,
        \Magento\Sales\Api\OrderRepositoryInterface     $orderRepository,
        \Blockbee\Blockbee\Model\Method\BlockbeePayment $payment,
        \Psr\Log\LoggerInterface                        $logger
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        $this->payment = $payment;
        $this->logger = $logger;
    }

    public function execute()
    {
        $order_timeout = (int)$this->scopeConfig->getValue('payment/blockbee/order_cancelation_timeout');
        $value_refresh = (int)$this->scopeConfig->getValue('payment/blockbee/refresh_value_interval');

        if ($order_timeout === 0 && $value_refresh === 0) {
            return;
        }

        $orders = $this->getOrderCollectionPaymentMethod();

        if (empty($orders)) {
            return;
        }

        $apiKey = $this->scopeConfig->getValue('payment/blockbee/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $disable_conversion = $this->scopeConfig->getValue('payment/blockbee/disable_conversion');

        foreach ($orders as $order) {
            $orderQuoteId = $order->getQuoteId();

            $metaData = json_decode($this->helper->getPaymentResponse($order->getQuoteId()), true);

            $history = json_decode($metaData['blockbee_history'], true);

            $min_tx = floatval($metaData['blockbee_min']);

            $calc = $this->payment::calcOrder($history, $metaData);

            $remaining = $calc['remaining'];
            $remaining_pending = $calc['remaining_pending'];
            $already_paid = $calc['already_paid'];

            $qrcode_size = $this->scopeConfig->getValue('payment/blockbee/qrcode_size');

            if (!empty($metaData['blockbee_address']) && $value_refresh !== 0 && $metaData['blockbee_cancelled'] !== '1' && (int)$metaData['blockbee_last_price_update'] + $value_refresh <= time() && $remaining_pending > 0) {
                if ($remaining === $remaining_pending) {
                    $blockbee_coin = $metaData['blockbee_currency'];

                    $crypto_total = BlockbeeHelper::get_conversion($order->getOrderCurrencyCode(), $blockbee_coin, $metaData['blockbee_total_fiat'], $disable_conversion, $apiKey);
                    $this->helper->updatePaymentData($orderQuoteId, 'blockbee_total', $crypto_total);

                    $calc_cron = $this->payment::calcOrder($history, $metaData);
                    $crypto_remaining_total = $calc_cron['remaining_pending'];

                    if ($remaining_pending <= $min_tx && !$remaining_pending <= 0) {
                        $qr_code_data_value = BlockbeeHelper::get_static_qrcode($metaData['blockbee_address'], $blockbee_coin, $min_tx, $apiKey, $qrcode_size);
                    } else {
                        $qr_code_data_value = BlockbeeHelper::get_static_qrcode($metaData['blockbee_address'], $blockbee_coin, $crypto_remaining_total, $apiKey, $qrcode_size);
                    }

                    $this->helper->updatePaymentData($orderQuoteId, 'blockbee_qr_code_value', $qr_code_data_value['qr_code']);

                }

                $this->helper->updatePaymentData($orderQuoteId, 'blockbee_last_price_update', time());
            }

            if ($order_timeout !== 0 && ((int)strtotime($order->getCreatedAt()) + $order_timeout) <= time() && empty($metaData['blockbee_pending']) && $already_paid <= 0 && (string)$metaData['blockbee_cancelled'] === '0') {
                $state = \Magento\Sales\Model\Order::STATE_CANCELED;
                $status = \Magento\Sales\Model\Order::STATE_CANCELED;
                $order->setState($state);
                $order->setStatus($status);
                $this->helper->updatePaymentData($orderQuoteId, 'blockbee_cancelled', '1');
                $order->save();
            }
        }
    }

    private function getOrderCollectionPaymentMethod()
    {
        $orders = $this->orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('status',
                ['in' => ['pending']]
            );

        $orders->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method')
            )
            ->where('sop.method = ?', 'blockbee');

        $orders->setOrder(
            'created_at',
            'desc'
        );

        return $orders;
    }
}

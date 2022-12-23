<?php

namespace Blockbee\Blockbee\Block;

use Blockbee\Blockbee\lib\BlockbeeHelper;
use Magento\Framework\View\Element\Template;

class Payment extends Template
{
    protected $helper;
    protected $payment;

    public function __construct(
        \Blockbee\Blockbee\Helper\Data                     $helper,
        \Blockbee\Blockbee\Model\Method\BlockbeePayment    $payment,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Element\Template\Context   $context,
        \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository,
        \Magento\Framework\App\Request\Http                $request,
        \Magento\Framework\App\ProductMetadataInterface    $productMetadata,
        \Magento\Store\Model\StoreManagerInterface         $storeManager,
        \Blockbee\Blockbee\Helper\Mail                     $mail,
        array                                              $data = []
    )
    {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->payment = $payment;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->productMetadata = $productMetadata;
        $this->mail = $mail;
        $this->storeManager = $storeManager;
    }

    public function getTemplateValues()
    {

        if ($this->productMetadata->getVersion() >= 2.3 && $this->productMetadata->getVersion() < 2.4) {
            $order = $this->payment->getOrder();
        } else {
            $order_id = (int)$this->request->getParam('order_id');
            $nonce = (string)$this->request->getParam('nonce');
            $order = $this->orderRepository->get($order_id);
        }

        $total = $order->getGrandTotal();
        $currencySymbol = $order->getOrderCurrencyCode();
        $metaData = $this->helper->getPaymentResponse($order->getQuoteId());

        if (empty($metaData)) {
            throw new \Magento\Framework\Exception\AlreadyExistsException(
                        __('You can only add one address per cryptocurrency')
                    );
        }

        $qrCodeSize = $this->scopeConfig->getValue('payment/blockbee/qrcode_size', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $branding = $this->scopeConfig->getValue('payment/blockbee/show_branding', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $metaData = json_decode($metaData, true);

        if ($nonce != $metaData['blockbee_nonce']) {
            return false;
        }
        $cryptoValue = $metaData['blockbee_total'];
        $cryptoCoin = $metaData['blockbee_currency'];

        if (isset($metaData['blockbee_address']) && !empty($metaData['blockbee_address'])) {
            $addressIn = $metaData['blockbee_address'];
        } else {
            /**
             * Makes request to API and generates all the payment data needed
             */

            $selected = $cryptoCoin;

            $params = [
                'order_id' => $order->getId(),
                'nonce' => $metaData['blockbee_nonce'],
            ];

            $callbackUrl = $this->payment->getCallbackUrl();

            $apiKey = $this->scopeConfig->getValue('payment/blockbee/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $api = new BlockbeeHelper($selected, $apiKey, $callbackUrl, $params, true);
            $addressIn = $api->get_address();
            $qrCode = $api->get_qrcode('', $qrCodeSize);
            $qrCodeValue = $api->get_qrcode($cryptoValue, $qrCodeSize);
            $this->helper->updatePaymentData($order->getQuoteId(), 'blockbee_address', $addressIn);
            $this->helper->updatePaymentData($order->getQuoteId(), 'blockbee_qr_code_value', $qrCodeValue['qr_code']);
            $this->helper->updatePaymentData($order->getQuoteId(), 'blockbee_qr_code', $qrCode['qr_code']);
            $this->helper->updatePaymentData($order->getQuoteId(), 'blockbee_payment_url', $this->storeManager->getStore()->getUrl('blockbee/index/payment/order_id/' . $order->getId(). '/nonce/' . $metaData['blockbee_nonce']));

            $metaData = json_decode($this->helper->getPaymentResponse($order->getQuoteId()), true);
            $this->mail->sendMail($order, $metaData);
        }

        $ajaxParams = [
            'order_id' => $order->getId(),
        ];

        $ajaxUrl = $this->payment->getAjaxStatusUrl($ajaxParams);

        $metaData = $this->helper->getPaymentResponse($order->getQuoteId());
        $metaData = json_decode($metaData, true);

        return [
            'crypto_value' => floatval($cryptoValue),
            'currency_symbol' => $currencySymbol,
            'total' => $total,
            'address_in' => $addressIn,
            'crypto_coin' => $cryptoCoin,
            'ajax_url' => $ajaxUrl,
            'qrcode_size' => $qrCodeSize,
            'qrcode' => $metaData['blockbee_qr_code'],
            'qrcode_value' => $metaData['blockbee_qr_code_value'],
            'qrcode_default' => $this->scopeConfig->getValue('payment/blockbee/qrcode_default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'show_branding' => $branding,
            'qr_code_setting' => $this->scopeConfig->getValue('payment/blockbee/qrcode_setting', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'order_timestamp' => strtotime($order->getCreatedAt()),
            'order_cancelation_timeout' => $this->scopeConfig->getValue('payment/blockbee/order_cancelation_timeout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'refresh_value_interval' => $this->scopeConfig->getValue('payment/blockbee/refresh_value_interval', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'last_price_update' => $metaData['blockbee_last_price_update'],
            'min_tx' => $metaData['blockbee_min'],
        ];
    }
}

<?php

namespace Blockbee\Blockbee\Model\Method;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;
use Blockbee\Blockbee\lib\BlockbeeHelper;
use Magento\Payment\Model\Method\AbstractMethod;

class BlockbeePayment extends AbstractMethod
{
    protected $_code = 'blockbee';
    protected $customerSession;
    protected $orderFactory;
    protected $orderSession;
    protected $blockbeeHelper;
    protected $urlBuilder;

    public function __construct(
        \Blockbee\Blockbee\Helper\Data                          $blockbeeHelper,
        \Magento\Checkout\Model\Session                         $orderSession,
        \Magento\Sales\Model\OrderFactory                       $orderFactory,
        \Magento\Customer\Model\Session                         $customerSession,
        \Magento\Framework\UrlInterface                         $urlBuilder,
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory       $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory            $customAttributeFactory,
        \Magento\Payment\Helper\Data                            $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface              $storeManager,
        \Magento\Payment\Model\Method\Logger                    $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = [],
        \Magento\Directory\Helper\Data                          $directory = null
    )
    {
        $this->blockbeeHelper = $blockbeeHelper;
        $this->orderSession = $orderSession;
        $this->orderFactory = $orderFactory;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;


        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
    }

    public function getConfigValue($key)
    {
        $pathConfig = 'payment/' . $this->_code . "/" . $key;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->_scopeConfig->getValue($pathConfig, $storeScope);
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        /** @var DataObject $info */
        $info = $this->getInfoInstance();
        $info->setAdditionalInformation(
            'blockbee_coin',
            $additionalData->getBlockbeeCoin()
        );

        return $this;
    }

    public function validate()
    {
        $quote = $this->getQuote();

        $paymentInfo = $this->getInfoInstance();

        $selected = $paymentInfo->getAdditionalInformation('blockbee_coin');

        if (empty($selected)) {
            return $this;
        }

        $apiKey = $this->scopeConfig->getValue('payment/blockbee/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $nonce = $this->generateNonce();
        $info = BlockbeeHelper::get_info($selected, false, $apiKey);

        $minTx = floatval($info->minimum_transaction_coin);

        $currencyCode = $quote->getQuoteCurrencyCode();

        $total = $quote->getGrandTotal();

        $cryptoTotal = BlockbeeHelper::get_conversion(
            $currencyCode,
            $selected,
            $total,
            $this->scopeConfig->getValue('payment/blockbee/disable_conversion', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            $apiKey
        );

        if ($cryptoTotal < $minTx) {
            $message = 'Payment error: Value too low, minimum is';
            $message .= ' ' . $minTx . ' ' . strtoupper($selected);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($message)
            );
        }

        $paymentData = [
            'blockbee_nonce' => $nonce,
            'blockbee_address' => '',
            'blockbee_total' => $cryptoTotal,
            'blockbee_total_fiat' => $total,
            'blockbee_currency' => $selected,
            'blockbee_history' => json_encode([]),
            'blockbee_cancelled' => '0',
            'blockbee_last_price_update' => time(),
            'blockbee_min' => $minTx,
            'blockbee_qr_code_value' => '',
            'blockbee_qr_code' => '',
            'blockbee_payment_url' => ''
        ];

        $paymentData = json_encode($paymentData);

        $this->blockbeeHelper->addPaymentResponse($quote->getId(), $paymentData);

        return $this;
    }

    public function getCheckout()
    {
        return $this->orderSession;
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getOrder()
    {
        return $this->getCheckout()->getLastRealOrder();
    }

    public function getCallbackUrl($params = [])
    {
        return $this->urlBuilder->getUrl('blockbee/index/callback', $params);
    }

    public function getAjaxStatusUrl($params = [])
    {
        return $this->urlBuilder->getUrl('blockbee/index/status', $params);
    }

    public function hasBeenPaid($order)
    {
        if ($order->getTotalPaid() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function calcOrder($history, $meta)
    {
        $already_paid = 0;
        $already_paid_fiat = 0;
        $remaining = $meta['blockbee_total'];
        $remaining_pending = $meta['blockbee_total'];
        $remaining_fiat = $meta['blockbee_total_fiat'];

        if (!empty($history)) {
            foreach ($history as $uuid => $item) {
                if ((int)$item['pending'] === 0) {
                    $remaining = bcsub(BlockbeeHelper::sig_fig($remaining, 6), $item['value_paid'], 8);
                }

                $remaining_pending = bcsub(BlockbeeHelper::sig_fig($remaining_pending, 6), $item['value_paid'], 8);
                $remaining_fiat = bcsub(BlockbeeHelper::sig_fig($remaining_fiat, 6), $item['value_paid_fiat'], 8);

                $already_paid = bcadd(BlockbeeHelper::sig_fig($already_paid, 6), $item['value_paid'], 8);
                $already_paid_fiat = bcadd(BlockbeeHelper::sig_fig($already_paid_fiat, 6), $item['value_paid_fiat'], 8);
            }
        }

        return [
            'already_paid' => floatval($already_paid),
            'already_paid_fiat' => floatval($already_paid_fiat),
            'remaining' => floatval($remaining),
            'remaining_pending' => floatval($remaining_pending),
            'remaining_fiat' => floatval($remaining_fiat)
        ];
    }

    public function generateNonce($len = 32)
    {
        $data = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $nonce = [];
        for ($i = 0; $i < $len; $i++) {
            $nonce[] = $data[random_int(0, count($data) - 1)];
        }

        return implode('', $nonce);
    }
}

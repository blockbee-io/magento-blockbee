<?php

namespace Blockbee\Blockbee\Model\Method;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\AbstractMethod;

class BlockbeePayment extends AbstractMethod
{
    protected $_code = 'blockbee';
    protected $customerSession;
    protected $orderFactory;
    protected $orderSession;
    protected $blockbeeHelper;
    protected $blockbeeLib;
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

        $nonce = $this->generateNonce();

        $currencyCode = $quote->getQuoteCurrencyCode();

        $total = $quote->getGrandTotal();

        # Config
        $apiKey = $this->getConfigValue('api_key');
        $expireAt = (int)$this->getConfigValue('order_cancelation_timeout');

        $bbParams = [
            'currency' => $currencyCode,
            'item_description' => '#' . $quote->getId()
        ];

        if ($expireAt > 0) {
            $bbParams['expire_at'] = time() + $expireAt;
        }

        $api = new \Blockbee\Blockbee\lib\BlockbeeHelper($apiKey, [
            'order_id' => $quote->getId(),
            'nonce' => $nonce,
        ], $bbParams);

        $callbackUrl = $this->getCallbackUrl();

        $requestPayment = $api->payment_request(
            $this->storeManager->getStore()->getUrl(),
            $callbackUrl,
            $total
        );

        $paymentData = [
            'blockbee_nonce' => $nonce,
            'blockbee_success_token' => $requestPayment->success_token,
            'blockbee_payment_id' => $requestPayment->payment_id,
            'blockbee_total_fiat' => $total,
            'blockbee_fiat' => $currencyCode,
            'blockbee_history' => json_encode([]),
            'blockbee_payment_url' => $requestPayment->payment_url
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

    public function hasBeenPaid($order)
    {
        if ($order->getTotalPaid() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getOrderPlaceRedirectUrl()
    {
        $quote = $this->getQuote();

        $paymentData = json_decode($this->blockbeeHelper->getPaymentResponse($quote->getId()), true);

        if (!empty($paymentData['blockbee_payment_url'])) {
            return $paymentData['blockbee_payment_url'];
        }

        return '';
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

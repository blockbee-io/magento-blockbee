<?php

namespace Blockbee\Blockbee\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Blockbee\Blockbee\lib\BlockbeeHelper;

class BlockbeeConfigProvider implements ConfigProviderInterface
{
    const CODE = 'blockbee';
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        PaymentHelper                                      $paymentHelper,
        Escaper                                            $escaper,
        \Magento\Framework\App\CacheInterface              $cache,
        \Magento\Framework\Serialize\SerializerInterface   $serializer,
        \Blockbee\Blockbee\Model\Method\BlockbeePayment    $payment,
        \Psr\Log\LoggerInterface                           $logger
    )
    {
        $this->escaper = $escaper;
        $this->scopeConfig = $scopeConfig;
        $this->paymentHelper = $paymentHelper;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->payment = $payment;
        $this->logger = $logger;
    }

    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => [
                    'cryptocurrencies' => $this->getCryptocurrencies(),
                    'instructions' => $this->getInstructions(),
                ]
            ]
        ];
    }

    public function getInstructions(): \Magento\Framework\Phrase
    {
        return __('Pay with cryptocurrency');
    }

    public function getCryptocurrencies(): array
    {
        $cacheKey = \Blockbee\Blockbee\Model\Cache\Type::TYPE_IDENTIFIER;
        $cacheTag = \Blockbee\Blockbee\Model\Cache\Type::CACHE_TAG;

        if (empty($this->cache->load($cacheKey)) || !$this->serializer->unserialize($this->cache->load($cacheKey))) {
            $this->cache->save(
                $this->serializer->serialize($this->serializer->serialize(BlockbeeHelper::get_supported_coins())),
                $cacheKey,
                [$cacheTag],
                86400
            );
        }

        $selected = explode(',', $this->scopeConfig->getValue('payment/blockbee/cryptocurrencies', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

        $apiKey = $this->scopeConfig->getValue('payment/blockbee/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $available_cryptos = json_decode($this->serializer->unserialize($this->cache->load($cacheKey)));

        $output = [];

        if (!empty($selected) && !empty($apiKey)) { // Check for API Key / Address configuration. Prevents unexpected errors.
            foreach ($available_cryptos as $ticker => $coin) {
                foreach ($selected as $uuid => $data) {
                    if ($ticker === $data) {
                        $output[] = [
                            'value' => $data,
                            'type' => $coin,
                        ];
                    }
                }
            }
        }

        return $output;
    }
}

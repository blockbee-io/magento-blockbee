<?php

namespace Blockbee\Blockbee\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Blockbee\Blockbee\Model\Method\BlockbeePayment;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;

class BlockbeeConfigProvider implements ConfigProviderInterface
{
    const CODE = 'blockbee';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var BlockbeePayment
     */
    protected $payment;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PaymentHelper $paymentHelper,
        CacheInterface $cache,
        SerializerInterface $serializer,
        BlockbeePayment $payment,
        LoggerInterface $logger
    ) {
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
                    'instructions' => $this->getInstructions(),
                ]
            ]
        ];
    }

    public function getInstructions(): Phrase
    {
        return __('Pay with cryptocurrency');
    }
}

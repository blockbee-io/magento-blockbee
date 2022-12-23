<?php

namespace Blockbee\Blockbee\Model\Config\Source;

use Blockbee\Blockbee\lib\BlockbeeHelper;


class Cryptocurrencies implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        $options = [];
        foreach (BlockbeeHelper::get_supported_coins() as $ticker => $coin) {
            $options[] = [
                'value' => $ticker,
                'label' => $coin
            ];
        }

        return $options;
    }
}

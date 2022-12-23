<?php

namespace Blockbee\Blockbee\Block\Adminhtml\Form\Field;

use Blockbee\Blockbee\lib\BlockbeeHelper;
use Magento\Framework\View\Element\Html\Select;

class CryptocurrenciesSelect extends Select
{
    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    private function getSourceOptions(): array
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

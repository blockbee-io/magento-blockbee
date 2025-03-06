<?php

namespace Blockbee\Blockbee\Model\Config;

class ConfigPlugin
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    private $sortOrderArray = [];

    /**
     * Construct.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $this->request = $request;
    }

    /**
     * added validation for sort order around save action
     *
     * @param \Magento\Config\Model\Config $subject
     * @param \Closure $proceed
     *
     * @return void
     */
    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure                     $proceed
    )
    {
        $requestData = $this->request->getParams();

        $configSection = $requestData["section"] ?? "";
        if ($configSection == "payment") {
            $groups = $requestData["groups"] ?? [];
            if (!empty($groups)) {
                $apiKey = $groups["blockbee"]["fields"]["api_key"]["value"];

                if (empty($apiKey)) {
                    throw new \Magento\Framework\Exception\AlreadyExistsException(
                        __("Please make sure you API Key.")
                    );
                }
            }
        }

        return $proceed();
    }

}

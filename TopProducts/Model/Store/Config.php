<?php

namespace AmeshExtensions\TopProducts\Model\Store;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class to provide the system configuration of module
 */
class Config
{
    /**
     * Config path for the status configuration
     */
    private const XML_PATH_STATUS = 'top_products/category_listing/status';

    /**
     * Config path for the limit configuration
     */
    private const XML_PATH_LIMIT = 'top_products/category_listing/limit';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Config construct function
     *
     * @param ScopeConfigInterface $scopeConfig
     * @return void
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get if the module is enabled or not.
     *
     * @return mixed
     */
    public function isEnabled(): mixed
    {
        return $this->scopeConfig->getValue(self::XML_PATH_STATUS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the limit of items to display in front-end
     *
     * @return mixed
     */
    public function getLimit(): mixed
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LIMIT, ScopeInterface::SCOPE_STORE);
    }
}

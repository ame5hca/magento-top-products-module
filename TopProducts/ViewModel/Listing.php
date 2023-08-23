<?php

namespace AmeshExtensions\TopProducts\ViewModel;

use AmeshExtensions\TopProducts\Model\Store\Config;
use AmeshExtensions\TopProducts\Model\TopProducts;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class for the top products list front-end display
 */
class Listing implements ArgumentInterface
{
    /**
     * @var Config
     */
    private Config $configProvider;

    /**
     * @var TopProducts
     */
    private TopProducts $topProducts;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * Listing construct function
     *
     * @param Config $configProvider
     * @param TopProducts $topProducts
     * @param RequestInterface $request
     * @return void
     */
    public function __construct(
        Config $configProvider,
        TopProducts $topProducts,
        RequestInterface        $request
    ) {
        $this->configProvider = $configProvider;
        $this->topProducts = $topProducts;
        $this->request = $request;
    }

    /**
     * Get the top products list
     *
     * @return Collection|null
     */
    public function getTopProducts(): ?Collection
    {
        $categoryId = $this->request->getParam('id', null);
        $limit = $this->configProvider->getLimit();
        return $this->topProducts->getList(
            $categoryId,
            ($limit ? $limit : TopProducts::DEFAULT_LIMIT)
        );
    }

    /**
     * Show or hide the listing
     *
     * @return mixed
     */
    public function showListing(): mixed
    {
        $pageNo = $this->request->getParam('p', 0);
        if ($pageNo > 1) {
            return false;
        }
        return $this->configProvider->isEnabled();
    }
    
    /**
     * Get the current category name
     *
     * @return string
     */
    public function getCurrentCategoryName(): string
    {
        $categoryId = $this->request->getParam('id', 0);
        return $this->topProducts->getCategoryName($categoryId);
    }
}

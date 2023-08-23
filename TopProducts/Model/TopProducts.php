<?php

namespace AmeshExtensions\TopProducts\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Reports\Model\ResourceModel\Product\CollectionFactory as MostViewedProductsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory as BestSellerCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Class to provide the top products list.
 */
class TopProducts
{
    /**
     * Default products display limit
     */
    public const DEFAULT_LIMIT = 5;

    /**
     * @var BestSellerCollectionFactory
     */
    protected BestSellerCollectionFactory $bestSellerCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var MostViewedProductsCollectionFactory
     */
    protected MostViewedProductsCollectionFactory $mostViewedProductCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected ProductCollectionFactory $productCollectionFactory;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;
    
    /**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * TopProducts construct function
     *
     * @param BestSellerCollectionFactory $bestSellerCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param MostViewedProductsCollectionFactory $mostViewedProductCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @return void
     */
    public function __construct(
        BestSellerCollectionFactory         $bestSellerCollectionFactory,
        StoreManagerInterface               $storeManager,
        MostViewedProductsCollectionFactory $mostViewedProductCollectionFactory,
        ProductCollectionFactory            $productCollectionFactory,
        CategoryCollectionFactory           $categoryCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->bestSellerCollectionFactory = $bestSellerCollectionFactory;
        $this->storeManager = $storeManager;
        $this->mostViewedProductCollectionFactory = $mostViewedProductCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Get the top products list.
     *
     * Logic of getting the top products is, if there is best-seller products available in the category
     * then that products will be listed in front-end and if there is no best-seller products/count is less than
     * the limit, then the rest of the count of items are taken from the most viewed products list.
     *
     * @param null|int|string $categoryId
     * @param int $limit
     * @return Collection|null
     */
    public function getList($categoryId = null, $limit = self::DEFAULT_LIMIT): ?Collection
    {
        $mostViewedProductsIds = [];
        try {
            $bestSellerProductIds = $this->getBestSellingProducts($categoryId, $limit);
            $bestSellerCount = count($bestSellerProductIds);
            if ($bestSellerCount < $limit) {
                $mostViewedLimit = $limit - $bestSellerCount;
                $mostViewedProductsIds = $this->getMostViewedProducts($mostViewedLimit);
            }
            $topProductIds = array_merge($bestSellerProductIds, $mostViewedProductsIds);
            return $this->getProducts($topProductIds, $categoryId);
        } catch (\Exception $ex) {
            $this->logger->info('TopProductError : ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * Get the products objects
     *
     * @param mixed[] $productIds
     * @param null|int|string $categoryId
     * @return Collection
     */
    protected function getProducts($productIds, $categoryId): Collection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
        if ($categoryId != null) {
            $collection->addCategoriesFilter(['in' => [$categoryId]]);
        }
        return $collection;
    }

    /**
     * Get the best seller products ids
     *
     * @param null|int|string $categoryId
     * @param int $limit
     * @return array<mixed>
     * @throws NoSuchEntityException
     */
    protected function getBestSellingProducts($categoryId, $limit): array
    {
        $productIds = [];
        $collection = $this->bestSellerCollectionFactory->create();
        $collection->setModel(Product::class);
        $collection->setPeriod('month');
        $collection->setPageSize($limit);
        $collection->setCurPage(1);
        $collection->addStoreFilter($this->storeManager->getStore()->getId());
        if ($categoryId != null) {
            $collection->getSelect()->joinLeft(
                ['cat' => 'catalog_category_product'],
                'cat.product_id = sales_bestsellers_aggregated_monthly.product_id',
                []
            );
            $collection->getSelect()->where(
                $collection->getConnection()->prepareSqlCondition('cat.category_id', ['eq' => $categoryId])
            );
        }
        foreach ($collection as $item) {
            $productIds[] = $item->getProductId();
        }
        return $productIds;
    }

    /**
     * Get the most viewed products ids
     *
     * @param int $limit
     * @return array<mixed>
     * @throws NoSuchEntityException
     */
    protected function getMostViewedProducts($limit): array
    {
        $productIds = [];
        $storeId = $this->storeManager->getStore()->getId();
        $collection = $this->mostViewedProductCollectionFactory->create();
        $collection->addAttributeToSelect('product_id');
        $collection->addViewsCount();
        $collection->setStoreId($storeId)->addStoreFilter($storeId);
        $collection->setPageSize($limit);
        $collection->setCurPage(1);

        $items = $collection->getItems();
        if (empty($items)) {
            return $productIds;
        }
        foreach ($items as $item) {
            $productIds[] = $item->getId();
        }
        return $productIds;
    }

    /**
     * Get the category name
     *
     * @param int|string $categoryId
     * @return string
     */
    public function getCategoryName($categoryId): string
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToFilter('entity_id', ['eq' => $categoryId]);
        if ($collection->getSize()) {
            return $collection->getFirstItem()->getName();
        }
        return '';
    }
}

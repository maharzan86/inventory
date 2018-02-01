<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Controller\Adminhtml\Stock;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Inventory\Model\StockSourceLink;
use Magento\Inventory\Model\StockSourceLinkFactory;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\StockSourceLinksDeleteInterface;
use Magento\InventoryApi\Api\StockSourceLinksSaveInterface;

/**
 * At the time of processing Stock save form this class used to save links correctly
 * Performs replace strategy of sources for the stock
 */
class StockSourceLinkProcessor
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var StockSourceLinkFactory
     */
    private $stockSourceLinkFactory;

    /**
     * @var StockSourceLinksSaveInterface
     */
    private $stockSourceLinksSave;

    /**
     * @var StockSourceLinksDeleteInterface
     */
    private $stockSourceLinksDelete;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StockSourceLinkFactory $stockSourceLinkFactory
     * @param StockSourceLinksSaveInterface $stockSourceLinksSave
     * @param StockSourceLinksDeleteInterface $stockSourceLinksDelete
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StockSourceLinkFactory $stockSourceLinkFactory,
        StockSourceLinksSaveInterface $stockSourceLinksSave,
        StockSourceLinksDeleteInterface $stockSourceLinksDelete,
        GetStockSourceLinksInterface $getStockSourceLinks,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stockSourceLinkFactory = $stockSourceLinkFactory;
        $this->stockSourceLinksSave = $stockSourceLinksSave;
        $this->stockSourceLinksDelete = $stockSourceLinksDelete;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param int $stockId
     * @param array $linksData
     * @return void
     * @throws InputException
     */
    public function process(int $stockId, array $linksData)
    {
        $this->validateStockSourceData($linksData);

        $linksForDelete = $this->getAssignedLinks($stockId);
        $linksForSave = [];

        foreach ($linksData as $linkData) {
            $sourceCode = $linkData[StockSourceLink::SOURCE_CODE];

            if (isset($linksForDelete[$sourceCode])) {
                $link = $linksForDelete[$sourceCode];
            } else {
                /** @var StockSourceLinkInterface $link */
                $link = $this->stockSourceLinkFactory->create();
            }

            $linkData[StockSourceLink::STOCK_ID] = $stockId;
            $this->dataObjectHelper->populateWithArray($link, $linkData, StockSourceLinkInterface::class);

            $linksForSave[] = $link;
            unset($linksForDelete[$sourceCode]);
        }

        if (count($linksForSave) > 0) {
            $this->stockSourceLinksSave->execute($linksForSave);
        }
        if (count($linksForDelete) > 0) {
            $this->stockSourceLinksDelete->execute($linksForDelete);
        }
    }

    /**
     * @param array $stockSourceLinksData
     * @return void
     * @throws InputException
     */
    private function validateStockSourceData(array $stockSourceLinksData)
    {
        foreach ($stockSourceLinksData as $stockSourceLinkData) {
            if (!isset($stockSourceLinkData[StockSourceLink::SOURCE_CODE])) {
                throw new InputException(__('Wrong Stock to Source relation parameters given.'));
            }
        }
    }

    /**
     * Retrieves links that are assigned to $stockId
     *
     * @param int $stockId
     * @return StockSourceLinkInterface[]
     */
    private function getAssignedLinks(int $stockId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->create();

        $result = [];
        foreach ($this->getStockSourceLinks->execute($searchCriteria)->getItems() as $link) {
            $result[$link->getSourceCode()] = $link;
        }
        return $result;
    }
}

<?php

namespace Intaro\RetailCrm\Icml;

use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Repository\CatalogRepository;
use Intaro\RetailCrm\Repository\FileRepository;
use Intaro\RetailCrm\Repository\HlRepository;
use Intaro\RetailCrm\Repository\MeasureRepository;
use Intaro\RetailCrm\Repository\SiteRepository;

/**
 * Class XmlOfferFactory
 * @package Intaro\RetailCrm\Icml
 */
class XmlOfferFactory
{
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup
     */
    private $setup;
    
    /**
     * @var \Intaro\RetailCrm\Repository\FileRepository
     */
    private $fileRepository;
    
    /**
     * @var \Intaro\RetailCrm\Icml\QueryParamsMolder
     */
    private $builder;
    
    /**
     * @var \Intaro\RetailCrm\Repository\CatalogRepository
     */
    private $catalogRepository;
    
    /**
     * @var \Intaro\RetailCrm\Icml\XmlOfferBuilder
     */
    private $xmlOfferBuilder;
    
    /**
     * XmlOfferFactory constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     */
    public function __construct(XmlSetup $setup)
    {
        $this->setup = $setup;
        $this->fileRepository = new FileRepository(SiteRepository::getDefaultServerName());
        $this->builder = new QueryParamsMolder();
        $this->catalogRepository = new CatalogRepository();
        $this->xmlOfferBuilder = new XmlOfferBuilder(
            $setup,
            MeasureRepository::getMeasures(),
            SiteRepository::getDefaultServerName()
        );
    }
    
    /**
     * Возвращает страницу (массив) с товарами или торговыми предложениями (в зависимости от $param)
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $param
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @return XmlOffer[]
     */
    public function getXmlOffersPart(SelectParams $param, CatalogIblockInfo $catalogIblockInfo): array
    {
        $where         = $this->builder->getWhereForOfferPart($param->parentId, $catalogIblockInfo);
        $ciBlockResult = $this->catalogRepository->getProductPage(
            $where,
            array_merge($param->configurable, $param->main),
            $param->nPageSize,
            $param->pageNumber
        );
        
        $barcodes =  $this->catalogRepository->getProductBarcodesByIblockId($catalogIblockInfo->productIblockId);
        $products = [];
        
        while ($product = $ciBlockResult->GetNext()) {
            $this->setXmlOfferParams($param, $product, $catalogIblockInfo, $barcodes);
            $this->xmlOfferBuilder->createXmlOffer();
            $this->xmlOfferBuilder->addDataFromItem(
                $product,
                $this->catalogRepository->getProductCategoriesIds($product['ID'])
            );
            $products[] = $this->xmlOfferBuilder->getXmlOffer();
        }
        
        return $products;
    }
    
    /**
     * возвращает массив XmlOffers для конкретного продукта
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $paramsForOffer
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer          $product
     * @return XmlOffer[]
     */
    public function getXmlOffersBySingleProduct(
        SelectParams $paramsForOffer,
        CatalogIblockInfo $catalogIblockInfo,
        XmlOffer $product
    ): array {
        $xmlOffers = $this->getXmlOffersPart($paramsForOffer, $catalogIblockInfo);
        
        return $this->xmlOfferBuilder->addProductInfo($xmlOffers, $product);
    }
    
    /**
     * Получение настраиваемых параметров, если они лежат в HL-блоке
     *
     * @param int   $iblockId //ID инфоблока товаров, даже если данные нужны по SKU
     * @param array $productProps
     * @param array $configurableParams
     * @param array $hls
     * @return array
     */
    private function getHlParams(int $iblockId, array $productProps, array $configurableParams, array $hls): array
    {
        $params = [];
        
        foreach ($hls as $hlName => $hlBlockProduct) {
            if (isset($hlBlockProduct[$iblockId])) {
                reset($hlBlockProduct[$iblockId]);
                $firstKey     = key($hlBlockProduct[$iblockId]);
                $hlRepository = new HlRepository($hlName);
                
                if ($hlRepository->getHl() === null) {
                    continue;
                }

                $result = $hlRepository->getDataByXmlId($productProps[$configurableParams[$firstKey] . '_VALUE']);
                
                if ($result === null) {
                    continue;
                }
                
                foreach ($hlBlockProduct[$iblockId] as $hlPropCodeKey => $hlPropCode) {
                    if (isset($result[$hlPropCode])) {
                        $params[$hlPropCodeKey] = $result[$hlPropCode];
                    }
                }
            }
        }
        
        return $params;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $param
     * @param array                                                $product
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @param array                                                $barcodes
     */
    private function setXmlOfferParams(
        SelectParams $param,
        array $product,
        CatalogIblockInfo $catalogIblockInfo,
        array $barcodes
    ): void {
        if ($param->parentId === null) {
            $pictureProperty = $this->setup->properties->products->pictures[$catalogIblockInfo->productIblockId];
        } else {
            $pictureProperty = $this->setup->properties->sku->pictures[$catalogIblockInfo->productIblockId];
        }
    
        //достаем значения из HL блоков товаров
        $this->xmlOfferBuilder->setProductHlParams($this->getHlParams(
            $catalogIblockInfo->productIblockId,
            $product,
            $param->configurable,
            $this->setup->properties->highloadblockProduct
        ));
    
        //достаем значения из HL блоков торговых предложений
        $this->xmlOfferBuilder->setSkuHlParams($this->getHlParams(
            $catalogIblockInfo->productIblockId,
            $product,
            $param->configurable,
            $this->setup->properties->highloadblockSku
        ));
        $this->xmlOfferBuilder->setSelectParams($param);
        $this->xmlOfferBuilder->setOfferProps($product);
        $this->xmlOfferBuilder->setBarcode($barcodes[$product['ID']] ?? '');
        $this->xmlOfferBuilder->setCatalogIblockInfo($catalogIblockInfo);
        $this->xmlOfferBuilder->setPicturesPath(
            $this
                ->fileRepository
                ->getProductPicture($product, $pictureProperty ?? '')
        );
    }
}
<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Controller\Loyalty
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Exception;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\LoyaltyService;

/**
 * Class AdminPanel
 * @package Intaro\RetailCrm\Controller\Loyalty
 */
class Basket extends Controller
{
    /** @var LoyaltyService */
    private $service;
    
    /**
     * AdminPanel constructor.
     *
     * @param \Bitrix\Main\Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->service = ServiceLocator::get(LoyaltyService::class);
        parent::__construct($request);
    }
    
    /**
     * @param array $basketData
     * @return array
     */
    public function calculateBasketBonusesAction(array $basketData): array
    {
        $calculateBasket = [];
        $calculate = $this->service->calculateBonus($basketData['BASKET_ITEM_RENDER_DATA']);
        
        if ($calculate->success) {
            $calculateBasket = $this->service->calculateBasket($basketData, $calculate);
        }
    
        return $calculateBasket;
    }
}

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */

/** @var array $arResult */

use Bitrix\Currency\CurrencyLangTable;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Component\Builder\Api\CustomerBuilder;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\LoyaltyService;

try {
    Main\Loader::includeModule('intaro.retailcrm');
} catch (Main\LoaderException $exception) {
    AddMessage2Log($exception->getMessage());
}

$contragentsTypes = ConfigProvider::getContragentTypes();
$key = array_search('individual', $contragentsTypes, true);

$defaultParams = [
    'TEMPLATE_THEME' => 'blue',
];
$arParams      = array_merge($defaultParams, $arParams);
unset($defaultParams);

$arParams['TEMPLATE_THEME'] = (string)($arParams['TEMPLATE_THEME']);

if ('' !== $arParams['TEMPLATE_THEME']) {
    $arParams['TEMPLATE_THEME'] = preg_replace('/[^a-zA-Z0-9_\-\(\)\!]/', '', $arParams['TEMPLATE_THEME']);
    
    if ('site' === $arParams['TEMPLATE_THEME']) {
        $templateId                 = (string)Main\Config\Option::get('main', 'wizard_template_id', 'eshop_bootstrap', SITE_ID);
        $templateId                 = (0 === strpos($templateId, "eshop_adapt")) ? 'eshop_adapt' : $templateId;
        $arParams['TEMPLATE_THEME'] = (string)Main\Config\Option::get('main', 'wizard_' . $templateId . '_theme_id', 'blue', SITE_ID);
    }
    
    if (('' !== $arParams['TEMPLATE_THEME']) && !is_file($_SERVER['DOCUMENT_ROOT'] . $this->GetFolder() . '/themes/' . $arParams['TEMPLATE_THEME'] . '/style.css')) {
        $arParams['TEMPLATE_THEME'] = '';
    }
}

if ('' === $arParams['TEMPLATE_THEME']) {
    $arParams['TEMPLATE_THEME'] = 'blue';
}


$arResult['LOYALTY_STATUS']          = ConfigProvider::getLoyaltyProgramStatus();
/* @var LoyaltyService $service*/
$service   = ServiceLocator::get(LoyaltyService::class);
$arResult['PERSONAL_LOYALTY_STATUS'] = $service::getLoyaltyPersonalStatus();

if ($arResult['LOYALTY_STATUS'] === 'Y' && $arResult['PERSONAL_LOYALTY_STATUS'] === true) {
   
    $discountPercent = round($arResult['DISCOUNT_PRICE_ALL']/($arResult['allSum']/100), 0);
    $calculate = $service->calculateBonus($arResult['BASKET_ITEM_RENDER_DATA'], $arResult['DISCOUNT_PRICE_ALL'], $discountPercent);

    
    if ($calculate->success) {
        $arResult['AVAILABLE_BONUSES']    = $calculate->order->bonusesChargeTotal;
        $arResult['TOTAL_BONUSES_COUNT']  = $calculate->order->loyaltyAccount->amount;
        $arResult['LP_CALCULATE_SUCCESS'] = $calculate->success;
        $arResult['WILL_BE_CREDITED']     = $calculate->order->bonusesCreditTotal;
    }
    
    $component = $this->__component;
    
    try {
        $currency = CurrencyLangTable::query()
            ->setSelect(['FORMAT_STRING'])
            ->where([
                ['CURRENCY', '=', ConfigProvider::getCurrencyOrDefault()],
                ['LID', '=', 'LANGUAGE_ID'],
            ])
            ->fetch();
    } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
        AddMessage2Log($exception->getMessage());
    }
    
    $arResult['BONUS_CURRENCY'] = $currency['FORMAT_STRING'];
}
<?php
IncludeModuleLangFile(__FILE__);
class ICrmOrderActions
{
    protected static $MODULE_ID = 'intaro.intarocrm';
    protected static $CRM_API_HOST_OPTION = 'api_host';
    protected static $CRM_API_KEY_OPTION = 'api_key';
    protected static $CRM_ORDER_TYPES_ARR = 'order_types_arr';
    protected static $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    protected static $CRM_PAYMENT_TYPES = 'pay_types_arr';
    protected static $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    protected static $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
    protected static $CRM_ORDER_LAST_ID = 'order_last_id';
    protected static $CRM_ORDER_SITES = 'sites_ids';
    protected static $CRM_ORDER_PROPS = 'order_props';
    protected static $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    protected static $CRM_ORDER_HISTORY_DATE = 'order_history_date';
    protected static $CRM_MULTISHIP_INTEGRATION_CODE = 'multiship';
    protected static $MUTLISHIP_DELIVERY_TYPE = 'mlsp';
    protected static $MULTISHIP_MODULE_VER = 'multiship.v2';

    const CANCEL_PROPERTY_CODE = 'INTAROCRM_IS_CANCELED';

    /**
     * Mass order uploading, without repeating; always returns true, but writes error log
     * @param $pSize
     * @param $failed -- flag to export failed orders
     * @return boolean
     */
    public static function uploadOrders($pSize = 50, $failed = false) {

        // COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, 0); // -- for test

        if (!CModule::IncludeModule("iblock")) {
            //handle err
            self::eventLog('ICrmOrderActions::uploadOrders', 'iblock', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("sale")) {
            //handle err
            self::eventLog('ICrmOrderActions::uploadOrders', 'sale', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("catalog")) {
            //handle err
            self::eventLog('ICrmOrderActions::uploadOrders', 'catalog', 'module not found');
            return true;
        }

        $resOrders = array();
        $resCustomers = array();

        $lastUpOrderId = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, 0);
        $lastOrderId = 0;

        $failedIds = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, 0));
        if (!$failedIds)
            $failedIds = array();

        $dbOrder = CSaleOrder::GetList(array("ID" => "ASC"), array('>ID' => $lastUpOrderId));
        $dbFailedOrder = CSaleOrder::GetList(array("ID" => "ASC"), array('ID' => $failedIds));

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        //saved cat params
        $optionsOrderTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0));
        $optionsDelivTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0));
        $optionsPayTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0));
        $optionsPayStatuses = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0)); // --statuses
        $optionsPayment = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0));
        $optionsSites = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_SITES, 0));
        $optionsOrderProps = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_PROPS, 0));

        $api = new IntaroCrm\RestApi($api_host, $api_key);

        $arParams = array(
            'optionsOrderTypes' => $optionsOrderTypes,
            'optionsDelivTypes' => $optionsDelivTypes,
            'optionsPayTypes' => $optionsPayTypes,
            'optionsPayStatuses' => $optionsPayStatuses,
            'optionsPayment' => $optionsPayment,
            'optionSites' => $optionsSites,
            'optionsOrderProps' => $optionsOrderProps
        );

        if (!$failed) {

            //packmode

            $orderCount = 0;

            while ($arOrder = $dbOrder->GetNext()) { // here orders by id asc
                if (is_array($optionsSites))
                    if (!empty($optionsSites))
                        if (!in_array($arOrder['LID'], $optionsSites))
                            continue;

                $result = self::orderCreate($arOrder, $api, $arParams);

                if (!$result['order'] || !$result['customer'])
                    continue;

                $orderCount++;

                $resOrders[] = $result['order'];
                $resCustomers[] = $result['customer'];

                $lastOrderId = $arOrder['ID'];

                if ($orderCount >= $pSize) {

                    try {
                        $customers = $api->customerUpload($resCustomers);
                    } catch (\IntaroCrm\Exception\ApiException $e) {
                        self::eventLog(
                            'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        if($e->getCode() != 201)
                            if($e->getCode() != 460)
                                return false;
                    } catch (\IntaroCrm\Exception\CurlException $e) {
                        self::eventLog(
                            'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload::CurlException',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        return false;
                    }

                    try {
                        $orders = $api->orderUpload($resOrders);
                    } catch (\IntaroCrm\Exception\ApiException $e) {
                        self::eventLog(
                            'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        if($e->getCode() != 201)
                            if($e->getCode() != 460)
                                return false;
                    } catch (\IntaroCrm\Exception\CurlException $e) {
                        self::eventLog(
                            'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload::CurlException',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        return false;
                    }

                    if ($lastOrderId)
                        COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, $lastOrderId);

                    return true; // end of pack
                }
            }
            if (!empty($resOrders)) {
                try {
                    $customers = $api->customerUpload($resCustomers);
                } catch (\IntaroCrm\Exception\ApiException $e) {
                    self::eventLog(
                        'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    if($e->getCode() != 201)
                        if($e->getCode() != 460)
                            return false;
                } catch (\IntaroCrm\Exception\CurlException $e) {
                    self::eventLog(
                        'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    return false;
                }

                try {
                    $orders = $api->orderUpload($resOrders);
                } catch (\IntaroCrm\Exception\ApiException $e) {
                    self::eventLog(
                        'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    if($e->getCode() != 201)
                        if($e->getCode() != 460)
                            return false;
                } catch (\IntaroCrm\Exception\CurlException $e) {
                    self::eventLog(
                        'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    return false;
                }
            }

            if ($lastOrderId)
                COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, $lastOrderId);

        } else {

            // failed orders upload
            $orderCount = 0;
            $recOrders = array();

            while ($arOrder = $dbFailedOrder->GetNext()) { // here orders by id asc
                if (is_array($optionsSites))
                    if (!empty($optionsSites))
                        if (!in_array($arOrder['LID'], $optionsSites))
                            continue;

                $result = self::orderCreate($arOrder, $api, $arParams);

                if (!$result['order'] || !$result['customer'])
                    continue;

                $orderCount++;

                $resOrders[] = $result['order'];
                $resCustomers[] = $result['customer'];

                $recOrders[] = $arOrder['ID'];

                if ($orderCount >= $pSize) {
                    try {
                        $customers = $api->customerUpload($resCustomers);
                    } catch (\IntaroCrm\Exception\ApiException $e) {
                        self::eventLog(
                            'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        if($e->getCode() != 201)
                            if($e->getCode() != 460)
                                return false;
                    } catch (\IntaroCrm\Exception\CurlException $e) {
                        self::eventLog(
                            'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload::CurlException',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        return false;
                    }

                    try {
                        $orders = $api->orderUpload($resOrders);
                    } catch (\IntaroCrm\Exception\ApiException $e) {
                        self::eventLog(
                            'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        if($e->getCode() != 201)
                            if($e->getCode() != 460)
                                return false;
                    } catch (\IntaroCrm\Exception\CurlException $e) {
                        self::eventLog(
                            'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload::CurlException',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        return false;
                    }

                    if (!empty($recOrders)) {
                        $failedIds = array_merge(array_diff($failedIds, $recOrders)); // clear success ids
                        COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, serialize($failedIds));
                    }

                    return true; // end of pack
                }
            }
            if (!empty($resOrders)) {
                try {
                    $customers = $api->customerUpload($resCustomers);
                } catch (\IntaroCrm\Exception\ApiException $e) {
                    self::eventLog(
                        'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    if($e->getCode() != 201)
                        if($e->getCode() != 460)
                            return false;
                } catch (\IntaroCrm\Exception\CurlException $e) {
                    self::eventLog(
                        'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    return false;
                }

                try {
                    $orders = $api->orderUpload($resOrders);
                } catch (\IntaroCrm\Exception\ApiException $e) {
                    self::eventLog(
                        'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    if($e->getCode() != 201)
                        if($e->getCode() != 460)
                            return false;
                } catch (\IntaroCrm\Exception\CurlException $e) {
                    self::eventLog(
                        'ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    return false;
                }
            }

            if (!empty($recOrders)) {
                $failedIds = array_merge(array_diff($failedIds, $recOrders)); // clear success ids
                COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, serialize($failedIds));
            }
        }

        return true; //all ok!
    }

    protected static function updateCancelProp($arProduct, $value) {
        $propUpdated = false;
        foreach($arProduct['PROPS'] as $key => $item) {
            if ($item['CODE'] == self::CANCEL_PROPERTY_CODE) {
                $arProduct['PROPS'][$key]['VALUE'] = $value;
                $propUpdated = true;
                break;
            }
        }

        if (!$propUpdated) {
            $arProduct['PROPS'][] = array(
                'NAME' => GetMessage('PRODUCT_CANCEL'),
                'CODE' => self::CANCEL_PROPERTY_CODE,
                'VALUE' => $value,
                'SORT' => 10,
            );
        }

        return $arProduct;
    }

    /**
     *
     * History update, cron usage only
     * @global CUser $USER
     * @return boolean
     */
    public static function orderHistory() {
        global $USER;
        if (is_object($USER) == false) {
            $USER = new RetailUser;
        }

        if (!CModule::IncludeModule("iblock")) {
            self::eventLog('ICrmOrderActions::orderHistory', 'iblock', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("sale")) {
            self::eventLog('ICrmOrderActions::orderHistory', 'sale', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("catalog")) {
            self::eventLog('ICrmOrderActions::orderHistory', 'catalog', 'module not found');
            return true;
        }

        $defaultSiteId = 0;
        $rsSites = CSite::GetList($by, $sort, array('DEF' => 'Y'));
            while ($ar = $rsSites->Fetch()) {
                $defaultSiteId = $ar['LID'];
                break;
            }

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        //saved cat params (crm -> bitrix)
        $optionsOrderTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0)));
        $optionsDelivTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0)));
        $optionsPayTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0)));
        $optionsPayStatuses = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0))); // --statuses
        $optionsPayment = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0)));
        $optionsSites = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_SITES, 0));
        $optionsOrderProps = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_PROPS, 0));

        $api = new IntaroCrm\RestApi($api_host, $api_key);

        $dateStart = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY_DATE, null);

        if(!$dateStart) {
            $dateStart = new \DateTime();
            $dateStart = $dateStart->format('Y-m-d H:i:s');
        }

        try {
            $orderHistory = $api->orderHistory($dateStart);
        } catch (\IntaroCrm\Exception\ApiException $e) {
            self::eventLog(
                'ICrmOrderActions::orderHistory', 'IntaroCrm\RestApi::orderHistory',
                $e->getCode() . ': ' . $e->getMessage()
            );

            return true;
        } catch (\IntaroCrm\Exception\CurlException $e) {
            self::eventLog(
                'ICrmOrderActions::orderHistory', 'IntaroCrm\RestApi::orderHistory::CurlException',
                $e->getCode() . ': ' . $e->getMessage()
            );

            return true;
        }

        $dateFinish = $api->getGeneratedAt();

        // default orderType
        $defaultOrderType = 1;

        // if it not a ph. entity
        $dbOrderTypesList = CSalePersonType::GetList(
            array(
                "SORT" => "ASC",
                "NAME" => "ASC"
            ),
            array(
                "ACTIVE" => "Y",
            ),
            false,
            false,
            array()
        );

        if ($arOrderTypesList = $dbOrderTypesList->Fetch())
            $defaultOrderType = $arOrderTypesList['ID'];

        // apiv3 !
        if (!$dateFinish) {
            $dateFinish = new \DateTime();
        }

        $GLOBALS['INTARO_CRM_FROM_HISTORY'] = true;

        // pushing existing orders
        foreach ($orderHistory as $order) {
            if(function_exists('intarocrm_order_pre_persist')) {
                $order = intarocrm_order_pre_persist($order);
            }

            if (!isset($order['externalId'])) {

                // custom orderType function
                if (function_exists('intarocrm_set_order_type')) {
                    $orderType = intarocrm_set_order_type($order);
                    if ($orderType) {
                        $optionsOrderTypes[$order['orderType']] = $orderType;
                    } else {
                        $optionsOrderTypes[$order['orderType']] = $defaultOrderType;
                    }
                }

                // we dont need new orders without any customers (can check only for externalId)
                if (!isset($order['customer']['externalId'])) {
                    if (!isset($order['customer']['id'])) {
                        continue;
                    }

                    $registerNewUser = true;

                    if (!isset($order['customer']['email'])) {
                        $login = $order['customer']['email'] = uniqid('user_' . time()) . '@crm.com';
                    } else {
                        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'ASC'), array('=EMAIL' => $order['email']));
                        switch ($dbUser->SelectedRowsCount()) {
                            case 0:
                                $login = $order['customer']['email'];
                                break;
                            case 1:
                                $arUser = $dbUser->Fetch();
                                $registeredUserID = $arUser['ID'];
                                $registerNewUser = false;
                                break;
                            default:
                                $login = uniqid('user_' . time()) . '@crm.com';
                        }
                    }

                    if ($registerNewUser === true) {
                        $userPassword = uniqid();

                        $newUser = new CUser;
                        $arFields = array(
                            "NAME"              => self::fromJSON($order['customer']['firstName']),
                            "LAST_NAME"         => self::fromJSON($order['customer']['lastName']),
                            "EMAIL"             => $order['customer']['email'],
                            "LOGIN"             => $login,
                            "LID"               => "ru",
                            "ACTIVE"            => "Y",
                            "PASSWORD"          => $userPassword,
                            "CONFIRM_PASSWORD"  => $userPassword
                        );

                        $registeredUserID = $newUser->Add($arFields);

                        if ($registeredUserID === false) {
                            self::eventLog('ICrmOrderActions::orderHistory', 'CUser::Register', 'Error register user');
                            continue;
                        }
 
                        try {
                            $api->customerFixExternalIds(array(array('id' => $order['customer']['id'], 'externalId' => $registeredUserID)));
                        } catch (\IntaroCrm\Exception\ApiException $e) {
                            self::eventLog(
                                'ICrmOrderActions::orderHistory', 'IntaroCrm\RestApi::customerFixExternalIds',
                                $e->getCode() . ': ' . $e->getMessage()
                            );
                        
                            continue;
                        } catch (\IntaroCrm\Exception\CurlException $e) {
                            self::eventLog(
                                'ICrmOrderActions::orderHistory', 'IntaroCrm\RestApi::customerFixExternalIds::CurlException',
                                $e->getCode() . ': ' . $e->getMessage()
                            );
                        
                            continue;
                        }
                    }

                    $order['customer']['externalId'] = $registeredUserID;
                }

                // new order
               $newOrderFields = array(
                    'LID'              => $defaultSiteId,
                    'PERSON_TYPE_ID'   => ($optionsOrderTypes[$order['orderType']]) ? $optionsOrderTypes[$order['orderType']] : $defaultOrderType,
                    'PAYED'            => 'N',
                    'CANCELED'         => 'N',
                    'STATUS_ID'        => 'N',
                    'PRICE'            => 0,
                    'CURRENCY'         => 'RUB',
                    'USER_ID'          => $order['customer']['externalId'],
                    'PAY_SYSTEM_ID'    => 0,
                    'PRICE_DELIVERY'   => 0,
                    'DELIVERY_ID'      => 0,
                    'DISCOUNT_VALUE'   => 0,
                    'USER_DESCRIPTION' => ''
                );

                $externalId = CSaleOrder::Add($newOrderFields);
                if (!isset($order['externalId'])) {
                    try {
                        $api->orderFixExternalIds(array(array('id' => $order['id'], 'externalId' => $externalId)));
                    } catch (\IntaroCrm\Exception\ApiException $e) {
                        self::eventLog(
                            'ICrmOrderActions::orderHistory', 'IntaroCrm\RestApi::orderFixExternalIds',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        continue;
                    } catch (\IntaroCrm\Exception\CurlException $e) {
                        self::eventLog(
                            'ICrmOrderActions::orderHistory', 'IntaroCrm\RestApi::orderFixExternalIds::CurlException',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        continue;
                    }
                }
                $order['externalId'] = $externalId;
            }

            if (isset($order['externalId']) && $order['externalId']) {

                // custom orderType function
                if (function_exists('intarocrm_set_order_type')) {
                    $orderType = intarocrm_set_order_type($order);
                    if ($orderType) {
                        $optionsOrderTypes[$order['orderType']] = $orderType;
                    } else {
                        $optionsOrderTypes[$order['orderType']] = $defaultOrderType;
                    }
                }

                $arFields = CSaleOrder::GetById($order['externalId']);

                // incorrect order
                if ($arFields === false || empty($arFields)) {
                    continue;
                }

                $LID = $arFields['LID'];
                $userId = $arFields['USER_ID'];

                if(isset($order['customer']['externalId']) && !is_null($order['customer']['externalId'])) {
                    $userId = $order['customer']['externalId'];
                }

                $rsOrderProps = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $arFields['ID']));

                while ($ar = $rsOrderProps->Fetch()) {
                    if (isset($order['delivery']) && isset($order['delivery']['address']) && $order['delivery']['address']) {
                        switch ($ar['CODE']) {
                            case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['index']:
                                if (isset($order['delivery']['address']['index'])) {
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['index'])));
                                }
                                break;
                            case ($ar['CODE'] == 'CITY') || ($ar['CODE'] == 'LOCATION'):
                                if (isset($order['delivery']['address']['city'])) {
                                    $prop = CSaleOrderProps::GetByID($ar['ORDER_PROPS_ID']);

                                    if($prop['TYPE'] == 'LOCATION') {
                                        $cityId = self::getLocationCityId(self::fromJSON($order['delivery']['address']['city']));
                                        if (!$cityId) {
                                            break;
                                        }

                                        CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => $cityId));
                                       break;
                                    }

                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['city'])));
                                }
                                break;
                            case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['text']:
                                if (isset($order['delivery']['address']['text'])) {
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['text'])));
                                }
                                break;
                        }

                        if (count($optionsOrderProps[$arFields['PERSON_TYPE_ID']]) > 4) {
                            switch ($ar['CODE']) {
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['street']: if (isset($order['delivery']['address']['street']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['street'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['building']: if (isset($order['delivery']['address']['building']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['building'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['flat']: if (isset($order['delivery']['address']['flat']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['flat'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['intercomcode']: if (isset($order['delivery']['address']['intercomcode']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['intercomcode'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['floor']: if (isset($order['delivery']['address']['floor']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['floor'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['block']: if (isset($order['delivery']['address']['block']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['block'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['house']: if (isset($order['delivery']['address']['house']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['delivery']['address']['house'])));
                                    break;
                            }
                        }
                    }

                    switch ($ar['CODE']) {
                        case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['fio']:
                                $contactName = array(); // cleanup
                                if (isset($order['lastName'])) {
                                    $contactName['lastName'] = self::fromJSON($order['lastName']);
                                }
                                if (isset($order['firstName'])) {
                                    $contactName['firstName'] = self::fromJSON($order['firstName']);
                                }
                                if (isset($order['patronymic'])) {
                                    $contactName['patronymic'] = self::fromJSON($order['patronymic']);
                                }
                                if (!isset($contactName) || empty($contactName)) {
                                    break;
                                }

                                CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => implode(" ", $contactName)));
                            break;
                        case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['phone']:
                            if (isset($order['phone'])) {
                                CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['phone'])));
                            }
                            break;
                        case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['email']:
                            if (isset($order['email'])) {
                                CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['email'])));
                            }
                            break;
                    }

                }

                // here check if smth wasnt added or new propetties
                if (isset($order['delivery']) && isset($order['delivery']['address']) && count($order['delivery']['address']) > 0) {
                    if (isset($order['delivery']['address']['index'])) {
                        self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['index'],
                                self::fromJSON($order['delivery']['address']['index']), $order['externalId']);
                    }

                    if (isset($order['delivery']['address']['city'])) {
                        self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['city'], self::fromJSON($order['delivery']['address']['city']), $order['externalId']);
                        self::addOrderProperty('CITY', self::fromJSON($order['delivery']['address']['city']), $order['externalId']);

                        $cityId = self::getLocationCityId(self::fromJSON($order['delivery']['address']['city']));
                        if ($cityId) {
                            self::addOrderProperty('LOCATION', $cityId, $order['externalId']);
                        } else {
                            self::addOrderProperty('LOCATION', 0, $order['externalId']);
                        }
                    }

                    if (isset($order['delivery']['address']['text'])) {
                        self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['text'], self::fromJSON($order['delivery']['address']['text']), $order['externalId']);
                    }

                    if (count($optionsOrderProps[$arFields['PERSON_TYPE_ID']]) > 4) {
                        if (isset($order['delivery']['address']['street'])) {
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['street'],
                                    self::fromJSON($order['delivery']['address']['street']), $order['externalId']);
                        }

                        if (isset($order['delivery']['address']['building'])) {
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['building'],
                                    self::fromJSON($order['delivery']['address']['bulding']), $order['externalId']);
                        }

                        if (isset($order['delivery']['address']['flat'])) {
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['flat'],
                                    self::fromJSON($order['delivery']['address']['flat']), $order['externalId']);
                        }

                        if (isset($order['delivery']['address']['intercomcode'])) {
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['intercomcode'],
                                    self::fromJSON($order['delivery']['address']['intercomcode']), $order['externalId']);
                        }

                        if (isset($order['delivery']['address']['floor'])) {
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['floor'],
                                    self::fromJSON($order['delivery']['address']['floor']), $order['externalId']);
                        }

                        if (isset($order['delivery']['address']['block'])) {
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['block'],
                                    self::fromJSON($order['delivery']['address']['block']), $order['externalId']);
                        }

                        if (isset($order['delivery']['address']['house'])) {
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['house'],
                                    self::fromJSON($order['delivery']['address']['house']), $order['externalId']);
                        }
                    }
                }

                if (isset($order['phone'])) {
                    self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['phone'],
                            self::fromJSON($order['phone']), $order['externalId']);
                }

                if (isset($order['email']))
                    self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['email'],
                            self::fromJSON($order['email']), $order['externalId']);

                $contactName = array(); // cleanup
                if (isset($order['firstName'])) {
                    $contactName['firstName'] = self::fromJSON($order['firstName']);
                }
                if (isset($order['lastName'])) {
                    $contactName['lastName'] = self::fromJSON($order['lastName']);
                }
                if (isset($order['patronymic'])) {
                    $contactName['patronymic'] = self::fromJSON($order['patronymic']);
                }

                if (isset($contactName) && !empty($contactName)) {
                    self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['fio'],
                            implode(" ", $contactName), $order['externalId']);
                }

                foreach($order['items'] as $item) {
                    // del from basket
                    if(isset($item['deleted']) && $item['deleted']) {
                        $p = CSaleBasket::GetList(
                            array('PRODUCT_ID' => 'ASC'),
                            array('ORDER_ID' => $order['externalId'], 'PRODUCT_ID' => $item['id']))->Fetch();

                        if ($p) {
                            CSaleBasket::Delete($p['ID']);
                        }

                         continue;
                    }

                    if (!isset($item['offer']) && !isset($item['offer']['externalId'])) {
                        continue;
                    }

                    $p = CSaleBasket::GetList(
                            array('PRODUCT_ID' => 'ASC'),
                            array('ORDER_ID' => $order['externalId'], 'PRODUCT_ID' => $item['offer']['externalId'])
                    )->Fetch();

                    if (!$p) {
                        $p = CIBlockElement::GetByID($item['offer']['externalId'])->Fetch();
                        // select iblock to obtain an CATALOG_XML_ID
                        $iblock = CIBlock::GetByID($p['IBLOCK_ID'])->Fetch();
                        $p['CATALOG_XML_ID'] = $iblock['XML_ID'];
                        // product field XML_ID is called PRODUCT_XML_ID in basket 
                        $p['PRODUCT_XML_ID'] = $p['XML_ID'];
                        unset($p['XML_ID']); 
                    } else {
                        //for basket props updating (in props we save cancel status)
                        $propResult = CSaleBasket::GetPropsList(
                          array(''),
                          array('BASKET_ID' => $p['ID']),
                          false,
                          false,
                          array('NAME', 'CODE', 'VALUE', 'SORT')
                        );

                        while($r = $propResult->Fetch()) {
                            $p['PROPS'][] = $r;
                        }
                    }

                    // change existing basket items
                    $arProduct = array();

                    // create new
                    if(isset($item['created']) && $item['created']) {

                        $productPrice = GetCatalogProductPrice($item['offer']['externalId'], 1);

                        $arProduct = array(
                            'FUSER_ID'               => $userId,
                            'ORDER_ID'               => $order['externalId'],
                            'QUANTITY'               => $item['quantity'],
                            'CURRENCY'               => $productPrice['CURRENCY'],
                            'LID'                    => $LID,
                            'PRODUCT_ID'             => $item['offer']['externalId'],
                            'PRODUCT_PRICE_ID'       => $p['PRODUCT_PRICE_ID'],
                            'WEIGHT'                 => $p['WEIGHT'],
                            'DELAY'                  => $p['DELAY'],
                            'CAN_BUY'                => $p['CAN_BUY'],
                            'MODULE'                 => $p['MODULE'],
                            'NOTES'                  => $item['comment'] ?: $p['NOTES'],
                            'PRODUCT_PROVIDER_CLASS' => $p['PRODUCT_PROVIDER_CLASS'],
                            'DETAIL_PAGE_URL'        => $p['DETAIL_PAGE_URL'],
                            'CATALOG_XML_ID'         => $p['CATALOG_XML_ID'],
                            'PRODUCT_XML_ID'         => $p['PRODUCT_XML_ID'],
                            'CUSTOM_PRICE'           => 'Y'
                        );

                        if (isset($item['initialPrice']) && $item['initialPrice']) {
                            $arProduct['PRICE'] = (double) $item['initialPrice'];
                        }

                        if (isset($item['discount'])) {
                            $arProduct['DISCOUNT_PRICE'] = $item['discount'];
                        }

                        if (isset($item['discountPercent'])) {
                            $arProduct['DISCOUNT_VALUE'] = $item['discountPercent'];
                            $newPrice = floor ($arProduct['PRICE'] / 100 * (100 - $arProduct['DISCOUNT_VALUE']));
                            $arProduct['DISCOUNT_PRICE'] = $arProduct['DISCOUNT_PRICE'] + $arProduct['PRICE'] - $newPrice;
                        }

                        if(isset($item['discount']) || isset($item['discountPercent'])) {
                            $arProduct['PRICE'] -= $arProduct['DISCOUNT_PRICE'];
                        }

                        if (isset($item['offer']['name']) && $item['offer']['name']) {
                            $arProduct['NAME'] = self::fromJSON($item['offer']['name']);
                        }

                        if (isset($item['isCanceled'])) {
                            //for product excluding from order
                            $arProduct['PRICE'] = 0;
                            $arProduct = self::updateCancelProp($arProduct, 1);
                        }

                        CSaleBasket::Add($arProduct);
                        continue;
                    }

                    $arProduct['PROPS'] = $p['PROPS'];

                    if (!isset($item['isCanceled'])) {
                        // update old
                        if (isset($item['initialPrice']) && $item['initialPrice']) {
                                $arProduct['PRICE'] = (double) $item['initialPrice'];
                        }

                        if (isset($item['discount'])) {
                            $arProduct['DISCOUNT_PRICE'] = $item['discount'];
                        }

                        if (isset($item['discountPercent'])) {
                            $arProduct['DISCOUNT_VALUE'] = $item['discountPercent'];
                            $newPrice = floor ($arProduct['PRICE'] / 100 * (100 - $arProduct['DISCOUNT_VALUE']));
                            $arProduct['DISCOUNT_PRICE'] = $arProduct['DISCOUNT_PRICE'] + $arProduct['PRICE'] - $newPrice;
                        }

                        if(isset($item['discount']) || isset($item['discountPercent'])) {
                            $arProduct['PRICE'] -= $arProduct['DISCOUNT_PRICE'];
                        }

                        $arProduct = self::updateCancelProp($arProduct, 0);
                    } else {
                        //for product excluding from order
                        $arProduct['PRICE'] = 0;
                        $arProduct = self::updateCancelProp($arProduct, 1);
                    }


                    if (isset($item['quantity']) && $item['quantity']) {
                        $arProduct['QUANTITY'] = $item['quantity'];
                    }

                    if (isset($item['offer']['name']) && $item['offer']['name']) {
                        $arProduct['NAME'] = self::fromJSON($item['offer']['name']);
                    }

                    CSaleBasket::Update($p['ID'], $arProduct);
                    CSaleBasket::DeleteAll($userId);
                }

                if (!isset($order['delivery']) || !isset($order['delivery']['cost'])) {
                    $order['delivery']['cost'] = $arFields['PRICE_DELIVERY'];
                }

                if (!isset($order['summ']) || (isset($order['summ']) && !$order['summ'] && $order['summ'] !== 0)) {
                    $order['summ'] = $arFields['PRICE'] - $arFields['PRICE_DELIVERY'];
                }

                $wasCanaceled = $arFields['CANCELED'] == 'Y' ? true : false;

                $resultDeliveryTypeId = $optionsDelivTypes[$order['delivery']['code']];

                if(isset($order['delivery']['service']) && !empty($order['delivery']['service'])) {
                    if (strpos($order['delivery']['service']['code'], "-") !== false)
                        $deliveryServiceCode = explode("-", $order['delivery']['service']['code'], 2);

                    if ($deliveryServiceCode)
                        $resultDeliveryTypeId = $resultDeliveryTypeId . ':' . $deliveryServiceCode[1];
                }

                if(isset($order['delivery']) && $order['delivery'] && isset($order['delivery']['integrationCode']) &&
                   $order['delivery']['integrationCode'] == self::$CRM_MULTISHIP_INTEGRATION_CODE &&
                   isset($order['delivery']['data']) && $order['delivery']['data'] &&
                   isset($order['delivery']['data']['service']) && $order['delivery']['data']['service']) {
                    if(CModule::IncludeModule(self::$MULTISHIP_MODULE_VER)) {
                        $resultDeliveryTypeId = $resultDeliveryTypeId . ':' . $order['delivery']['data']['service'];
                    }
                }

                // orderUpdate
                $arFields = self::clearArr(array(
                    'PRICE_DELIVERY'   => $order['delivery']['cost'],
                    'PRICE'            => $order['summ'] + (double) $order['delivery']['cost'],
                    'DATE_MARKED'      => $order['markDatetime'],
                    'USER_ID'          => $userId,
                    'PAY_SYSTEM_ID'    => $optionsPayTypes[$order['paymentType']],
                    'DELIVERY_ID'      => $resultDeliveryTypeId,
                    'STATUS_ID'        => $optionsPayStatuses[$order['status']],
                    'REASON_CANCELED'  => self::fromJSON($order['statusComment']),
                    'USER_DESCRIPTION' => self::fromJSON($order['customerComment']),
                    'COMMENTS'         => self::fromJSON($order['managerComment'])
                ));

                if (isset($order['discount'])) {
                    $arFields['DISCOUNT_VALUE'] = $order['discount'];
                    $arFields['PRICE'] -= $order['discount'];
                }

                if(!empty($arFields)) {
                    CSaleOrder::Update($order['externalId'], $arFields);
                }

                if(isset($order['status']) && $order['status']) {
                    if(isset($optionsPayStatuses[$order['status']]) && $optionsPayStatuses[$order['status']]) {
                        // set STATUS_ID
                        CSaleOrder::StatusOrder($order['externalId'], $optionsPayStatuses[$order['status']]);

                        // uncancel order
                        if($wasCanaceled && ($optionsPayStatuses[$order['status']] != 'YY')) {
                            CSaleOrder::CancelOrder($order['externalId'], "N", $order['statusComment']);
                        }

                        // cancel order
                        if($optionsPayStatuses[$order['status']] == 'YY') {
                            CSaleOrder::CancelOrder($order['externalId'], "Y", $order['statusComment']);
                        }
                    }
                }

                // set PAYED
                if(isset($order['paymentStatus']) && $order['paymentStatus'] && $optionsPayment[$order['paymentStatus']]) {
                    CSaleOrder::PayOrder($order['externalId'], $optionsPayment[$order['paymentStatus']]);
                }

                if(function_exists('intarocrm_order_post_persist')) {
                    intarocrm_order_post_persist($order);
                }
            }
        }

        if (count($orderHistory)) {
            COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY_DATE, $dateFinish->format('Y-m-d H:i:s'));
        }

        $GLOBALS['INTARO_CRM_FROM_HISTORY'] = false;

        return true;
    }

    /**
     *
     * w+ event in bitrix log
     */

    public static function eventLog($auditType, $itemId, $description) {

        CEventLog::Add(array(
            "SEVERITY"      => "SECURITY",
            "AUDIT_TYPE_ID" => $auditType,
            "MODULE_ID"     => self::$MODULE_ID,
            "ITEM_ID"       => $itemId,
            "DESCRIPTION"   => $description,
        ));
    }

    /**
     *
     * Agent function
     *
     * @return self name
     */

    public static function uploadOrdersAgent() {
        self::uploadOrders();
        $failedIds = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, 0));
        if (is_array($failedIds) && !empty($failedIds)) {
            self::uploadOrders(50, true);
        }

        return 'ICrmOrderActions::uploadOrdersAgent();';
    }

    /**
     *
     * Agent function
     *
     * @return self name
     */

    public static function orderAgent() {
        if(COption::GetOptionString('main', 'agents_use_crontab', 'N') != 'N') {
            define('NO_AGENT_CHECK', true);
        }

        self::uploadOrdersAgent();
        self::orderHistory();

        return 'ICrmOrderActions::orderAgent();';
    }

    /**
     *
     * Creates order or returns array of order and customer for mass upload
     *
     * @param array $arFields
     * @param $api
     * @param $arParams
     * @param $send
     * @return boolean
     * @return array - array('order' = $order, 'customer' => $customer)
     */
    public static function orderCreate($arFields, $api, $arParams, $send = false) {
        if(!$api || empty($arParams)) { // add cond to check $arParams
            return false;
        }

        if (empty($arFields)) {
            //handle err
            self::eventLog('ICrmOrderActions::orderCreate', 'empty($arFields)', 'incorrect order');

            return false;
        }

        $rsUser = CUser::GetByID($arFields['USER_ID']);
        $arUser = $rsUser->Fetch();

        $createdAt = new \DateTime($arUser['DATE_REGISTER']);
        $createdAt = $createdAt->format('Y-m-d H:i:s');

        // push customer (for crm)
        $firstName = self::toJSON($arUser['NAME']);
        $lastName = self::toJSON($arUser['LAST_NAME']);
        $patronymic = self::toJSON($arUser['SECOND_NAME']);

        // convert encoding for comment
        $statusComment   = self::toJson($arFields['REASON_CANCELED']);
        $customerComment = self::toJson($arFields['USER_DESCRIPTION']);
        $managerComment  = self::toJson($arFields['COMMENTS']);

        $phones = array();

        $phonePersonal = array(
            'number' => self::toJSON($arUser['PERSONAL_PHONE']),
            'type'   => 'mobile'
        );

        if($phonePersonal['number'])
            $phones[] = $phonePersonal;

        $phoneWork = array(
            'number' => self::toJSON($arUser['WORK_PHONE']),
            'type'   => 'work'
        );

        if($phoneWork['number'])
            $phones[] = $phoneWork;

        $customer = self::clearArr(array(
            'externalId' => $arFields['USER_ID'],
            'lastName'   => $lastName,
            'firstName'  => $firstName,
            'patronymic' => $patronymic,
            'phones'     => $phones,
            'createdAt'  => $createdAt
        ));

        // delivery types
        $arId = array();
        if (strpos($arFields['DELIVERY_ID'], ":") !== false)
            $arId = explode(":", $arFields["DELIVERY_ID"]);

        if ($arId)
            $resultDeliveryTypeId = $arId[0];
        else
            $resultDeliveryTypeId = $arFields['DELIVERY_ID'];

        // deliveryService
        $deliveryService = array();
        if(count($arId) > 1) {
            $dbDeliveryType = CSaleDeliveryHandler::GetBySID($arId[0]);

            if ($arDeliveryType = $dbDeliveryType->GetNext()) {
                foreach($arDeliveryType['PROFILES'] as $id => $profile) {
                    if($id == $arId[1]) {
                        $deliveryService = array(
                            'code' => $arId[0] . '-' . $id,
                            'name' => $profile['TITLE']
                        );
                    }
                }
            }
        }

        $resOrder = array();
        $resOrderDeliveryAddress = array();
        $contactNameArr = array();

        $rsOrderProps = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $arFields['ID']));
        while ($ar = $rsOrderProps->Fetch()) {
            switch ($ar['CODE']) {
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['index']: $resOrderDeliveryAddress['index'] = self::toJSON($ar['VALUE']);
                    break;
                case 'CITY':
                    $prop = CSaleOrderProps::GetByID($ar['ORDER_PROPS_ID']);
                    if($prop['TYPE'] == 'LOCATION') {
                        $resOrderDeliveryAddress['city'] = CSaleLocation::GetByID($ar['VALUE']);
                        $resOrderDeliveryAddress['city'] = self::toJSON($resOrderDeliveryAddress['city']['CITY_NAME_LANG']);
                        break;
                    }
                    $resOrderDeliveryAddress['city'] = self::toJSON($ar['VALUE']);
                    break;
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['text']: $resOrderDeliveryAddress['text'] = self::toJSON($ar['VALUE']);
                    break;
                case 'LOCATION': if(!isset($resOrderDeliveryAddress['city']) && !$resOrderDeliveryAddress['city']) {
                        $prop = CSaleOrderProps::GetByID($ar['ORDER_PROPS_ID']);
                        if($prop['TYPE'] == 'LOCATION') {
                            $resOrderDeliveryAddress['city'] = CSaleLocation::GetByID($ar['VALUE']);
                            $resOrderDeliveryAddress['city'] = self::toJSON($resOrderDeliveryAddress['city']['CITY_NAME_LANG']);
                            break;
                        }
                        $resOrderDeliveryAddress['city'] = self::toJSON($ar['VALUE']);
                        break;
                    }
                    break;
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['fio']: $contactNameArr = self::explodeFIO($ar['VALUE']);
                    break;
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['phone']: $resOrder['phone'] = $ar['VALUE'];
                    break;
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['email']: $resOrder['email'] = $ar['VALUE'];
                    break;
            }

            if (count($arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]) > 4) {
                switch ($ar['CODE']) {
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['street']: $resOrderDeliveryAddress['street'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['building']: $resOrderDeliveryAddress['building'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['flat']: $resOrderDeliveryAddress['flat'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['intercomcode']: $resOrderDeliveryAddress['intercomcode'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['floor']: $resOrderDeliveryAddress['floor'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['block']: $resOrderDeliveryAddress['block'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['house']: $resOrderDeliveryAddress['house'] = self::toJSON($ar['VALUE']);
                        break;
                }
            }
        }

        $items = array();

        $rsOrderBasket = CSaleBasket::GetList(array('ID' => 'ASC'), array('ORDER_ID' => $arFields['ID']));
        while ($p = $rsOrderBasket->Fetch()) {
            //for basket props updating (in props we save cancel status)
            $propCancel = CSaleBasket::GetPropsList(
              array(),
              array('BASKET_ID' => $p['ID'], 'CODE' => self::CANCEL_PROPERTY_CODE)
            )->Fetch();

            if ($propCancel) {
                $propCancel = (int)$propCancel['VALUE'];
            }

            $pr = CCatalogProduct::GetList(array(), array('ID' => $p['PRODUCT_ID']))->Fetch();

            if(!$pr) {
                $pr = '';
                unset($item['productId']);
            } else $pr = $pr['PURCHASING_PRICE'];

            $item = array(
                'discountPercent' => 0,
                'quantity'        => $p['QUANTITY'],
                'productId'       => $p['PRODUCT_ID'],
                'productName'     => self::toJSON($p['NAME']),
                'comment'         => $p['NOTES'],
            );

            //if it is canceled product don't send price
            if (!$propCancel) {
                $item['initialPrice'] = (double) $p['PRICE'] + (double) $p['DISCOUNT_PRICE'];
                $item['discount'] = $p['DISCOUNT_PRICE'];
            }

            $items[] = $item;
        }

        if($arFields['CANCELED'] == 'Y')
            $arFields['STATUS_ID'] = $arFields['CANCELED'].$arFields['CANCELED'];

        $createdAt = new \DateTime($arFields['DATE_INSERT']);
        $createdAt = $createdAt->format('Y-m-d H:i:s');

        $delivery = array(
            'code'    => $arParams['optionsDelivTypes'][$resultDeliveryTypeId],
            'service' => ($arParams['optionsDelivTypes'][$resultDeliveryTypeId]) ? $deliveryService : '',
            'address' => $resOrderDeliveryAddress,
            'cost'    => $arFields['PRICE_DELIVERY']
        );

        if($arParams['optionsDelivTypes'][$resultDeliveryTypeId] == self::$MUTLISHIP_DELIVERY_TYPE) {
            if(CModule::IncludeModule(self::$MULTISHIP_MODULE_VER)) {
                $multishipArr = DBOrdersMlsp::GetByOI($arFields['ID']);
                $delivery['data'] = array('ms_id' => $multishipArr['MULTISHIP_ID']);
                $delivery['integrationCode'] = 'multiship';
            }
        }

        $resOrder = array(
            'customer'        => $customer,
            'number'          => $arFields['ACCOUNT_NUMBER'],
            'phone'           => $resOrder['phone'],
            'email'           => $resOrder['email'],
            'summ'            => $arFields['PRICE'],
            'markDateTime'    => $arFields['DATE_MARKED'],
            'externalId'      => $arFields['ID'],
            'customerId'      => $arFields['USER_ID'],
            'paymentType'     => $arParams['optionsPayTypes'][$arFields['PAY_SYSTEM_ID']],
            'paymentStatus'   => $arParams['optionsPayment'][$arFields['PAYED']],
            'orderType'       => $arParams['optionsOrderTypes'][$arFields['PERSON_TYPE_ID']],
            'status'          => $arParams['optionsPayStatuses'][$arFields['STATUS_ID']],
            'statusComment'   => $statusComment,
            'customerComment' => $customerComment,
            'managerComment'  => $managerComment,
            'createdAt'       => $createdAt,
            'delivery'        => $delivery,
            'discount'        => $arFields['DISCOUNT_VALUE'],
            'items'           => $items
        );

        if(isset($arParams['optionsSites']) && is_array($arParams['optionsSites'])
                && in_array($arFields['LID'], $arParams['optionsSites']))
            $resOrder['site'] = $arFields['LID'];

        // parse fio
        if(count($contactNameArr) > 0) {
            $resOrder = array_merge($resOrder, $contactNameArr);
        }

        // custom orderType function
        if(function_exists('intarocrm_get_order_type')) {
            $orderType = intarocrm_get_order_type($arFields);
            if($orderType)
                $resOrder['orderType'] = $orderType;
            else
                $orderType['orderType'] = 'new';
        }

        // custom order & customer fields function
        if(function_exists('intarocrm_before_order_send')) {
            $newResOrder = intarocrm_before_order_send($resOrder);

            if(is_array($newResOrder) && !empty($newResOrder))
                $resOrder = $newResOrder;

        }

        $resOrder = self::clearArr($resOrder);

        if(isset($resOrder['customer']) && is_array($resOrder['customer']) && !empty($resOrder['customer'])) {
            $customer = $resOrder['customer'];
            unset($resOrder['customer']);
        }

        if($send) {

            try {
                $customer = $api->customerEdit($customer);
            } catch (\IntaroCrm\Exception\ApiException $e) {
                self::eventLog(
                    'ICrmOrderActions::orderCreate', 'IntaroCrm\RestApi::customerEdit',
                    $e->getCode() . ': ' . $e->getMessage()
                );

                return false;
            } catch (\IntaroCrm\Exception\CurlException $e) {
                self::eventLog(
                    'ICrmOrderActions::orderCreate', 'IntaroCrm\RestApi::customerEdit::CurlException',
                    $e->getCode() . ': ' . $e->getMessage()
                );

                return false;
            }

            try {
                return $api->orderEdit($resOrder);
            } catch (\IntaroCrm\Exception\ApiException $e) {
                self::eventLog(
                    'ICrmOrderActions::orderCreate', 'IntaroCrm\RestApi::orderEdit',
                    $e->getCode() . ': ' . $e->getMessage()
                );

                return false;
            } catch (\IntaroCrm\Exception\CurlException $e) {
                self::eventLog(
                    'ICrmOrderActions::orderCreate', 'IntaroCrm\RestApi::orderEdit::CurlException',
                    $e->getCode() . ': ' . $e->getMessage()
                );

                return false;
            }
        }

        return array(
            'order'    => $resOrder,
            'customer' => $customer
        );
    }

    /**
     * removes all empty fields from arrays
     * working with nested arrs
     *
     * @param array $arr
     * @return array
     */
    public static function clearArr($arr) {
        if (is_array($arr) === false) {
            return $arr;
        }

        $result = array();
        foreach ($arr as $index => $node ) {
            $result[ $index ] = is_array($node) === true ? self::clearArr($node) : trim($node);
            if ($result[ $index ] == '' || $result[ $index ] === null || count($result[ $index ]) < 1) {
                unset($result[ $index ]);
            }
        }

        return $result;
    }

    /**
     *
     * @global $APPLICATION
     * @param $str in SITE_CHARSET
     * @return  $str in utf-8
     */
    public static function toJSON($str) {
        global $APPLICATION;

        return $APPLICATION->ConvertCharset($str, SITE_CHARSET, 'utf-8');
    }

    /**
     *
     * @global $APPLICATION
     * @param $str in utf-8
     * @return $str in SITE_CHARSET
     */
    public static function fromJSON($str) {
        global $APPLICATION;

        return $APPLICATION->ConvertCharset($str, 'utf-8', SITE_CHARSET);
    }

    public static function explodeFIO($fio) {
        $newFio = empty($fio) ? false : explode(" ", self::toJSON($fio), 3);
        $result = array();
        switch (count($newFio)) {
            default:
            case 0:
                $result['firstName']  = $fio;
                break;
            case 1:
                $result['firstName']  = $newFio[0];
                break;
            case 2:
                $result = array(
                    'lastName'  => $newFio[1],
                    'firstName' => $newFio[0]
                );
                break;
            case 3:
                $result = array(
                    'lastName'   => $newFio[1],
                    'firstName'  => $newFio[0],
                    'patronymic' => $newFio[2]
                );
                break;
        }

        return $result;
    }

    public static function addOrderProperty($code, $value, $order) {
        if (!$code)
            return;

        if (!CModule::IncludeModule('sale'))
            return;

        if ($arProp = CSaleOrderProps::GetList(array(), array('CODE' => $code))->Fetch()) {
            return CSaleOrderPropsValue::Add(array(
                        'NAME' => $arProp['NAME'],
                        'CODE' => $arProp['CODE'],
                        'ORDER_PROPS_ID' => $arProp['ID'],
                        'ORDER_ID' => $order,
                        'VALUE' => $value,
            ));
        }
    }

    public static function getLocationCityId($cityName) {
        if(!$cityName)
            return;

        $dbLocation = CSaleLocation::GetList(
                        array(
                            "SORT" => "ASC",
                            "CITY_NAME_LANG" => "ASC"
                        ),
                        array("LID" => "ru", "CITY_NAME" => $cityName), false, false, array());

        if($location = $dbLocation->Fetch())
                return $location['ID'];
    }
}

class RetailUser extends CUser
{
    public function GetID()
    {

        $rsUser = CUser::GetList(($by='ID'), ($order='DESC'), array('LOGIN' => 'retailcrm%'));
        if ($arUser = $rsUser->Fetch()) {
            return $arUser['ID'];
        } else {
            $retailUser = new CUser;
            $userPassword = uniqid();
            $arFields = array(
                           "NAME"             => 'retailcrm',
                           "LAST_NAME"        => 'retailcrm',
                           "EMAIL"            => 'retailcrm@retailcrm.com',
                           "LOGIN"            => 'retailcrm',
                           "LID"              => "ru",
                           "ACTIVE"           => "Y",
                           "GROUP_ID"         => array(2),
                           "PASSWORD"         => $userPassword,
                           "CONFIRM_PASSWORD" => $userPassword
                        );
            $id = $retailUser->Add($arFields);
            if (!$id) {
                return null;
            } else {
                return $id;
            }
        }
    }
}

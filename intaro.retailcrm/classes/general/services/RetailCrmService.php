<?php
class RetailCrmService
{
    public static function unsetIntegrationDeliveryFields($order)
    {
        $integrationDelivery = unserialize(COption::GetOptionString(RetailcrmConstants::MODULE_ID, RetailcrmConstants::CRM_INTEGRATION_DELIVERY, 0));
        $deliveryCode = $order['delivery']['code'];
        if ($deliveryCode) {
            switch ($integrationDelivery[$deliveryCode]) {
                case "sdek":
                    unset($order['number']);
                    unset($order['height']);
                    unset($order['length']);
                    unset($order['width']);
                    break;
                case "dpd":
                    unset($order['manager']);
                    unset($order['firstName']);
                    unset($order['lastName']);
                    break;
                case "newpost":
                    unset($order['customer']);
                    break;
                default:
                    unset($order['firstName']);
                    unset($order['lastName']);
            }

            unset($order['weight']);
            unset($order['phone']);
            unset($order['delivery']['cost']);
            unset($order['shipmentStore']);
            unset($order['delivery']['address']);
            unset($order['delivery']['data']);
        }

        return $order;
    }
}

<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Order\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Response\Settings;

/**
 * Class CredentialsResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\Settings
 */
class CredentialsResponse
{
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var boolean $success
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("success")
     */
    public $success;
    
    /**
     * @var array $credentials
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("credentials")
     */
    public $credentials;
    
    /**
     * @var string $siteAccess
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("siteAccess")
     */
    public $siteAccess;
    
    /**
     * @var array $sitesAvailable
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("sitesAvailable")
     */
    public $sitesAvailable;
}

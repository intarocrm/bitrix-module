<?php

namespace Intaro\RetailCrm\Model\Api\Response\SmsVerification;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class SmsVerificationCreateResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class SmsVerificationCreateResponse extends AbstractApiModel
{
    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("success")
     */
    public $success;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SmsVerification
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SmsVerification")
     * @Mapping\SerializedName("verification")
     */
    public $verification;
}

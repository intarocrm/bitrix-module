<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Response;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class CustomersNotesResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CustomersNotesResponse extends OperationResponse
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\CustomerNote[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\CustomerNote[]")
     * @Mapping\SerializedName("notes")
     */
    public $notes;
}

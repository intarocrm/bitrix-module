<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Bitrix\Xml;

/**
 * единица измерения для товара, элемент не является обязательным в icml
 *
 * Class Unit
 * @package Intaro\RetailCrm\Model\Bitrix\Xml
 */
class Unit
{
    /**
     * @var string
     */
    public $name;
    
    /**
     * @var string
     */
    public $code;
    
    /**
     * единица измерения товара
     *
     * @var string
     */
    public $sym;
}
<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Mapping
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json\Mapping;

use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\Annotation;
use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\Annotation\Target;
use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\Annotation\Attribute;
use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\Annotation\Attributes;

/**
 * Class Name
 *
 * @package Intaro\RetailCrm\Component\Json\Mapping
 * @Annotation
 * @Attributes(
 *     @Attribute("name", required=true, type="string")
 * )
 * @Target({"PROPERTY","ANNOTATION"})
 */
final class Name
{
    /**
     * Property name in result JSON
     *
     * @var string
     */
    public $name;
}
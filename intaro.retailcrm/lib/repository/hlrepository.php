<?php

namespace Intaro\RetailCrm\Repository;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Service\Hl;

/**
 * Class HlRepository
 * @package Intaro\RetailCrm\Repository
 */
class HlRepository
{
    /**
     * @var \Bitrix\Main\Entity\DataManager|string|null
     */
    private $hl;
    
    public function __construct($hlName)
    {
        $this->hl = Hl::getHlClassByTableName($hlName);
    }
    
    /**
     * @param string $propertyValue
     * @return array|null
     */
    public function getDataByXmlId(string $propertyValue): ?array
    {
        try {
            $result = $this->hl::query()
                ->setSelect(['*'])
                ->where('UF_XML_ID', '=', $propertyValue)
                ->fetch();
            
            if ($result === false) {
                return null;
            }
            
            return $result;
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            AddMessage2Log($exception->getMessage());
            return null;
        }
    }
    
    /**
     * @return \Bitrix\Main\Entity\DataManager|string|null
     */
    public function getHl()
    {
        return $this->hl;
    }
}

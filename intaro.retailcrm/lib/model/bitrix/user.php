<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Bitrix;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

/**
 * Class User
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getLogin()
 * @method void setLogin(string $login)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method bool getActive()
 * @method void setActive(bool $active)
 * @method DateTime getDateRegister()
 * @method void setDateRegister(DateTime $dateRegister)
 * @method DateTime getDateRegShort()
 * @method void setDateRegShort(DateTime $dateRegShort)
 * @method DateTime getLastLogin()
 * @method void setLastLogin(DateTime $lastLogin)
 * @method DateTime getLastLoginShort()
 * @method void setLastLoginShort(DateTime $lastLoginShort)
 * @method DateTime getLastActivityDate()
 * @method void setLastActivityDate(DateTime $lastActivityDate)
 * @method DateTime getTimestampX()
 * @method void setTimestampX(DateTime $timestampX)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getSecondName()
 * @method void setSecondName(string $secondName)
 * @method string getLastName()
 * @method void setLastName(string $lastName)
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string getExternalAuthId()
 * @method void setExternalAuthId(string $externalAuthId)
 * @method string getXmlId()
 * @method void setXmlId(string $xmlId)
 * @method string getBxUserId()
 * @method void setBxUserId(string $bxUserId)
 * @method string getConfirmCode()
 * @method void setConfirmCode(string $confirmCode)
 * @method string getLid()
 * @method void setLid(string $lid)
 * @method string getLanguageId()
 * @method void setLanguageId(string $languageId)
 * @method int getTimeZoneOffset()
 * @method void setTimeZoneOffset(int $timeZoneOffset)
 * @method string getPersonalProfession()
 * @method void setPersonalProfession(string $personalProfession)
 * @method string getPersonalPhone()
 * @method void setPersonalPhone(string $personalPhone)
 * @method string getPersonalMobile()
 * @method void setPersonalMobile(string $personalMobile)
 * @method string getPersonalWww()
 * @method void setPersonalWww(string $personalWww)
 * @method string getPersonalIcq()
 * @method void setPersonalIcq(string $personalIcq)
 * @method string getPersonalFax()
 * @method void setPersonalFax(string $personalFax)
 * @method string getPersonalPager()
 * @method void setPersonalPager(string $personalPager)
 * @method string getPersonalStreet()
 * @method void setPersonalStreet(string $personalStreet)
 * @method string getPersonalMailbox()
 * @method void setPersonalMailbox(string $personalMailbox)
 * @method string getPersonalCity()
 * @method void setPersonalCity(string $personalCity)
 * @method string getPersonalState()
 * @method void setPersonalState(string $personalState)
 * @method string getPersonalZip()
 * @method void setPersonalZip(string $personalZip)
 * @method string getPersonalCountry()
 * @method void setPersonalCountry(string $personalCountry)
 * @method DateTime getPersonalBirthday()
 * @method void setPersonalBirthday(DateTime $personalBirthday)
 * @method string getPersonalGender()
 * @method void setPersonalGender(string $personalGender)
 * @method int getPersonalPhoto()
 * @method void setPersonalPhoto(int $personalPhoto)
 * @method string getPersonalNotes()
 * @method void setPersonalNotes(string $personalNotes)
 * @method string getWorkCompany()
 * @method void setWorkCompany(string $workCompany)
 * @method string getWorkDepartment()
 * @method void setWorkDepartment(string $workDepartment)
 * @method string getWorkPhone()
 * @method void setWorkPhone(string $workPhone)
 * @method string getWorkPosition()
 * @method void setWorkPosition(string $workPosition)
 * @method string getWorkWww()
 * @method void setWorkWww(string $workWww)
 * @method string getWorkFax()
 * @method void setWorkFax(string $workFax)
 * @method string getWorkPager()
 * @method void setWorkPager(string $workPager)
 * @method string getWorkStreet()
 * @method void setWorkStreet(string $workStreet)
 * @method string getWorkMailbox()
 * @method void setWorkMailbox(string $workMailbox)
 * @method string getWorkCity()
 * @method void setWorkCity(string $workCity)
 * @method string getWorkState()
 * @method void setWorkState(string $workState)
 * @method string getWorkZip()
 * @method void setWorkZip(string $workZip)
 * @method string getWorkCountry()
 * @method void setWorkCountry(string $workCountry)
 * @method string getWorkProfile()
 * @method void setWorkProfile(string $workProfile)
 * @method int getWorkLogo()
 * @method void setWorkLogo(int $workLogo)
 * @method string getWorkNotes()
 * @method void setWorkNotes(string $workNotes)
 * @method string getAdminNotes()
 * @method void setAdminNotes(string $adminNotes)
 * @method string getShortName()
 * @method void setShortName(string $shortName)
 * @method bool getIsOnline()
 * @method void setIsOnline(bool $isOnline)
 * @method bool getIsRealUser()
 * @method void setIsRealUser(bool $isRealUser)
 * @method mixed getIndex()
 * @method void setIndex($index)
 * @method mixed getIndexSelector()
 * @method void setIndexSelector($indexSelector)
 */
class User extends AbstractModelProxy
{
    /**
     * @return \Bitrix\Main\ORM\Objectify\EntityObject|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected static function newObject(): ?EntityObject
    {
        return UserTable::createObject();
    }
}
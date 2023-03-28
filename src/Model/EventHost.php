<?php

namespace XD\AttendableEvents\Model;

use SilverShop\HasOneField\HasOneButtonField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use XD\Events\Model\EventDateTime;

/**
 * Class EventHost
 * @package XD\AttendableEvents\Model
 * @method Member|MemberExtension $Member
 * @method EventDateTime $EventDateTime
 */
class EventHost extends DataObject
{

    private static $table_name = 'AttendableEvents_EventHost';

    private static $db = [
        'Title' => 'Varchar',
        'Sort' => 'Int'
    ];

    private static $has_one = [
        'EventDateTime' => EventDateTime::class,
        'Member' => Member::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Member.Title' => 'Member'
    ];

    private static $default_sort = 'Sort ASC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(['Sort', 'EventDateTimeID', 'MemberID']);

        $field = new HasOneButtonField($this, 'Member');
        $fields->addFieldToTab('Root.Main', $field);

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if( !trim($this->Title) ){
            $this->Title = $this->Member()->getFullName();
        }
    }

}
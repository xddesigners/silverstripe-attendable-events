<?php

namespace XD\AttendableEvents\Model;

use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use XD\Events\Model\EventDateTime;

class EventDayDateTime extends DataObject
{

    private static $table_name = 'AttendableEvents_EventDayDateTime';

    private static $db = [
        'StartDate' => 'Date',
        'StartTime' => 'Time',
        'EndTime' => 'Time',
    ];

    private static $has_one = [
        'EventDateTime' => EventDateTime::class
    ];

    private static $summary_fields = [
        'StartDate',
        'StartTime',
        'EndTime',
    ];

    private static $default_sort = 'StartDate ASC, StartTime ASC, EndTime ASC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $group = FieldGroup::create([
            $fields->dataFieldByName('StartTime'),
            $fields->dataFieldByName('EndTime')
        ]);

        $fields->removeByName(['StartTime','EndTime','EventDateTimeID']);
        $fields->insertAfter('StartDate',$group);
        return $fields;
    }

    public function canView($member = null)
    {
        if (parent::canView($member)) return true;
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function canEdit($member = null)
    {
        if (parent::canEdit($member)) return true;
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function canDelete($member = null)
    {
        if (parent::canDelete($member)) return true;
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function canCreate($member = null, $context = [])
    {
        if (parent::canCreate($member, $context)) return true;
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

}

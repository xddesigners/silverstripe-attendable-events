<?php

namespace XD\AttendableEvents\Model;

use SilverStripe\Forms\Tab;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use XD\Basic\Injector\Addressable;

class EventLocation extends DataObject{

    private static $table_name = 'AttendableEvents_EventLocation';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $extensions = [
        Addressable::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        /* @var Tab $tab */
        $tab = $fields->fieldByName('Root.Address');
        $fields->removeByName('Address');
        $fields->addFieldsToTab('Root.Main',$tab->Fields());
        $fields->removeByName('AddressHeader');

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
<?php

namespace XD\AttendableEvents\Model;

use SilverStripe\Forms\Tab;
use SilverStripe\ORM\DataObject;
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

}
<?php

namespace XD\AttendableEvents\Extension;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use XD\AttendableEvents\Forms\Fields\AttendField;
use XD\AttendableEvents\GridField\GridFieldConfig_AttendFields;
use XD\AttendableEvents\Models\EventAttendance;

class EventPageExtension extends DataExtension
{
    private static $db = [
        'ExternalTicketProvider' => 'Varchar',
        'AttendeeLimit' => 'Int', // used for override
        'SkipWaitingList' => 'Boolean', // used for override
        'AllowExternalAttendees' => 'Boolean',
        'EventWaitingListConfirmationEmailContent' => 'HTMLText',
        'EventConfirmationEmailContent' => 'HTMLText',
    ];

    private static $has_many = [
        'AttendFields' => AttendField::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.Date', [
            NumericField::create('AttendeeLimit', _t(__CLASS__ . '.AttendeeLimit', 'Attendee limit'))
                ->setDescription(_t(__CLASS__ . '.AttendeeLimitDescription', 'This value is used for all the dates above if they are set to value 0. Limit of 0 means unlimited.')),
            CheckboxField::create('SkipWaitingList', _t(__CLASS__ . '.SkipWaitingList', 'Place attendees directly in confirmed list'))
                ->setDescription(_t(__CLASS__ . '.SkipWaitingListDescription', 'This value is used for all dates if it is set.')),
            CheckboxField::create('AllowExternalAttendees', _t(__CLASS__ . '.AllowExternalAttendees', 'Allow external attendees (no login required).'))
        ]);

        /** @var Tab $tab */
        $tab = $fields->fieldByName('Root.Date');
        $tab->setTitle(_t(__CLASS__.'.Date','Dates'));

        $fields->addFieldsToTab('Root.AttendForm', [
            GridField::create(
                'AttendFields',
                _t(__CLASS__ . '.AttendFields', 'Extra inschrijf velden'),
                $this->owner->AttendFields(),
                GridFieldConfig_AttendFields::create()
            ),
            TextField::create('ExternalTicketProvider', _t(__CLASS__ . '.ExternalTicketProvider', 'External ticketprovider')),
            HTMLEditorField::create('EventWaitingListConfirmationEmailContent', _t(__CLASS__ . '.EventWaitingListConfirmationEmailContent', 'Waiting list email content'))->setRows(7)
                ->setDescription(_t(__CLASS__ . '.EventWaitingListConfirmationEmailContentDescription', 'Override default settings in Settings.')),
            HTMLEditorField::create('EventConfirmationEmailContent', _t(__CLASS__ . '.EventConfirmationEmailContent', 'Confirmation email content'))->setRows(7)
                ->setDescription(_t(__CLASS__ . '.EventConfirmationEmailContentDescription', 'Override default settings in Settings.')),
        ]);

        $tab = $fields->fieldByName('Root.AttendForm');
        $tab->setTitle(_t(__CLASS__ . '.AttendForm', 'AttendForm'));

        return $fields;
    }

    public function getAttendees()
    {
        $dateTimeIDs = $this->owner->DateTimes()->columnUnique();
        if (!empty($dateTimeIDs)) {
            return EventAttendance::get()->filter(['EventDateID' => $dateTimeIDs]); //->Sort("\"Status\" ASC, \"EventDate\".\"StartDate\" ASC");
        }
        return EventAttendance::get()->filter(['ID' => -1]);
    }
}

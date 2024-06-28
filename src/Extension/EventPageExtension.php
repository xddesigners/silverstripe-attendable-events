<?php

namespace XD\AttendableEvents\Extension;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use XD\Events\Model\EventPage;
use XD\AttendableEvents\Forms\Fields\AttendField;
use XD\AttendableEvents\GridField\GridFieldConfig_AttendeesOverview;
use XD\AttendableEvents\GridField\GridFieldConfig_AttendFields;
use XD\AttendableEvents\GridField\GridFieldConfig_EventDateTimes;
use XD\AttendableEvents\Model\EventAttendance;

/**
 * Class EventPageExtension
 * @package XD\AttendableEvents\Extension
 *
 * @property string ExternalTicketProvider
 * @property int AttendeeLimit
 * @property bool SkipWaitingList
 * @property bool AllowExternalAttendees
 * @property bool ExternalAttendeesSkipWaitingList
 * @property string EventWaitingListConfirmationEmailContent
 * @property string EventConfirmationEmailContent
 * @property bool UnattendAllowed
 * @property EventPage|EventPageExtension $owner
 * @method HasManyList AttendFields()
 */
class EventPageExtension extends DataExtension
{
    private static $db = [
        'ExternalTicketProvider' => 'Varchar',
        'AttendeeLimit' => 'Int', // used for override
        'SkipWaitingList' => 'Boolean', // used for override
        'AllowExternalAttendees' => 'Boolean',
        'ExternalAttendeesSkipWaitingList' => 'Boolean',
        'EventWaitingListConfirmationEmailContent' => 'HTMLText',
        'EventConfirmationEmailContent' => 'HTMLText',
        'UnattendAllowed' => 'Boolean'
    ];

    private static $has_many = [
        'AttendFields' => AttendField::class
    ];

    private static $defaults = [
        'AttendeeLimit' => -1
    ];

    public function updateCMSFields(FieldList $fields)
    {

        $this->syncAttendeeFields();

        $fields->removeByName(['DateTimes']);

        $config = new GridFieldConfig_EventDateTimes();

        $dateTimeField = new GridField('DateTimes', _t(__CLASS__ . '.DateTimes', 'DateTimes'), $this->owner->DateTimes()->Sort('StartDate DESC'), $config);

        $fields->addFieldToTab('Root.Date', $dateTimeField);

        $fields->addFieldsToTab('Root.Date', [
            NumericField::create('AttendeeLimit', _t(__CLASS__ . '.AttendeeLimit', 'Attendee limit'))
                ->setDescription(_t(__CLASS__ . '.AttendeeLimitDescription', 'This value is used for all the dates above if they are set to value 0. Limit of 0 means unlimited.')),
            CheckboxField::create('SkipWaitingList', _t(__CLASS__ . '.SkipWaitingList', 'Place logged in attendees directly in confirmed list.'))
                ->setDescription(_t(__CLASS__ . '.SkipWaitingListDescription', 'This value is used for all dates if it is set.')),
            CheckboxField::create('AllowExternalAttendees', _t(__CLASS__ . '.AllowExternalAttendees', 'Allow external attendees (no login required).')),
            CheckboxField::create('ExternalAttendeesSkipWaitingList', _t(__CLASS__ . '.ExternalAttendeesSkipWaitingList', 'Place external attendees directly in confirmed list.')),
            CheckboxField::create('UnattendAllowed', _t(__CLASS__ . '.UnattendAllowed', 'Allow to unattend.')),
        ]);

        /** @var Tab $tab */
        $tab = $fields->fieldByName('Root.Date');
        $tab->setTitle(_t(__CLASS__ . '.Date', 'Dates'));

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

    public function canDelete($member = null)
    {
        $dates = $this->owner->DateTimes();
        if ($dates->exists()) {
            /** @var EventDateTime|EventDateTimeExtension $date */
            foreach ($dates as $date) {
                $attendees = $date->Attendees();
                if ($attendees->exists()) {
                    return false;
                }
            }
        }
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function syncAttendeeFields()
    {
        // get fields
        $attendFields = $this->owner->AttendFields();
        if (!$attendFields->exists()) return;

        // get all attendances
        $dateTimes = $this->owner->DateTimes();
        if (!$dateTimes->exists()) return;
        $dateTimeIDs = $dateTimes->columnUnique();

        // get all eventAttendances
        $attendances = EventAttendance::get()->filter(['EventDateID' => $dateTimeIDs]);
        if( !$attendances->exists() ) return;

        foreach( $attendances as $attendance ){
            // loop fields add fields if necesary
            foreach( $attendFields as $attendField ){
                $field = $attendance->Fields()->filter(['ID'=>$attendField->ID])->first();
                if( !$field ){
                    // $attendance->Fields()->add($attendField);
                    $maxResult = DB::query('SELECT MAX(ID) AS max_id FROM AttendableEvents_EventAttendance_Fields;');
                    $firstResult = $maxResult->first();
                    $maxID = ((int) $firstResult['max_id'])+1;
                    DB::query("INSERT INTO AttendableEvents_EventAttendance_Fields (ID, AttendableEvents_EventAttendanceID, AttendableEvents_AttendFieldID) VALUES ( $maxID, $attendance->ID, $attendField->ID);");
                }
            }
        }

    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        // sync all EventAttendances with AttendFields if changed
        $this->syncAttendeeFields();
    }

}

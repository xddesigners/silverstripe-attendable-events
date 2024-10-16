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
use SilverStripe\Security\Permission;
use XD\AttendableEvents\Forms\Fields\AttendField;
use XD\AttendableEvents\GridField\GridFieldConfig_AttendeesOverview;
use XD\AttendableEvents\GridField\GridFieldConfig_AttendFields;
use XD\AttendableEvents\GridField\GridFieldConfig_EventDateTimes;
use XD\AttendableEvents\Model\EventAttendance;

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
        'EventIntakeEmailContent' => 'HTMLText',
        'EventEvaluationEmailContent' => 'HTMLText',
    ];

    private static $has_one = [
        'IntakeForm' => SiteTree::class,
        'EvaluationForm' => SiteTree::class,
    ];

    private static $has_many = [
        'AttendFields' => AttendField::class
    ];

    public function updateCMSFields(FieldList $fields)
    {

        $fields->removeByName(['DateTimes']);

        $config = new GridFieldConfig_EventDateTimes();

        $dateTimeField = new GridField('DateTimes', _t(__CLASS__ . '.DateTimes', 'DateTimes'), $this->owner->DateTimes()->Sort('StartDate DESC'), $config);

        $fields->addFieldToTab('Root.Date', $dateTimeField);

        $fields->addFieldsToTab('Root.Date', [
            NumericField::create('AttendeeLimit', _t(__CLASS__ . '.AttendeeLimit', 'Attendee limit'))
                ->setDescription(_t(__CLASS__ . '.AttendeeLimitDescription', 'This value is used for all the dates above if they are set to value 0. Limit of 0 means unlimited.')),
            CheckboxField::create('SkipWaitingList', _t(__CLASS__ . '.SkipWaitingList', 'Place attendees directly in confirmed list'))
                ->setDescription(_t(__CLASS__ . '.SkipWaitingListDescription', 'This value is used for all dates if it is set.')),
            CheckboxField::create('AllowExternalAttendees', _t(__CLASS__ . '.AllowExternalAttendees', 'Allow external attendees (no login required).')),
            CheckboxField::create('ExternalAttendeesSkipWaitingList', _t(__CLASS__ . '.ExternalAttendeesSkipWaitingList', 'Place external attendees directly in confirmed list.')),
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
            TreeDropdownField::create('IntakeFormID', _t(__CLASS__ . '.IntakeForm', 'Intake form'), SiteTree::class)
                ->setDescription(_t(__CLASS__ . '.IntakeFormDescription', 'Link to this form will be automatically sent to event attendee after subscription.')),
            HTMLEditorField::create('EventIntakeEmailContent', _t(__CLASS__ . '.EventIntakeEmailContent', 'Intake email content'))->setRows(7)
                ->setDescription(_t(__CLASS__ . '.EventIntakeEmailContentDescription', 'Override default settings in Settings.')),
            TreeDropdownField::create('EvaluationFormID', _t(__CLASS__ . '.EvaluationForm', 'Evaluation form'), SiteTree::class)
                ->setDescription(_t(__CLASS__ . '.EvaluationFormDescription', 'Link to this form can be sent to all event attendees after the event took place.')),
            HTMLEditorField::create('EventEvaluationEmailContent', _t(__CLASS__ . '.EventEvaluationEmailContent', 'Evaluation email content'))->setRows(7)
                ->setDescription(_t(__CLASS__ . '.EventEvaluationEmailContentDescription', 'Override default settings in Settings.')),
            HTMLEditorField::create('EventWaitingListConfirmationEmailContent', _t(__CLASS__ . '.EventWaitingListConfirmationEmailContent', 'Waiting list email content'))->setRows(7)
                ->setDescription(_t(__CLASS__ . '.EventWaitingListConfirmationEmailContentDescription', 'Override default settings in Settings.')),
            HTMLEditorField::create('EventConfirmationEmailContent', _t(__CLASS__ . '.EventConfirmationEmailContent', 'Confirmation email content'))->setRows(7)
                ->setDescription(_t(__CLASS__ . '.EventConfirmationEmailContentDescription', 'Override default settings in Settings.')),
        ]);

        $tab = $fields->fieldByName('Root.AttendForm');
        $tab->setTitle(_t(__CLASS__ . '.AttendForm', 'AttendForm'));

        /*
        $config = new GridFieldConfig_AttendeesOverview();
        $fields->addFieldsToTab('Root.Attendees', [
            GridField::create('Attendees', _t(__CLASS__ . '.Attendees', 'Attendees'), $this->owner->getAttendees(), $config)
                ->addExtraClass('compact-grid-field')
                ->setDescription(_t(__CLASS__ . '.AttendeesOverviewDescription', 'Note: Global list of attendees, attendees are managed per date instance.'))
        ]);
        */

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
        if(  $dates->exists() ){
            /** @var EventDateTime|EventDateTimeExtension $date */
            foreach( $dates as $date ){
                $attendees = $date->Attendees();
                if( $attendees->exists() ){
                    return false;
                }
            }
        }
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

}

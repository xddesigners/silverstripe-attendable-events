<?php

namespace XD\AttendableEvents\Extension;

use Exception;
use LeKoala\ExcelImportExport\ExcelGridFieldExportButton;
use SilverShop\HasOneField\HasOneButtonField;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use XD\AttendableEvents\GridField\GridFieldEventAttendanceDetailForm_ItemRequest;
use XD\AttendableEvents\Model\EventHost;
use XD\AttendableEvents\Model\EventAttendance;
use XD\AttendableEvents\GridField\GridFieldConfig_EventAttendees;
use XD\AttendableEvents\Model\EventDayDateTime;
use XD\AttendableEvents\Model\EventLocation;
use XD\Basic\GridField\GridFieldConfig_Sortable;
use XD\Basic\GridField\GridFieldConfig_SortableEditableInline;
use XD\Events\Form\GridFieldConfig_EventDayDateTimes;
use XD\Events\Model\EventDateTime;

/**
 * Class EventDateTimeExtension
 * @package XD\AttendableEvents\Extension
 * @property EventDateTime|EventDateTimeExtension $owner
 * @property Boolean $SkipWaitingList
 */
class EventDateTimeExtension extends DataExtension
{
    private static $db = [
        'Title' => 'Varchar',
        'AttendeeLimit' => 'Int',
        'SkipWaitingList' => 'Boolean',
        'ShowAsFull' => 'Boolean',
    ];

    private static $has_one = [
        'Location' => EventLocation::class
    ];

    private static $has_many = [
        'Attendees' => EventAttendance::class,
        'Hosts' => EventHost::class,
        'DayDateTimes' => EventDayDateTime::class
    ];

    private static $defaults = [
        'SkipWaitingList' => 1
    ];

    public function AttendeeCount()
    {
        $count = $this->owner->Attendees()->filter(['Status' => 'Confirmed'])->Count();
        $count += $this->owner->AttendingMembers()->filter(['Status' => ['Confirmed', 'SignedUp']])->Count();
        return $count;
    }

    public function getListTitle()
    {
        return $this->owner->LocationID ? $this->owner->StartDate . ' - ' . $this->owner->Location()->Title : $this->owner->StartDate;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(['Title', 'AttendeeLimit', 'ShowAsFull', 'SkipWaitingList', 'AutoSendConfirmation', 'ExternalTicketProvider',
            'EventID', 'AllDay', 'StartTime', 'EndTime', 'EndDate', 'LocationID', 'Pinned', 'PinnedForever', 'DayDateTimes']);

        if ($this->owner->ID) {
            $fields->removeByName('StartDate');
            $daysConfig = GridFieldConfig_EventDayDateTimes::create();
            $daysGrid = GridField::create('DayDateTimes', _t(__CLASS__ . '.DayDateTimes', 'Days'), $this->owner->DayDateTimes(), $daysConfig);
            $fields->addFieldToTab('Root.Main', $daysGrid);
        }

        $locationField = HasOneButtonField::create($this->owner, 'Location', 'LocationID', _t(__CLASS__ . '.Location', 'Location'));

        $fields->addFieldsToTab('Root.Main', [
            CompositeField::create([
                FieldGroup::create([
                    NumericField::create('AttendeeLimit', _t(__CLASS__ . '.AttendeeLimit', 'Attendee limit')),
                    NumericField::create('AttendeeCount', _t(__CLASS__ . '.Attendees', 'Attendees'), $this->summaryAttendeeCount())->setDisabled(true),
                    NumericField::create('ConfirmedAttendeeCount', _t(__CLASS__ . '.ConfirmedAttendees', 'ConfirmedAttendees'), $this->summaryConfirmedAttendeeCount())->setDisabled(true),
                ]),
                CheckboxField::create('ShowAsFull', _t(__CLASS__ . '.ShowAsFull', 'Toon event als vol')),
                CheckboxField::create('SkipWaitingList', _t(__CLASS__ . '.SkipWaitingList', 'Place attendees directly in confirmed list'))
                    ->setDescription(_t(__CLASS__ . '.SkipWaitingListDescription', 'Attendees will receive an automatic confirmation email.')),
                $locationField,
            ])->setTitle(_t(__CLASS__ . '.EventDetails', 'Event details')),
            CompositeField::create([
                CheckboxField::create('Pinned', _t(__CLASS__ . '.Pinned', 'Pinned')),
                CheckboxField::create('PinnedForever', _t(__CLASS__ . '.PinnedForever', 'Pinned forever')),
            ])->setTitle(_t(__CLASS__ . '.EventOptions', 'Event options')),
        ]);

        $fields->addFieldsToTab('Root.Attendees', [
            $attendeeGrid = GridField::create(
                'Attendees',
                _t(__CLASS__ . '.Attendees', 'Aanmeldingen'),
                $this->owner->Attendees()->exclude(['Status' => 'Confirmed']),
                GridFieldConfig_EventAttendees::create()->removeComponentsByType(GridFieldAddNewButton::class)
            )->addExtraClass('compact-grid-field'),
            LiteralField::create('attendee-seperator', '<hr>'),
            $confirmedAttendeeGrid = GridField::create(
                'ConfirmedAttendees',
                _t(__CLASS__ . '.ConfirmedAttendees', 'Deelnemers'),
                $this->owner->ConfirmedAttendees(),
                GridFieldConfig_EventAttendees::create()
            )->addExtraClass('compact-grid-field'),
        ]);

        $tab = $fields->fieldByName('Root.Attendees');
        $tab->setTitle(_t(__CLASS__ . '.Attendees', 'Attendees'));

        if ($this->owner->ConfirmedAttendees()->exists()) {
            /** @var ExcelGridFieldExportButton $exportButton */
            $exportButton = $confirmedAttendeeGrid->getConfig()->getComponentByType(ExcelGridFieldExportButton::class);
            /** @var EventAttendance $attendee */
            $attendee = $this->owner->ConfirmedAttendees()->first();
            $columns = $attendee->config()->get('exported_fields');
            $exportButton->setExportColumns($columns);
        }

        $fields->addFieldsToTab(
            'Root.Hosts', [
                GridField::create(
                    'Hosts',
                    _t(__CLASS__ . '.Hosts', 'Hosts'),
                    $this->owner->Hosts(),
                    GridFieldConfig_Sortable::create()
                )->addExtraClass('compact-grid-field'),
            ]
        );

        return $fields;
    }

    /**
     * Utility to send a test mail to the current cms user
     */
    public function testEventConfirmation()
    {
        $currentUser = Security::getCurrentUser();
        if (!$currentUser->Email) {
            return false;
        }

        $to = $currentUser->Email;
        $from = Email::config()->get('admin_email');
        $email = new Email($from, $to);

        $event = $this->owner->Event();
        $subject = _t(__CLASS__ . '.EventConfirmationEmailSubject', 'Jouw inschrijving voor {event} op {date} is bevestigd', null, [
            'date' => $this->owner->dbObject('StartDate')->Format('EEEE d MMMM'),
            'event' => $event->Title
        ]);

        $email->setSubject($subject);

        // default body from Settings
        $siteConfig = SiteConfig::current_site_config();
        $body = $siteConfig->EventConfirmationEmailContent;

        // override on event
        if (!empty($event->EventConfirmationEmailContent)) {
            $body = $event->EventConfirmationEmailContent;
        }

        $fakeAttendance = EventAttendance::create([
            'MemberID' => $currentUser->ID,
            'EventDateID' => $this->owner->ID,
            'Status' => 'Confirmed',
        ]);

        $email->setHTMLTemplate('XD\AttendableEvents\Email\EventConfirmationEmail.ss');
        $email->setData($fakeAttendance);
        $email->addData('Body', $body);

        return $email->send();
    }

    public function sendEventConfirmation()
    {
        $sent = [];
        foreach ($this->owner->Attendees()->filter(['Status' => 'Confirmed', 'EventConfirmationEmailSent' => null]) as $attendee) {
            /* @var EventAttendance $attendee */
            try {
                $sent[] = $attendee->sendEventConfirmationEmail() ? 1 : 0;
            } catch (Exception $e) {
                $sent[] = 0;
            }
        }

        return !empty($sent) && min($sent);
    }

    public function sendEventEvaluation()
    {
        $sent = [];
        foreach ($this->owner->Attendees()->filter(['Status' => 'Confirmed', 'EvaluationEmailSent' => null]) as $attendee) {
            /* @var EventAttendance $attendee */
            try {
                $sent[] = $attendee->sendEvaluationEmail() ? 1 : 0;
            } catch (Exception $e) {
                $sent[] = 0;
            }
        }

        return !empty($sent) && min($sent);
    }


    public function getUnattendLink($memberId = null)
    {
        return Controller::join_links(array_filter([
            $this->owner->Event()->Link(),
            'unattend',
            $this->owner->ID,
            $memberId
        ]));
        // return $this->owner->Event()->Link("unattend/{$this->owner->ID}");
    }

    /**
     * @return mixed
     * used in summary
     */
    public function AutoAttendeeLimit()
    {
        return $this->owner->AttendeeLimit ?: $this->owner->Event()->AttendeeLimit;
    }

    /**
     * @return mixed
     * used in summary
     */
    public function AutoSkipWaitingList()
    {
        return $this->owner->SkipWaitingList ?: $this->owner->Event()->SkipWaitingList;
    }

    /**
     * @return mixed
     * used in summary
     */
    public function summaryAttendeeCount()
    {
        return $this->owner->Attendees()->Count();
    }

    /**
     * @return mixed
     * used in summary
     */
    public function summaryConfirmedAttendeeCount()
    {
        return $this->owner->ConfirmedAttendees()->Count();
    }

    public function getAttendableDateOption()
    {
        // todo retun rendered template with embedded 'unattend' action
        return $this->owner->renderWith('XD\AttendableEvents\Field\AttendableDateOption');
    }

    public function canDelete($member = null)
    {
        if ($this->owner->Attendees()->exists()) {
            return false;
        }
        return true;
    }

    public function getCanAttend()
    {
        return $this->owner->dbObject('StartDate')->InFuture();
    }

    public function getIsAttending($member = null)
    {
        // we only add members to the waiting list, so check that
        return $this->getIsOnWaitingList($member);
    }

    public function getAttendingMembers()
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return null;
        }
        return $this->owner->Attendees()->filter('MemberID', $member->ID);
    }

    public function getIsOnWaitingList($member = null)
    {
        if (!$member && !($member = Security::getCurrentUser())) {
            return false;
        }

        return (bool)$this->owner->Attendees()->find('MemberID', $member->ID);
    }

    public function ConfirmedAttendees()
    {
        return $this->owner->Attendees()->filter(['Status' => 'Confirmed']);
    }

    public function getIsAttendable()
    {
        return true; // $this->owner->AutoAttendeeLimit() > 0 || ;
    }

    public function getIsUnlimited()
    {
        $limit = $this->owner->AutoAttendeeLimit();
        return $limit == -1 || $limit == 0;
    }

    public function getNumberPlacesAvailable()
    {
        if ($this->owner->ShowAsFull) {
            return 0;
        }

        return $this->owner->AutoAttendeeLimit() - $this->owner->ConfirmedAttendees()->count();
    }

    public function getPlacesAvailable()
    {
        if ($this->owner->ShowAsFull) {
            return false;
        }

        if ($this->getIsUnlimited()) {
            // always places available
            return true;
        }

        return $this->owner->AutoAttendeeLimit() > $this->owner->ConfirmedAttendees()->count();
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        // force change to trigger onAfterWrite()
        $this->owner->LastEdited = DBDatetime::now()->Rfc2822();
        $this->owner->Title = $this->owner->StartDate;
        if ($location = $this->owner->Location()) {
            $this->owner->Title = $this->owner->StartDate . ' - ' . $location->Title;
        }

        // sync date
        $days = $this->owner->DayDateTimes();
        if ($days->exists()) {
            $firstDate = $days->first();
            if ($firstDate && $firstDate->StartDate) {
                $this->owner->StartDate = $firstDate->StartDate;
                $this->owner->StartTime = $firstDate->StartTime;
                $this->owner->EndTime = $firstDate->EndTime;
            }
        }

    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $days = $this->owner->DayDateTimes();
        if (!$days->exists() && $this->owner->StartDate) {
            // auto create
            $day = new EventDayDateTime();
            $day->EventDateTimeID = $this->owner->ID;
            $day->StartDate = $this->owner->StartDate;
            $day->StartTime = $this->owner->StartTime;
            $day->EndTime = $this->owner->EndTime;
            $day->write();
        }
    }

    public function ICS()
    {

        $event = $this->owner->Event();
        $fileName = $event->URLSegment . '-' . $this->owner->StartDate;
        $start = $this->owner->StartDate . ' ' . $this->owner->StartTime;
        $end = $this->owner->StartDate . ' ' . $this->owner->EndTime;

        $lines[] = "BEGIN:VCALENDAR";
        $lines[] = "VERSION:2.0";
        $lines[] = "METHOD:PUBLISH";

        $lines[] = "BEGIN:VEVENT";
        $lines[] = "DTSTART:" . gmdate("Ymd\THis\Z", strtotime($start));
        $lines[] = "DTEND:" . gmdate("Ymd\THis\Z", strtotime($end));
        if ($event->LocationID) {
            $lines[] = "LOCATION:" . $event->Location()->Title;
        }
        $lines[] = "TRANSP:OPAQUE";
        $lines[] = "SEQUENCE:0";
        $lines[] = "UID:";
        $lines[] = "DTSTAMP:" . gmdate("Ymd\THis\Z");
        $lines[] = "SUMMARY:" . $event->Title;
        // $lines[] = "DESCRIPTION:" . $event->;
        $lines[] = "PRIORITY:1";
        $lines[] = "CLASS:PUBLIC";
        $lines[] = "BEGIN:VALARM";
        $lines[] = "TRIGGER:-PT1440M";
        $lines[] = "ACTION:DISPLAY";
        $lines[] = "END:VALARM";
        $lines[] = "END:VEVENT";
        $lines[] = "END:VCALENDAR";

        $data = implode("\n", $lines);

        header("Content-type:text/calendar");
        header('Content-Disposition: attachment; filename="' . $fileName . '.ics"');
        Header('Content-Length: ' . strlen($data));
        Header('Connection: close');
        echo $data;

    }

}

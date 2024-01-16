<?php

namespace XD\AttendableEvents\Model;

use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;
use XD\AttendableEvents\Forms\Fields\AttendField;
use XD\Events\Model\EventDateTime;
use XD\Events\Model\EventPage;

/**
 * Class EventAttendance
 * @package XD\AttendableEvents\Model
 * @method Member Member
 * @method EventDateTime EventDate
 */
class EventAttendance extends DataObject
{
    private static $table_name = 'AttendableEvents_EventAttendance';

    private static $db = [
        'Status' => 'Enum("WaitingList,Confirmed,MemberCancelled,AdminCancelled", "WaitingList")',
        'WaitingListConfirmationEmailSent' => 'DBDatetime',
        'EventConfirmationEmailSent' => 'DBDatetime',
        'Name' => 'Varchar',
        'Email' => 'Varchar',
        'Phone' => 'Varchar',
        'Organisation' => 'Varchar',
    ];

    private static $has_one = [
        'Member' => Member::class,
        'EventDate' => EventDateTime::class
    ];

    private static $many_many = [
        'Fields' => AttendField::class
    ];

    private static $many_many_extraFields = [
        'Fields' => [
            'Value' => 'Varchar'
        ]
    ];

    private static $summary_fields = [
        'StatusNice' => 'Status', 
        'Title',
        'AttendeeOrganisation',
        'EventDate.StartDate' => 'Start',
        'EventConfirmationEmailSent',
        'WaitingListConfirmationEmailSent',
    ];

    private static $exported_fields = [
        'StatusNice' => 'Status',
        'AttendeeName' => 'Naam',
        'AttendeeEmail' => 'Email', 
        'AttendeePhone' => 'Telefoon',
        'EventDate.StartDate' => 'EventDate',
        'ExtraFields' => 'Extra velden'
    ];

    public function getStatusNice()
    {
        $status = $this->Status ?? $this->dbObject('Status')->getDefault();
        return _t(__CLASS__ . ".Status_{$status}", $status);
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['MemberID', 'EventDateID', 'Fields']);
        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create('MemberID', _t(__CLASS__ . '.Member', 'Deelnemer'), Member::get()->map()->toArray())->setEmptyString(_t(__CLASS__ . '.ChooseMember', 'Choose member'))
        ]);

        $fields->replaceField('Status', DropdownField::create(
            'Status',
            _t(__CLASS__ . '.Status', 'Status'),
            array_map(
                fn($status) => _t(__CLASS__ . ".Status_{$status}", $status), 
                $this->dbObject('Status')->enumValues()
            )
        ));

        if ($this->EventDateID) {
            /** @var EventPage $event */
            $event = $this->EventDate()->Event();
            $otherDates = $event->DateTimes(); //->exclude(['ID'=>$this->EventDateID]);
            if ($otherDates->exists()) {
                $field = new DropdownField('EventDateID', _t(__CLASS__ . '.EventDate', 'Datum'), $otherDates->map('ID', 'getListTitle'));
                $fields->insertAfter('Status', $field);
            }
        }

        // todo interface for adding attendee
        // member or outsider
        foreach ($this->Fields() as $attendeeField) {
            $field = $attendeeField->getFormField();
            $values = json_decode($attendeeField->Value ?? '', true);
            if ($values && is_array($values)) {
                foreach($values as $key => $val) {
                    $itemField = clone $field;
                    $name = $itemField->getName();
                    $itemField->setName($name . "[$key]");
                    $itemField->setValue($val);
                    $fields->addFieldToTab('Root.Main', $itemField);
                }
            } else {
                $fields->addFieldToTab('Root.Main', $field);
            }
        }

        return $fields;
    }

    public function getParsedFields()
    {
        $parsed = new ArrayList();
        foreach ($this->Fields() as $attendeeField) {
            $values = json_decode($attendeeField->Value ?? '', true);
            if (is_array($values)) {
                // Field is an multivalue field
                $value = implode(', ', $values);
            } else {
                // Field is single value
                $value = $attendeeField->Value;
            }

            $parsed->push(new ArrayData([
                'Title' => $attendeeField->Title,
                'Value' => $value
            ]));
        }

        return $parsed;
    }

    public function relField($fieldName)
    {

        // default case
        if ($value = parent::relField($fieldName)) {
            return $value;
        }

        if( strpos($fieldName, 'AttendField')!==false ) {
            $id = explode('_',$fieldName)[1];
            if( $field = $this->Fields()->filter(['AttendableEvents_AttendFieldID'=>$id])->first()){
                return $field->Value;
            }

        }
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Update any changes to extra fields data
        if ($this->exists()) {
            $this->updateExtraFields();
        }
    }

    public function updateExtraFields()
    {
        if ($this->Fields()->count()) {
            foreach ($this->Fields() as $attendField) {
                $name = $attendField->getFieldName();
                $value = $this->{$name};
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                if ($this->isChanged($name)) {
                    $this->Fields()->add($attendField, [
                        'Value' => $this->{$name}
                    ]);
                }
            }
        } elseif (($event = $this->getEvent()) && $event->AttendFields()->count()) {
            foreach ($event->AttendFields() as $field) {
                $this->Fields()->add($field);
            }
        }
    }

    public function getExtraFields()
    {
        if (!($fields = $this->Fields()) || !$fields->exists()) {
            return null;
        }

        $extraFields = [];
        $values = $fields->map('Title', 'Value')->toArray();
        foreach ($values as $title => $value) {
            $values = json_decode($value ?? '', true);
            if ($values && is_array($values)) {
                foreach ($values as $key => $val) {
                    $extraFields[] = "$title: $val";        
                }
            } else {
                $extraFields[] = "$title: $value";
            }
        }

        return implode(' | ', $extraFields);
    }

    /**
     * @return false|\SilverStripe\CMS\Model\SiteTree
     */
    public function getEvent()
    {
        $eventDate = $this->EventDate();
        if ($eventDate->exists()) {
            return $eventDate->Event();
        }
        return false;
    }

    private function memberOrAttendeeField($field, $memberField = null)
    {
        if (!($member = $this->Member()) && !$member->exists()) {
            return $this->{$field};
        }

        if (!$memberField) {
            $memberField = $field;
        }

        return $member->{$memberField};
    }

    public function getAttendeePhone()
    {
        return $this->memberOrAttendeeField('Phone');
    }

    public function getAttendeeEmail()
    {
        return $this->memberOrAttendeeField('Email');
    }

    public function getAttendeeName()
    {
        return $this->memberOrAttendeeField('Name');
    }

    public function getTitle()
    {
        return $this->getAttendeeName();
    }

    protected function onBeforeDelete()
    {
        parent::onBeforeDelete();
        $this->Fields()->removeAll();
    }


    // send when created
    public function sendWaitingListConfirmationEmail()
    {
        if (!empty($this->WaitingListConfirmationEmailSent)) {
            return false;
        }

        $event = $this->getEvent();
        if (!$event) {
            return false;
        }

        if (!$this->MemberID || !($member = $this->Member())) {
            $to = $this->Email;
        } else {
            $to = $member->hasMethod('getCurrentEmail') ? $member->getCurrentEmail() : $member->Email;
        }

        if (!$to) {
            return false;
        }

        $from = Email::config()->get('admin_email');
        $email = new Email($from, $to);
        $email->setBCC($from);

        $days = $this->EventDate()->DayDateTimes();
        if( $days->exists() && $days->Count() > 1 ){
            $subject = _t(__CLASS__ . '.WaitingListConfirmationEmailSubject', 'Bedankt voor je inschrijving voor {event}', null, [
                'event' => $this->EventDate()->Event()->Title
            ]);
        } else {
            $subject = _t(__CLASS__ . '.WaitingListConfirmationEmailSubject', 'Bedankt voor je inschrijving voor {event} op {date}', null, [
                'date' => $this->EventDate()->dbObject('StartDate')->Format('EEEE d MMMM'),
                'event' => $this->EventDate()->Event()->Title
            ]);
        }

        $email->setSubject($subject);

        // default body from Settings
        $siteConfig = SiteConfig::current_site_config();
        $body = $siteConfig->EventWaitingListConfirmationEmailContent;

        // override on event
        if (!empty($event->EventWaitingListConfirmationEmailContent)) {
            $body = $event->EventWaitingListConfirmationEmailContent;
        }

        $email->setHTMLTemplate('XD\AttendableEvents\Email\WaitingListConfirmationEmail.ss');
        $email->setData($this);
        $email->addData('Body', $body);
        $email->send();

        $this->WaitingListConfirmationEmailSent = DBDatetime::now()->getValue();
        $this->write();
        return true;
    }

    // send when status is set to confirmed and mail has not been send
    public function sendEventConfirmationEmail()
    {
        if (!empty($this->EventConfirmationEmailSent)) {
            return false;
        }

        $event = $this->getEvent();
        if (!$event) {
            return false;
        }

        if (!$this->MemberID || !($member = $this->Member())) {
            $to = $this->Email;
        } else {
            $to = $member->hasMethod('getCurrentEmail') ? $member->getCurrentEmail() : $member->Email;
        }

        if (!$to) {
            return false;
        }

        $from = Email::config()->get('admin_email');
        $email = new Email($from, $to);
        $email->setBCC($from);

        $days = $this->EventDate()->DayDateTimes();
        if( $days->exists() && $days->Count() > 1 ){
            $subject = _t(__CLASS__ . '.EventConfirmationEmailSubject', 'Jouw inschrijving voor {event} is bevestigd', null, [
                'event' => $this->EventDate()->Event()->Title
            ]);
        } else {
            $subject = _t(__CLASS__ . '.EventConfirmationEmailSubject', 'Jouw inschrijving voor {event} op {date} is bevestigd', null, [
                'date' => $this->EventDate()->dbObject('StartDate')->Format('EEEE d MMMM'),
                'event' => $this->EventDate()->Event()->Title
            ]);
        }

        $email->setSubject($subject);

        // default body from Settings
        $siteConfig = SiteConfig::current_site_config();
        $body = $siteConfig->EventConfirmationEmailContent;

        // override on event
        if (!empty($event->EventConfirmationEmailContent)) {
            $body = $event->EventConfirmationEmailContent;
        }

        $email->setHTMLTemplate('XD\AttendableEvents\Email\EventConfirmationEmail.ss');
        $email->setData($this);
        $email->addData('Body', $body);
        $email->send();

        $this->EventConfirmationEmailSent = DBDatetime::now()->getValue();
        $this->write();
        return true;
    }

}

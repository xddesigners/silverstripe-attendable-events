<?php

namespace XD\AttendableEvents\Model;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\UserForms\Model\Submission\SubmittedFormField;
use SilverStripe\View\ArrayData;
use VGS\Members\Model\VGSMember;
use XD\AttendableEvents\Forms\Fields\AttendField;
use XD\Basic\Forms\Fields\AttendMemberDietField;
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
        'Status' => 'Enum("WaitingList,Confirmed,MemberCancelled,AdminCancelled")',
        'WaitingListConfirmationEmailSent' => 'DBDatetime',
        'EventConfirmationEmailSent' => 'DBDatetime',
        'IntakeEmailSent' => 'DBDatetime',
        'EvaluationEmailSent' => 'DBDatetime',
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
        'Status',// => 'Status',
        'Title', // => 'Title',
        'AttendeeOrganisation', // => 'Organisation',
        'EventDate.StartDate' => 'Start',
        'EventDate.Location.Title' => 'Locatie', // => 'Location',
        'EventConfirmationEmailSent', // => 'Confirmation sent',
        'IntakeEmailSent', // => 'Intake sent',
        'EvaluationEmailSent', // => 'Evaluation sent',
    ];

    private static $exported_fields = [
        'Status' => 'Status',
        'AttendeeNumber' => 'Lidnummer',
        'AttendeeName' => 'Naam',
        'AttendeeEmail' => 'Email', 
        'AttendeePhone' => 'Telefoon',
        'AttendeeOrganisation' => 'Organisatie',
        'EventDate.StartDate' => 'EventDate',
        'EventDate.Location.Title' => 'Location',// niet gevonden
        'ExtraFields' => 'Extra velden'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['MemberID', 'EventDateID', 'Fields']);
        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create('MemberID', _t(__CLASS__ . '.Member', 'Deelnemer'), Member::get()->map()->toArray())->setEmptyString(_t(__CLASS__ . '.ChooseMember', 'Choose member'))
        ]);

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
            $value = $attendeeField->Value ? $attendeeField->Value : '';
            $values = $value ? json_decode($value, true) : false;
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
            $values = json_decode(''.$attendeeField->Value, true);
            // Field is a diet field
            if ($attendeeField instanceof AttendMemberDietField) {
                $value = [];
                foreach ($values as $memberId => $fieldValue) {
                    if ($member = Member::get_by_id($memberId)) {
                        $value[] = "{$member->getName()}: $fieldValue";
                    } else {
                        $value[] = $fieldValue;
                    }
                }

                $value = implode(', ', $values);
            } else if (is_array($values)) {
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


//
//        // check values for a form field with the matching name.
//        $formField = SubmittedFormField::get()->filter(array(
//            'ParentID' => $this->ID,
//            'Name' => $fieldName
//        ))->first();
//
//        if ($formField) {
//            return $formField->getFormattedValue();
//        }
    }


    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Update any changes to extra fields data
        if ($this->exists()) {
            if ($this->Fields()->count()) {
                // $changes = $this->getChangedFields();
                // foreach ($this->Fields() as $attendeeField) {
                //     $name = $attendeeField->getFieldName();
                //     $oldval = $attendeeField->Value;
                //     $oldvalArr = json_decode($oldval, true);
                //     if ($oldvalArr && is_array($oldvalArr)) {
                //         $value = [];
                //         foreach ($oldvalArr as $key => $val) {
                //             $arrName = $name . "[$key]";
                //             $value[$key] = $this->{$arrName};
                //         }
                //     } else {
                //         $value = $this->{$name};
                //     }

                //     if (is_array($value)) {
                //         $value = json_encode($value);
                //     }

                //     // fixme: cant update CheckboxSet field data here ..?

                //     if ($oldval != $value) {
                //         $this->Fields()->add($attendeeField, [
                //             'Value' => $value
                //         ]);
                //     }
                // }
                foreach ($this->Fields() as $attendField) {
                    $name = $attendField->getFieldName();
                    $value = $this->{$name};
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }

                    // fixme: cant update CheckboxSet field data here ..?

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

    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if (!$this->IntakeEmailSent) {
            $event = $this->getEvent();
            if ($event && $event->IntakeForm()->exists()) {
                // send intake form message
                $this->sendIntakeEmail();
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
            $values = json_decode($value ? $value : '', true);
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

    public function getAttendeePhone()
    {
        $phone = $this->Phone;
        if (($member = $this->Member()) && $member->exists()) {
            if ($member instanceof VGSMember) {
                return $member->getFunctionPhone() ?? $member->PrivatePhone;
            }

            // return $member->Telephone ? $member->Telephone : $member->MobilePhone;
            return '-';
        }

        return $phone;
    }

    public function getAttendeeEmail()
    {
        $email = $this->Email;
        if (($member = $this->Member()) && $member->exists()) {
            return $member->Email;
            // return $member->hasMethod('getCurrentEmail') ? $member->getCurrentEmail() : $member->Email;
        }

        return $email;
    }

    public function getAttendeeName()
    {
        $name = $this->Name;
        if (($member = $this->Member()) && $member->exists()) {
            $name = $member->getName();
        }

        return $name;
    }

    public function getAttendeeNumber()
    {
        if (($member = $this->Member()) && $member->exists() && $member instanceof VGSMember) {
            return $member->MembershipNumber;
        }

        return '-';
    }

    public function getAttendeeOrganisation()
    {
        if (($member = $this->Member()) && $member->exists() && $member instanceof VGSMember) {
            if( $organisation = $member->getCurrentOrganisation() ){
                return $organisation->Title;
            }
        }

        return $this->Organisation;
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

    // send when created and if IntakeForm was selected on Event
    public function sendEvaluationEmail()
    {
        if (!empty($this->EvaluationEmailSent)) {
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


        $days = $this->EventDate()->DayDateTimes();
        if( $days->exists() && $days->Count() > 1 ){
            $subject = _t(__CLASS__ . '.EventEvaluationEmailSubject', 'Evaluatie van {event}', null, [
                'event' => $this->EventDate()->Event()->Title
            ]);
        } else {
            $subject = _t(__CLASS__ . '.EventEvaluationEmailSubject', 'Evaluatie van {event} op {date}', null, [
                'date' => $this->EventDate()->dbObject('StartDate')->Format('EEEE d MMMM'),
                'event' => $this->EventDate()->Event()->Title
            ]);
        }

        $email->setSubject($subject);

        // default body from Settings
        $siteConfig = SiteConfig::current_site_config();
        $body = $siteConfig->EventEvaluationEmailContent;

        // override on event
        if (!empty($event->EventIntakeEmailContent)) {
            $body = $event->EventIntakeEmailContent;
        }

        $link = $event->EvaluationForm()->AbsoluteLink();

        if (strpos($body, '[EVALUATION_FORM]') !== false) {
            // replace
            $body = str_replace('[EVALUATION_FORM]', '<a href="' . $link . '" target="_blank">Open formulier &raquo;</a>', $body);
        } else {
            // append
            $body .= '<p><a href="' . $link . '" target="_blank">Open formulier &raquo;</a></p>';
        }

        $email->setHTMLTemplate('XD\AttendableEvents\Email\EventEvaluationEmail.ss');
        $email->setData($this);
        $email->addData('Body', $body);

        if ($email->send()) {
            $this->EvaluationEmailSent = DBDatetime::now()->getValue();
            $this->write();
            return true;
        }

        return false;
    }

    // send when created and if IntakeForm was selected on Event
    public function sendIntakeEmail()
    {
        if (!empty($this->IntakeEmailSent)) {
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

        $days = $this->EventDate()->DayDateTimes();
        if( $days->exists() && $days->Count() > 1 ){
            $subject = _t(__CLASS__ . '.EventIntakeEmailSubject', 'Vragenlijst voor {event}', null, [
                'event' => $this->EventDate()->Event()->Title
            ]);
        } else {
            $subject = _t(__CLASS__ . '.EventIntakeEmailSubject', 'Vragenlijst voor {event} op {date}', null, [
                'date' => $this->EventDate()->dbObject('StartDate')->Format('EEEE d MMMM'),
                'event' => $this->EventDate()->Event()->Title
            ]);
        }

        $email->setSubject($subject);

        // default body from Settings
        $siteConfig = SiteConfig::current_site_config();
        $body = $siteConfig->EventIntakeEmailContent;

        // override on event
        if (!empty($event->EventIntakeEmailContent)) {
            $body = $event->EventIntakeEmailContent;
        }

        $link = $event->IntakeForm()->AbsoluteLink();

        if (strpos($body, '[INTAKE_FORM]') !== false) {
            // replace
            $body = str_replace('[INTAKE_FORM]', '<a href="' . $link . '" target="_blank">Open formulier &raquo;</a>', $body);
        } else {
            // append
            $body .= '<p><a href="' . $link . '" target="_blank">Open formulier &raquo;</a></p>';
        }

        $email->setHTMLTemplate('XD\AttendableEvents\Email\EventIntakeEmail.ss');
        $email->setData($this);
        $email->addData('Body', $body);

        if ($email->send()) {
            $this->IntakeEmailSent = DBDatetime::now()->getValue();
            $this->write();
            return true;
        }

        return false;
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

        if ($email->send()) {
            $this->WaitingListConfirmationEmailSent = DBDatetime::now()->getValue();
            $this->write();
            return true;
        }

        return false;
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

        if ($email->send()) {
            $this->EventConfirmationEmailSent = DBDatetime::now()->getValue();
            $this->write();
            return true;
        }

        return false;
    }

}

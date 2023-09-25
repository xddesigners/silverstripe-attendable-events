<?php

namespace XD\AttendableEvents\Forms;

use DateTime;
use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use XD\AttendableEvents\Extension\Attendable;
use XD\AttendableEvents\Extension\EventControllerExtension;
use XD\AttendableEvents\Extension\EventDateTimeExtension;
use XD\AttendableEvents\Forms\Fields\AttendField;
use XD\AttendableEvents\Model\EventAttendance;
use XD\Basic\Extensions\MemberExtension;
use XD\Basic\Forms\Fields\AttendMemberDietField;
use XD\Events\Model\EventDateTime;
use XD\Events\Model\EventPage;
use XD\Events\Model\EventPageController;

class AttendForm extends Form
{
    const DEFAULT_NAME = 'AttendForm';

    private static $allow_external_attendees = true;

    public function __construct(RequestHandler $controller)
    {
        /** @var EventPageController|EventControllerExtension $controller */
        /** @var EventPage $event */
        $event = $controller->data();
        $dates = $controller->getAttendableDates();
        $dates = $controller->getUpcomingDates();

        // todo: create date fields

        $attendableDatesField = $this->createAttendableDatesField($dates);
        $fields = new FieldList([
            $attendableDatesField,
        ]);
        

        // check expiration
        $eventExpired = false;
        if (!$dates->count()) {
            $eventExpired = true;
        }

        $lastAvailableDate = DBDatetime::create()->setValue($dates->max('StartDate'));
        if ($lastAvailableDate->InPast()) {
            $eventExpired = true;
        }
        
        if( $eventExpired ){
            $this->addExtraClass('attend-form--expired');
        }

        $externalTicketProvider = $event->ExternalTicketProvider;
        if( $externalTicketProvider ){
            $this->addExtraClass('attend-form--external');
        } elseif(!$eventExpired) {
            $memberFields = $this->createMemberFields($controller);
            $fields->push($memberFields);
        }

        $requiredFields = new RequiredFields(['AttendableDates']);
        if (isset($memberFields) && $memberFields instanceof CompositeField) {
            foreach ($memberFields->getChildren()->column('Name') as $required) {
                $requiredFields->addRequiredField($required);
            }
        }

        $action = $this->createFormAction($dates, $event);

        // attendfields on event instead of date?

        if ($action->getName() === 'action_attend' && ($attendFields = $event->AttendFields()) && $attendFields->exists()) {
            // add configured form fields
            $members = $this->getMembers();
            /** @var AttendField $attendField */
            foreach ($attendFields as $attendField) {
                $field = $attendField->getFormField($members);
                $fields->add($field);
                if ($attendField->Required) {
                    if( get_class($field) == CompositeField::class ){
                     $children = $field->getChildren();
                     foreach( $children as $child ){
                         $requiredFields->addRequiredField($child->getName());
                     }

                    } else {
                        $requiredFields->addRequiredField($field->getName());
                    }
                }
            }
        }


        $actions = new FieldList([$action]);
        parent::__construct($controller, self::DEFAULT_NAME, $fields, $actions, $requiredFields);

        if ($event->AllowExternalAttendees && !Security::getCurrentUser() && $this->hasMethod('enableSpamProtection') )   {
            $this->enableSpamProtection();
        }

        $this->extend('updateForm');

    }

    private function getMembers()
    {
        $member = Security::getCurrentUser();

        if (!$member) {
            return new ArrayList();
        }

        $members = new ArrayList([$member]);
        $this->extend('updateMembers', $members);

        return $members;
    }

    private function createAttendableDatesField($dates)
    {
        // op event een of meerdere opties beschikbaar

        if ($dates->count() === 0) {
            return new LiteralField('NoOptions', '<p>Momenteel geen datum bekend</p>');
        }

        if ($dates->count() === 1) {
            $dateSelectTitle = _t(__CLASS__ . '.AttendableDatesSingle', 'Datum');
        } else {
            $dateSelectTitle = _t(__CLASS__ . '.AttendableDates', 'Selecteer datum');
        }

        $field = CheckboxSetField::create(
            'AttendableDates',
            $dateSelectTitle,
            $dates->map('ID', 'AttendableDateOption')->toArray()
        )->addExtraClass('attendable-dates');

        $disabled = [];
        $attending = [];
        foreach ($dates as $date) {
            // disabled wanneer:
            // 1. in het verleden
            // 2. geen plek meer beschikbaar

            if ($date->dbObject('StartDate')->InPast() || !$date->getPlacesAvailable() ) {
                $disabled[$date->ID] = $date->ID;
            }

            // Select when attending
            // todo: check who of the managed members is attending
            $members = $this->getMembers();
            foreach ($members as $member) {
                if ($date->getIsAttending($member)) {
                    $attending[$date->ID] = $date->ID;
                }    
            }
            if ($date->getIsAttending()) {
                $attending[$date->ID] = $date->ID;
            }
        }

        $field->setDisabledItems($disabled);

        $field->setValue($attending);

        // Wanneer er maar 1 optie beschikbaar is, plaats deze in een hidden field
        if ($dates->count() === 1 ) {
            // $field->setValue([$dates->first()->ID]);
            $field = CompositeField::create([
                HiddenField::create('AttendableDates', _t(__CLASS__ . '.AttendableDates', 'Selecteer datum'), $dates->first()->ID),
                $field->setName('DatePreview')->setDisabled(true)->addExtraClass('single-option-disabled')
            ]);
        }

        return $field;
    }

    private function createMemberFields($controller)
    {
        $members = $this->getMembers();

        if ($members && $members->count() > 1) {
            return CheckboxSetField::create(
                'Attendees',
                _t(__CLASS__ . '.Attendees', 'Deelnemers'),
                $members->map()->toArray()
            );
        } elseif ($members && $members->first()) {
            /* @var Member|MemberExtension|\XD\Lea\Extensions\MemberExtension $member */

            $member = $members->first();
            return CompositeField::create(
                [
                    HiddenField::create('Attendee', _t(__CLASS__ . '.Attendee', 'Deelnemer'), $member->ID),
                    HeaderField::create('MemberHeader', _t(__CLASS__ . '.MemberHeader', 'Jouw gegevens'), 5),
                    TextField::create('LoggedInName', _t(__CLASS__ . '.LoggedInName', 'Ingelogd als'), $member->getName())->setDisabled(true),
                ]
            );

        }

        if (self::config()->get('allow_external_attendees')) {
            return CompositeField::create([
                HeaderField::create('MemberHeader', _t(__CLASS__ . '.MemberHeader', 'Jouw gegevens'), 5),
                LiteralField::create('AskLogin', _t(
                    __CLASS__ . '.AskLogin', 
                    '<p>Bent u lid van de vereniging? <a href="/Security/login?BackURL={link}">Log dan eerst in.</a> <br>Niet-leden kunnen zich aanmelden via onderstaand formulier.</p>',
                    null,
                    ['link' => $controller->Link()]
                )),
//                LiteralField::create('MemberschipNote', _t(__CLASS__ . '.MemberschipNote', '<div class="callout">Let op: U bent als niet automatisch lid. <a href="/over-ons/vereniging/lidmaatschap/">Lees hier meer over het lidmaatschap, inschrijving en de voordelen.</a></div>')),
                TextField::create('Name', _t(__CLASS__ . '.Name', 'Naam'))->addExtraClass('requiredField'),
                EmailField::create('Email', _t(__CLASS__ . '.Email', 'E-mail'))->addExtraClass('requiredField'),
                TextField::create('Phone', _t(__CLASS__ . '.Phone', 'Telefoon'))->addExtraClass('requiredField'),
                TextField::create('Organisation', _t(__CLASS__ . '.Organisation', 'Organisatie'))->addExtraClass('requiredField'),
            ])->setName('Attendee');
        }

        return HiddenField::create('Attendee', _t(__CLASS__ . '.Attendee', 'Deelnemer'), 0);
    }

    private function createFormAction(DataList $dates, $event)
    {
        // acties
        // 1. external -> per datum of per event
        // 2. afgelopen -> alle data in verleden
        // 3. unattend -> bij de datum optie
        // 4. geen plek -> alle data geen plek en per datum tonen/disable
        // 5. attend

        // Check external
        $externalTicketProvider = $event->ExternalTicketProvider;

        // check expiriation
        $eventExpired = false;
        if (!$dates->count()) {
            $eventExpired = true;
        }

        // todo niet uitgaan van db veld StartDate
        $lastAvailableDate = DBDatetime::create()->setValue($dates->max('StartDate'));
        if ($lastAvailableDate->InPast()) {
            $eventExpired = true;
        }

        $availability = [];
        if( $eventExpired ) {
            $placesAvailable = 0;
        } else {
            foreach ($dates as $date) {
                /** @var Attendable $date */
                $availability[] = $date->getPlacesAvailable();
            }
            $placesAvailable = (boolean)max($availability);
        }

        // $currentUser = Security::getCurrentUser();

        if ($externalTicketProvider) {
            $label = _t(__CLASS__ . '.Attend', 'Inschrijven op extern adres');

            if (filter_var($externalTicketProvider, FILTER_VALIDATE_EMAIL)) {
                // enabel email subscription
                return LiteralField::create(
                    'external',
                    "<a href='mailto:" . $externalTicketProvider . "?subject=Aanmelden " . $event->Title . "' class='action button primary' target='_blank'>
                    <span>Inschrijven via e-mail</span>
                    <i class='fas fa-envelope'></i>
                </a>"
                );
            }

            // subscription on external website
            return LiteralField::create(
                'external',
                "<a href='$externalTicketProvider' class='action button primary' target='_blank'>
                    <span>$label</span>
                    <i class='fas fa-external-link-alt'></i>
                </a>"
            );
        } elseif ($eventExpired) {
            $label = _t(__CLASS__ . '.Expired', 'Verlopen');
            return FormAction::create('expired', $label)
                ->setUseButtonTag(true)
                ->setAttribute('title', $label)
                ->setButtonContent("<span>$label</span> <i class='fas fa-calendar-times'></i>")
                ->setDisabled(true)
                ->addExtraClass('button warning');
            // todo unattend action in date select
            // } elseif ($currentUser && $event->getIsAttending()) {
            //     $attending = _t(__CLASS__ . '.Attending', 'Ingeschreven');
            //     $unattend = _t(__CLASS__ . '.Unattend', 'Uitschrijven');
            //     return FormAction::create('unattend', $unattend)
            //         ->setUseButtonTag(true)
            //         ->setAttribute('title', $unattend)
            //         ->setButtonContent("
            //             <div class='button__states'>
            //                 <div class='button__state button__state--default'>
            //                     <span>$attending</span><i class='fas fa-check'></i>
            //                 </div>
            //                 <div class='button__state button__state--hover'>
            //                     <span>$unattend</span><i class='fas fa-times'></i>
            //                 </div>
            //             </div>
            //         ")
            //         ->addExtraClass('button success small button--has-hover-state');
        } elseif (!$placesAvailable) {
            $label = _t(__CLASS__ . '.Full', 'Vol');
            return FormAction::create('full', $label)
                ->setUseButtonTag(true)
                ->setAttribute('title', $label)
                ->setButtonContent("<span>$label</span> <i class='fas fa-times'></i>")
                ->setDisabled(true)
                ->addExtraClass('button alert small');
        } else {
            $label = _t(__CLASS__ . '.Attend', 'Direct inschrijven');
            return FormAction::create('attend', $label)
                ->setUseButtonTag(true)
                ->setAttribute('title', $label)
                ->setButtonContent("<span>$label</span> <i class='far fa-plus'></i>")
                ->addExtraClass('button primary');
        }
    }

    public function attend($data, Form $form)
    {
        $controller = $form->getController();
        /** @var EventPage $event */
        $event = $controller->data();

        if (empty($data['AttendableDates'])) {
            $form->sessionMessage(
                _t(__CLASS__ . '.NoDate', 'Geen datum geselecteerd')
            );
            return $controller->redirectBack();
        }

        // get the dates
        $attendableDates = EventDateTime::get()->filter(['ID' => $data['AttendableDates']]);


        $attendeeIds = [];
        if (isset($data['Attendee'])) {
            $attendeeIds[] = $data['Attendee'];
        } elseif (isset($data['Attendees'])) {
            $attendeeIds = $data['Attendees'];
        }

        $attendees = [];
        foreach ($attendableDates as $date) {
            /* @var EventDateTime|EventDateTimeExtension $date */
            $status = $date->AutoSkipWaitingList() ? 'Confirmed' : 'WaitingList';

            foreach ($attendeeIds as $attendee) {
                $attendees[] = [
                    'Status' => $status,
                    'EventDateID' => $date->ID,
                    'MemberID' => $attendee,
                ];
            }
        }

        // if external user
        if (isset($data['Name'])) {
            foreach ($attendableDates as $date) {
                $attendees[] = [
                    // !! external user should always go into the WaitingList !!
                    'Status' => 'WaitingList',
                    'EventDateID' => $date->ID,
                    'Name' => $data['Name'],
                    'Email' => $data['Email'],
                    'Phone' => $data['Phone'],
                    'Organisation' => $data['Organisation'],
                ];
            }
        }

        foreach ($attendees as $attendee) {

            // check if exists
            $attendance = EventAttendance::create($attendee);

            if (isset($attendee['MemberID']) && ($found = $attendance->EventDate()->Attendees()->find('MemberID', $attendee['MemberID']))) {
                $attendance = $found;
            }

            if (isset($data['AttendField'])) {
                // link to fields so we can calculate field option availability
                foreach ($data['AttendField'] as $name => $value) {
                    if ($attendField = $event->AttendFields()->find('Name', $name)) {
                        if (is_array($value)) {
                            $attendance->Fields()->add($attendField, [
                                'Value' => json_encode($value)
                            ]);
                        } else {
                            $attendance->Fields()->add($attendField, [
                                'Value' => $value
                            ]);
                        }

                        $attendField->onAttend($value, $attendance);
                    }
                }
            }

            $attendance->write();
            if ($attendance->Status == 'WaitingList') {
                $attendance->sendWaitingListConfirmationEmail();
            } elseif ($attendance->Status == 'Confirmed') {
                $attendance->sendEventConfirmationEmail();
            }
        }

        $form->sessionMessage(
            _t(__CLASS__ . '.AttendFormResult', 'Bedankt voor je inschrijving'),
            ValidationResult::TYPE_GOOD
        );

        return $controller->redirectBack();
    }
}

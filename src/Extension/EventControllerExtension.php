<?php

namespace XD\AttendableEvents\Extension;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use XD\AttendableEvents\Forms\AttendForm;
use XD\AttendableEvents\Model\EventAttendance;
use XD\Events\Model\EventDateTime;
use XD\Events\Model\EventPage;

class EventControllerExtension extends Extension
{
    private static $allowed_actions = [
        'AttendForm',
        'unattend',
        'ics'
    ];

    public function getAttendableDates()
    {
        return $this->owner->DateTimes();
    }

    public function AttendForm()
    {
        return new AttendForm($this->owner);
    }

    public function ICS()
    {
        $params = $this->owner->getURLParams();
        if (isset($params['ID'])) {
            $id = (int) $params['ID'];
            if($eventDateTime = EventDateTime::get()->byID($id)){
                $eventDateTime->ICS();
            }
        }
    }

    public function unattend(HTTPRequest $request)
    {
        $controller = $this->owner;

        /** @var EventPage $event */
        $event = $controller->data();

        /** @var ManyManyList $list */
        // $list = $event->Attendees();

        if (!($dateTime = EventDateTime::get_by_id($request->param('ID'))) || !$dateTime->exists()) {
            return $controller->redirectBack();
        }

        if (!($member = Security::getCurrentUser()) || !$member->exists()) {
            return Security::permissionFailure($this->owner, 'U moet ingelogd zijn om uzelf af te melden voor dit event');
        }

        $memberId = $member->ID;
        if ($request->param('OtherID')) {
            $memberId = $request->param('OtherID');
        }

        $attendees = $dateTime->Attendees()->filter([
            'MemberID' => $memberId
        ]);

        $attendees->removeAll();

        // add form message ?
        // migrate to form action ? 
        // how to put a form action in a checkbox option

        $controller->redirectBack();
    }
}

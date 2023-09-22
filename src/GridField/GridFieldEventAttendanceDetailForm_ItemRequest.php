<?php

namespace XD\AttendableEvents\GridField;

use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;

class GridFieldEventAttendanceDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{

    public function doSave($data, $form)
    {
        $result = parent::doSave($data, $form);

        if (!empty($_POST['EventDateID']) && $_POST['EventDateID'] != $this->record->EventDateID) {
            $this->record->EventDateID = $_POST['EventDateID'];
            $this->record->write();
            // redirect to other event
            $controller = $this->getToplevelController();
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            $event = $this->record->EventDate()->Event();
            $url = '/admin/events/XD-Events-Model-EventPage/EditForm/field/XD-Events-Model-EventPage/item/' . $event->ID . '/ItemEditForm/field/DateTimes/item/' . $this->record->EventDateID; //. '/ItemEditForm/field/ConfirmedAttendees/item/' . $this->record->ID . '/edit';
            return $controller->redirect($url, 302);
        }

        return $result;

    }


}
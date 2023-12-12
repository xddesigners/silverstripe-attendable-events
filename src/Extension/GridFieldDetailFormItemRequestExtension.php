<?php

namespace XD\AttendableEvents\Extension;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use XD\AttendableEvents\Model\EventAttendance;
use XD\Events\Model\EventDateTime;

/**
 * Class GridFieldDetailFormItemRequestExtension
 * @package Broarm\EventTickets\Extensions
 *
 * @property GridFieldDetailForm_ItemRequest owner
 */
class GridFieldDetailFormItemRequestExtension extends Extension
{
    private static $allowed_actions = [
        'sendEventConfirmationEmail',
        'testEventConfirmation'
    ];

    public function updateItemEditForm(Form $form)
    {
        $connectionActions = CompositeField::create()->setName('ConnectionActions');
        $connectionActions->setFieldHolderTemplate(CompositeField::class . '_holder_buttongroup');

        if (($record = $this->owner->getRecord()) && $record instanceof EventAttendance) {
            $sendConfirmationAction = FormAction::create('sendEventConfirmationEmail', _t(__CLASS__ . '.sendEventConfirmationEmail', 'Send confirmation'))
            ->addExtraClass('btn btn-outline-secondary font-icon-p-mail')
            ->setAttribute('data-icon', 'p-mail')
            ->setUseButtonTag(true);

            $connectionActions->push($sendConfirmationAction);

            $form->Actions()->insertBefore('RightGroup', $connectionActions);
        }

        if (($record = $this->owner->getRecord()) && $record instanceof EventDateTime) {
            $testConfirmationAction = FormAction::create('testEventConfirmation', _t(__CLASS__ . '.testEventConfirmation', 'Test confirmation mail'))
                ->addExtraClass('btn btn-outline-secondary font-icon-help-circled')
                ->setAttribute('data-icon', 'help-circled')
                ->setUseButtonTag(true);

            $connectionActions->push($testConfirmationAction);

            $sendConfirmationAction = FormAction::create('sendEventConfirmationEmail', _t(__CLASS__ . '.sendEventConfirmationEmailToAll', 'Send confirmation to all attendees'))
                ->addExtraClass('btn btn-outline-secondary font-icon-p-mail')
                ->setAttribute('data-icon', 'p-mail')
                ->setUseButtonTag(true);

            $connectionActions->push($sendConfirmationAction);
            $form->Actions()->insertBefore('RightGroup', $connectionActions);
        }
    }

    public function testEventConfirmation()
    {
        $this->handleActionOnRecord('testEventConfirmation', 'Sent test confirmation', 'Cannot send test confirmation');
    }

    public function sendEventConfirmationEmail()
    {
        $this->handleActionOnRecord('sendEventConfirmationEmail', 'Sent event confirmation', 'Cannot send event confirmation');
    }

    public function handleActionOnRecord($method, $successMessage, $errorMessage)
    {
        $record = $this->owner->getRecord();
        $controller = Controller::curr();
        if ($record->hasMethod($method) && $record->{$method}()) {
            $message = $successMessage ?? _t(__CLASS__ . '.HandleActionSuccess', 'success');
            $controller->getResponse()->addHeader(
                'X-Status',
                rawurlencode($message)
            );
        } else {
            $message = $errorMessage ?? _t(__CLASS__ . '.HandleActionError', 'error');
            $controller->getResponse()->addHeader(
                'X-Status',
                rawurlencode($message)
            );
        }
    }
}

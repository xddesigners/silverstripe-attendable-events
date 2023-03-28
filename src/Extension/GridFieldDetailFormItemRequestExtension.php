<?php

namespace XD\AttendableEvents\Extension;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use XD\Events\Model\EventDateTime;

/**
 * Class GridFieldDetailFormItemRequestExtension
 * @package Broarm\EventTickets\Extensions
 *
 * @property GridFieldDetailForm_ItemRequest owner
 */
class GridFieldDetailFormItemRequestExtension extends \SilverStripe\Core\Extension
{
    private static $allowed_actions = [
        'sendConfirmationEmail',
        'sendEvaluationEmail',
        'testEventConfirmation'
    ];

    public function updateItemEditForm(Form $form)
    {
        if (($record = $this->owner->getRecord()) && $record instanceof EventDateTime) {

            $event = $record->Event();

            $connectionActions = CompositeField::create()->setName('ConnectionActions');
            $connectionActions->setFieldHolderTemplate(CompositeField::class . '_holder_buttongroup');

            $testConfirmationAction = FormAction::create('testEventConfirmation', _t(__CLASS__ . '.testEventConfirmation', 'Test confirmation mail'))
                ->addExtraClass('btn btn-outline-secondary font-icon-help-circled')
                ->setAttribute('data-icon', 'help-circled')
                ->setUseButtonTag(true);

            $connectionActions->push($testConfirmationAction);

            $sendConfirmationAction = FormAction::create('sendConfirmationEmail', _t(__CLASS__ . '.sendConfirmationEmail', 'Send confirmation to all attendees'))
                ->addExtraClass('btn btn-outline-secondary font-icon-p-mail')
                ->setAttribute('data-icon', 'p-mail')
                ->setUseButtonTag(true);

            $connectionActions->push($sendConfirmationAction);

            if( $event->EvaluationFormID ) {
                $sendEvaluationAction = FormAction::create('sendEvaluationEmail', _t(__CLASS__ . '.sendEvaluationEmail', 'Send evaluation to all attendees'))
                    ->addExtraClass('btn btn-outline-secondary font-icon-p-mail')
                    ->setAttribute('data-icon', 'p-mail')
                    ->setUseButtonTag(true);

                $connectionActions->push($sendEvaluationAction);
            }

            $form->Actions()->insertBefore('RightGroup', $connectionActions);
        }
    }

    public function testEventConfirmation()
    {
        $record = $this->owner->getRecord();
        $controller = Controller::curr();
        /* @var EventDateTime|EventDateTimeExtension $record */
        if ($record->hasMethod('testEventConfirmation') && $record->testEventConfirmation()) {
            $controller->getResponse()->addHeader(
                'X-Status',
                rawurlencode("Sent test confirmation")
            );
        } else {
            $controller->getResponse()->addHeader(
                'X-Status',
                rawurlencode("Cannot send test confirmation")
            );
        }
    }

    public function sendConfirmationEmail()
    {
        $record = $this->owner->getRecord();
        $controller = Controller::curr();
        /* @var EventDateTime|EventDateTimeExtension $record */
        if ($record->hasMethod('sendEventConfirmation') && $record->sendEventConfirmation()) {
            $controller->getResponse()->addHeader(
                'X-Status',
                rawurlencode("Sent event confirmation")
            );
        } else {
            $controller->getResponse()->addHeader(
                'X-Status',
                rawurlencode("Cannot send event confirmation")
            );
        }
    }


    public function sendEvaluationEmail()
    {
        $record = $this->owner->getRecord();
        $controller = Controller::curr();
        /* @var EventDateTime|EventDateTimeExtension $record */
        if ($record->hasMethod('sendEventEvaluation') && $record->sendEventEvaluation()) {
            $controller->getResponse()->addHeader(
                'X-Status',
                rawurlencode("Sent event evaluation")
            );
        } else {
            $controller->getResponse()->addHeader(
                'X-Status',
                rawurlencode("Cannot send event evaluation")
            );
        }
    }




}

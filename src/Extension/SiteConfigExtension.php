<?php

namespace XD\AttendableEvents\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * class SiteConfigExtension
 * Extension to modify SiteConfig
 * @property SiteConfig|SiteConfigExtension owner
 */
class SiteConfigExtension extends Extension
{
    private static $db = [
        'EventWaitingListConfirmationEmailContent' => 'HTMLText',
        'EventConfirmationEmailContent' => 'HTMLText',
        'EventIntakeEmailContent' => 'HTMLText',
        'EventEvaluationEmailContent' => 'HTMLText',
    ];

    public function updateCMSFields(FieldList $fields)
    {

        $fields->addFieldsToTab('Root.EventMail', [
            HTMLEditorField::create(
                'EventWaitingListConfirmationEmailContent',
                _t(__CLASS__ . '.EventWaitingListConfirmationEmailContent', 'Event waiting list mail')
            )
                ->setRows(4)
                ->addExtraClass('stacked')
                ->setDescription(_t(__CLASS__ . '.EventWaitingListConfirmationEmailContentDescription', 'This email is sent when a member adds him/her self to a event')),
            HTMLEditorField::create(
                'EventConfirmationEmailContent',
                _t(__CLASS__ . '.EventConfirmationEmailContent', 'Event confirmation mail')
            )
                ->setRows(4)
                ->addExtraClass('stacked')
                ->setDescription(_t(__CLASS__ . '.EventConfirmationEmailContentDescription', 'This email is sent when a member is added to the confirmed list')),
            HTMLEditorField::create(
                'EventIntakeEmailContent',
                _t(__CLASS__ . '.EventIntakeEmailContent', 'Event intake mail')
            )
                ->setRows(4)
                ->addExtraClass('stacked')
                ->setDescription(_t(__CLASS__ . '.EventIntakeEmailContentDescription', 'This email is sent automatically after filling out the attendee form, if an attendee form is selected on the event.')),
            HTMLEditorField::create(
                'EventEvaluationEmailContent',
                _t(__CLASS__ . '.EventEvaluationEmailContent', 'Event evaluation mail')
            )
                ->setRows(4)
                ->addExtraClass('stacked')
                ->setDescription(_t(__CLASS__ . '.EventEvaluationEmailContentDescription', 'This email can be sent to all attendees after the event, if an evaluation form is selected on the event.')),
        ]);
        
        return $fields;
    }
}

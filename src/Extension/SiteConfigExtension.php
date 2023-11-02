<?php

namespace XD\AttendableEvents\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * class SiteConfigExtension
 * Extension to modify SiteConfig
 * @property SiteConfig|SiteConfigExtension owner
 */
class SiteConfigExtension extends DataExtension
{
    private static $db = [
        'EventWaitingListConfirmationEmailContent' => 'HTMLText',
        'EventConfirmationEmailContent' => 'HTMLText'
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
        ]);
        
        return $fields;
    }
}

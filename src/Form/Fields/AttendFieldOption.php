<?php

namespace XD\AttendableEvents\Forms\Fields;

use SilverStripe\ORM\DataObject;

class AttendFieldOption extends DataObject
{
    private static $table_name = 'AttendableEvents_AttendField_Option';

    private static $db = [
        'Title' => 'Varchar',
        'Value' => 'Varchar',
        'Sort' => 'Int',
    ];

    private static $summary_fields = [
        'Value',
        'Title'
    ];

    private static $has_one = [
        'Field' => AttendOptionsetField::class
    ];

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->Value) {
            $this->Value = $this->Title;
        }
    }
}

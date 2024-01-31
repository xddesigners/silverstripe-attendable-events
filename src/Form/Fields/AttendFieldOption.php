<?php

namespace XD\AttendableEvents\Forms\Fields;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\DataObject;

class AttendFieldOption extends DataObject
{
    private static $table_name = 'AttendableEvents_AttendField_Option';

    private static $db = [
        'Title' => 'Varchar',
        'Value' => 'Varchar',
        'Sort' => 'Int',
        'Disabled' => 'Boolean',
    ];

    private static $summary_fields = [
        'Value',
        'Title',
        'Disabled'
    ];

    private static $has_one = [
        'Field' => AttendOptionsetField::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName(['Sort', 'FieldID']);
        $fields->insertAfter('Value', CheckboxField::create('Disabled', _t(__CLASS__ . '.Disabled', 'Disabled')));

        return $fields;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->Value) {
            $this->Value = $this->Title;
        }
        if (!$this->Title) {
            $this->Title = $this->Value;
        }
    }
}

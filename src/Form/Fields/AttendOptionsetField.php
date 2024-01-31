<?php

namespace XD\AttendableEvents\Forms\Fields;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\MultiSelectField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\SelectField;
use XD\AttendableEvents\GridField\GridFieldConfig_AttendFieldOptions;


class AttendOptionsetField extends AttendField
{
    private static $table_name = 'AttendableEvents_AttendField_OptionsetField';

    private static $fieldType = OptionsetField::class;

    private static $has_many = [
        'Options' => AttendFieldOption::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Options');
        if ($this->exists()) {
            $fields->addFieldsToTab('Root.Main', [
                GridField::create(
                    'Options',
                    _t(__CLASS__ . '.Options', 'Options'),
                    $this->Options(),
                    GridFieldConfig_AttendFieldOptions::create()
                )
            ]);
        }
        return $fields;
    }

    public function getFormField($members = null)
    {
        $fieldType = $this->config()->get('fieldType');
        $field = $fieldType::create($this->getFieldName(), $this->Title);
        if ($field instanceof SelectField) {
            $field->setSource($this->Options()->map('Value', 'Title')->toArray());

            $value = $this->Value ?? '';
            if ($field instanceof MultiSelectField && !is_array($value)) {
                $value = json_decode($value, true);
            }

            $field->setValue($value);
        }
        $disabledItems = $this->owner->Options()->filter('Disabled', true);
        if ($disabledItems->exists()) {
            $field->setDisabledItems($disabledItems->column('Value'));
        }
        $this->extend('updateFormField', $field);
        return $field;
    }
}

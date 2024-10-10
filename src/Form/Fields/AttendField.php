<?php

namespace XD\AttendableEvents\Forms\Fields;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use XD\AttendableEvents\Model\EventAttendance;
use XD\Events\Model\EventPage;

class AttendField extends DataObject
{
    private static $table_name = 'AttendableEvents_AttendField';

    private static $fieldType = FormField::class;

    private static $db = [
        'Name' => 'Varchar',
        'Title' => 'Varchar',
        'Sort' => 'Int',
        'Required' => 'Boolean'
    ];

    private static $has_one = [
        'Event' => EventPage::class
    ];

    private static $default_sort = 'Sort ASC';

    private static $summary_fields = [
        'FieldType',
        'Name',
        'Title',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['Sort', 'EventID']);
        return $fields;
    }

    public function getFieldType()
    {
        return ClassInfo::shortName($this->ClassName);
    }

    public function getFormField($members = null)
    {
        $fieldType = $this->config()->get('fieldType');
        $field = $fieldType::create($this->getFieldName(), $this->Title, $this->Value);
        if( $this->Required ){
            $field->addExtraClass('requiredField');
        }
        return $field;
    }

    public function getFieldName()
    {
        return "AttendField[{$this->Name}]";
    }

    public function canCreate($member = null, $context = [])
    {
        if ($canCreate = parent::canCreate($member, $context)) {
            $fieldType = $this->config()->get('fieldType');
            $canCreate = $fieldType !== FormField::class;
        }
        
        return $canCreate;
    }

    public function validate()
    {
        $valid = parent::validate();
        if ($valid->isValid() && empty($this->Name)) {
            $valid->addError('"Name" should not be empty', ValidationResult::TYPE_ERROR);
        }

        return $valid;
    }

    public function onAttend($value, EventAttendance $attendace)
    {
        $this->extend('onAfterAttend', $value, $attendace);
    }

    public function canView($member = null)
    {
        return $this->Event()->canView($member);
    }

    public function canEdit($member = null)
    {
        return $this->Event()->canEdit($member);
    }

    public function canDelete($member = null)
    {
        return $this->Event()->canEdit($member);
    }
    
}

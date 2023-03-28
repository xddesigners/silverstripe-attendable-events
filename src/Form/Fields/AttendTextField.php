<?php

namespace XD\AttendableEvents\Forms\Fields;

use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;

class AttendTextField extends AttendField
{
    private static $table_name = 'AttendableEvents_AttendField_AttendTextField';

    private static $fieldType = TextField::class;

    private static $db = [
        'Rows' => 'Int'
    ];

    public function getFormField($members = null)
    {
        $fieldType = TextField::class;
        if ($this->Rows > 1) {
            $field = TextareaField::create($this->getFieldName(), $this->Title, $this->Value)
                ->setRows($this->Rows);
        } else {
            $field = TextField::create($this->getFieldName(), $this->Title, $this->Value);
        }

        return $field;
    }
}

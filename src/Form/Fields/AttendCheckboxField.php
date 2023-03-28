<?php

namespace XD\AttendableEvents\Forms\Fields;

use SilverStripe\Forms\CheckboxField;

class AttendCheckboxField extends AttendField
{
    private static $table_name = 'AttendableEvents_AttendField_CheckboxField';

    private static $fieldType = CheckboxField::class;
}

<?php

namespace XD\AttendableEvents\Forms\Fields;

use SilverStripe\Forms\CheckboxSetField;

class AttendCheckboxSetField extends AttendOptionsetField
{
    private static $table_name = 'AttendableEvents_AttendField_CheckboxSetField';

    private static $fieldType = CheckboxSetField::class;
}

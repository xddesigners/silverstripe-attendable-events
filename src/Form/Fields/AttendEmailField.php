<?php

namespace XD\AttendableEvents\Forms\Fields;

use SilverStripe\Forms\EmailField;

class AttendEmailField extends AttendField
{
    private static $table_name = 'AttendableEvents_AttendField_EmailField';
    
    private static $fieldType = EmailField::class;
}

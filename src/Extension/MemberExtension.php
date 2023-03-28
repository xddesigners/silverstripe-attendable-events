<?php

namespace XD\AttendableEvents\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;
use XD\AttendableEvents\Models\EventAttendance;

/**
 * Class MemberExtension
 * @package XD\AttendableEvents\Extensions
 * @property Member|MemberExtension $owner
 */
class MemberExtension extends DataExtension
{

    private static $has_many = [
        'EventAttendances' => EventAttendance::class
    ];

}
<?php

namespace XD\AttendableEvents\BulkHandlers;

use Colymba\BulkManager\BulkAction\Handler;
use Colymba\BulkTools\HTTPBulkToolsResponse;
use Exception;
use SilverStripe\Control\HTTPRequest;

class MoveAttendeesHandler extends Handler
{
    private static $url_segment = 'moveattendees';

    private static $allowed_actions = [
        'index',
        'moveattendees'
    ];

    private static $url_handlers = [
        '' => 'moveattendees',
        'moveattendees' => 'moveattendees'
    ];

    protected $label = 'Move attendees';

    protected $icon = '';

    protected $buttonClasses = 'font-icon-edit';

    protected $xhr = false;

    protected $destructive = false;

    public function getI18nLabel()
    {
        return _t(__CLASS__ . '.MoveAttendees', 'Verplaats deelnemers');
    }

    public function moveattendees(HTTPRequest $request)
    {
        $records = $this->getRecords();
        $response = new HTTPBulkToolsResponse(false, $this->gridField);

        try {
            foreach ($records as $record) {
                if ($record->Status == 'Confirmed') {
                    $record->Status = 'AdminCancelled';
                } else {
                    // WaitingList,MemberCancelled,AdminCancelled go to confirmed
                    $record->Status = 'Confirmed';
                }

                try {
                    $record->write();
                    $response->addSuccessRecord($record);
                } catch (Exception $e) {
                    $response->addFailedRecord($record, $e->getMessage());
                }
            }

            $doneCount = count($response->getSuccessRecords());
            $failCount = count($response->getFailedRecords());
            $message = sprintf(
                'Moved %1$d of %2$d records.',
                $doneCount,
                $doneCount + $failCount
            );
            $response->setMessage($message);
        } catch (Exception $ex) {
            $response->setStatusCode(500);
            $response->setMessage($ex->getMessage());
        }
        
        return $this->redirectBack();
        // return $response;
    }
}

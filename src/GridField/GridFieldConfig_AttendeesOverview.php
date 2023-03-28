<?php

namespace XD\AttendableEvents\GridField;

use Colymba\BulkManager\BulkManager;
use LeKoala\ExcelImportExport\ExcelGridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use XD\AttendableEvents\BulkHandlers\MoveAttendeesHandler;

/**
 * Class GridFieldConfig_EventAttendees
 * @package XD\AttendableEvents\GridField
 */
class GridFieldConfig_AttendeesOverview extends GridFieldConfig_RecordViewer
{
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);
        $this->addComponent(new ExcelGridFieldExportButton('buttons-before-left'));
    }
}

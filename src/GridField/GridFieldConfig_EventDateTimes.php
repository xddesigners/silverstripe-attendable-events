<?php

namespace XD\AttendableEvents\GridField;

use Colymba\BulkManager\BulkManager;
use LeKoala\ExcelImportExport\ExcelGridFieldExportButton;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\UserForms\Model\EditableFormField;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use XD\AttendableEvents\BulkHandlers\MoveAttendeesHandler;
use XD\Events\Model\EventDateTime;

/**
 * Class GridFieldConfig_EventAttendees
 * @package XD\AttendableEvents\GridField
 */
class GridFieldConfig_EventDateTimes extends GridFieldConfig_RecordEditor
{
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);
        $this->addComponent(new GridFieldTitleHeader());
        $this->removeComponentsByType([
            new GridField_ActionMenu(),
            new GridFieldFilterHeader(),
            new GridFieldSortableHeader(),
            new GridFieldPageCount(),
            new GridFieldPaginator(),
            new GridFieldDeleteAction()]);


        $editButton = $this->getComponentByType(GridFieldEditButton::class);
        $editButton->removeExtraClass('grid-field__icon-action--hidden-on-hover');

        $dataColumns = $this->getComponentByType(GridFieldDataColumns::class);
        $dataColumns->setDisplayFields([
            'StartDate' => [
                'title' => _t(EventDateTime::Class.'.StartDate','StartDate'),
            ],
            'Location.Title' => [
                'title' => _t(EventDateTime::Class.'.Location','Location'),
            ],
            'DayDateTimes.Count' => [
                'title' => _t(EventDateTime::Class.'.DayDateTimes','Days'),
            ],
            'AutoAttendeeLimit' => [
                'title' => _t(EventDateTime::Class.'.AttendeeLimit','AttendeeLimit'),
            ],
            'summaryAttendeeCount' => [
                'title' => _t(EventDateTime::Class.'.Attendees','Attendees'),
            ],
            'summaryConfirmedAttendeeCount' => [
                'title' => _t(EventDateTime::Class.'.Confirmed','Confirmed'),
            ],
        ]);

    }
}

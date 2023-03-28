<?php

namespace XD\AttendableEvents\GridField;

use Colymba\BulkManager\BulkAction\EditHandler;
use Colymba\BulkManager\BulkAction\UnlinkHandler;
use Colymba\BulkManager\BulkManager;
use LeKoala\ExcelImportExport\ExcelGridFieldExportButton;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use XD\AttendableEvents\BulkHandlers\MoveAttendeesHandler;

/**
 * Class GridFieldConfig_EventAttendees
 * @package XD\AttendableEvents\GridField
 */
class GridFieldConfig_EventAttendees extends GridFieldConfig_RecordEditor
{
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);
        $this->removeComponentsByType(GridField_ActionMenu::class);
        $this->removeComponentsByType(GridFieldAddNewButton::class);

        $this->addComponent(new ExcelGridFieldExportButton('buttons-before-left'));
        $this->addComponent($managed = new BulkManager());
        $managed->addBulkAction(MoveAttendeesHandler::class);
        $managed->removeBulkAction(UnlinkHandler::class);
        $managed->removeBulkAction(EditHandler::class);

        if ($editButton = $this->getComponentByType(GridFieldEditButton::class)) {
            $editButton->removeExtraClass('grid-field__icon-action--hidden-on-hover');
        }

        if ($detailForm = $this->getComponentByType(GridFieldDetailForm::class)) {
            $detailForm->setItemRequestClass(GridFieldEventAttendanceDetailForm_ItemRequest::class);
        }
    }
}

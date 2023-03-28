<?php

namespace XD\AttendableEvents\GridField;

use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class GridFieldConfig_AttendFields
 * @package XD\AttendableEvents\GridField
 */
class GridFieldConfig_AttendFields extends GridFieldConfig_RecordEditor
{
    public function __construct($itemsPerPage = null, $sortField = 'Sort')
    {
        parent::__construct($itemsPerPage, $sortField);
        $this->removeComponentsByType(new GridField_ActionMenu());
        $this->removeComponentsByType(new GridFieldDataColumns());
        $this->removeComponentsByType(new GridFieldAddNewButton());

        $this->addComponent(new GridFieldOrderableRows($sortField));
        $this->addComponent(new GridFieldEditableColumns(), new GridFieldEditButton());
        $this->addComponent(new GridFieldAddNewInlineButton());
        $this->addComponent(new GridFieldAddNewMultiClass());

        /** @var GridFieldEditButton $editButton */
        $editButton = $this->getComponentByType(new GridFieldEditButton());
        $editButton->removeExtraClass('grid-field__icon-action--hidden-on-hover');
    }
}
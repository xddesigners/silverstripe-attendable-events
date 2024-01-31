<?php

namespace XD\AttendableEvents\GridField;

use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class GridFieldConfig_SortableEditableInline
 * @package XD\Basic\GridField
 */
class GridFieldConfig_AttendFieldOptions extends GridFieldConfig_RecordEditor
{
    public function __construct($itemsPerPage = null, $sortField = 'Sort')
    {
        parent::__construct($itemsPerPage);
        $this->removeComponentsByType(GridFieldAddNewButton::class);
        $this->addComponent(new GridFieldAddNewInlineButton());

        $this->addComponent(new GridFieldOrderableRows($sortField));

        $editButton = $this->getComponentByType(GridFieldEditButton::class);
        $editButton->removeExtraClass('grid-field__icon-action--hidden-on-hover');

        $this->removeComponentsByType(GridFieldDataColumns::class);
        $this->removeComponentsByType(GridField_ActionMenu::class);
        $this->addComponent(new GridFieldEditableColumns(), new GridFieldEditButton());

        /** @var GridFieldEditButton $editButton */
        $editButton = $this->getComponentByType(GridFieldEditButton::class);
        $editButton->removeExtraClass('grid-field__icon-action--hidden-on-hover');

    }
}
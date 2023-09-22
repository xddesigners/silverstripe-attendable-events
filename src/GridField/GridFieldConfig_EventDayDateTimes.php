<?php

namespace XD\Events\Form;

use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;

/**
 * Class EventDateTimeGridField
 *
 * @author Bram de Leeuw
 */
class GridFieldConfig_EventDayDateTimes extends GridFieldConfig
{
    public function __construct($itemsPerPage = null)
    {

        $editButton = new GridFieldEditButton();
        $editButton->removeExtraClass('grid-field__icon-action--hidden-on-hover');

        parent::__construct();
        $this->addComponent(new GridFieldButtonRow('before'));
        $this->addComponent(new GridFieldToolbarHeader());
        $this->addComponent(new GridFieldTitleHeader());
        $this->addComponent(new GridFieldEditableColumns());
        $this->addComponent(new GridFieldAddNewInlineButton("buttons-before-left"));
        $this->addComponent(new GridFieldDetailForm(null,true,false));
        $this->addComponent(new GridFieldPaginator(999));
        $this->addComponent($editButton);
        $this->addComponent(new GridFieldDeleteAction());
    }
}

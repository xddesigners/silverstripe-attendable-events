<?php

namespace XD\AttendableEvents\GridField;

use LeKoala\ExcelImportExport\ExcelGridFieldExportButton;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;

/**
 * Excel-export-knop met een STABIELE actienaam per GridField.
 *
 * De parent ({@see ExcelGridFieldExportButton}) leidt zijn actienaam af van een globale statische
 * instance-teller ("excelexport_<n>"). Bij meerdere export-knoppen op één pagina (bv. de wachtlijst-,
 * bevestigde-deelnemers-, DateTimes- en Invoices-grids op het event-scherm) loopt die teller bij de
 * export-request in een andere volgorde dan bij het renderen → het instance-nummer verschilt → de
 * GridField kan de actie niet vinden ("Can't handle action excelexport_3").
 *
 * Deze subclass baseert de actienaam op de GridField-naam (uniek binnen het formulier en stabiel tussen
 * requests), waardoor de mismatch verdwijnt.
 */
class StableExcelGridFieldExportButton extends ExcelGridFieldExportButton
{
    protected function stableActionName(GridField $gridField): string
    {
        $key = preg_replace('/[^a-z0-9]/', '', strtolower((string) $gridField->getName()));
        return 'excelexport_' . $key;
    }

    public function getHTMLFragments($gridField)
    {
        $title = $this->buttonTitle ? $this->buttonTitle : _t(
            'ExcelImportExport.XLSEXPORT',
            'Export to Excel'
        );

        $name = $this->stableActionName($gridField);

        $button = new GridField_FormAction(
            $gridField,
            $name,
            $title,
            $name,
            null
        );
        $button->addExtraClass('btn btn-secondary no-ajax font-icon-down-circled action_export');
        $button->setForm($gridField->getForm());

        return array(
            $this->targetFragment => $button->Field()
        );
    }

    public function getActions($gridField)
    {
        return array($this->stableActionName($gridField));
    }

    public function getURLHandlers($gridField)
    {
        return array($this->stableActionName($gridField) => 'handleExport');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if (in_array($actionName, $this->getActions($gridField))) {
            return $this->handleExport($gridField);
        }
    }
}

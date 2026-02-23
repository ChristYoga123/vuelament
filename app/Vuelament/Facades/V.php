<?php

namespace App\Vuelament\Facades;

// Form
use App\Vuelament\Components\Form\TextInput;
use App\Vuelament\Components\Form\Select;
use App\Vuelament\Components\Form\RichEditor;
use App\Vuelament\Components\Form\Textarea;
use App\Vuelament\Components\Form\Radio;
use App\Vuelament\Components\Form\Checkbox;
use App\Vuelament\Components\Form\DatePicker;
use App\Vuelament\Components\Form\DateRangePicker;
use App\Vuelament\Components\Form\TimePicker;
use App\Vuelament\Components\Form\Toggle;
use App\Vuelament\Components\Form\FileInput;
use App\Vuelament\Components\Form\Repeater;

// Filter
use App\Vuelament\Components\Filters\SelectFilter;
use App\Vuelament\Components\Filters\ToggleFilter;
use App\Vuelament\Components\Filters\RadioFilter;
use App\Vuelament\Components\Filters\CheckboxFilter;
use App\Vuelament\Components\Filters\CustomFilter;

// Infolist
use App\Vuelament\Components\Infolists\TextEntry;
use App\Vuelament\Components\Infolists\ImageEntry;

// Actions (page/header level)
use App\Vuelament\Components\Actions\Action;
use App\Vuelament\Components\Actions\CreateAction;
use App\Vuelament\Components\Actions\ImportAction;
use App\Vuelament\Components\Actions\ExportAction;
use App\Vuelament\Components\Actions\BulkAction;
use App\Vuelament\Components\Actions\DeleteBulkAction;
use App\Vuelament\Components\Actions\RestoreBulkAction;
use App\Vuelament\Components\Actions\ForceDeleteBulkAction;

// Table
use App\Vuelament\Components\Table\Table;
use App\Vuelament\Components\Table\Column;

// Layout
use App\Vuelament\Components\Layout\Grid;
use App\Vuelament\Components\Layout\Section;
use App\Vuelament\Components\Layout\Card;

// Widgets
use App\Vuelament\Components\Widgets\StatsOverviewWidget;
use App\Vuelament\Components\Widgets\Stat;
use App\Vuelament\Components\Widgets\ChartWidget;
use App\Vuelament\Components\Widgets\TableWidget;

// Core
use App\Vuelament\Core\PageSchema;
use App\Vuelament\Core\Panel;
use App\Vuelament\Core\NavigationGroup;
use App\Vuelament\Core\NavigationItem;

class V
{
    // ── Form ─────────────────────────────────────────────

    public static function textInput(string $name): TextInput { return TextInput::make($name); }
    public static function select(string $name): Select { return Select::make($name); }
    public static function textarea(string $name): Textarea { return Textarea::make($name); }
    public static function richEditor(string $name): RichEditor { return RichEditor::make($name); }
    public static function radio(string $name): Radio { return Radio::make($name); }
    public static function checkbox(string $name): Checkbox { return Checkbox::make($name); }
    public static function datePicker(string $name): DatePicker { return DatePicker::make($name); }
    public static function dateRangePicker(string $name): DateRangePicker { return DateRangePicker::make($name); }
    public static function timePicker(string $name): TimePicker { return TimePicker::make($name); }
    public static function toggle(string $name): Toggle { return Toggle::make($name); }
    public static function fileInput(string $name): FileInput { return FileInput::make($name); }
    public static function repeater(string $name): Repeater { return Repeater::make($name); }

    // ── Filter ───────────────────────────────────────────

    public static function selectFilter(string $name): SelectFilter { return SelectFilter::make($name); }
    public static function toggleFilter(string $name): ToggleFilter { return ToggleFilter::make($name); }
    public static function radioFilter(string $name): RadioFilter { return RadioFilter::make($name); }
    public static function checkboxFilter(string $name): CheckboxFilter { return CheckboxFilter::make($name); }
    public static function customFilter(string $name): CustomFilter { return CustomFilter::make($name); }

    // ── Infolist ─────────────────────────────────────────

    public static function textEntry(string $name): TextEntry { return TextEntry::make($name); }
    public static function imageEntry(string $name): ImageEntry { return ImageEntry::make($name); }

    // ── Actions (page/header level) ──────────────────────

    public static function action(string $name): Action { return Action::make($name); }
    public static function createAction(string $name = ''): CreateAction { return CreateAction::make($name); }
    public static function importAction(string $name = ''): ImportAction { return ImportAction::make($name); }
    public static function exportAction(string $name = ''): ExportAction { return ExportAction::make($name); }
    public static function bulkAction(string $name): BulkAction { return BulkAction::make($name); }
    public static function deleteBulkAction(string $name = ''): DeleteBulkAction { return DeleteBulkAction::make($name); }
    public static function restoreBulkAction(string $name = ''): RestoreBulkAction { return RestoreBulkAction::make($name); }
    public static function forceDeleteBulkAction(string $name = ''): ForceDeleteBulkAction { return ForceDeleteBulkAction::make($name); }

    // ── Table ────────────────────────────────────────────

    public static function table(): Table { return Table::make(); }
    public static function column(string $name): Column { return Column::make($name); }

    // ── Layout ───────────────────────────────────────────

    public static function grid(int $columns = 2): Grid { return Grid::make($columns); }
    public static function section(string $heading = ''): Section { return Section::make($heading); }
    public static function card(string $heading = ''): Card { return Card::make($heading); }

    // ── Widgets ──────────────────────────────────────────

    public static function statsOverview(): StatsOverviewWidget { return StatsOverviewWidget::make(); }
    public static function stat(string $label, string $value): Stat { return Stat::make($label, $value); }
    public static function chartWidget(): ChartWidget { return ChartWidget::make(); }
    public static function tableWidget(): TableWidget { return TableWidget::make(); }

    // ── Navigation ───────────────────────────────────────

    public static function navigationGroup(string $label = ''): NavigationGroup { return NavigationGroup::make($label); }
    public static function navigationItem(string $label = ''): NavigationItem { return NavigationItem::make($label); }

    // ── Core ─────────────────────────────────────────────

    public static function page(): PageSchema { return PageSchema::make(); }
    public static function panel(): Panel { return Panel::make(); }
}
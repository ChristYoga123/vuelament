<?php

namespace App\Vuelament\Components\Table;

/**
 * FiltersLayout — menentukan posisi tampilan filter di tabel
 *
 * - Dropdown:                  filter di dalam dropdown (icon titik tiga) — DEFAULT
 * - AboveContent:              filter ditampilkan di atas tabel, selalu visible
 * - AboveContentCollapsible:   filter di atas tabel, bisa di-collapse/expand
 */
enum FiltersLayout: string
{
    case Dropdown                = 'dropdown';
    case AboveContent            = 'aboveContent';
    case AboveContentCollapsible = 'aboveContentCollapsible';
}

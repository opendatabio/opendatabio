<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Location;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;

class LocationsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('name', function ($location) {
            return '<a href="'.url('locations/'.$location->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($location->name).'</a>';
        })
        ->editColumn('adm_level', function ($location) { return Lang::get('levels.adm.'.$location->adm_level); })
        ->addColumn('full_name', function ($location) {return $location->full_name; })
        ->addColumn('parent', function ($location) {
            return empty($location->parent) ? '' : $location->parent->name;
        })
        ->rawColumns(['name']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Location::query()->select([
            'locations.name',
            'locations.adm_level',
            'locations.rgt',
            'locations.lft',
            'locations.parent_id',
            'locations.id',
        ]);

        return $this->applyScopes($query);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\Datatables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns([
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'adm_level' => ['title' => Lang::get('messages.adm_level'), 'searchable' => true, 'orderable' => true],
                'parent' => ['title' => Lang::get('messages.parent'), 'searchable' => false, 'orderable' => false],
                'full_name' => ['title' => Lang::get('messages.full_name'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' => [
                    'csv',
                    'excel',
                    'print',
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [1, 3],
                    'visible' => false,
                ]],
            ]);
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'odb_locations_'.time();
    }
}

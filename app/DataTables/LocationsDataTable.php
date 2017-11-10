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
        ->addColumn('plants', function ($location) {return $location->plants_count; })
        ->addColumn('vouchers', function ($location) {return $location->vouchers_count; })
        ->addColumn('measurements', function ($location) {return $location->measurements_count; })
        ->addColumn('latitude', function ($location) {return $location->latitudeSimple; })
        ->addColumn('longitude', function ($location) {return $location->longitudeSimple; })
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
        $query = Location::withCount(['plants', 'vouchers', 'measurements'])->withGeom();

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
                'plants' => ['title' => Lang::get('messages.plants'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
                'latitude' => ['title' => Lang::get('messages.latitude'), 'searchable' => false, 'orderable' => false],
                'longitude' => ['title' => Lang::get('messages.longitude'), 'searchable' => false, 'orderable' => false],
                'altitude' => ['title' => Lang::get('messages.altitude'), 'searchable' => false, 'orderable' => false],
                'x' => ['title' => Lang::get('messages.dimensions').' X', 'searchable' => false, 'orderable' => false],
                'y' => ['title' => Lang::get('messages.dimensions').' Y', 'searchable' => false, 'orderable' => false],
                'startx' => ['title' => Lang::get('messages.start').' X', 'searchable' => false, 'orderable' => false],
                'starty' => ['title' => Lang::get('messages.start').' Y', 'searchable' => false, 'orderable' => false],
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
                    'targets' => [1, 3, 8, 9, 10, 11, 12, 13, 14],
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

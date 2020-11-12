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
use DB;
use App\Voucher;
use Lang;
use Log;
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
            return $location->rawLink();
        })
        ->filterColumn('name', function ($query, $keyword) {
            $sql = " name LIKE '%".$keyword."%' ";
            $query->whereRaw($sql);
        })
        ->editColumn('adm_level', function ($location) { return Lang::get('levels.adm_level.'.$location->adm_level); })
        ->addColumn('full_name', function ($location) {return $location->full_name; })
        ->addColumn('plants', function ($location) {
              return '<a href="'.url('locations/'.$location->id.'/plants').'">'.$location->plants_public_count().'</a>';
        })
        ->addColumn('vouchers', function ($location) {
              return '<a href="'.url('locations/'.$location->id.'/vouchers').'">'.$location->vouchers_public_count().'</a>';
        })
        ->addColumn('measurements', function ($location) {return '<a href="'.url('locations/'.$location->id.'/measurements').'">'.$location->measurements_count.'</a>'; })
        ->addColumn('pictures', function ($location) {return '<a href="'.url('locations/'.$location->id).'">'.$location->pictures_count.'</a>'; })
        ->addColumn('latitude', function ($location) {return $location->latitudeSimple; })
        ->addColumn('longitude', function ($location) {return $location->longitudeSimple; })
        ->addColumn('parent', function ($location) {
            return empty($location->parent) ? '' : $location->parent->name;
        })
        ->rawColumns(['name', 'pictures', 'plants','vouchers', 'measurements','latitude','longitude']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Location::query()
        ->select([
            'locations.name',
            'locations.adm_level',
            'locations.rgt',
            'locations.lft',
            'locations.parent_id',
            'locations.id',
            'locations.altitude',
            'locations.x',
            'locations.y',
            'locations.startx',
            'locations.starty',
        ])->withCount(['measurements', 'pictures'])->noWorld();

        if ($this->project) {
          $query = $query->whereHas('plants',function($plant) { $plant->where('project_id',$this->project);})->orWhereHas('vouchers',function($voucher) { $voucher->where('project_id',$this->project);});
        }
        if ($this->location_id) {
            $locations = Location::find($this->location_id)->getDescendants()->pluck('id')->toArray();
            $query = $query->whereIn('id',$locations);
        }

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
                'adm_level' => ['title' => Lang::get('messages.adm_level'), 'searchable' => false, 'orderable' => true],
                'parent' => ['title' => Lang::get('messages.parent'), 'searchable' => false, 'orderable' => false],
                'full_name' => ['title' => Lang::get('messages.full_name'), 'searchable' => false, 'orderable' => false],
                'plants' => ['title' => Lang::get('messages.plants'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
                'pictures' => ['title' => Lang::get('messages.pictures'), 'searchable' => false, 'orderable' => false],
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
                'lengthMenu' => [3,5,10,15,20,50,100],
                'buttons' => [
                    'pageLength',
                    'csv',
                    'excel',
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'pageLength' => 10,
                'autoWidth' => false,
                'columnDefs' => [
                  [
                    'targets' => [1, 4,7, 8,9, 10, 11, 12, 13, 14, 15],
                    'visible' => false,
                ],
                [
                  "width" => "20%",
                  "targets" => [0]
                ]
              ],
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

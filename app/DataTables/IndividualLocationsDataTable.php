<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use App\Models\IndividualLocation;
use Lang;
use DB;

class IndividualLocationsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('name', function($indloc) {
          return $indloc->location->rawLink();
        })
        ->editColumn('latitude', function($indloc) {
          return round($indloc->latitude,6);
        })
        ->editColumn('longitude', function($indloc) {
          return round($indloc->longitude,6);
        })
        ->addColumn('action',  function ($indloc) {
          //data-toggle="modal" data-target="#locationModal"
          if (!isset($indloc->noaction)) {
            return '<button type="button"  data-indloc="'.$indloc->id.'" class="btn btn-xs btn-primary far fa-edit editlocation" data-toggle="modal" data-target="#locationModal"></button>&nbsp;<button type="button"  data-indloc="'.$indloc->id.'" class="btn btn-xs btn-danger far fa-trash-alt deletelocation"></button>';
          } else {
            return '<i class="fas fa-ban"></i>';
          }
          return "";
        })
        ->rawColumns(['name','action']);

    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = IndividualLocation::query()->join('locations','location_id','=','locations.id')->select(
          [
            'individual_location.id',
            'location_id',
            'locations.name',
            'individual_location.notes',
            'individual_location.date_time',
            'individual_location.altitude',
            DB::raw('AsText(individual_location.relative_position) as relativePosition'),
          ]);
        if ($this->noaction) {
          $query = $query->addSelect(DB::raw('1 as noaction'));
        }
        $query = $query->where('individual_id',$this->individual);
        return $query;
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
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'action' => ['title' => Lang::get('messages.actions'), 'searchable' => false, 'orderable' => false],
                'name' => ['title' => Lang::get('messages.location'), 'searchable' => true, 'orderable' => true],
                'notes' => ['title' => Lang::get('messages.notes'), 'searchable' => true, 'orderable' => true],
                'date_time' => ['title' => Lang::get('messages.date'), 'searchable' => true, 'orderable' => true],
                'altitude' => ['title' => Lang::get('messages.altitude'), 'searchable' => true, 'orderable' => true],
                'relativePosition' => ['title' => Lang::get('messages.relative_position'), 'searchable' => true, 'orderable' => true],
                'latitude' => ['title' => Lang::get('messages.latitude'), 'searchable' => false, 'orderable' => false],
                'longitude' => ['title' => Lang::get('messages.longitude'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'desc']],
                'buttons' => [
                    'csv',
                    //'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [0,1,3],
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
        return 'odb_individual_locations_'.time();
    }
}

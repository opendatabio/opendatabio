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
use Auth;

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
        ->addColumn('scientificName', function($indloc) {
          return $indloc->scientificName;
        })
        ->addColumn('individual', function($indloc) {
          return $indloc->individual->rawLink();
        })
        ->addColumn('higherGeography', function($indloc) {
          return $indloc->location->higherGeography;
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
        ->rawColumns(['name','action','individual']);

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
            'individual_id',
            'individual_location.id',
            'location_id',
            'locations.name',
            'individual_location.notes',
            'individual_location.date_time',
            'individual_location.altitude',
            DB::raw('ST_AsText(individual_location.relative_position) as relativePosition'),
          ]);
        if ($this->noaction) {
          $query = $query->addSelect(DB::raw('1 as noaction'));
        }
        if ($this->dataset) {
          $dataset = $this->dataset;
          $query = $query->whereHas("individual",function($i) use($dataset) {
            $i->where('dataset_id',$dataset);
          });
        }
        if ($this->individual) {
          $query = $query->where('individual_id',$this->individual);
        }
        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\Datatables\Html\Builder
     */
    public function html()
    {
          $exportbutton = [
            'text' => Lang::get('datatables.export'),
            'action' => "function () {
              var isvisible = document.getElementById('export_pannel').style.display;
              if (isvisible == 'none') {
                document.getElementById('export_pannel').style.display = 'block';
              } else {
                  document.getElementById('export_pannel').style.display = 'none';
              }
            }",
          ];

          if (Auth::user()) {
            $buttons = [
                'pageLength',
                'reload',
                ['extend' => 'colvis',  'columns' => ':gt(0)'],
              ];
          } else {
            $buttons = [
                'pageLength',
                'reload',
                ['extend' => 'colvis',  'columns' => ':gt(0)'],
              ];
          }
          if ($this->noaction) {
            $hidcol = [0,1,3,5,6,9,10,11];
          } else {
            $hidcol = [0,3,5,6,9,10,11];
          }

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
                'individual' => ['title' => Lang::get('messages.individual'), 'searchable' => false, 'orderable' => true],
                'scientificName' => ['title' => Lang::get('messages.taxon'), 'searchable' => false, 'orderable' => true],
                'individual' => ['title' => Lang::get('messages.individual'), 'searchable' => false, 'orderable' => true],
                'higherGeography' => ['title' => Lang::get('messages.higherGeography'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'desc']],
                'buttons' => $buttons,
                'columnDefs' => [[
                    'targets' => $hidcol,
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

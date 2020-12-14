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
          if ($this->project) {
            $plant_count = $location->getCount('App\Project',$this->project,'plants');
            return '<a href="'.url('plants/'.$location->id.'|'.$this->project.'/location_project').'">'.$plant_count.'</a>';
          } else {
            if ($this->dataset) {
              $plant_count = $location->getCount('App\Dataset',$this->dataset,'plants');
              return '<a href="'.url('plants/'.$location->id.'|'.$this->dataset.'/location_dataset').'">'.$plant_count.'</a>';
            } else {
              $plant_count = $location->getCount('all',null,"plants");
              return '<a href="'.url('plants/'.$location->id.'/location').'">'.$plant_count.'</a>';
            }
          }
        })
        ->addColumn('vouchers', function ($location) {
          if ($this->project) {
            $voucher_count = $location->getCount('App\Project',$this->project,'vouchers');
            return '<a href="'.url('vouchers/'.$location->id.'|'.$this->project.'/location_project').'">'.$voucher_count.'</a>';
          } else {
            if ($this->dataset) {
              $voucher_count = $location->getCount('App\Dataset',$this->dataset,'vouchers');
              return '<a href="'.url('vouchers/'.$location->id.'|'.$this->dataset.'/location_dataset').'">'.$voucher_count.'</a>';
            } else {
              $voucher_count = $location->getCount('all',null,"vouchers");
              return '<a href="'.url('vouchers/'.$location->id.'/location').'">'.$voucher_count.'</a>';
            }
          }
        })
        ->addColumn('measurements', function ($location) {
          if ($this->project) {
            $measurements_count = $location->getCount('App\Project',$this->project,"measurements");
            return '<a href="'.url('measurements/'.$location->id.'|'.$this->project.'/location_project').'">'.$measurements_count.'</a>';
          } else {
            if ($this->dataset) {
              $measurements_count = $location->getCount('App\Dataset',$this->dataset,'measurements');
              return '<a href="'.url('measurements/'.$location->id.'|'.$this->dataset.'/location_dataset').'">'.$measurements_count.'</a>';
            } else {
              $measurements_count = $location->getCount('all',null,"measurements");
              return '<a href="'.url('measurements/'.$location->id.'/location').'">'.$measurements_count.'</a>';
            }
          }
        })
        ->addColumn('taxons', function ($location) {
          if ($this->project) {
            $taxons_count = $location->getCount('App\Project',$this->project,"taxons");
            return '<a href="'.url('taxons/'.$location->id.'|'.$this->project.'/location_project').'">'.$taxons_count.'</a>';
          } else {
            if ($this->dataset) {
              $taxons_count = $location->getCount('App\Dataset',$this->dataset,'taxons');
              return '<a href="'.url('taxons/'.$location->id.'|'.$this->dataset.'/location_dataset').'">'.$taxons_count.'</a>';
            } else {
              $taxons_count = $location->getCount('all',null,"taxons");
              return '<a href="'.url('taxons/'.$location->id.'/location').'">'.$taxons_count.'</a>';
            }
          }
        })
        ->addColumn('pictures', function ($location) {
          $pictures_count = $location->getCount('all',null,"pictures");
          return '<a href="'.url('locations/'.$location->id).'">'.$pictures_count.'</a>';
        })
        ->addColumn('latitude', function ($location) {return $location->latitudeSimple; })
        ->addColumn('longitude', function ($location) {return $location->longitudeSimple; })
        ->addColumn('parent', function ($location) {
            if (isset($location->parent)) {
              if ($this->project) {
                $url = url('locations/'.$location->parent->id.'|'.$this->project.'/location_project');
              } else {
                if ($this->dataset) {
                  $url = url('locations/'.$location->parent->id.'|'.$this->dataset.'/location_dataset');
                } else {
                  $url = url('locations/'.$location->parent->id.'/location');
                }
              }
              return '<a href="'.$url.'">'.$location->name.'</a>';
            }
        })
        ->addColumn('select_locations',  function ($location) {
            return $location->id;
        })
        ->rawColumns(['name', 'pictures', 'plants','vouchers', 'measurements','latitude','longitude','taxons']);
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
          $query->whereHas('summary_counts',function($count) {
            $count->where('scope_id',"=",$this->project)->where('scope_type',"=","App\Project")->where('value',">",0);
          });
        }
        if ($this->dataset) {
          $query->whereHas('summary_counts',function($count) {
            $count->where('scope_id',"=",$this->dataset)->where('scope_type',"=","App\Dataset")->where('value',">",0);
          });
        }

        if ($this->location) {
            $locations = Location::find($this->location)->getDescendants()->pluck('id')->toArray();
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
                'select_locations' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'adm_level' => ['title' => Lang::get('messages.adm_level'), 'searchable' => false, 'orderable' => true],
                'parent' => ['title' => Lang::get('messages.parent'), 'searchable' => false, 'orderable' => false],
                'full_name' => ['title' => Lang::get('messages.full_name'), 'searchable' => false, 'orderable' => false],
                'plants' => ['title' => Lang::get('messages.plants'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
                'taxons' => ['title' => Lang::get('messages.taxons'), 'searchable' => false, 'orderable' => false],
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
                    /*'csv',
                    'excel',*/
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'pageLength' => 10,
                'autoWidth' => false,
                'columnDefs' => [
                  [
                    'targets' => [2,4,5,11,12,13,14,15,16,17],
                    'visible' => false,
                  ],
                  [
                  "width" => "20%",
                  "targets" => [1]
                  ],
                  [
                    'targets' => 0,
                    'checkboxes' => [
                    'selectRow' => true
                    ]
                  ],
              ],
              'select' => [
                    'style' => 'multi',
              ]
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

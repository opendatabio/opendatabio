<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Models\Location;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use DB;
use App\Models\Voucher;
use Lang;
use Log;
use Auth;

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
        ->editColumn('adm_level', function ($location) {
          return Lang::get('levels.adm_level.'.$location->adm_level);
        })
        ->addColumn('full_name', function ($location) {
          return $location->full_name;
        })
        ->addColumn('individuals', function ($location) {
          if ($this->project) {
            $individuals_count = $location->getCount('App\Models\Project',$this->project,'individuals');
            return '<a href="'.url('individuals/'.$location->id.'|'.$this->project.'/location_project').'">'.$individuals_count.'</a>';
          } else {
            if ($this->dataset) {
              $individuals_count = $location->getCount('App\Models\Dataset',$this->dataset,'individuals');
              return '<a href="'.url('individuals/'.$location->id.'|'.$this->dataset.'/location_dataset').'">'.$individuals_count.'</a>';
            } else {
              $individuals_count = $location->getCount('all',null,"individuals");
              return '<a href="'.url('individuals/'.$location->id.'/location').'">'.$individuals_count.'</a>';
            }
          }
        })
        ->addColumn('vouchers', function ($location) {
          if ($this->project) {
            $voucher_count = $location->getCount('App\Models\Project',$this->project,'vouchers');
            return '<a href="'.url('vouchers/'.$location->id.'|'.$this->project.'/location_project').'">'.$voucher_count.'</a>';
          } else {
            if ($this->dataset) {
              $voucher_count = $location->getCount('App\Models\Dataset',$this->dataset,'vouchers');
              return '<a href="'.url('vouchers/'.$location->id.'|'.$this->dataset.'/location_dataset').'">'.$voucher_count.'</a>';
            } else {
              $voucher_count = $location->getCount('all',null,"vouchers");
              return '<a href="'.url('vouchers/'.$location->id.'/location').'">'.$voucher_count.'</a>';
            }
          }
        })
        ->addColumn('measurements', function ($location) {
          if ($this->project) {
            $measurements_count = $location->getCount('App\Models\Project',$this->project,"measurements");
            return '<a href="'.url('measurements/'.$location->id.'|'.$this->project.'/location_project').'">'.$measurements_count.'</a>';
          } else {
            if ($this->dataset) {
              $measurements_count = $location->getCount('App\Models\Dataset',$this->dataset,'measurements');
              return '<a href="'.url('measurements/'.$location->id.'|'.$this->dataset.'/location_dataset').'">'.$measurements_count.'</a>';
            } else {
              $measurements_count = $location->getCount('all',null,"measurements");
              return '<a href="'.url('measurements/'.$location->id.'/location').'">'.$measurements_count.'</a>';
            }
          }
        })
        ->addColumn('taxons', function ($location) {
          if ($this->project) {
            $taxons_count = $location->taxonsCount('App\Models\Project',$this->project);
            return '<a href="'.url('taxons/'.$location->id.'|'.$this->project.'/location_project').'">'.$taxons_count.'</a>';
          } else {
            if ($this->dataset) {
              $taxons_count = $location->taxonsCount('App\Models\Dataset',$this->dataset);
              return '<a href="'.url('taxons/'.$location->id.'|'.$this->dataset.'/location_dataset').'">'.$taxons_count.'</a>';
            } else {
              $taxons_count = $location->taxonsCount('all',null);
              return '<a href="'.url('taxons/'.$location->id.'/location').'">'.$taxons_count.'</a>';
            }
          }
        })
        ->addColumn('media', function ($location) {
          $mediaCount = $location->getCount('all',null,"media");
          $urlShowAllMedia = "media/".$location->id."/locations";
          return '<a href="'.url($urlShowAllMedia).'">'.$mediaCount.'</a>';
        })
        ->addColumn('latitude', function ($location) {return $location->latitudeSimple; })
        ->addColumn('longitude', function ($location) {return $location->longitudeSimple; })
        ->addColumn('parent', function ($location) {
            if (null != $location->parent_id) {
              if ($this->project) {
                $url = url('locations/'.$location->parent_id.'|'.$this->project.'/location_project');
              } else {
                if ($this->dataset) {
                  $url = url('locations/'.$location->parent_id.'|'.$this->dataset.'/location_dataset');
                } else {
                  $url = url('locations/'.$location->parent_id);
                }
              }
              return '<a href="'.$url.'">'.$location->parent->name.'</a>';
            }
        })
        ->addColumn('select_locations',  function ($location) {
            return $location->id;
        })
        ->rawColumns(['name', 'media', 'individuals','vouchers', 'measurements','latitude','longitude','taxons','parent']);
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
        ])->withCount(['measurements'])->noWorld();

        if ($this->project) {
          /*
          $query->whereHas('summary_counts',function($count) {
            $count->where('scope_id',"=",$this->project)->where('scope_type',"=","App\Models\Project")->where('value',">",0);
          });
          */
          $query->whereHas('individuals',function($ind) {
            $ind->where('project_id',$this->project);
          });
        }
        if ($this->dataset) {
          $query->whereHas('individuals',function($ind) {
            $ind->whereHas('measurements', function($mea) {
              $mea->where('dataset_id',$this->dataset);});
          });
          $query->orWhereHas('vouchers',function($ind) {
            $ind->whereHas('measurements', function($mea) {
              $mea->where('dataset_id',$this->dataset);});
          });
          $query->orWhereHas('measurements',function($mea) {
            $mea->where('dataset_id',$this->dataset);
          });
        }

        if ($this->location) {
            $location = Location::withoutGeom()->findOrFail($this->location);
            $query = $query->where('lft','>',$location->lft)->where('rgt','<',$location->rgt);
        }

        if ($this->request()->has('adm_level')) {
          $adm_level =  (int) $this->request()->get('adm_level');
          if ($adm_level>0) {
            $query = $query->where('adm_level',$adm_level);
          }
        }

        //$query = $query->orderBy('adm_level')->orderBy('name');
        return $this->applyScopes($query);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\Datatables\Html\Builder
     */
    public function html()
    {

        $locations_level =  Location::used_adm_levels();
        $title_level = Lang::get('messages.level');
        if (count($locations_level)) {
          $title_level  = Lang::get('messages.level').'<select name="location_level" id="location_level" ><option value="">'.Lang::get('messages.all').'</option>';
          foreach ($locations_level as $level) {
                 $title_level  .= '<option value="'.$level.'" >'.Lang::get('levels.adm_level.' . $level).'</option>';
          }
          $title_level  .= '</select>';
        }
        if (Auth::user()) {
          $hidcol = [1,4,5,11,12,13,14,15,16,17];
        } else {
          $hidcol = [0,1,4,5,11,12,13,14,15,16,17];
        }

        return $this->builder()
            ->columns([
                'select_locations' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'adm_level' => ['title' => $title_level, 'searchable' => false, 'orderable' => true],
                'parent' => ['title' => Lang::get('messages.parent'), 'searchable' => false, 'orderable' => false],
                'full_name' => ['title' => Lang::get('messages.full_name'), 'searchable' => false, 'orderable' => false],
                'individuals' => ['title' => Lang::get('messages.individuals'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
                'taxons' => ['title' => Lang::get('messages.taxons'), 'searchable' => false, 'orderable' => false],
                'media' => ['title' => Lang::get('messages.media_files'), 'searchable' => false, 'orderable' => false],
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
                'lengthMenu' => [3,5,10,15,20,50],
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
                    'targets' => $hidcol,
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

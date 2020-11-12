<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Dataset;
use App\Project;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Gate;
use Lang;
use DB;

class DatasetsDataTable extends DataTable
{

    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('name', function ($dataset) {
            return $dataset->rawLink();
        })
        ->editColumn('privacy', function ($dataset) { return Lang::get('levels.privacy.'.$dataset->privacy); })
        ->addColumn('full_name', function ($dataset) {return $dataset->full_name; })
        ->addColumn('measurements', function ($dataset) {
            $meas_counts = DB::table('measurements')->selectRaw('COUNT(*) as count')->where('dataset_id',$dataset->id)->get();
            if ($meas_counts->first()->count >0) {
              return '<a href="'.url('datasets/'.$dataset->id.'/measurements').'" data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.tooltip_view_measurements').'" >'.$meas_counts->first()->count.'</a>';
            } else {
              return 0;
            }
        })
        ->addColumn('members', function ($dataset) {
            if (empty($dataset->users)) {
                return '';
            }
            $ret = '';
            foreach ($dataset->users as $user) {
                if (isset($user->person->full_name)) {
                  $ret .= htmlspecialchars($user->person->full_name).'<br>';
                } else {
                  $ret .= htmlspecialchars($user->email).'<br>';
                }
            }

            return $ret;
        })
        ->addColumn('tags', function ($dataset) { return $dataset->tagLinks; })
        ->addColumn('plants', function ($dataset) {
            $plcounts = DB::table('measurements')->selectRaw('COUNT(DISTINCT measured_id) as count')->where('measured_type','App\Plant')->where('dataset_id',$dataset->id)->get()->first()->count;
            if ($plcounts>0) {
            return '<a href="'.url('plants/'.$dataset->id."/datasets").'" data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.tooltip_view_measured_plants').'" >'.$plcounts.'</a>';
          } else { return 0;}
        })
        ->addColumn('vouchers', function ($dataset) {
            $vccounts = DB::table('measurements')->selectRaw('COUNT(DISTINCT measured_id) as count')->where('measured_type','App\Voucher')->where('dataset_id',$dataset->id)->get()->first()->count;
            if ($vccounts>0) {
            return '<a href="'.url('vouchers/'.$dataset->id.'/datasets').'" data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.tooltip_view_measured_vouchers').'" >'.$vccounts.'</a>';
            } else {
            return 0;
            }
        })
        ->addColumn('action',  function ($dataset) {
            if (Gate::denies('export', $dataset)) {
              return  '<a href="'.url('datasets/'.$dataset->id."/request").'" class="btn btn-warning btn-xs datasetexport" id='.$dataset->id.'  data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.tooltip_request_dataset').'"><span class="glyphicon glyphicon-download-alt unstyle"></span></a>';
            } else {
              return  '<a href="'.url('datasets/'.$dataset->id."/download").'" class="btn btn-success btn-xs datasetexport" id='.$dataset->id.' data-toggle="tooltip" rel="tooltip" data-placement="right" title="'.Lang::get('messages.tooltip_download_dataset').'"><span class="glyphicon glyphicon-download-alt unstyle"></span></a>';
            }
        })
        ->rawColumns(['name', 'members', 'tags','measurements','plants','vouchers','action']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Dataset::query();
        //->withCount(
          //['measurements'])->with(['users', 'tags.translations']);

        if ($this->project) {
            $ids = Project::find($this->project)->datasets()->pluck('id')->toArray();
            //$query = $query->whereHas('measurements', function($query) { $query->whereHasMorph('measured',['App\Plant','App\Voucher'],function($q) { $q->where('project_id',$this->project);}); });
            //this is required for non logged users to see tables
            //$dts = DB::select('SELECT DISTINCT tb.dataset_id FROM (SELECT DISTINCT measurements.dataset_id FROM measurements LEFT JOIN plants ON plants.id=measurements.measured_id WHERE measurements.measured_type="App\\\Plant" AND plants.project_id='.$this->project.' UNION SELECT DISTINCT measurements.dataset_id FROM measurements LEFT JOIN vouchers ON vouchers.id=measurements.measured_id WHERE measurements.measured_type="App\\\Voucher" AND vouchers.project_id='.$this->project.') AS tb');
            //$dts = array_map(function ($value) { return (array)$value;}, $dts);
            $query->whereIn('id',$ids);
        }
        if ($this->tag) {
            $query->whereHas('tags',function($tag) { $tag->where('tags.id',$this->tag);});
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
                'action' => ['title' => Lang::get('messages.actions'), 'searchable' => false, 'orderable' => false],
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'privacy' => ['title' => Lang::get('messages.privacy'), 'searchable' => false, 'orderable' => true],
                'members' => ['title' => Lang::get('messages.members'), 'searchable' => false, 'orderable' => false],
                'tags' => ['title' => Lang::get('messages.tags'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
                'plants' => ['title' => Lang::get('messages.plants'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
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
                    'targets' => [2,3,4],
                    'visible' => false,
                ],
              ],
              'pagelength' => 5,
            ]);
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'odb_datasets_'.time();
    }
}

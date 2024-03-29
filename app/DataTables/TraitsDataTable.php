<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Models\ODBTrait;
use App\Models\UserTranslation;
use Lang;
use DB;
use Auth;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
#use Lang;

class TraitsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('name', function ($odbtrait) {
            return $odbtrait->rawLink();
        })
        ->editColumn('type', function ($odbtrait) { return Lang::get('levels.traittype.'.$odbtrait->type); })
        ->addColumn('details', function ($odbtrait) {return $odbtrait->details(); })
        ->addColumn('measurements', function ($odbtrait) {
          if ($this->dataset) {
            return '<a href="'.url('measurements/'.$odbtrait->id.'|'.$this->dataset.'/individual_dataset').'">'.$odbtrait->measurements()->withoutGlobalScopes()->where('dataset_id','=',$this->dataset)->count().'</a>';
          }
          return '<a href="'.url('measurements/'.$odbtrait->id.'/trait').'">'.$odbtrait->measurements()->withoutGlobalScopes()->count().'</a>';
        })
        ->filterColumn('name', function ($query, $keyword) {
            $query->whereHas('translations',function($trn) use ($keyword) { $trn->where('translation','like','%'.$keyword.'%');})->orWhere('export_name','like','%'.$keyword.'%');

            //$translations = UserTranslation::where('translation', 'like', '%'.$keyword.'%')->where('translatable_type','like','%ODBTrait%')->get()->pluck('translatable_id')->toArray();
            //$query->whereIn('id', $translations);
        })
        ->addColumn('select',  function ($odbtrait) {
            return $odbtrait->id;
        })
        ->rawColumns(['name','measurements']);

    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
      $query = ODBTrait::query()->with(['translations','categories.translations'])->withCount("measurements");
      //->orderBy('export_name', 'asc');

      if ($this->request()->has('type')) {
        $type =  (int) $this->request()->get('type');
        if ($type>0) {
          $query = $query->where('type',$type);
        }
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

        $trait_types =  DB::table('traits')->selectRaw('DISTINCT type')->cursor()->pluck('type')->toArray();
        $title_type = Lang::get('messages.type');
        if (count($trait_types)) {
          $title_type  = Lang::get('messages.type').'<select name="type" id="trait_type" ><option value="">'.Lang::get('messages.all').'</option>';
          foreach ($trait_types as $type) {
                 $title_type  .= '<option value="'.$type.'" >'.Lang::get('levels.traittype.' . $type).'</option>';
          }
          $title_type  .= '</select>';
        }

        if (Auth::user()) {
          $hidcol = [1,2,5];
          $buttons = [
              'pageLength',
              'reload',
              ['extend' => 'colvis',  'columns' => ':gt(0)'],
              [
                'text' => Lang::get('datatables.export'),
                'action' => "function () {
                  var isvisible = document.getElementById('export_pannel').style.display;
                  if (isvisible == 'none') {
                    document.getElementById('export_pannel').style.display = 'block';
                  } else {
                      document.getElementById('export_pannel').style.display = 'none';
                  }
                }",
              ],
            ];

        } else {
          $hidcol = [0,1,2,5];
          $buttons = [
              'pageLength',
              'reload',
              ['extend' => 'colvis',  'columns' => ':gt(0)'],
            ];
        }
        return $this->builder()
            ->columns([
                'select' => ['title' => '', 'searchable' => false, 'orderable' => false],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'export_name' => ['title' => Lang::get('messages.export_name'), 'searchable' => false, 'orderable' => true],
                'name' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => false],
                'type' => ['title' => $title_type, 'searchable' => false, 'orderable' => true],
                'details' => ['title' => Lang::get('messages.details'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' =>   $buttons,
                'columnDefs' => [
                  [
                    'targets' => $hidcol,
                    'visible' => false,
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
        return 'odb_odbtraits_'.time();
    }
}

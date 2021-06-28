<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use Baum\Node;
use App\Models\Individual;
use App\Models\Location;
use App\Models\Taxon;
use App\Models\HasAuthLevels;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use DB;
use App\Models\User;
use Auth;


class IndividualsDataTable extends DataTable
{

    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('tag', function ($individual) {
            return $individual->rawLink();
        })
        ->filterColumn('tag', function ($query, $keyword) {
            $sql = " tag='".$keyword."' OR tag like '%-".$keyword."'";
            $taxon = Taxon::whereRaw("odb_txname(name, level, parent_id) like '%".$keyword."%'");
            if ($taxon->count()) {
              $taxon_list = $taxon->cursor()->first()->getDescendantsAndSelf()->pluck('id')->toArray();
              $query->where(function($subquery) use($taxon_list,$sql) {
                $subquery->whereHas('indirectidentification', function ($q) use ($taxon_list) {
                  $q->whereIn('taxon_id',$taxon_list);
                })->orWhereRaw($sql);
              });
            } else {
              $query->whereRaw($sql);
            }
        })
        ->addColumn('project', function ($individual) { return $individual->project->name; })
        ->addColumn('identification', function ($individual) {
            return $individual->taxonName == Lang::get('messages.unidentified') ?
                   $individual->taxonName : '<em>'.htmlspecialchars($individual->taxonName).'</em>';
        })
        ->addColumn('collectors', function ($individual) {
            $col = $individual->collectors;
            return implode(', ', $col->map(function ($c) {return $c->person->fullname; })->all());
        })
        ->editColumn('date', function ($individual) { return $individual->formatDate; })
        ->addColumn('measurements', function ($individual) {
            if ($this->dataset) {
              return '<a href="'.url('measurements/'.$individual->id.'|'.$this->dataset.'/individual_dataset').'">'.$individual->measurements()->withoutGlobalScopes()->where('dataset_id','=',$this->dataset)->count().'</a>';
            }
            return '<a href="'.url('measurements/'.$individual->id.'/individual').'">'.$individual->measurements()->withoutGlobalScopes()->count().'</a>';
        })
        ->addColumn('location', function ($individual) {
            return $individual->LocationDisplay();
        })
        ->addColumn('select_individuals',  function ($individual) {
            return $individual->id;
        })
        ->addColumn('vouchers',function($individual) {
            return '<a href="'.url('individuals/'.$individual->id.'/vouchers').'">'.$individual->vouchers()->withoutGlobalScopes()->count().'</a>';
        })
        ->rawColumns(['tag', 'identification','measurements','location','vouchers']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Individual::query()->with(['project', 'locations', 'collectors.person'])
            ->select([
                'individuals.id',
                'individuals.tag',
                'individuals.project_id',
                'individuals.date',
                DB::raw('odb_ind_relativePosition(individuals.id) as relativePosition'),
                DB::raw('odb_ind_fullname(individuals.id,individuals.tag) as fullname'),
            ])->withCount('measurements','vouchers');
        // customizes the datatable query
        if ($this->location) {
            $locationsids = Location::where('id', '=', $this->location)->first()->getDescendantsAndSelf()->pluck('id');
            $query = $query->whereHas('locations',function($q) use($locationsids) {
              $q->whereIn('location_id',$locationsids);
            });
        }
        if ($this->project) {
            $query = $query->where('project_id', '=', $this->project);
        }
        if ($this->taxon) {
            $taxons = Taxon::where('id',$this->taxon)->first()->getDescendantsAndSelf()->pluck('id')->toArray();
            $query = $query->whereHas('identification', function ($q) use ($taxons) {$q->whereIn('taxon_id',$taxons); });
        }
        if ($this->person) {
            $person = $this->person;
            $query = $query->whereHas('collectors', function ($q) use ($person) {$q->where('person_id', '=', $person); });
        }
        if ($this->dataset) {
            $dataset  = $this->dataset;
            $query = $query->whereHas('measurements', function ($q) use ($dataset) {$q->where('dataset_id', '=', $dataset); });

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

      if (Auth::user()) {
        $hidcol = [1,5, 6];
        $buttons = [
            'pageLength',
            'reload',
            ['extend' => 'colvis',  'columns' => ':gt(0)', 'collectionLayout' => 'two-column'],
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
        $hidcol = [0,1,5, 6];
        $buttons = [
            'pageLength',
            'reload',
            ['extend' => 'colvis',  'columns' => ':gt(0)', 'collectionLayout' => 'two-column'],
          ];
      }
        return $this->builder()
            ->columns([
                'select_individuals' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'tag' => ['title' => Lang::get('messages.individual'), 'searchable' => true, 'orderable' => true],
                'identification' => ['title' => Lang::get('messages.identification'), 'searchable' => false, 'orderable' => false],
                'location' => ['title' => Lang::get('messages.location'), 'searchable' => false, 'orderable' => false],
                'collectors' => ['title' => Lang::get('messages.collectors'), 'searchable' => false, 'orderable' => false],
                'date' => ['title' => Lang::get('messages.date'), 'searchable' => false, 'orderable' => true],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => true],
                'vouchers' => ['title' => Lang::get('messages.voucher'), 'searchable' => false, 'orderable' => false],
                'project' => ['title' => Lang::get('messages.project'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' => $buttons,
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
                  ]
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
        return 'odb_individuals_'.time();
    }
}

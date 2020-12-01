<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use Baum\Node;
use App\Plant;
use App\Location;
use App\Taxon;
use App\HasAuthLevels;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use App\User;
use Auth;


class PlantsDataTable extends DataTable
{

    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('tag', function ($plant) {
            return $plant->rawLink();
        })
        ->filterColumn('tag', function ($query, $keyword) {
            $sql = " tag='".$keyword."' OR tag like '%-".$keyword."'";
            $query->whereRaw($sql);
        })
        ->addColumn('project', function ($plant) { return $plant->project->name; })
        ->addColumn('identification', function ($plant) {
            return $plant->taxonName == Lang::get('messages.unidentified') ?
                   $plant->taxonName : '<em>'.htmlspecialchars($plant->taxonName).'</em>';
        })
        ->addColumn('tag_team', function ($plant) {
            $col = $plant->collectors;
            return implode(', ', $col->map(function ($c) {return $c->person->fullname; })->all());
        })
        ->editColumn('date', function ($plant) { return $plant->formatDate; })
        ->addColumn('measurements', function ($plant) {
            return '<a href="'.url('plants/'.$plant->id.'/measurements').'">'.$plant->measurements()->count().'</a>';
        })
        ->addColumn('location', function ($plant) {
            $loc = $plant->locationWithGeom;
            if (!$loc) {
                return;
            }

            return $loc->coordinatesSimple;
        })
        ->addColumn('select_plants',  function ($plant) {
            return $plant->id;
        })
        ->addColumn('vouchers',function($plant) {
            return $plant->vouchers()->count();
        })
        ->rawColumns(['tag', 'identification','measurements','location']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Plant::query()->with(['identification', 'project', 'location', 'collectors.person'])
            ->select([
                'plants.id',
                'tag',
                'location_id',
                'project_id',
                'plants.date',
            ])->withCount('measurements');
        // customizes the datatable query
        if ($this->location) {
            $locationsids = Location::where('id', '=', $this->location)->first()->getDescendantsAndSelf()->pluck('id');
            //$locationsids = array_merge((array)$this->location,$locationsids);
            $query = $query->whereIn('location_id',$locationsids);
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
        return $this->builder()
            ->columns([
                'select_plants' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'tag' => ['title' => Lang::get('messages.location_and_tag'), 'searchable' => true, 'orderable' => true],
                'identification' => ['title' => Lang::get('messages.identification'), 'searchable' => false, 'orderable' => false],
                'project' => ['title' => Lang::get('messages.project'), 'searchable' => false, 'orderable' => false],
                'tag_team' => ['title' => Lang::get('messages.tag_team'), 'searchable' => false, 'orderable' => false],
                'date' => ['title' => Lang::get('messages.date'), 'searchable' => false, 'orderable' => true],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => true],
                'location' => ['title' => Lang::get('messages.location'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.voucher'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' => [
                    /* export buttons are in views now. Print button also disable because large tables will stuck requests
                    'csv',
                    'excel',
                    'print',
                    */
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)', 'collectionLayout' => 'two-column'],
                ],
                'columnDefs' => [
                  [
                    'targets' => [1,5, 6, 8],
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
        return 'odb_plants_'.time();
    }
}

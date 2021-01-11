<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use Baum\Node;
use App\Voucher;
use App\Location;
use App\Taxon;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use DB;
class VouchersDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('fullname', function ($voucher) {
            return $voucher->rawLink();
        })
        ->filterColumn('fullname', function ($query, $keyword) {
            $taxon = Taxon::whereRaw('odb_txname(name, level, parent_id) like ?', ["%".$keyword."%"])->cursor();
            if ($taxon->count()) {
              $taxon_list = $taxon->first()->getDescendantsAndSelf()->pluck('id')->toArray();
              $query->where('number', 'like', ["%{$keyword}%"])->orWhere('persons.full_name','like',["%{$keyword}%"])->orWhere(function($subquery) use($taxon_list) {
                $subquery->whereHas('identification', function ($q) use ($taxon_list) {$q->whereIn('taxon_id',$taxon_list); })->orWhereHas('plant_identification',function($q) use($taxon_list) {
              $q->whereIn('taxon_id',$taxon_list);
              });});
            } else {
              $query->where('number', 'like', ["%{$keyword}%"])->orWhere('persons.full_name','like',["%{$keyword}%"]);
            }
        })
        ->addColumn('project', function ($voucher) { return $voucher->project->name; })
        ->addColumn('identification', function ($voucher) {
            return $voucher->taxonName == Lang::get('messages.unidentified') ?
                   $voucher->taxonName : '<em>'.htmlspecialchars($voucher->taxonName).'</em>';
        })

        ->addColumn('collectors', function ($voucher) {
            $col = $voucher->collectors;
            return implode(', ',$col->map(function ($c) {return $c->person->fullname; })->all()
          );
        })
        ->editColumn('parent_type', function ($voucher) {
            $text = 'Linked to location';
            if ($voucher->parent_type ==  'App\Plant') {
              $text = $voucher->parent->rawLink();
            }
            return $text;
        })
        ->addColumn('location',function($voucher) {
            $text = "";
            if (null !== $voucher->locationWithGeom) {
                $text = $voucher->locationWithGeom->first()->rawLink();
                $text .= "<br>".$voucher->locationWithGeom->first()->coordinatesSimple;
            }
            return $text;
        })
        ->editColumn('date', function ($voucher) { return $voucher->formatDate; })
        ->addColumn('measurements', function ($voucher) {
            return '<a href="'.url('vouchers/'.$voucher->id.'/measurements').'">'.$voucher->measurements()->withoutGlobalScopes()->count().'</a>';
        })
        ->addColumn('select_vouchers',  function ($voucher) {
            return $voucher->id;
        })
        ->rawColumns(['fullname', 'identification','location','measurements','parent_type']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        //This slows down and is not needed here
        $query = Voucher::query()
            ->select([
                'vouchers.id',
                'number',
                'person_id',
                'project_id',
                'parent_id',
                'parent_type',
                'date',
                'persons.full_name',
            ])->join('persons','person_id','=','persons.id');
            //with(['identification.taxon', 'person']);
            //->withCount('measurements');
        // customizes the datatable query
        if ($this->location) {
            $locationsids = Location::find($this->location)->getDescendantsAndSelf()->pluck('id')->toArray();
            $query = $query->where(function($q) use($locationsids) {
              $q->where(function($subquery) use($locationsids) {
                $subquery->where('parent_type', 'App\Location')->whereIn('parent_id',$locationsids);
              })->orWhereHasMorph('parent',["App\Plant"],function($plant) use($locationsids) {
                  $plant->whereIn('location_id',$locationsids); });
            });


        }
        if ($this->plant) {
            $query = $query->where('parent_type', 'App\Plant')->where('parent_id', '=', $this->plant);
        }
        if ($this->project) {
            $query = $query->where('project_id', '=', $this->project);
        }
        if ($this->taxon) {
            $taxon = $this->taxon;
            $taxon_list = Taxon::where('id',$taxon)->first()->getDescendantsAndSelf()->pluck('id')->toArray();
            $query = $query->where(function($subquery) use($taxon_list) {
              $subquery->whereHas('identification', function ($q) use ($taxon_list) {$q->whereIn('taxon_id',$taxon_list); })->orWhereHas('plant_identification',function($q) use($taxon_list) {
                $q->whereIn('taxon_id',$taxon_list);
            });});
        }
        if ($this->person) {
            $person = $this->person;
            $q1 = $query->whereHas('collectors', function ($q) use ($person) {$q->where('person_id', '=', $person); });
            $query2 = Voucher::query()->with(['identification.taxon', 'project', 'parent'])
                ->select([
                    'vouchers.id',
                    'number',
                    'person_id',
                    'project_id',
                    'parent_id',
                    'parent_type',
                    'date',
                ]);
                //->withCount('measurements');
            $query = $query2->where('person_id', $person)->union($q1);
        }
        if ($this->dataset) {
            $dataset  = $this->dataset;
            $query = $query->whereHas('measurements', function ($q) use ($dataset) {$q->where('dataset_id', '=', $dataset); });

        }
        if ($this->herbarium_id) {
            $herbid =$this->herbarium_id;
            $query = $query->whereHas('herbaria', function ($q) use ($herbid) {$q->where('herbarium_id', '=', $herbid); });
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
                'select_vouchers' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'fullname' => ['title' => Lang::get('messages.collector_and_number'), 'searchable' => true, 'orderable' => true],
                'identification' => ['title' => Lang::get('messages.identification'), 'searchable' => true, 'orderable' => false],
                'parent_type' => ['title' => Lang::get('messages.planttag'), 'searchable' => false, 'orderable' => true],
                'location' => ['title' => Lang::get('messages.location'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => true],
                'date' => ['title' => Lang::get('messages.date'), 'searchable' => false, 'orderable' => true],
                'project' => ['title' => Lang::get('messages.project'), 'searchable' => false, 'orderable' => false],
                'collectors' => ['title' => Lang::get('messages.collectors'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' => [
                    /*'csv',
                    'excel',
                    'print',*/
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [
                  [
                    'targets' => [1,4,9],
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
        return 'odb_vouchers_'.time();
    }
}

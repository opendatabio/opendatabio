<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use Baum\Node;
use App\Models\Voucher;
use App\Models\Location;
use App\Models\Taxon;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use DB;
use Auth;

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
              $query->whereHas('identification',function($q) use($taxon_list) {
                $q->whereIn('taxon_id',$taxon_list);
              });
            }
            $query->whereRaw('odb_voucher_fullname(vouchers.id,vouchers.number,vouchers.individual_id,vouchers.biocollection_id) like ?', ["%".$keyword."%"]);
        })
        ->editColumn('biocollection_id', function ($voucher) {
            return $voucher->biocollection->rawLink();
        })
        ->editColumn('biocollection_type', function ($voucher) {
            return $voucher->is_type;
        })
        ->addColumn('identification', function ($voucher) {
            return $voucher->taxon_name == Lang::get('messages.unidentified') ?
                   $voucher->taxon_name : '<em>'.htmlspecialchars($voucher->taxon_name).'</em>';
        })
        ->addColumn('collectors', function ($voucher) {
            return $voucher->all_collectors;
        })
        ->addColumn('individual', function ($voucher) {
            return $voucher->individual->rawLink();
        })
        ->addColumn('location',function($voucher) {
            //return $voucher->location_first()->first()->location()->first()->rawLink();
            return $voucher->LocationDisplay;
        })
        ->editColumn('date', function ($voucher) {
            return $voucher->collection_date;
        })
        ->addColumn('measurements', function ($voucher) {
            return '<a href="'.url('vouchers/'.$voucher->id.'/measurements').'">'.$voucher->measurements()->withoutGlobalScopes()->count().'</a>';
        })
        ->addColumn('select_vouchers',  function ($voucher) {
            return $voucher->id;
        })
        ->addColumn('project', function ($voucher) {
          return $voucher->project_name;
        })
        ->rawColumns(['fullname', 'identification','location','measurements','parent_type','individual','biocollection_id']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        //This slows down and is not needed here
        $query = Voucher::query()->select([
          'vouchers.id',
          'vouchers.individual_id',
          'vouchers.biocollection_id',
          'vouchers.biocollection_number',
          'vouchers.biocollection_type',
          'vouchers.notes',
          'vouchers.number',
          'vouchers.project_id',
          'vouchers.date',
          DB::raw('odb_voucher_fullname(vouchers.id,vouchers.number,vouchers.individual_id,vouchers.biocollection_id) as fullname')
        ]);
        // customizes the datatable query
        if ($this->location) {
            $locationsids = Location::find($this->location)->getDescendantsAndSelf()->pluck('id')->toArray();
            $query = $query->whereHas('location_first',function($q) use($locationsids) {
                $q->whereIn('location_id',$locationsids);
            });
        }
        if ($this->individual) {
            $query = $query->where('individual_id',$this->individual);
        }
        if ($this->project) {
            $query = $query->where('project_id', '=', $this->project);
        }
        if ($this->taxon) {
            $taxon = $this->taxon;
            $taxon_list = Taxon::where('id',$taxon)->first()->getDescendantsAndSelf()->pluck('id')->toArray();
            $query = $query->whereHas('identification', function ($q) use ($taxon_list) { $q->whereIn('taxon_id',$taxon_list); });
        }
        if ($this->person) {
            $person = $this->person;
            $query = $query->whereHas('collectors', function ($q) use ($person) {
                $q->where('person_id', '=', $person);
            });
        }
        if ($this->dataset) {
            $dataset  = $this->dataset;
            $query = $query->whereHas('measurements', function ($q) use ($dataset) {$q->where('dataset_id', '=', $dataset); });

        }
        if ($this->biocollection_id) {
            $query = $query->where('biocollection_id',$this->biocollection_id);
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
          $hidcol = [1,4,6,7,10,12];
        } else {
          $hidcol = [0,1,4,6,7,10,12];
        }

        return $this->builder()
            ->columns([
                'select_vouchers' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'fullname' => ['title' => Lang::get('messages.voucher'), 'searchable' => true, 'orderable' => true],
                'identification' => ['title' => Lang::get('messages.identification'), 'searchable' => false, 'orderable' => false],
                'individual' => ['title' => Lang::get('messages.individual'), 'searchable' => false, 'orderable' => false],
                'biocollection_id' => ['title' => Lang::get('messages.biocollection'), 'searchable' => false, 'orderable' => false],
                'biocollection_number' => ['title' => Lang::get('messages.biocollection_number'), 'searchable' => false, 'orderable' => false],
                'biocollection_type' => ['title' => Lang::get('messages.voucher_isnomenclatural_type'), 'searchable' => false, 'orderable' => false],
                'location' => ['title' => Lang::get('messages.location'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
                'date' => ['title' => Lang::get('messages.date'), 'searchable' => false, 'orderable' => false],
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
        return 'odb_vouchers_'.time();
    }
}

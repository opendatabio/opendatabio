<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Voucher;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;

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
        ->editColumn('number', function ($voucher) {
            return '<a href="'.url('vouchers/'.$voucher->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($voucher->fullname).'</a>';
        })
        ->addColumn('project', function ($voucher) { return $voucher->project->name; })
        ->addColumn('identification', function ($voucher) { return $voucher->taxonName; })
//        ->filterColumn('title', function ($query, $keyword) {
//            $query->where('bibtex', 'like', ["%{$keyword}%"]);
//        })
        ->rawColumns(['number']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Voucher::query()->with(['identification.taxon', 'project', 'parent'])
            ->select([
                'vouchers.id',
                'number',
                'person_id',
                'project_id',
                'parent_id',
            ]);
        // customizes the datatable query
        if ($this->location) {
            $query = $query->where('parent_type', 'App\Location')->where('parent_id', '=', $this->location);
        }
        if ($this->plant) {
            $query = $query->where('parent_type', 'App\Plant')->where('parent_id', '=', $this->plant);
        }
        if ($this->project) {
            $query = $query->where('project_id', '=', $this->project);
        }
        if ($this->taxon) {
            $taxon = $this->taxon;
            $query = $query->whereHas('identification', function ($q) use ($taxon) {$q->where('taxon_id', '=', $taxon); });
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
                ]);
            $query = $query2->where('person_id', $person)->union($q1);
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
                'number' => ['title' => Lang::get('messages.collector_and_number'), 'searchable' => false, 'orderable' => true],
                'identification' => ['title' => Lang::get('messages.identification'), 'searchable' => false, 'orderable' => false],
                'project' => ['title' => Lang::get('messages.project'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Brtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'asc']],
                'buttons' => [
                    'csv',
                    'excel',
                    'print',
                    'reload',
                ],
            ]);
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'odb_samples_'.time();
    }
}

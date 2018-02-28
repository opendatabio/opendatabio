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
        ->addColumn('identification', function ($voucher) {
            return $voucher->taxonName == Lang::get('messages.unidentified') ?
                   $voucher->taxonName : '<em>'.htmlspecialchars($voucher->taxonName).'</em>';
        })
        ->addColumn('collectors', function ($voucher) {
            $col = $voucher->collectors;

            return implode(', ', array_merge([$voucher->person->fullname],
                $col->map(function ($c) {return $c->person->fullname; })->all()
            ));
        })
        ->editColumn('date', function ($voucher) { return $voucher->formatDate; })
        ->addColumn('measurements', function ($voucher) {return $voucher->measurements_count; })
        ->addColumn('location', function ($voucher) {
            if (!$voucher->parent) {
                return;
            }
            if ($voucher->locationWithGeom) {
                return $voucher->locationWithGeom->name.' '.$voucher->locationWithGeom->coordinatesSimple;
            }
            // else, parent is a plant
            if ($voucher->parent->locationWithGeom) {
                return $voucher->parent->locationWithGeom->name.' '.$voucher->parent->locationWithGeom->coordinatesSimple;
            }
        })
        ->rawColumns(['number', 'identification']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Voucher::query()->with(['identification.taxon', 'person', 'collectors.person', 'project', 'parent'])
            ->select([
                'vouchers.id',
                'number',
                'person_id',
                'project_id',
                'parent_id',
                'parent_type',
                'date',
            ])->withCount('measurements');
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
                    'parent_type',
                    'date',
                ])->withCount('measurements');
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
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'identification' => ['title' => Lang::get('messages.identification'), 'searchable' => false, 'orderable' => false],
                'project' => ['title' => Lang::get('messages.project'), 'searchable' => false, 'orderable' => false],
                'collectors' => ['title' => Lang::get('messages.collectors'), 'searchable' => false, 'orderable' => false],
                'date' => ['title' => Lang::get('messages.date'), 'searchable' => false, 'orderable' => true],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => true],
                'location' => ['title' => Lang::get('messages.location'), 'searchable' => false, 'orderable' => false],
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
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [1, 4, 5, 7],
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
        return 'odb_samples_'.time();
    }
}

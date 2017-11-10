<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Measurement;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;

class MeasurementsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('value', function ($measurement) {
            return '<a href="'.url('measurements/'.$measurement->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($measurement->valueActual).'</a>';
        })
        ->editColumn('trait_id', function ($measurement) {
            return '<a href="'.url('traits/'.$measurement->trait_id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($measurement->odbtrait->name).'</a>';
        })
        ->editColumn('measured_id', function ($measurement) {
            $object = $measurement->measured;
            if (empty($object)) {
                return '';
            }

            return $object->taxonName ?
                htmlspecialchars($object->fullname).' (<em>'.
                htmlspecialchars($object->taxonName).'</em>)' :
                htmlspecialchars($object->fullname);
        })
        ->editColumn('dataset_id', function ($measurement) { return $measurement->dataset->name; })
        ->addColumn('unit', function ($measurement) { return $measurement->odbtrait->unit; })
        ->editColumn('date', function ($measurement) { return $measurement->formatDate; })
        ->editColumn('person_id', function ($measurement) { return $measurement->person ? $measurement->person->full_name : ''; })
        ->rawColumns(['value', 'trait_id', 'measured_id']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Measurement::query()->with(['odbtrait', 'person', 'dataset', 'measured'])
            ->select([
                'measurements.id',
                'value',
                'trait_id',
                'value_i',
                'value_a',
                'person_id',
                'dataset_id',
                'measured_id',
                'measured_type',
                'date',
            ]);
        // customizes the datatable query
        if ($this->measured) {
            $query = $query->where('measured_id', '=', $this->measured)->where('measured_type', $this->measured_type);
        }
        if ($this->dataset) {
            $query = $query->where('dataset_id', $this->dataset);
        }
        if ($this->odbtrait) {
            $query = $query->where('trait_id', $this->odbtrait);
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
                'value' => ['title' => Lang::get('messages.value'), 'searchable' => false, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'trait_id' => ['title' => Lang::get('messages.trait'), 'searchable' => false, 'orderable' => false],
                'measured_id' => ['title' => Lang::get('messages.object'), 'searchable' => false, 'orderable' => false],
                'unit' => ['title' => Lang::get('messages.unit'), 'searchable' => false, 'orderable' => false],
                'dataset_id' => ['title' => Lang::get('messages.dataset'), 'searchable' => false, 'orderable' => false],
                'person_id' => ['title' => Lang::get('messages.measurement_measurer'), 'searchable' => false, 'orderable' => false],
                'date' => ['title' => Lang::get('messages.date'), 'searchable' => false, 'orderable' => true],
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
                    'targets' => [1, 4, 5, 6],
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
        return 'odb_measurements_'.time();
    }
}

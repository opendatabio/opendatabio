<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Plant;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;

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
            return '<a href="'.url('plants/'.$plant->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($plant->fullname).'</a>';
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
//        ->filterColumn('title', function ($query, $keyword) {
//            $query->where('bibtex', 'like', ["%{$keyword}%"]);
//        })
        ->rawColumns(['tag', 'identification']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Plant::query()->with(['identification.taxon', 'project', 'location', 'collectors.person'])
            ->select([
                'plants.id',
                'tag',
                'location_id',
                'project_id',
                'plants.date',
            ]);
        // customizes the datatable query
        if ($this->location) {
            $query = $query->where('location_id', '=', $this->location);
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
            $query = $query->whereHas('collectors', function ($q) use ($person) {$q->where('person_id', '=', $person); });
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
                'tag' => ['title' => Lang::get('messages.location_and_tag'), 'searchable' => false, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'identification' => ['title' => Lang::get('messages.identification'), 'searchable' => false, 'orderable' => false],
                'project' => ['title' => Lang::get('messages.project'), 'searchable' => false, 'orderable' => false],
                'tag_team' => ['title' => Lang::get('messages.tag_team'), 'searchable' => false, 'orderable' => false],
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
                    'targets' => [1, 4, 5],
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
        return 'odb_plants_'.time();
    }
}

<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Person;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;

class HistoryDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
            ->addColumn('user', function ($history) { 
                return $history->userResponsible() ? 
                    $history->userResponsible()->email : 
                    Lang::get('messages.unknown_user'); 
            })
            ->addColumn('field', function ($history) { 
                return $history->fieldName() == "created_at" ? Lang::get('messages.history_created') : $history->fieldName(); 
            }) 
            ->addColumn('old_value', function ($history) { return $history->oldValue(); })
            ->addColumn('new_value', function ($history) { 
                return $history->fieldName() == "created_at" ? '' :  $history->newValue(); 
            });
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        if ($this->person) {
            $query = Person::findOrFail($this->person)->revisionHistory();
        } else { 
            throw new \Exception ("Unsupported history request!");
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
                'created_at' => ['title' => Lang::get('messages.when'), 'searchable' => false, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => true, 'orderable' => false],
                'user' => ['title' => Lang::get('messages.user'), 'searchable' => false, 'orderable' => false],
                'field' => ['title' => Lang::get('messages.field'), 'searchable' => false, 'orderable' => false],
                'old_value' => ['title' => Lang::get('messages.old_value'), 'searchable' => false, 'orderable' => false],
                'new_value' => ['title' => Lang::get('messages.new_value'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Brtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'desc']],
                'buttons' => [
                    'csv',
                    'excel',
                    'print',
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [1],
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
        return 'odb_persons_'.time();
    }
}

<?php

namespace App\DataTables;

use App\Person;
use Yajra\Datatables\Services\DataTable;

class PersonsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable()
    {
        return $this->datatables
            ->eloquent($this->query())
	    ->editColumn('abbreviation', function ($person) {
		    return '<a href="' . url('persons/' . $person->id) . '">' . 
			    // Needs to escape special chars, as this will be passed RAW
			    htmlspecialchars($person->abbreviation) . '</a>';
	    }) 
	    ->rawColumns(['abbreviation']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Person::query()->select($this->getColumns());

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
                    ->columns($this->getColumns())
		    ->removeColumn('id') // need to remove it from showing HERE
                    ->parameters([
                        'dom'     => 'Bfrtip',
                        'order'   => [[0, 'asc']],
                        'buttons' => [
                            'csv',
                            'excel',
                            'print',
                            'reload',
                        ],
                    ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            'id',
            'abbreviation',
            'full_name',
            'email'
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'odb_persons_' . time();
    }
}

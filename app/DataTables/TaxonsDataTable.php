<?php

namespace App\DataTables;

use App\Taxon;
use Yajra\Datatables\Services\DataTable;
use Lang;

class TaxonsDataTable extends DataTable
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
	    ->editColumn('name', function ($taxon) {
                $valid = ($taxon->valid) ? '' : '**';
		    return '<a href="' . url('taxons/' . $taxon->id) . '">' . 
                    $valid . 
                    // Needs to escape special chars, as this will be passed RAW
			    htmlspecialchars($taxon->fullname) . '</a>';
	    }) 
	    ->editColumn('level', function($taxon) { return Lang::get('levels.tax.' . $taxon->level); })
//	    ->addColumn('full_name', function($location) {return $location->full_name;})
	    ->rawColumns(['name']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Taxon::query()->select($this->getColumns());

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
		    ->removeColumn('rgt') // need to remove it from showing HERE
		    ->removeColumn('lft') // need to remove it from showing HERE
		    ->removeColumn('parent_id') // need to remove it from showing HERE
		    ->removeColumn('valid') // need to remove it from showing HERE
//		    ->addColumn(['data' => 'full_name', 'title' => 'Full name', 'searchable' => true])
                    ->parameters([
                        'dom'     => 'Bfrtip',
                        'order'   => [[0, 'desc']],
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
	    // we need to ask for all of the columns that might be needed for other methods
        return [
            'id',
            'name',
            'parent_id',
	    'rgt',
	    'lft',
        'level',
        'valid',
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'odb_locations_' . time();
    }
}

<?php

namespace App\DataTables;

use App\Location;
use Yajra\Datatables\Services\DataTable;
use Lang;
use DB;

class LocationsDataTable extends DataTable
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
	    ->editColumn('name', function ($location) {
		    return '<a href="' . url('locations/' . $location->id) . '">' . 
			    // Needs to escape special chars, as this will be passed RAW
			    htmlspecialchars($location->name) . '</a>';
	    }) 
	    ->editColumn('adm_level', function($location) { return Lang::get('levels.adm.' . $location->adm_level); })
	    ->addColumn('full_name', function($location) {return $location->full_name;})
	    ->rawColumns(['name']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Location::query()->select([
            'locations.name', 
            'locations.adm_level', 
            DB::raw('count(plants.id) as plant_count'),
            'locations.rgt', 
            'locations.lft', 
            'locations.id', 
        ])->join('plants', function($join) {
            $join->on('plants.location_id', '>=', 'lft');
            $join->on('plants.location_id', '<=', 'rgt');
        })->groupBy([
            'locations.id', 
            'locations.name', 
            'locations.rgt', 
            'locations.lft', 
            'locations.adm_level', 
        ]);

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
                ['data' => 'name', 'title' => 'Name', 'searchable' => true, 'orderable' => true], 
                ['data' => 'adm_level', 'title' => 'Adm Level', 'searchable' => true, 'orderable' => true], 
                ['data' => 'plant_count', 'title' => 'Plant Count', 'searchable' => false], 
                ['data' => 'full_name', 'title' => 'Full Name', 'searchable' => false, 'orderable' => false], 
            ])
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
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'odb_locations_' . time();
    }
}

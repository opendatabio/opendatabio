<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Activity;
use App\Models\ActivityFunctions;
use App\Models\Individual;
use App\Models\User;
use App\Models\Person;
use App\Models\Voucher;
use App\Models\Taxon;
use App\Models\ODBTrait;
use App\Models\Location;
use App\Models\Measurement;
use App\Models\Dataset;
use App\Models\BibReference;
use App\Models\Project;
use App\Models\Media;
use Lang;



class ActivityDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('causer_id', function ($activity) {
            $user = User::find($activity->causer_id);
            if (null !== $user->person_id) {
              return  Person::find($user->person_id)->rawLink();
            } else {
              return User::find($activity->causer_id)->email;
            }
        })
        ->editColumn('created_at', function ($activity) {
            return $activity->created_at->format('Y-m-d'); // human readable format
        })
        ->editColumn('subject_type', function ($activity) {
            if (null !== $activity->subject) {
              return $activity->subject->rawLink();
            }
            return '';
        })
        ->editColumn('properties', function ($activity) {
            return ActivityFunctions::formatActivityProperties($activity);
        })
        ->rawColumns(['subject_type','properties','causer_id']);

    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Activity::query()->select([
            'id',
            'log_name',
            'description',
            'properties',
            'subject_type',
            'subject_id',
            'causer_id',
            'created_at',
        ]);
        if ($this->individual) {
            $query = $query->where('subject_type' , Individual::class)->where('subject_id',$this->individual);
        }
        if ($this->location) {
            $query = $query->where('subject_type' , Location::class)->where('subject_id',$this->location);
        }
        if ($this->taxon) {
            $query = $query->where('subject_type' , Taxon::class)->where('subject_id',$this->taxon);
        }
        if ($this->measurement) {
            $query = $query->where('subject_type' , Measurement::class)->where('subject_id',$this->measurement);
        }
        if ($this->dataset) {
            $query = $query->where('subject_type' , Dataset::class)->where('subject_id',$this->dataset);
        }
        if ($this->odbtrait) {
            $query = $query->where('subject_type' , ODBTrait::class)->where('subject_id',$this->odbtrait);
        }
        if ($this->bibreference) {
            $query = $query->where('subject_type' , BibReference::class)->where('subject_id',$this->bibreference);
        }
        if ($this->voucher) {
            $query = $query->where('subject_type' , Voucher::class)->where('subject_id',$this->voucher);
        }
        if ($this->person) {
            $query = $query->where('subject_type' , Person::class)->where('subject_id',$this->person);
        }
        if ($this->project) {
            $query = $query->where('subject_type' , Project::class)->where('subject_id',$this->project);
        }
        if ($this->media) {
            $query = $query->where('subject_type' , Media::class)->where('subject_id',$this->media);
        }

        $query = $query->orderby('created_at','DESC');
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
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => false],
                'log_name' => ['title' => Lang::get('messages.activity_logname'), 'searchable' => true, 'orderable' => true],
                'description' => ['title' => Lang::get('messages.activity_description'), 'searchable' => true, 'orderable' => true],
                'properties' => ['title' => Lang::get('messages.changes'), 'searchable' => false, 'orderable' => false],
                'subject_type' => ['title' => Lang::get('messages.object'), 'searchable' => false, 'orderable' => false],
                'causer_id' => ['title' => Lang::get('messages.modified_by'), 'searchable' => false, 'orderable' => false],
                'created_at' => ['title' => Lang::get('messages.when'), 'searchable' => false, 'orderable' => true]
            ])
            ->parameters([
                'dom' => 'Bfrtip',
                'language' => DataTableTranslator::language(),
                'order' => [[0, 'desc']],
                'lengthMenu' => [1,5,10,15,20,50,100],
                'buttons' => [
                    'pageLength',
                    'csv',
                    'print',
                    'reload',
                    ['extend' => 'colvis',  'columns' => ':gt(0)'],
                ],
                'columnDefs' => [[
                    'targets' => [0,1,4],
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
        return 'odb_activity_'.time();
    }
}

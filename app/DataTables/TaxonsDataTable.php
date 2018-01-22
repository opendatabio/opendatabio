<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\DataTables;

use App\Taxon;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\DataTables;
use Lang;
use DB;

class TaxonsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @return \Yajra\Datatables\Engines\BaseEngine
     */
    public function dataTable(DataTables $dataTables, $query)
    {
        return (new EloquentDataTable($query))
        ->editColumn('fullname', function ($taxon) {
            return '<a href="'.url('taxons/'.$taxon->id).'">'.
                // Needs to escape special chars, as this will be passed RAW
                htmlspecialchars($taxon->qualifiedFullname).'</a>';
        })
        ->editColumn('level', function ($taxon) { return $taxon->levelName; })
        ->addColumn('authorSimple', function ($taxon) { return $taxon->authorSimple; })

        ->filterColumn('fullname', function ($query, $keyword) {
            $query->whereRaw('odb_txname(name, level, parent_id) like ?', ["%{$keyword}%"]);
        })
        ->filterColumn('authorSimple', function ($query, $keyword) {
            $query->where('persons.full_name', 'like', ["%{$keyword}%"])->orWhere('author', 'like', ["%{$keyword}%"]);
        })
        ->addColumn('plants', function ($taxon) {return '<a href="'.url('taxons/'.$taxon->id.'/plants').'">'.$taxon->identified_plants_count.'</a>'; })
        ->addColumn('vouchers', function ($taxon) {return '<a href="'.url('taxons/'.$taxon->id.'/vouchers').'">'.$taxon->identified_vouchers_count.'</a>'; })
        ->addColumn('measurements', function ($taxon) {return '<a href="'.url('taxons/'.$taxon->id.'/measurements').'">'.$taxon->measurements_count.'</a>'; })
        ->addColumn('pictures', function ($taxon) {return '<a href="'.url('taxons/'.$taxon->id).'">'.$taxon->pictures_count.'</a>'; })
        ->addColumn('family', function ($taxon) {return $taxon->family; })
        ->addColumn('external', function ($taxon) {
            $ret = '';
            if ($taxon->mobot) {
                $ret .= '<a href="http://tropicos.org/Name/'.$taxon->mobot.'"><img src="'.asset('images/TropicosLogo.gif').'" alt="Tropicos"></a>"';
            }
            if ($taxon->ipni) {
                $ret .= '<a href="http://www.ipni.org/ipni/idPlantNameSearch.do?id='.$taxon->ipni.'"><img src="'.asset('images/IpniLogo.png').'" alt="IPNI" width="33px"></a>';
            }
            if ($taxon->mycobank) {
                $ret .= '<a href="http://www.mycobank.org/Biolomics.aspx?Table=Mycobank&Rec='.$taxon->mycobank.'&Fields=All"><img src="'.asset('images/MBLogo.png').'" alt="Mycobank" width="33px"></a>';
            }

            return $ret;
        })
        ->rawColumns(['fullname', 'plants', 'vouchers', 'measurements', 'pictures', 'external']);
    }

    /**
     * Get the query object to be processed by dataTables.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection
     */
    public function query()
    {
        $query = Taxon::query()
            ->select([
                'taxons.id',
                'name',
                'parent_id',
                'author_id',
                'author',
                'rgt',
                'lft',
                'level',
                'valid',
                'full_name',
            ])->addSelect(DB::raw('odb_txname(name, level, parent_id) as fullname'))
            ->leftJoin('persons', 'taxons.author_id', '=', 'persons.id')
            ->withCount(['identified_plants', 'identified_vouchers', 'measurements', 'pictures'])
            ->with('externalrefs');

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
                'fullname' => ['title' => Lang::get('messages.name'), 'searchable' => true, 'orderable' => true],
                'id' => ['title' => Lang::get('messages.id'), 'searchable' => false, 'orderable' => true],
                'family' => ['title' => Lang::get('messages.family'), 'searchable' => true, 'orderable' => true],
                'level' => ['title' => Lang::get('messages.level'), 'searchable' => false, 'orderable' => true],
                'authorSimple' => ['title' => Lang::get('messages.author'), 'searchable' => true, 'orderable' => true],
                'plants' => ['title' => Lang::get('messages.plants'), 'searchable' => false, 'orderable' => false],
                'vouchers' => ['title' => Lang::get('messages.vouchers'), 'searchable' => false, 'orderable' => false],
                'measurements' => ['title' => Lang::get('messages.measurements'), 'searchable' => false, 'orderable' => false],
                'pictures' => ['title' => Lang::get('messages.pictures'), 'searchable' => false, 'orderable' => false],
                'external' => ['title' => Lang::get('messages.external'), 'searchable' => false, 'orderable' => false],
            ])
            ->parameters([
                'dom' => 'Bfrtip',
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
                    'targets' => [1, 9],
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
        return 'odb_taxons_'.time();
    }
}

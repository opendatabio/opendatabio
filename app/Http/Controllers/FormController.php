<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

namespace App\Http\Controllers;

use App\Form;
use App\Dataset;
use App\Measurement;
use App\Plant;
use App\Project;
use Illuminate\Http\Request;
use App\DataTables\FormsDataTable;
use Auth;
use Lang;

class FormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(FormsDataTable $dataTable)
    {
        return $dataTable->render('forms.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('forms.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Form::class);
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'measured_type' => 'required|string',
            'trait_id' => 'required|array|min:1',
            'trait_id.1' => 'required|numeric',
        ]);
        $form = new Form($request->only(['name', 'measured_type', 'notes']));
        $form->user_id = Auth::user()->id;
        $form->save(); // to generate id
        $ids = array_values(array_filter($request->trait_id)); // to collapse empty keys
        foreach ($ids as $order => $odbtrait) {
            $form->traits()->attach($odbtrait, ['order' => $order + 1]);
        }
        $form->save();

        return redirect('forms/'.$form->id)->withStatus(Lang::get('messages.stored'));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Form $form
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $form = Form::with('traits')->findOrFail($id);

        return view('forms.show', compact('form'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Form $form
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $form = Form::findOrFail($id);

        return view('forms.create', compact('form'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Form                $form
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $form = Form::findOrFail($id);
        $this->authorize('update', $form);
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'trait_id' => 'required|array|min:1',
        ]);
        $form->update($request->only(['name', 'notes']));
        $form->traits()->detach();
        $ids = array_values(array_filter($request->trait_id)); // to collapse empty keys
        foreach ($ids as $order => $odbtrait) {
            $form->traits()->attach($odbtrait, ['order' => $order + 1]);
        }

        return redirect('forms/'.$form->id)->withStatus(Lang::get('messages.saved'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Form $form
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Form $form)
    {
    }

    public function prepare(Request $request, $id)
    {
        $form = Form::findOrFail($id);
        $datasets = Auth::user()->datasets;
        $traits = $form->traits->pluck('id');
//        switch ($form->measured_type) {
//        case Plant::class:
        $items = Project::findOrFail($request->project_id)->plants;
//            break;
//        default:
//            $items = [];
//        }
        if ('on' != $request->blank) {
            $measurements = Measurement::where('measured_type', $form->measured_type)
                ->whereIn('measured_id', $items->pluck('id'))
                ->whereIn('trait_id', $traits)
                ->orderBy('date', 'DESC')
                ->with('categories')
                ->get();
        } else {
            $measurements = collect();
        }

        return view('forms.prepare', compact('form', 'items', 'measurements', 'datasets'));
    }

    public function fill(Request $request, $id)
    {
        $form = Form::findOrFail($id);
        $traits = $form->traits->pluck('id');
        // TODO: more validation
        $request->validate([
            'date_year' => 'required|integer',
            'person_id' => 'required|integer',
            'dataset_id' => 'required|integer',
        ]);
        $dataset = Dataset::findOrFail($request->dataset_id);
        $this->authorize('create', [Measurement::class, $dataset]);
        // TODO: support link type traits

        // TODO: request->measured_id not needed??

        foreach ($request->value as $line => $elements) {
            for ($column = 0; $column < count($traits); ++$column ) {
                if (array_key_exists($column, $elements) and !is_null($elements[$column])) {
                    $measurement = new Measurement([
                        'trait_id' => $traits[$column],
                        'measured_id' => $line,
                        'measured_type' => $form->measured_type,
                        'dataset_id' => $request->dataset_id,
                        'person_id' => $request->person_id,
                        'bibreference_id' => $request->bibreference_id,
                        'notes' => 'Measurements created with form '.$id,
                    ]);
                    $measurement->setDate($request->date_month, $request->date_day, $request->date_year);
                    $measurement->save();
                    $measurement->valueActual = $elements[$column];
                    $measurement->save();
                }
            }
        }

        return redirect('forms/'.$id)->withStatus(Lang::get('messages.stored'));
    }
}

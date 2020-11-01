<?php

namespace App\Http\Controllers;

use App\Facades\BFServe;
use Illuminate\Http\Request;
use App\Models\CountryModel;
use App\Models\TripPurpose;
use App\Models\BusinessFormModel;
use App\Http\Requests\BusinessFormStoreRequest;

class GuestFormController extends FormController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $name = 'guest_invit';

        if (isset($_GET["copy"]))
            $copy = $_GET["copy"];
        else
            $copy = 0;

        $fields = ['id', 'name_ru', 'group'];

        $allcountries = CountryModel::orderBy('name_ru', 'asc')->get($fields);
        $countries = CountryModel::orderBy('name_ru', 'asc')->where('guest',1)->get($fields);
        $dipcountries = CountryModel::orderBy('name_ru', 'asc')->where('dippred','1')->get($fields);

        $tripPurpose = TripPurpose::where('type','business')->get('purpose_ru');

        return view('visardosites.business_invit',
            compact('name', 'countries', 'allcountries', 'dipcountries', 'tripPurpose'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BusinessFormStoreRequest $request)
    {
        $name = 'store_guest';
        $data = $request->input();

        $data['order'] = $this->getNewOrder();

        BFServe::storeFiles($request, $data);   //  store incoming files to disc

        $item = (new BusinessFormModel())->create($data);  // add data to database

        BFServe::ExcelCreate($data);            // create Excel File

        $ret = BFServe::sendMail($data, 'guest');

        return view('visardosites.business_invit_ok', compact('data', 'name'));
        //return redirect()->route('business_invit_ok', compact('data', 'name'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

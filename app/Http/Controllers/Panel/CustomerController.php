<?php

namespace App\Http\Controllers\Panel;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Models\Customer;
use Illuminate\Routing\Controller;

class CustomerController extends Controller{
    public function store(StoreCustomerRequest $request){
        $validated = $request->validated();
        $customer = Customer::firstOrCreate(
            ['document_number' => $validated['document_number']],
            $validated
        );
        return response()->json([
            'message' => $customer->wasRecentlyCreated
                ? 'Cliente guardado con éxito.'
                : 'Cliente ya estaba registrado. Se retornan sus datos.',
            'data' => $customer
        ]);
    }
    public function index(){
        $customers = Customer::orderBy('id', 'DESC')->paginate(10);
        return response()->json($customers);
    }
}

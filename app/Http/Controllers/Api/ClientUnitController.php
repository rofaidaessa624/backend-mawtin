<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Unit;
use App\Models\ClientUnit;

class ClientUnitController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'unit_id' => 'required|exists:units,id',
            'agreed_price' => 'required|numeric',
            'paid_amount' => 'nullable|numeric',
            'purchase_date' => 'nullable|date',
            'contract_status' => 'nullable|string|in:active,completed,cancelled'
        ]);

        $clientUnit = ClientUnit::create($request->all());

        return response()->json(['success' => true, 'data' => $clientUnit], 201);
    }

    public function getClientUnits($clientId)
    {
        $client = Client::findOrFail($clientId);
        $units = $client->units()->withPivot('agreed_price', 'paid_amount', 'purchase_date', 'contract_status')->get();
        return response()->json($units);
    }
}
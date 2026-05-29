<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;

class ClientDeviceController extends Controller
{

public function saveDeviceToken(Request $request)
{
    $request->validate([
        'client_id' => 'required|exists:clients,id',
        'device_token' => 'required'
    ]);

    $client = Client::find($request->client_id);

    if (!$client) {
        return response()->json([
            'message' => 'Client not found'
        ], 404);
    }

    $client->device_token = $request->device_token;
    $client->save();

    return response()->json([
        'message' => 'Device token saved successfully'
    ]);
}
    // public function saveDeviceToken(Request $request)
    // {
    //     $request->validate([
    //         'client_id' => 'required|exists:clients,id',
    //         'device_token' => 'required'
    //     ]);

    //     $client = Client::find($request->client_id);

    //     $client->device_token = $request->device_token;
    //     $client->save();

    //     return response()->json([
    //         'message' => 'Device token saved successfully'
    //     ]);
    // }
}
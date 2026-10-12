<?php

namespace App\Http\Controllers;

use App\Support\CitySearch;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $q = (string) ($request->validate(['q' => ['nullable', 'string', 'max:60']])['q'] ?? '');
        $limit = min(max((int) $request->query('limit', 6), 1), 20);

        return response()->json(['data' => CitySearch::search($q, $limit)]);
    }
}

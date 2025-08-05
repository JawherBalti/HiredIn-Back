<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function index()
    {
        return response()->json(Company::all());
    }

    public function getCurrentUserCompanies()
    {
        return response()->json(
            Company::where('user_id', Auth::id())->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'nullable|url',
            'industry' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();

        $company = Company::create($validated);
        return response()->json($company, 201);
    }

    public function show(Company $company)
    {
        return response()->json($company);
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'website' => 'nullable|url',
            'industry' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $company->update($validated);
        return response()->json($company);
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json(null, 204);
    }
}
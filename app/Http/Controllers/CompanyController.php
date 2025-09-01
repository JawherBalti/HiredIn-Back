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
            'logo_url' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        
        // Check if user already has 3 or more companies
        $companyCount = Company::where('user_id', $userId)->count();
        
        if ($companyCount >= 3) {
            return response()->json([
                'message' => 'You have reached the maximum limit of 3 companies per user.'
            ], 422);
        }

        $validated['user_id'] = $userId;
        $company = Company::create($validated);
        
        return response()->json($company, 201);
    }

    public function show(Company $company)
    {
        return response()->json($company);
    }

    public function getCompanyById($id)
    {
        // Find the job offer with company data
        $company = Company::find($id);

        // If not found, return 404
        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        // Return the job offer data
        return response()->json($company);
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'website' => 'nullable|url',
            'industry' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|string|max:255',
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
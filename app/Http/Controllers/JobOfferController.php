<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobOfferController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 9); // Default to 9 items per page
        $page = $request->get('page', 1); // Default to page 1
    
        $jobOffers = JobOffer::with('company')
        ->orderBy('created_at', 'desc') // Add this line to order by creation date descending
        ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $jobOffers->items(),
            'pagination' => [
                'current_page' => $jobOffers->currentPage(),
                'per_page' => $jobOffers->perPage(),
                'total' => $jobOffers->total(),
                'last_page' => $jobOffers->lastPage(),
                'from' => $jobOffers->firstItem(),
                'to' => $jobOffers->lastItem(),
            ]
        ]);
    }

    public function getRecentJobs()
    {
        $recentJobOffers = JobOffer::with('company')
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->get();
        
        return response()->json($recentJobOffers);
    }

    public function getCurrentUserJobs(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default to 10 items per page
        $page = $request->get('page', 1);
        
        $jobOffers = JobOffer::with('company')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc') // Add ordering
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $jobOffers->items(),
            'pagination' => [
                'current_page' => $jobOffers->currentPage(),
                'per_page' => $jobOffers->perPage(),
                'total' => $jobOffers->total(),
                'last_page' => $jobOffers->lastPage(),
                'from' => $jobOffers->firstItem(),
                'to' => $jobOffers->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:applied,interview,offer',
            'deadline' => 'nullable|date',
            'salary' => 'nullable|string|max:255',
            'location' => 'nullable|string',
            'type' => 'nullable|string'
            // 'resume' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        $jobOffer = new JobOffer($validated);
        $jobOffer->user_id = Auth::id();

        if ($request->hasFile('resume')) {
            $path = $request->file('resume')->store('resumes', 'public');
            $jobOffer->resume_path = $path;
        }

        $jobOffer->save();

        return response()->json($jobOffer->load('company'), 201);
    }

    public function show(JobOffer $jobOffer)
    {
        $this->authorize('view', $jobOffer);
        return response()->json($jobOffer->load('company'));
    }

    public function getOneJobOffer($id)
    {
        // Find the job offer with company data
        $jobOffer = JobOffer::with('company')->find($id);

        // If not found, return 404
        if (!$jobOffer) {
            return response()->json(['message' => 'Job offer not found'], 404);
        }

        // Return the job offer data
        return response()->json($jobOffer);
    }

public function update(Request $request, JobOffer $jobOffer)
{
    // Handle both JSON and form-data
    if ($request->isJson()) {
        $data = $request->json()->all();
    } else {
        // For form-data, manually parse the input
        $data = $request->all();
        
        // If empty (common with PUT/PATCH + form-data), parse manually
        if (empty($data)) {
            $input = file_get_contents('php://input');
            parse_str($input, $data);
        }
    }


    $validated = validator($data, [
        'company_id' => 'sometimes|required|exists:companies,id',
        'title' => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'status' => 'sometimes|required|in:applied,interview,offer',
        'type' => 'nullable|string',
        'deadline' => 'nullable|date',
        'salary' => 'nullable|string|max:255',
        'location' => 'nullable|string',
    ])->validate();

    $jobOffer->update($validated);
    
    return response()->json($jobOffer->fresh()->load('company'));
}

    public function destroy(JobOffer $jobOffer)
    {
        $this->authorize('delete', $jobOffer);
        
        if ($jobOffer->resume_path) {
            Storage::disk('public')->delete($jobOffer->resume_path);
        }
        
        $jobOffer->delete();
        return response()->json(null, 204);
    }
}
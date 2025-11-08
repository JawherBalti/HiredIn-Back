<?php
    // app/Http/Controllers/ResumeController.php
    namespace App\Http\Controllers;

    use App\Models\User;
    use App\Models\Resume;
    use App\Models\JobOffer;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Auth;
    use App\Notifications\ApplicationAccepted;
    use App\Notifications\JobApplied;

    class ResumeController extends Controller
    {
    // Apply to a job (upload resume)
    public function store(Request $request, JobOffer $jobOffer)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'cover_letter' => 'nullable|string'
        ]);

        // Check if user already applied
        if ($jobOffer->resumes()->where('user_id', auth()->id())->exists()) {
            return response()->json(['message' => 'You have already applied to this job'], 422);
        }

        $file = $request->file('resume');
        $path = $file->store('resumes');
        $settings = json_decode($jobOffer->user->settings);
        if( $settings == null || $settings->notifications->pushNotifications )
        $jobOffer->user->notify(new JobApplied($jobOffer));    

        $resume = Resume::create([
            'user_id' => auth()->id(), // Moved inside the create() method
            'job_offer_id' => $jobOffer->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'cover_letter' => $request->cover_letter,
            'status' => 'pending'
        ]);

        return response()->json($resume, 201);
    }

    // app/Http/Controllers/ResumeController.php
    public function index(JobOffer $jobOffer, Request $request)
    {
        $perPage = $request->get('per_page', 9); // Default to 10 items per page
        $page = $request->get('page', 1);
        
        $resumesQuery = $jobOffer->resumes()
            ->with(['user', 'interview']);
        
        // Get paginated results
        $resumes = $resumesQuery->paginate($perPage, ['*'], 'page', $page);
        
        // Transform the data while maintaining your structure
        $transformedData = $resumes->getCollection()->map(function ($resume) {
            return [
                'id' => $resume->id,
                'user_id' => $resume->user_id,
                'job_offer_id' => $resume->job_offer_id,
                'cover_letter' => $resume->cover_letter,
                'status' => $resume->status,
                'created_at' => $resume->created_at,
                'updated_at' => $resume->updated_at,
                'user' => $resume->user,
                'interview' => $resume->interview
            ];
        });
        
        return response()->json([
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $resumes->currentPage(),
                'per_page' => $resumes->perPage(),
                'total' => $resumes->total(),
                'last_page' => $resumes->lastPage(),
                'from' => $resumes->firstItem(),
                'to' => $resumes->lastItem(),
            ]
        ]);
    }

    // app/Http/Controllers/ResumeController.php
    public function getUserApplications(Request $request)
    {
        $perPage = $request->get('per_page', 9);
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        $jobType = $request->get('job_type', '');
        $industry = $request->get('industry', '');
        $dateFilter = $request->get('date_filter', '');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Resume::where('user_id', auth()->id())
            ->with(['jobOffer', 'jobOffer.company', 'interview'])
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($search) {
            $query->whereHas('jobOffer', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Job type filter
        if ($jobType) {
            $query->whereHas('jobOffer', function ($q) use ($jobType) {
                $q->where('type', $jobType);
            });
        }

        // Industry filter
        if ($industry) {
            $query->whereHas('jobOffer.company', function ($q) use ($industry) {
                $q->where('industry', $industry);
            });
        }

        // Date filters - using resume created_at (application date)
        if ($dateFilter) {
            switch ($dateFilter) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereBetween('created_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth()
                    ]);
                    break;
                case 'custom':
                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [
                            $startDate,
                            $endDate
                        ]);
                    }
                    break;
            }
        }

        // Get paginated results
        $applications = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the paginated results
        $transformedData = $applications->getCollection()->map(function ($resume) {
            return [
                'job_offer' => $resume->jobOffer,
                'status' => $resume->status,
                'applied_at' => $resume->created_at,
                'cover_letter' => $resume->cover_letter,
                'resume_id' => $resume->id,
                'interview' => $resume->interview
            ];
        });

        return response()->json([
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $applications->currentPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
                'last_page' => $applications->lastPage(),
                'from' => $applications->firstItem(),
                'to' => $applications->lastItem(),
            ]
        ]);
    }

    // Download a resume
    public function download(Resume $resume)
    {
        return Storage::download($resume->file_path, $resume->file_name);
    }

    // Update application status
    // public function updateStatus(Request $request, Resume $resume)
    // {
    //     $request->validate([
    //         'status' => 'required|in:pending,reviewed,rejected,accepted'
    //     ]);

    //     $resume->update(['status' => $request->status]);

    //     return response()->json($resume);
    // }

    // Update application status
    public function updateStatus(Request $request, Resume $resume)
    {
        $request->validate([
            'status' => 'required|in:pending,reviewed,rejected,accepted'
        ]);

        $previousStatus = $resume->status;
        $resume->update(['status' => $request->status]);

        // Send notification if status changed to accepted
        if ($request->status != 'pending' && $previousStatus != $request->status) {
        try {
            $settings = json_decode($resume->user->settings);
            if( $settings == null || $settings->notifications->applicationUpdates )
            $resume->user->notify(new ApplicationAccepted($resume, $request->status));    
        } catch (\Exception $e) {
            \Log::error('Notification failed: '.$e->getMessage());
        }        
    }

        return response()->json($resume);
    }
}
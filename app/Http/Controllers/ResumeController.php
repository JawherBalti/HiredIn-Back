<?php
    // app/Http/Controllers/ResumeController.php
    namespace App\Http\Controllers;

    use App\Models\Resume;
    use App\Models\JobOffer;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Auth;
    use App\Notifications\ApplicationAccepted;


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
    public function index(JobOffer $jobOffer)
    {
        $resumes = $jobOffer->resumes()
            ->with(['user', 'jobOffer', 'interview'])
            ->get()
            ->map(function ($resume) {
                return [
                    'id' => $resume->id,
                    'user_id' => $resume->user_id,
                    'job_offer_id' => $resume->job_offer_id,
                    'status' => $resume->status,
                    'created_at' => $resume->created_at,
                    'updated_at' => $resume->updated_at,
                    'user' => $resume->user,
                    'job_offer' => $resume->jobOffer,
                    'interview' => $resume->interview // Explicitly include
                ];
            });
        
        return response()->json($resumes);
    }

    // app/Http/Controllers/ResumeController.php
    public function getUserApplications()
    {
        $applications = Resume::where('user_id', auth()->id())
            ->with(['jobOffer', 'jobOffer.company', 'interview']) // Add interview here
            ->get()
            ->map(function ($resume) {
                return [
                    'job_offer' => $resume->jobOffer,
                    'status' => $resume->status,
                    'applied_at' => $resume->created_at,
                    'cover_letter' => $resume->cover_letter,
                    'resume_id' => $resume->id,
                    'interview' => $resume->interview // Include interview data
                ];
            });

        return response()->json($applications);
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
            $resume->user->notify(new ApplicationAccepted($resume->jobOffer, $request->status));
        } catch (\Exception $e) {
            \Log::error('Notification failed: '.$e->getMessage());
        }        
    }

        return response()->json($resume);
    }
}
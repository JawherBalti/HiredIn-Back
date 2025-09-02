<?php
// app/Http/Controllers/InterviewController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Resume;
use App\Models\Interview;
use Illuminate\Http\Request;
use App\Notifications\InterviewScheduled;
use Illuminate\Support\Facades\Auth;

class InterviewController extends Controller
{
    public function schedule(Request $request, Resume $resume)
    {
        // Only job offer creator can schedule interview
        if ($resume->jobOffer->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only if application is accepted
        if ($resume->status !== 'accepted') {
            return response()->json(['message' => 'Application must be accepted first'], 422);
        }

        $request->validate([
            'scheduled_time' => 'required|date|after:now',
            'location' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        // Delete any existing interview
        $resume->interview()->delete();

        $interview = Interview::create([
            'resume_id' => $resume->id,
            'scheduled_by' => Auth::id(),
            'scheduled_time' => $request->scheduled_time,
            'location' => $request->location,
            'notes' => $request->notes
        ]);

        // Notify the applicant
        $resume->user->notify(new InterviewScheduled($interview, 'scheduled'));

        return response()->json($interview, 201);
    }

    public function getForResume(Resume $resume)
    {
        return response()->json($resume->interview);
    }

    public function update(Request $request, Interview $interview)
    {
        // Only scheduler can update
        if ($interview->scheduled_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'scheduled_time' => 'sometimes|date|after:now',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:scheduled,completed,canceled'
        ]);

        // Store original values before update to check if important fields changed
        $originalTime = $interview->scheduled_time;
        $originalLocation = $interview->location;

        $interview->update($request->all());

        // Check if important fields were updated
        $timeChanged = $request->has('scheduled_time') && $originalTime != $request->scheduled_time;
        $locationChanged = $request->has('location') && $originalLocation != $request->location;

        // Send notification if any important field was updated
        if ($timeChanged || $locationChanged) {
            $interview->resume->user->notify(new InterviewScheduled($interview, 'updated', [
                'time_changed' => $timeChanged,
                'location_changed' => $locationChanged,
                'original_time' => $originalTime,
                'original_location' => $originalLocation,
            ]));
        }

        return response()->json($interview);
    }

    // app/Http/Controllers/InterviewController.php
    public function getByApplicant(User $user, Request $request)
    {
        if (Auth::id() !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = $request->get('per_page', 9);
        $page = $request->get('page', 1);
        $search = $request->get('search', '');
        $jobType = $request->get('job_type', '');
        $industry = $request->get('industry', '');
        $dateFilter = $request->get('date_filter', '');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Interview::with([
                'resume.jobOffer.company', 
                'resume.jobOffer.user',
                'scheduler'
            ])
            ->whereHas('resume', function($query) use ($user) {
                $query->where('user_id', $user->id);
            });

        // Search filter
        if ($search) {
            $query->whereHas('resume.jobOffer', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Job type filter
        if ($jobType) {
            $query->whereHas('resume.jobOffer', function ($q) use ($jobType) {
                $q->where('type', $jobType);
            });
        }

        // Industry filter
        if ($industry) {
            $query->whereHas('resume.jobOffer.company', function ($q) use ($industry) {
                $q->where('industry', $industry);
            });
        }

        // Date filters - using interview scheduled_time
        if ($dateFilter) {
            switch ($dateFilter) {
                case 'today':
                    $query->whereDate('scheduled_time', today());
                    break;
                case 'week':
                    $query->whereBetween('scheduled_time', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereBetween('scheduled_time', [
                        now()->startOfMonth(),
                        now()->endOfMonth()
                    ]);
                    break;
                case 'custom':
                    if ($startDate && $endDate) {
                        $query->whereBetween('scheduled_time', [
                            $startDate,
                            $endDate
                        ]);
                    }
                    break;
            }
        }

        $query->orderBy('scheduled_time', 'desc');
        $interviews = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the data
        $transformedData = $interviews->getCollection()->map(function ($interview) {
            return [
                'id' => $interview->id,
                'scheduled_time' => $interview->scheduled_time,
                'location' => $interview->location,
                'notes' => $interview->notes,
                'status' => $interview->status,
                'created_at' => $interview->created_at,
                'job_offer' => [
                    'id' => $interview->resume->jobOffer->id,
                    'title' => $interview->resume->jobOffer->title,
                    'description' => $interview->resume->jobOffer->description,
                    'salary' => $interview->resume->jobOffer->salary,
                    'type' => $interview->resume->jobOffer->type,
                    'company' => [
                        'id' => $interview->resume->jobOffer->company->id,
                        'name' => $interview->resume->jobOffer->company->name,
                        'industry' => $interview->resume->jobOffer->company->industry,
                        'logo_url' => $interview->resume->jobOffer->company->logo_url
                    ],
                    'recruiter' => [
                        'name' => $interview->resume->jobOffer->user->name,
                        'email' => $interview->resume->jobOffer->user->email
                    ]
                ],
                'scheduler' => $interview->scheduler
            ];
        });

        return response()->json([
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $interviews->currentPage(),
                'per_page' => $interviews->perPage(),
                'total' => $interviews->total(),
                'last_page' => $interviews->lastPage(),
                'from' => $interviews->firstItem(),
                'to' => $interviews->lastItem(),
            ]
        ]);
    }
}
<?php
// app/Http/Controllers/InterviewController.php
namespace App\Http\Controllers;

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
        $resume->user->notify(new InterviewScheduled($interview));

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

        $interview->update($request->all());

        return response()->json($interview);
    }
}
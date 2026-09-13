<?php

namespace App\Http\Controllers;

use App\Models\ApplicationFormField;
use App\Models\FactionApplication;
use App\Services\DiscordService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FactionApplicationController extends Controller
{
    public function index()
    {
        $user         = auth()->user();
        $applications = FactionApplication::where('user_id', $user->id)->latest()->get();
        $current      = $applications->first();
        $formFields   = ApplicationFormField::ordered()->get();

        return $this->view('applications', compact('applications', 'current', 'formFields'));
    }

    public function store(Request $request)
    {
        $user       = auth()->user();
        $formFields = ApplicationFormField::ordered()->get();
        $current    = FactionApplication::where('user_id', $user->id)
            ->whereIn('status', ['PENDING', 'NEEDS_CHANGES'])
            ->latest()->first();

        if ($current && $current->status === 'NEEDS_CHANGES') {
            $editableIds = $current->fields_needing_changes ?? [];
            $newAnswers  = $this->validateAnswers($request, $formFields->whereIn('id', $editableIds));
            $current->update([
                'answers'                => array_merge($current->answers ?? [], $newAnswers),
                'status'                 => 'PENDING',
                'fields_needing_changes' => null,
                'review_note'            => null,
            ]);
            return redirect()->route('applications.index')->with('success', 'Jelentkezésed frissítve, újra elbírálás alatt.');
        }

        if ($current) {
            return back()->withErrors(['form' => 'Már van elbírálás alatt álló jelentkezésed.']);
        }

        $answers     = $this->validateAnswers($request, $formFields);
        $application = FactionApplication::create(['user_id' => $user->id, 'answers' => $answers, 'status' => 'PENDING']);

        $this->notifyAdminsOfApplication($user, $application, $formFields);

        return redirect()->route('applications.index')->with('success', 'Jelentkezésed elküldve! Amint elbírálják, itt és Discordon is értesítést kapsz.');
    }

    public function proposeSlots(Request $request)
    {
        $user        = auth()->user();
        $application = FactionApplication::where('user_id', $user->id)->where('status', 'APPROVED')->latest()->firstOrFail();

        $data = $request->validate([
            'slots'              => 'required|array|min:1|max:5',
            'slots.*.date'       => 'required|date|after_or_equal:today',
            'slots.*.start_time' => 'required',
            'slots.*.end_time'   => 'required',
        ]);

        $application->update(['proposed_slots' => array_values($data['slots'])]);

        return redirect()->route('applications.index')->with('success', 'Elérhetőséged elmentve. Hamarosan felveszik veled a kapcsolatot egy időpont megerősítéséhez.');
    }

    private function validateAnswers(Request $request, $formFields): array
    {
        $rules = [];
        foreach ($formFields as $field) {
            $key  = "answers.{$field->id}";
            $rule = [$field->is_required ? 'required' : 'nullable'];
            array_push($rule, ...match ($field->type) {
                'textarea' => ['string', 'max:5000'],
                'checkbox' => ['boolean'],
                'select'   => [Rule::in($field->options ?? [])],
                default    => ['string', 'max:255'],
            });
            $rules[$key] = $rule;
        }

        $validated = $request->validate($rules);

        $answers = [];
        foreach ($formFields as $field) {
            $value = $validated['answers'][$field->id] ?? null;
            $answers[$field->id] = $field->type === 'checkbox' ? (bool) $value : $value;
        }

        return $answers;
    }

    private function notifyAdminsOfApplication($user, FactionApplication $application, $formFields): void
    {
        $channelId = config('services.discord.applications_channel_id');
        if (!$channelId) return;

        $fields = [
            ['name' => 'Karakter neve', 'value' => $user->in_game_name ?? $user->name, 'inline' => true],
            ['name' => 'Felhasználónév', 'value' => $user->username, 'inline' => true],
        ];
        foreach ($formFields as $field) {
            $value = $application->answers[$field->id] ?? null;
            if ($value === null || $value === '') continue;
            $fields[] = [
                'name'  => $field->label,
                'value' => \Illuminate\Support\Str::limit(is_bool($value) ? ($value ? 'Igen' : 'Nem') : (string) $value, 1000),
            ];
        }

        app(DiscordService::class)->sendChannelMessage($channelId, '', [
            'title'     => 'Új jelentkezés a frakcióba',
            'color'     => hexdec('dc3545'),
            'fields'    => $fields,
            'footer'    => ['text' => 'Bíráld el az admin felület Jelentkezések fülén'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

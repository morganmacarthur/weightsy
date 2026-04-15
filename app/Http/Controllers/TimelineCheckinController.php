<?php

namespace App\Http\Controllers;

use App\Models\Checkin;
use App\Services\CheckinMessageParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimelineCheckinController extends Controller
{
    public function __construct(
        private readonly CheckinMessageParser $parser,
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'input' => ['required', 'string', 'max:100'],
            'occurred_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'chart' => ['nullable', 'in:weight,body_fat,blood_pressure'],
        ]);

        $parsed = $this->parser->parse($validated['input']);

        if ($parsed === null) {
            return redirect()
                ->route('timeline.show', array_filter(['chart' => $request->input('chart')]))
                ->withInput()
                ->withErrors([
                    'input' => 'Use one of these formats: 123, 120/70, or 14.0%',
                ]);
        }

        $user = $request->user();

        Checkin::query()->create([
            'user_id' => $user->id,
            'contact_point_id' => $user->contactPoints()->value('id'),
            'metric_type' => $parsed->metricType,
            'value_decimal' => $parsed->valueDecimal,
            'systolic' => $parsed->systolic,
            'diastolic' => $parsed->diastolic,
            'occurred_on' => $validated['occurred_on'],
            'received_at' => now(),
            'source_type' => 'manual_entry',
            'raw_input' => trim($validated['input']),
            'notes' => $validated['notes'] ?: null,
        ]);

        return redirect()
            ->route('timeline.show', array_filter(['chart' => $validated['chart'] ?? null]))
            ->with('status', 'Check-in added.');
    }

    public function update(Request $request, Checkin $checkin): RedirectResponse
    {
        abort_unless($checkin->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'input' => ['required', 'string', 'max:100'],
            'occurred_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'chart' => ['nullable', 'in:weight,body_fat,blood_pressure'],
        ]);

        $parsed = $this->parser->parse($validated['input']);

        if ($parsed === null) {
            return redirect()
                ->route('timeline.show', array_filter([
                    'edit' => $checkin->id,
                    'chart' => $request->input('chart'),
                ]))
                ->withInput()
                ->withErrors([
                    'input' => 'Use one of these formats: 123, 120/70, or 14.0%',
                ]);
        }

        $checkin->update([
            'metric_type' => $parsed->metricType,
            'value_decimal' => $parsed->valueDecimal,
            'systolic' => $parsed->systolic,
            'diastolic' => $parsed->diastolic,
            'occurred_on' => $validated['occurred_on'],
            'source_type' => 'manual_edit',
            'raw_input' => trim($validated['input']),
            'notes' => $validated['notes'] ?: null,
        ]);

        return redirect()
            ->route('timeline.show', array_filter(['chart' => $validated['chart'] ?? null]))
            ->with('status', 'Check-in updated.');
    }
}

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Weightsy Timeline</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|source-serif-4:400,600" rel="stylesheet" />
        <style>
            :root {
                --ink: #10212f;
                --sky: #d9eef8;
                --sand: #f6f1df;
                --rose: #cf6679;
                --sea: #7bb7cf;
                --panel: rgba(255, 252, 247, 0.88);
                --line: rgba(16, 33, 47, 0.12);
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                font-family: "Space Grotesk", sans-serif;
                color: var(--ink);
                background:
                    radial-gradient(circle at top left, rgba(123, 183, 207, 0.8), transparent 38%),
                    linear-gradient(180deg, #f7fbfc 0%, #f7f4ea 100%);
            }
            .shell { max-width: 980px; margin: 0 auto; padding: 32px 20px 64px; }
            .card {
                border: 1px solid var(--line);
                border-radius: 28px;
                background: var(--panel);
                backdrop-filter: blur(12px);
                box-shadow: 0 24px 60px rgba(16, 33, 47, 0.08);
            }
            .hero { padding: 28px; margin-bottom: 20px; }
            h1 { margin: 0 0 10px; font-size: clamp(2rem, 5vw, 3.4rem); letter-spacing: -0.05em; }
            .lede { margin: 0; line-height: 1.7; max-width: 44rem; }
            .actions { display: grid; gap: 20px; margin-bottom: 20px; }
            .panel { padding: 24px; }
            .panel h2 { margin: 0 0 12px; font-size: 1.35rem; }
            .status {
                margin-bottom: 14px;
                padding: 12px 14px;
                border-radius: 16px;
                background: rgba(123, 183, 207, 0.18);
            }
            .errors {
                margin-bottom: 14px;
                padding: 12px 14px;
                border-radius: 16px;
                background: rgba(207, 102, 121, 0.14);
            }
            form { display: grid; gap: 14px; }
            .fields {
                display: grid;
                grid-template-columns: 1.2fr 0.8fr;
                gap: 14px;
            }
            label { display: grid; gap: 8px; font-size: 0.95rem; }
            input, textarea {
                width: 100%;
                padding: 12px 14px;
                border-radius: 16px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.92);
                font: inherit;
                color: var(--ink);
            }
            textarea { min-height: 96px; resize: vertical; }
            .buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                align-items: center;
            }
            button, .link-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 11px 16px;
                border-radius: 999px;
                border: none;
                background: var(--ink);
                color: #fff;
                font: inherit;
                text-decoration: none;
                cursor: pointer;
            }
            .link-button.secondary {
                background: rgba(16, 33, 47, 0.08);
                color: var(--ink);
            }
            .list { display: grid; gap: 14px; }
            .row {
                display: grid;
                grid-template-columns: 140px 160px 1fr auto;
                gap: 16px;
                align-items: start;
                padding: 18px 20px;
            }
            .row + .row { border-top: 1px solid var(--line); }
            .pill {
                display: inline-block;
                padding: 8px 12px;
                border-radius: 999px;
                background: rgba(16, 33, 47, 0.06);
                font-size: 0.86rem;
                text-transform: capitalize;
            }
            .muted { opacity: 0.72; }
            .empty { padding: 24px; line-height: 1.7; }
            .meta { display: grid; gap: 4px; }
            .timeline-graph-scroll {
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
                border-radius: 20px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.55);
                margin-top: 4px;
            }
            .timeline-graph-scroll svg { display: block; }
            .chart-metric-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 14px;
            }
            .chart-metric-tab {
                display: inline-flex;
                align-items: center;
                padding: 8px 14px;
                border-radius: 999px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.75);
                color: var(--ink);
                font: inherit;
                font-size: 0.9rem;
                text-decoration: none;
            }
            .chart-metric-tab[aria-current="page"] {
                background: var(--ink);
                color: #fff;
                border-color: var(--ink);
            }
            .graph-legend {
                display: flex;
                flex-wrap: wrap;
                gap: 14px 18px;
                font-size: 0.88rem;
                margin-top: 12px;
            }
            .graph-legend span {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .graph-legend i {
                width: 12px;
                height: 12px;
                border-radius: 999px;
                display: inline-block;
            }
            .graph-footnote {
                margin-top: 10px;
                font-size: 0.88rem;
                opacity: 0.78;
                line-height: 1.5;
            }
            .graph-x-labels {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
            }
            @media (max-width: 720px) {
                .fields, .row { grid-template-columns: 1fr; }
            }
        </style>
    </head>
    <body>
        <main class="shell">
            <section class="card hero">
                <h1>{{ $user->display_name ?? $user->email }}</h1>
                <p class="lede">Your recent Weightsy history is here. This is the first pass of the longer-term view, so it is intentionally simple for now while the email loop stays front and center.</p>
            </section>

            <section class="actions">
                @if (session('status'))
                    <div class="card panel status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="card panel errors">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <section class="card panel">
                    <h2>Add check-in</h2>
                    <form method="post" action="{{ route('timeline.checkins.store') }}">
                        @csrf
                        <input type="hidden" name="chart" value="{{ $chartMetric }}">
                        <div class="fields">
                            <label>
                                Entry
                                <input name="input" value="{{ old('input') }}" placeholder="123 or 120/70 or 14.0%" required>
                            </label>
                            <label>
                                Date
                                <input type="date" name="occurred_on" value="{{ old('occurred_on', now($user->timezone)->toDateString()) }}" required>
                            </label>
                        </div>
                        <label>
                            Notes
                            <textarea name="notes" placeholder="Optional context">{{ old('notes') }}</textarea>
                        </label>
                        <div class="buttons">
                            <button type="submit">Save check-in</button>
                        </div>
                    </form>
                </section>

                <section class="card panel">
                    <h2>History graph</h2>
                    <p class="muted" style="margin: 0 0 14px; line-height: 1.55;">Scroll sideways to move through time. Tap a point to edit that check-in.</p>
                    <div class="chart-metric-tabs" role="tablist" aria-label="Metric">
                        @foreach (['weight' => 'Weight', 'body_fat' => 'Body fat', 'blood_pressure' => 'Blood pressure'] as $key => $label)
                            <a
                                class="chart-metric-tab"
                                href="{{ route('timeline.show', array_filter(['chart' => $key, 'edit' => $editCheckin?->id])) }}"
                                @if ($chartMetric === $key) aria-current="page" @endif
                            >{{ $label }}</a>
                        @endforeach
                    </div>
                    <div class="timeline-graph-scroll" data-timeline-graph-scroll>
                        <svg
                            width="{{ $chartPayload['inner_width'] }}"
                            height="{{ $chartPayload['height'] }}"
                            viewBox="0 0 {{ $chartPayload['inner_width'] }} {{ $chartPayload['height'] }}"
                            role="img"
                            aria-label="Check-in trend for the selected metric"
                        >
                            <title>Check-in trend</title>
                            <rect x="0" y="0" width="{{ $chartPayload['inner_width'] }}" height="{{ $chartPayload['height'] }}" fill="rgba(247, 251, 252, 0.9)" />
                            @foreach ($chartPayload['y_ticks'] as $tick)
                                <line
                                    x1="{{ $chartPayload['padding']['l'] }}"
                                    y1="{{ $tick['y'] }}"
                                    x2="{{ $chartPayload['inner_width'] - $chartPayload['padding']['r'] }}"
                                    y2="{{ $tick['y'] }}"
                                    stroke="rgba(16, 33, 47, 0.08)"
                                    stroke-width="1"
                                />
                                <text
                                    x="{{ $chartPayload['padding']['l'] - 8 }}"
                                    y="{{ $tick['y'] + 4 }}"
                                    text-anchor="end"
                                    font-size="11"
                                    fill="rgba(16, 33, 47, 0.55)"
                                    font-family="Space Grotesk, sans-serif"
                                >{{ $tick['label'] }}</text>
                            @endforeach
                            @foreach ($chartPayload['lines'] as $line)
                                @if ($chartPayload['has_data'] && $line['points'] !== '')
                                    <polyline
                                        fill="none"
                                        stroke="{{ $line['color'] }}"
                                        stroke-width="2.5"
                                        stroke-linejoin="round"
                                        stroke-linecap="round"
                                        points="{{ $line['points'] }}"
                                    />
                                @endif
                            @endforeach
                            @foreach ($chartPayload['lines'] as $line)
                                @foreach ($line['dots'] as $dot)
                                    <a href="{{ route('timeline.show', array_filter(['chart' => $chartMetric, 'edit' => $dot['id']])) }}" style="cursor: pointer;">
                                        <circle
                                            cx="{{ $dot['cx'] }}"
                                            cy="{{ $dot['cy'] }}"
                                            r="7"
                                            fill="#fff"
                                            stroke="{{ $line['color'] }}"
                                            stroke-width="2.5"
                                        >
                                            <title>{{ $dot['title'] }}</title>
                                        </circle>
                                    </a>
                                @endforeach
                            @endforeach
                        </svg>
                    </div>
                    @if ($chartPayload['metric'] === 'blood_pressure')
                        <div class="graph-legend" aria-hidden="true">
                            @foreach ($chartPayload['lines'] as $line)
                                <span><i style="background: {{ $line['color'] }}"></i> {{ $line['label'] }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="graph-footnote graph-x-labels">
                        <span>{{ $chartPayload['x_end_labels']['start'] }}</span>
                        <span>{{ $chartPayload['x_end_labels']['end'] }}</span>
                    </div>
                    @unless ($chartPayload['has_data'])
                        <p class="empty" style="margin: 0; padding: 8px 0 0;">No {{ str_replace('_', ' ', $chartMetric) }} entries yet — add one above or switch metric.</p>
                    @endunless
                </section>

                @if ($editCheckin)
                    <section class="card panel">
                        <h2>Edit check-in</h2>
                        <form method="post" action="{{ route('timeline.checkins.update', $editCheckin) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="chart" value="{{ $chartMetric }}">
                            <div class="fields">
                                <label>
                                    Entry
                                    <input name="input" value="{{ old('input', $editCheckin->editableInput()) }}" placeholder="123 or 120/70 or 14.0%" required>
                                </label>
                                <label>
                                    Date
                                    <input type="date" name="occurred_on" value="{{ old('occurred_on', $editCheckin->occurred_on?->toDateString()) }}" required>
                                </label>
                            </div>
                            <label>
                                Notes
                                <textarea name="notes" placeholder="Optional context">{{ old('notes', $editCheckin->notes) }}</textarea>
                            </label>
                            <div class="buttons">
                                <button type="submit">Update check-in</button>
                                <a class="link-button secondary" href="{{ route('timeline.show', array_filter(['chart' => $chartMetric])) }}">Cancel</a>
                            </div>
                        </form>
                    </section>
                @endif
            </section>

            <section class="card">
                @if ($checkins->isEmpty())
                    <div class="empty">No check-ins yet.</div>
                @else
                    <div class="list">
                        @foreach ($checkins as $checkin)
                            <article class="row">
                                <div>{{ $checkin->occurred_on?->format('M j, Y') }}</div>
                                <div><span class="pill">{{ str_replace('_', ' ', $checkin->metric_type) }}</span></div>
                                <div>
                                    <div>
                                        {{ $checkin->displayValue() }}
                                    </div>
                                    <div class="meta">
                                        <div class="muted">{{ $checkin->received_at?->setTimezone($user->timezone)->format('g:i A T') }}</div>
                                        @if ($checkin->notes)
                                            <div class="muted">{{ $checkin->notes }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div><a class="link-button secondary" href="{{ route('timeline.show', array_filter(['edit' => $checkin->id, 'chart' => $chartMetric])) }}">Edit</a></div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-timeline-graph-scroll]').forEach(function (el) {
                    el.scrollLeft = el.scrollWidth - el.clientWidth;
                });
            });
        </script>
    </body>
</html>

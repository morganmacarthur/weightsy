<?php

namespace App\Http\Controllers;

use App\Models\Checkin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TimelineController extends Controller
{
    private const CHART_METRICS = ['weight', 'body_fat', 'blood_pressure'];

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $editCheckin = null;

        if ($request->filled('edit')) {
            $editCheckin = Checkin::query()
                ->where('user_id', $user->id)
                ->find($request->integer('edit'));
        }

        $checkins = $user->checkins()
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $chartMetric = $this->normalizeChartMetric($request->query('chart'));

        return view('timeline.show', [
            'user' => $user,
            'checkins' => $checkins,
            'editCheckin' => $editCheckin,
            'chartMetric' => $chartMetric,
            'chartPayload' => $this->buildChartPayload($user, $chartMetric),
        ]);
    }

    private function normalizeChartMetric(?string $metric): string
    {
        if ($metric !== null && in_array($metric, self::CHART_METRICS, true)) {
            return $metric;
        }

        return 'weight';
    }

    /**
     * @return array{
     *   metric: string,
     *   has_data: bool,
     *   inner_width: int,
     *   height: int,
     *   padding: array{t: int, r: int, b: int, l: int},
     *   lines: list<array{key: string, label: string, color: string, points: string, dots: list<array{id: int, cx: float, cy: float, title: string}>}>,
     *   y_ticks: list<array{label: string, y: float}>,
     *   x_end_labels: array{start: string, end: string},
     * }
     */
    private function buildChartPayload(User $user, string $metric): array
    {
        $tz = $user->timezone ?: config('app.timezone');
        $today = Carbon::now($tz)->startOfDay();

        $rows = $user->checkins()
            ->where('metric_type', $metric)
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->limit(3000)
            ->get(['id', 'occurred_on', 'metric_type', 'value_decimal', 'systolic', 'diastolic']);

        $byDay = $this->collapseCheckinsToLatestPerDay($rows);

        $padding = ['t' => 20, 'r' => 24, 'b' => 40, 'l' => 52];
        $plotHeight = 200;
        $height = $padding['t'] + $plotHeight + $padding['b'];

        $daySpan = 42;
        $start = $today->copy()->subDays($daySpan - 1);
        $end = $today->copy();

        if ($byDay->isNotEmpty()) {
            $first = Carbon::parse($byDay->keys()->first())->startOfDay();
            $last = Carbon::parse($byDay->keys()->last())->startOfDay();
            $start = $first->lt($start) ? $first : $start;
            $end = $last->gt($end) ? $last : $end;
        }

        $totalDays = max(1, (int) $start->diffInDays($end) + 1);
        $pxPerDay = 10;
        $innerWidth = max(640, $totalDays * $pxPerDay);
        $plotWidth = $innerWidth - $padding['l'] - $padding['r'];

        $startTs = $start->timestamp;
        $endTs = $end->copy()->endOfDay()->timestamp;
        $timeSpan = max(1, $endTs - $startTs);

        $yRange = $this->yRangeForMetric($metric, $byDay);
        $yMin = $yRange['min'];
        $yMax = $yRange['max'];

        $scaleY = function (float $v) use ($plotHeight, $padding, $yMin, $yMax): float {
            $span = max(1e-9, $yMax - $yMin);

            return $padding['t'] + $plotHeight - (($v - $yMin) / $span) * $plotHeight;
        };

        $scaleX = function (string $dateStr) use ($padding, $plotWidth, $startTs, $timeSpan): float {
            $ts = Carbon::parse($dateStr)->startOfDay()->timestamp;

            return $padding['l'] + (($ts - $startTs) / $timeSpan) * $plotWidth;
        };

        $lines = [];
        $hasData = $byDay->isNotEmpty();

        if ($metric === 'blood_pressure') {
            $sysPts = [];
            $diaPts = [];
            $sysDots = [];
            $diaDots = [];
            foreach ($byDay as $dateStr => $checkin) {
                $cx = $scaleX($dateStr);
                $sys = (float) $checkin->systolic;
                $dia = (float) $checkin->diastolic;
                $cyS = $scaleY($sys);
                $cyD = $scaleY($dia);
                $sysPts[] = $cx.','.$cyS;
                $diaPts[] = $cx.','.$cyD;
                $titleS = $dateStr.' · '.$sys.'/'.$dia.' mmHg';
                $sysDots[] = [
                    'id' => $checkin->id,
                    'cx' => $cx,
                    'cy' => $cyS,
                    'title' => $titleS,
                ];
                $diaDots[] = [
                    'id' => $checkin->id,
                    'cx' => $cx,
                    'cy' => $cyD,
                    'title' => $titleS,
                ];
            }
            $lines[] = [
                'key' => 'systolic',
                'label' => 'Systolic',
                'color' => '#7bb7cf',
                'points' => implode(' ', $sysPts),
                'dots' => $sysDots,
            ];
            $lines[] = [
                'key' => 'diastolic',
                'label' => 'Diastolic',
                'color' => '#cf6679',
                'points' => implode(' ', $diaPts),
                'dots' => $diaDots,
            ];
        } else {
            $pts = [];
            $dots = [];
            foreach ($byDay as $dateStr => $checkin) {
                $v = (float) $checkin->value_decimal;
                $cx = $scaleX($dateStr);
                $cy = $scaleY($v);
                $pts[] = $cx.','.$cy;
                $dots[] = [
                    'id' => $checkin->id,
                    'cx' => $cx,
                    'cy' => $cy,
                    'title' => $dateStr.' · '.$checkin->displayValue(),
                ];
            }
            $color = $metric === 'body_fat' ? '#cf6679' : '#10212f';
            $lines[] = [
                'key' => 'value',
                'label' => $metric === 'body_fat' ? 'Body fat' : 'Weight',
                'color' => $color,
                'points' => implode(' ', $pts),
                'dots' => $dots,
            ];
        }

        $yTicks = [];
        $tickCount = 4;
        for ($i = 0; $i <= $tickCount; $i++) {
            $v = $yMin + ($i / $tickCount) * ($yMax - $yMin);
            $yTicks[] = [
                'label' => $metric === 'blood_pressure'
                    ? (string) (int) round($v)
                    : ($metric === 'body_fat'
                        ? rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.').'%'
                        : rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.')),
                'y' => $scaleY($v),
            ];
        }

        return [
            'metric' => $metric,
            'has_data' => $hasData,
            'inner_width' => $innerWidth,
            'height' => $height,
            'padding' => $padding,
            'lines' => $lines,
            'y_ticks' => $yTicks,
            'x_end_labels' => [
                'start' => $start->format('M j, Y'),
                'end' => $end->format('M j, Y'),
            ],
        ];
    }

    /**
     * @param  Collection<int, Checkin>  $rows
     * @return Collection<string, Checkin>
     */
    private function collapseCheckinsToLatestPerDay(Collection $rows): Collection
    {
        $byDay = collect();
        foreach ($rows as $checkin) {
            $key = $checkin->occurred_on->toDateString();
            $byDay[$key] = $checkin;
        }

        return $byDay;
    }

    /**
     * @param  Collection<string, Checkin>  $byDay
     * @return array{min: float, max: float}
     */
    private function yRangeForMetric(string $metric, Collection $byDay): array
    {
        if ($byDay->isEmpty()) {
            return match ($metric) {
                'blood_pressure' => ['min' => 40.0, 'max' => 180.0],
                'body_fat' => ['min' => 5.0, 'max' => 50.0],
                default => ['min' => 140.0, 'max' => 220.0],
            };
        }

        if ($metric === 'blood_pressure') {
            $min = $byDay->min(fn (Checkin $c) => min((float) $c->systolic, (float) $c->diastolic));
            $max = $byDay->max(fn (Checkin $c) => max((float) $c->systolic, (float) $c->diastolic));
        } else {
            $min = (float) $byDay->min(fn (Checkin $c) => (float) $c->value_decimal);
            $max = (float) $byDay->max(fn (Checkin $c) => (float) $c->value_decimal);
        }

        $pad = ($max - $min) * 0.12;
        if ($pad < 1e-6) {
            $pad = $metric === 'blood_pressure' ? 5.0 : 2.0;
        }

        return [
            'min' => $min - $pad,
            'max' => $max + $pad,
        ];
    }
}

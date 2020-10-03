<?php

namespace Tracking\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Tracking\Models\Analytics;
use function parse_user_agent;

class AnalyticsService
{
    public $model;
    
    public function __construct(Analytics $model)
    {
        $this->model = $model;
    }


    public function log(\Illuminate\Http\Request $request): void
    {
        $requestData = json_encode(
            [
            'referer' => $request->server('HTTP_REFERER', null),
            'user_agent' => $request->server('HTTP_USER_AGENT', null),
            'host' => $request->server('HTTP_HOST', null),
            'remote_addr' => $request->server('REMOTE_ADDR', null),
            'uri' => $request->server('REQUEST_URI', null),
            'method' => $request->server('REQUEST_METHOD', null),
            'query' => $request->server('QUERY_STRING', null),
            'time' => $request->server('REQUEST_TIME', null),
            ]
        );

        if (Schema::hasTable('analytics')) {
            $data = [
                'data' => $requestData,
            ];
            if (Schema::hasColumn($this->model->getTable(), 'business_code')) { // || Business::isToIgnore())
                $data['business_code'] = \Business::getCode();
            }
            $this->model->create(
                $data
            );
        }
    }

    public function topReferers($count)
    {
        $analytics = $this->model->where('created_at', '>', Carbon::now()->subDays($count))->get();
        $data = $analytics->pluck('data')->all();

        return $this->convertDataToItems($data, 'referer', ['unknown' => 0]);
    }

    public function topPages($count)
    {
        $analytics = $this->model->where('created_at', '>', Carbon::now()->subDays($count))->get();
        $data = $analytics->pluck('data')->all();

        return $this->convertDataToItems($data, 'uri');
    }

    /**
     * @return array
     *
     * @psalm-return array<string, mixed>
     */
    public function topBrowsers($count): array
    {
        $analytics = $this->model->where('created_at', '>', Carbon::now()->subDays($count))->get();
        $data = $analytics->pluck('data')->all();

        $browsers = [];

        foreach ($this->convertDataToItems($data, 'user_agent') as $userAgent => $count) {
            $browser = parse_user_agent($userAgent);
            $browsers[$browser['browser'].' ('.$browser['version'].') on '.$browser['platform']] = $count;
        }

        return $browsers;
    }

    /**
     * @param int[] $conversions
     */
    public function convertDataToItems($data, string $key, array $conversions = [])
    {
        if (!isset($conversions['unknown'])) {
            $conversions['unknown'] = 0;
        }

        if (!isset($conversions['unknown'])) {
            $conversions['unknown'] = 0;
        }

        foreach ($data as $item) {
            $visit = json_decode($item);
            if (!empty($visit->$key) && $visit->$key > '') {
                $conversions[$visit->$key] = 0;
            }
        }

        foreach ($data as $item) {
            $visit = json_decode($item);
            if (!empty($visit->$key) && $visit->$key > '') {
                $conversions[$visit->$key] += 1;
            } else {
                $conversions['unknown'] += 1;
            }
        }

        return $conversions;
    }

    /**
     * @return array[]
     *
     * @psalm-return array{dates: array, visits: array}
     */
    public function getDays($count): array
    {
        $analytics = $this->model->where('created_at', '>', Carbon::now()->subDays($count));

        $visits = null;
        if ($analytics->first()) {
            $endDate = Carbon::now();
            $startDate = Carbon::parse($analytics->first()->created_at->format('Y-m-d'));

            $dateRange = $this->getDateRange($startDate, $endDate);

            foreach ($dateRange as $date) {
                $visits[$date] = $this->model->where('created_at', '>', $date.' 00:00:00')->where('created_at', '<', $date.' 23:59:59')->count();
            }

            $visitCollection = collect($visits);
        } else {
            $visitCollection = collect(
                [
                Carbon::now()->format('Y-m-d') => 0,
                ]
            );
        }

        return [
            'dates' => $visitCollection->keys()->toArray(),
            'visits' => $visitCollection->values()->toArray(),
        ];
    }

    /**
     * @return array
     *
     * @psalm-return list<mixed>
     */
    protected function getDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $dates = [];

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }
}

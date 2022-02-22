<?php

namespace Aloware\HorizonWildcardConsumer\Middlewares;

use Closure;
use Illuminate\Support\Str;

class WorkloadResponseMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (Str::contains($request->path(), 'workload')) {

            $env = config('app.env');

            $wildcards = collect(config('horizon.environments.' . $env))
                ->pluck('queue')
                ->collapse()
                ->filter(function ($item) {
                    return Str::contains($item, '*');
                });

            $groups = collect($response->getOriginalContent())
                ->map(function ($item) use ($wildcards) {
                    $queues = explode(',', $item['name']);
                    foreach ($wildcards as $wildcard) {
                        foreach ($queues as $queue) {
                            if ($this->wildcardMatches($wildcard, $queue)) {
                                $item['orig_name'] = $item['name'];
                                $item['name'] = $wildcard;
                            }
                        }
                    }
                    return $item;
                })
                ->groupBy('name')
                ->map(function ($group) {
                    $origNames = $group->pluck('orig_name');
                    return [
                        'name' => data_get($group->first(), 'name'),
                        'orig_name' => $origNames ? $origNames->join(',') : '',
                        'processes' => data_get($group->first(), 'processes'),
                        'wait' => $group->sum('wait'),
                        'length' => $group->sum('length')
                    ];
                });

            $response->setContent(
                $groups->values()->toJson()
            );
        }

        return $response;
    }

    private function wildcardMatches($pattern, $haystack): bool
    {
        $regex = str_replace(
            ["\*"], // wildcard chars
            ['.*', '.'],  // regexp chars
            preg_quote($pattern)
        );

        preg_match('/^' . $regex . '$/is', $haystack, $matches);

        return count($matches) > 0;
    }
}
<?php

namespace Aloware\HorizonWildcardConsumer\Middlewares;

use Closure;
use Illuminate\Support\Str;

class WorkloadResponseMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($request->path() == 'horizon/api/workload') {

            $env = config('app.env');

            $wildcards = collect(config('horizon.environments.' . $env))
                ->pluck('queue')
                ->flatten()
                ->filter(function ($item) {
                    return Str::contains($item, '*');
                })
                ->values()
                ->all();

            $groups = collect($response->getOriginalContent())
                ->map(function ($item) use ($wildcards) {

                    $queues = $this->normalizeQueueName($item['name']);

                    foreach ($wildcards as $wildcard) {

                        foreach ($queues as $queue) {

                            if ($this->wildcardMatches($wildcard, $queue)) {
                                $item['name'] = $wildcard;
                            }
                        }
                    }
                    return $item;
                })
                ->groupBy('name')
                ->map(function ($group) {
                    return [
                        'name' => data_get($group->first(), 'name'),
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
        return fnmatch($pattern, $haystack);
    }

    private function normalizeQueueName($queue): array
    {
        return is_array($queue) ? $queue : explode(',', $queue);
    }
}

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

            $wildcards = $this->getWildcards();

            $groups = collect($response->getOriginalContent())
                ->map(function ($item) use ($wildcards) {

                    $queues = $this->normalizeQueueName($item['name']);

                    foreach ($wildcards as $wildcard) {
                        foreach ($queues as $queue) {
                            if (fnmatch($wildcard, $queue)) {
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

    private function getWildcards(): array
    {
         $env = config('app.env');

         return collect(config('horizon.environments.' . $env))
             ->pluck('queue')
             ->map(function ($queue) {
                 return $this->normalizeQueueName($queue);
             })
             ->flatten()
             ->filter(function ($item) {
                 return Str::contains($item, '*');
             })
             ->all();
    }

    private function normalizeQueueName($queue): array
    {
        return is_array($queue) ? $queue : explode(',', $queue);
    }
}

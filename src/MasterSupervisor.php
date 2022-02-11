<?php

namespace Aloware\HorizonWildcardConsumer;

use Laravel\Horizon\MasterSupervisor as BaseMasterSupervisor;

class MasterSupervisor extends BaseMasterSupervisor
{
    /**
     * Monitor the worker processes.
     *
     * @return void
     */
    public function monitor()
    {
        print('started monitoring' . PHP_EOL);
        [$provisioning, $env] = func_get_args();

        $this->ensureNoOtherMasterSupervisors();

        $this->listenForSignals();

        $this->persist();

        while (true) {
            sleep(1);

            if ($provisioning->shouldRun()) {
                $updatedSupervisors = $provisioning->updatedSupervisors($env);

                dump('updated supervisors', $updatedSupervisors);
                
                if (count($updatedSupervisors) > 0) {
                    $supervisors = $this
                        ->supervisors
                        ->filter(
                            function ($supervisor) use ($updatedSupervisors) {
                                return in_array($supervisor->name, $updatedSupervisors);
                            }
                        );

                    if ($supervisors->count() > 0) {
                        dump('restarting supervisors', $supervisors);
                        $supervisors->each->terminate();
                        $provisioning->deploy($env);
                    }
                }
            }

            $this->loop();
        }
    }
}

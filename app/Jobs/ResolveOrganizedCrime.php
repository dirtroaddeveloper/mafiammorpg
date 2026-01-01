<?php

namespace App\Jobs;

use App\Domain\OC\OrganizedCrimeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResolveOrganizedCrime implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $ocId)
    {
    }

    public function handle(OrganizedCrimeService $organizedCrimeService): void
    {
        $organizedCrimeService->resolve($this->ocId);
    }
}

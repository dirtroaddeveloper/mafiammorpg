<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Underworld Savages');
})->purpose('Display an inspiring quote');

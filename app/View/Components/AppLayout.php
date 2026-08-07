<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Facades\Process;

class AppLayout extends Component
{
   
    public function render(): View
    {
        return view('layouts.app');
    }
}

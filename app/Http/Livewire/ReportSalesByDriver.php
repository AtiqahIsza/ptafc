<?php

namespace App\Http\Livewire;

use App\Models\BusDriver;
use Livewire\Component;

class ReportSalesByDriver extends Component
{
    public $drivers;

    public function render()
    {
        return view('livewire.report-sales-by-driver');
    }

    public function mount()
    {
        $this->drivers=BusDriver::all();
    }
}

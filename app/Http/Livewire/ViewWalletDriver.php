<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ViewWalletDriver extends Component
{
    public $records;
    public $drivers;

    public function render()
    {
        //dd($this->records);
        return view('livewire.view-wallet-driver');
    }
}

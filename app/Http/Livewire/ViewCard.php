<?php

namespace App\Http\Livewire;

use App\Models\RegionCode;
use App\Models\Route;
use App\Models\Stage;
use App\Models\TicketCard;
use Livewire\Component;

class ViewCard extends Component
{
    public $regions;
    public $cardType;
    public $cardStatus;
    public $cards;
    public $selectedRegion = NULL;
    public $selectedCardType = NULL;
    public $selectedCardStatus = NULL;

    public function mount()
    {
        $this->regions= collect();
        $this->cardType = collect();
        $this->cardStatus = collect();
    }

    public function render()
    {
        $this->regions = RegionCode::all();
        //$this->routes = Route::all();
        return view('livewire.view-card');
    }

    public function updatedSelectedRegion($region)
    {
        if (!is_null($region)) {
            $this->cardType = TicketCard::where('region_id', $region)->get();
            $this->selectedRegion = $region;
        }
    }

    public function updatedSelectedCardType($type)
    {
        if (!is_null($type)) {
            $this->selectedCardType = $type;
            $this->cardStatus = TicketCard::where([
                ['region_id', $this->selectedRegion],
                ['card_type', $type]
            ])->get();
        }
    }

    public function updatedSelectedCardStatus($status)
    {
        if (!is_null($status)) {
            $this->cards = TicketCard::where([
                ['card_type', $this->selectedCardType],
                ['card_status', $status],
                ['region_id', $this->selectedRegion],
            ])->get();
        }
    }
}

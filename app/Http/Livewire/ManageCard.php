<?php

namespace App\Http\Livewire;

use App\Models\CardBlacklistHistory;
use App\Models\RefundVoucher;
use App\Models\RegionCode;
use App\Models\TicketCard;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ManageCard extends Component
{
    public $selectedID;
    public $selectedCardStatus;
    public $ticketCards;
    public $cards;
    public $regions;
    public $passengerDetails;

    public $resetCardId;
    public $state = [];
    public $showEditModal = false;

    public function render()
    {
        return view('livewire.manage-card');
    }

    public function edit(TicketCard $card)
    {
        //dd($user);
        $this->reset();
        $this->ticketCards = $card;
        $this->state = $card->toArray();
        $this->dispatchBrowserEvent('show-edit-form');
    }

    public function updateCard()
    {
        $validatedData = Validator::make($this->state,[
            'title' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'address1'=> ['required', 'string', 'max:255'],
            'address2'=> ['required', 'string', 'max:255'],
            'home_phone'=> ['required', 'min:11', 'max:11', 'regex:/^0[0-689]\d{9}$/'],
            'cell_phone'=> ['required', 'min:10', 'max:11', 'regex:/^(\+?6?01)[0|1|2|3|4|5|6|7|8|9][0-9]{7,8}$/'],
            'email'=> ['required', 'max:255', 'email'],
            'gender'=> ['required', 'int'],
            'race'=> ['required', 'int'],
            'nationality'=> ['required', 'string', 'max:255'],
            'marital_status'=> ['required', 'int'],
            'work'=> ['required', 'int'],
            'single_mother'=> ['required', 'int'],
            'orphan'=> ['required', 'int'],
            'disabled'=> ['required', 'int'],
            'elderly'=> ['required', 'int'],
            'student'=> ['required', 'int'],
            'bkk'=> ['required', 'int'],
        ])->validate();

        $this->passengerDetails->update($validatedData);

        return redirect()->to('/cards/manage')->with(['message' => 'Passenger card details updated successfully!']);

        //return Redirect::back()->with(['message' => 'Sector updated successfully!']);
        //$this->emit('hide-form');
        //session()->flash('message', 'Sector successfully updated!');
        //$this->dispatchBrowserEvent('hide-form', ['message' => 'Sector updated successfully!']);
    }

    public function updatedSelectedCardStatus($status)
    {
        if (!is_null($status)) {
            $this->cards = TicketCard::where(function($query) {
                $query->where('id_number', $this->selectedID)
                    ->orWhere('manufacturing_id', $this->selectedID);
            })->where('card_status', $status)->get();
        }
    }

    public function generateVoucher(TicketCard $ticketCard)
    {
        //dd($user);
        $this->reset();
        $this->ticketCards = $ticketCard;
        $this->state = $ticketCard->toArray();
        $this->dispatchBrowserEvent('show-form');
    }

    public function updateGenerateVoucher()
    {
        //Once generated, the status back to active?

        $validatedData = Validator::make($this->state,[
            'current_balance'=> ['required', 'int'],
        ])->validate();

        /*$generateVoucher = RefundVoucher::create([
            'amount' => $validatedData['current_balance'],
            'date_claimed' => now(),
            'date_created' => now(),
            'blacklisted_date' => now()
        ]);*/

        $this->ticketCards->update($validatedData);

        return redirect()->to('/cards/manage')->with(['message' => 'Refund voucher generated successfully!']);
    }

    public function changeRegion(TicketCard $ticketCard)
    {
        //dd($user);
        $this->reset();
        $this->ticketCards = $ticketCard;
        $this->state = $ticketCard->toArray();
        $this->regions = RegionCode::all();
        $this->dispatchBrowserEvent('show-change-region-form');
    }

    public function updateRegion()
    {
        $validatedData = Validator::make($this->state,[
            'region_id'=> ['required', 'int'],
        ])->validate();

        $this->ticketCards->update($validatedData);

        return redirect()->to('/cards/manage')->with(['message' => 'Region changed successfully!']);
    }

    public function blacklist(TicketCard $ticketCard)
    {
        //dd($user);
        $this->reset();
        $this->ticketCards = $ticketCard;
        $this->state = $ticketCard->toArray();
        $this->dispatchBrowserEvent('show-blacklist-form');
    }

    public function blacklistCard()
    {
        $validatedData = Validator::make($this->state,[
            'reason'=> ['required', 'string', 'max:255'],
            'id'=> ['int'],
        ])->validate();

        $card = TicketCard::where('id', $validatedData['id'])->update(['card_status' => 3]);

        if($card){
            $blacklistCard = CardBlacklistHistory::create([
                'reason' => $validatedData['reason'],
                'card_id' => $validatedData['id'],
                'blacklisted_date' => now()
            ]);
            if($blacklistCard) {
                return redirect()->to('/cards/manage')->with(['message' => 'Card blacklisted successfully!']);
            }
            else{
                return redirect()->to('/cards/manage')->with(['message' => 'Card blacklisted failed!']);
            }
        }
        else{
            return redirect()->to('/cards/manage')->with(['message' => 'Card status updated failed!']);
        }
    }

    public function resetCard($id)
    {
        $this->resetCardId = $id;
        $this->ticketCards = TicketCard::findOrFail($id);
        $this->dispatchBrowserEvent('show-reset-modal');
    }

    public function updateResetCard()
    {
        $card= TicketCard::findOrFail($this->resetCardId);
        $card->update(['current_balance' => 0]);

        return redirect()->to('/cards/manage')->with(['message' => 'Card reset successfully!']);
    }


}

<?php

namespace App\Http\Livewire;

use App\Models\BusDriver;
use App\Models\TopupPromo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;

class Wallet extends Component
{
    public $state = [];

    public function render()
    {
        return view('livewire.wallet');
    }

    public function editTopup()
    {
        $topupPromo = DB::table('topup_promo')->latest()->first();
        //dd($topupPromo);
        if($topupPromo){
            //$promo = $topupPromo->promo_value;
            $this->state = [ "promo_value" => $topupPromo->promo_value ];
        }
    }

    public function updatePromo()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  updatePromo()");
        $user = auth()->user();

        $validatedData = Validator::make($this->state,[
            'promo_value' => ['required', 'int'],
        ])->validate();

        $validatedData['created_at'] = Carbon::now();
        $validatedData['created_by'] = $user->id;

        TopupPromo::create($validatedData);

        return redirect()->to('/wallet/view')->with(['message' => 'Top-up Promo Updated Successfully!']);
    }
}

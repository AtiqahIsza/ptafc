<?php

namespace App\Http\Livewire;

use App\Models\BusDriver;
use App\Models\DriverWalletRecord;
use App\Models\RouteSchedulerMSTR;
use App\Models\TopupPromo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Symfony\Component\Console\Output\ConsoleOutput;
use Barryvdh\DomPDF\Facade\Pdf;

class WalletTopup extends Component
{
    public $busDrivers;
    public $state = [];

    public function render()
    {
        return view('livewire.wallet-topup');
    }

    public function mount()
    {
        $this->busDrivers = BusDriver::all();
    }

    public function printReceipt()
    {
        $out = new ConsoleOutput();
        $out->writeln("YOU ARE IN  printReceipt");
        $user = auth()->user();

        $validatedData = Validator::make($this->state, [
            'driver_id'=> ['required', 'int'],
            'value'=> ['required', 'between:0,99.99']
        ])->validate();

        $promo = TopupPromo::latest()->first();

        $topupIncludePromo = $validatedData['value'] +  ($validatedData['value'] * $promo->promo_value/100);

        $validatedData['created_at'] = Carbon::now();
        $validatedData['created_by'] = $user->id;
        $validatedData['topup_promo_id'] = $promo->id;
        $validatedData['value_after_promo'] = $topupIncludePromo;

        $out->writeln('driver_id: ' . $validatedData['driver_id']);
        $out->writeln('value: ' . $validatedData['value']);
        $out->writeln('created_at: ' . $validatedData['created_at']);
        $out->writeln('created_by: ' . $validatedData['created_by']);
        $out->writeln('topup_promo_id: ' . $validatedData['topup_promo_id']);
        $out->writeln('value_after_promo: ' . $validatedData['value_after_promo']);

        DriverWalletRecord::create($validatedData);

        $selectedDriver = BusDriver::where('id', $validatedData['driver_id'])->first();

        if($selectedDriver){

            $newBalance = $selectedDriver->wallet_balance + $topupIncludePromo;
            //dd($newBalance);
            $updateBalance = BusDriver::whereId($validatedData['driver_id'])->update(['wallet_balance' => $newBalance]);

            if($updateBalance) {
                $data = [
                    'driver_id' => $selectedDriver->id,
                    'driver_name' => $selectedDriver->driver_name,
                    'balance' => $newBalance,
                    'date' => $validatedData['created_at'],
                    'value' => $validatedData['value'],
                    'created_by' => $user,
                ];

               // dd($data);

                $pdf = PDF::loadView('wallet/print', $data)->output();

                $out->writeln("YOU ARE IN after pdf view");
                //return $pdf->download('receipt.pdf');
                //$pdfContent = PDF::loadView('view', $viewData)->output();
                return response()->streamDownload(
                    function () use ($pdf) {
                        return print($pdf);
                    },
                    "receipt.pdf"
                );
            }
        }
        return redirect()->to('/wallet/topup')->with(['message' => 'Topup Wallet Failed!!']);
    }


}

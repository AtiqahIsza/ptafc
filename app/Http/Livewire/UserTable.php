<?php

namespace App\Http\Livewire;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridEloquent;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\ActionButton;
use PowerComponents\LivewirePowerGrid\Rules\Rule;

final class UserTable extends PowerGridComponent
{
    use ActionButton;

    //Messages informing success/error data is updated.
    public bool $showUpdateMessages = true;
    public $users;
    public $state = [];
    public $showEditModal = false;
    /*
    |--------------------------------------------------------------------------
    |  Features Setup
    |--------------------------------------------------------------------------
    | Setup Table's general features
    |
    */

    protected function getListeners(): array
    {
        return array_merge($this->listeners, [
            'editEvent' => 'editEvent'
        ]);
    }

    public function setUp(): void
    {
        $this->showCheckBox()
            ->showPerPage()
            ->showSearchInput()
            ->showExportOption('download', ['excel', 'csv']);
    }

    /*
    |--------------------------------------------------------------------------
    |  Datasource
    |--------------------------------------------------------------------------
    | Provides data to your Table using a Model or Collection
    |
    */
    public function datasource(): ?Builder
    {
        return User::query();
    }

    /*
    |--------------------------------------------------------------------------
    |  Relationship Search
    |--------------------------------------------------------------------------
    | Configure here relationships to be used by the Search and Table Filters.
    |
    */

    /**
     * Relationship search.
     *
     * @return array<string, array<int, string>>
     */
    public function relationSearch(): array
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    |  Add Column
    |--------------------------------------------------------------------------
    | Make Datasource fields available to be used as columns.
    | You can pass a closure to transform/modify the data.
    |
    */
    public function addColumns(): ?PowerGridEloquent
    {
        return PowerGrid::eloquent()
            ->addColumn('id')
            ->addColumn('full_name')
            ->addColumn('ic_number')
            ->addColumn('phone_number')
            ->addColumn('username')
            ->addColumn('company_id', function (User $model) {
                return ucwords($model->company->company_name);
            })
            ->addColumn('user_role', function (User $model) {
                if($model->user_role == 1){return "Administrator";}
                elseif($model->user_role == 1){return "Report User";}
                else{return "Super User";}
            })
            ->addColumn('email')
            ->addColumn('created_at_formatted', function(User $model) {
                return Carbon::parse($model->created_at)->format('d/m/Y H:i:s');
            });
    }

    /*
    |--------------------------------------------------------------------------
    |  Include Columns
    |--------------------------------------------------------------------------
    | Include the columns added columns, making them visible on the Table.
    | Each column can be configured with properties, filters, actions...
    |
    */

     /**
     * PowerGrid Columns.
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::add()
                ->title('ID')
                ->field('id'),

            Column::add()
                ->title('FULL NAME')
                ->field('full_name')
                ->sortable()
                ->searchable()
                ->makeInputText()
                ->editOnClick(),

            Column::add()
                ->title('IC NUMBER')
                ->field('ic_number')
                ->sortable()
                ->searchable()
                ->makeInputText()
                ->editOnClick(),

            Column::add()
                ->title('PHONE NUMBER')
                ->field('phone_number')
                ->sortable()
                ->searchable()
                ->makeInputText()
                ->editOnClick(),

            Column::add()
                ->title('USERNAME')
                ->field('username')
                ->sortable()
                ->searchable()
                ->makeInputText()
                ->editOnClick(),

            Column::add()
                ->title('COMPANY NAME')
                ->field('company_id')
                ->sortable()
                ->searchable()
                ->makeInputText()
                ->editOnClick(),

            Column::add()
                ->title('USER ROLE')
                ->field('user_role')
                ->sortable()
                ->searchable()
                ->makeInputText()
                ->editOnClick(),

            Column::add()
                ->title('EMAIL')
                ->field('email')
                ->sortable()
                ->searchable()
                ->makeInputText()
                ->editOnClick(),

            Column::add()
                ->title('CREATED AT')
                ->field('created_at_formatted', 'created_at')
                ->searchable()
                ->sortable()
                ->makeInputDatePicker('created_at'),

        ]
;
    }

    /*
    |--------------------------------------------------------------------------
    | Actions Method
    |--------------------------------------------------------------------------
    | Enable the method below only if the Routes below are defined in your app.
    |
    */

     /**
     * PowerGrid User Action Buttons.
     *
     * @return array<int, \PowerComponents\LivewirePowerGrid\Button>
     */


    public function actions(): array
    {
       return [
           Button::add('edit')
               ->caption('Edit')
               ->class('btn btn-warning cursor-pointer px-3 py-2.5 m-1 rounded text-sm')
               ->openModal('edit-user', ['id' => 'id']),
               //->emit('edit-user',['id' => 'id']),
               //->route('editUser', ['user' => 'id']),

           Button::add('destroy')
               ->caption('Delete')
               ->class('btn btn-danger cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->emit('show-delete-form', ['user' => 'id'])
               //->route('user.destroy', ['user' => 'id'])
               //->method('delete')
        ];
    }

    public function editEvent (array $data){

        $user = User::find($data['id']);
        $this->showEditModal = true;
        $this->users = $user;
        $this->state = $user->toArray();
        $this->dispatchBrowserEvent('editEvent');

        /*
         * $user = User::find($params['id']);
         * $this->dispatchBrowserEvent('show-form', [
            'collection' => $user,
        ]);*/
        //dd($user);
    }

    /*
    |--------------------------------------------------------------------------
    | Actions Rules
    |--------------------------------------------------------------------------
    | Enable the method below to configure Rules for your Table and Action Buttons.
    |
    */

     /**
     * PowerGrid User Action Rules.
     *
     * @return array<int, \PowerComponents\LivewirePowerGrid\Rules\Rule>
     */


    public function actionRules(): array
    {
       return [

           //Hide button edit for ID 1
            Rule::button('edit')
                ->when(function ($user) {
                    return $user->id === 2;
                })
                ->hide()
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Edit Method
    |--------------------------------------------------------------------------
    | Enable the method below to use editOnClick() or toggleable() methods.
    | Data must be validated and treated (see "Update Data" in PowerGrid doc).
    |
    */

     /**
     * PowerGrid User Update.
     *
     * @param array<string,string> $data
     */


    public function update(array $data ): bool
    {
       try {
           $updated = User::query()->findOrFail($data['id'])
                ->update([
                    $data['field'] => $data['value'],
                ]);
       } catch (QueryException $exception) {
           $updated = false;
       }
       return $updated;
    }

    public function updateMessages(string $status = 'error', string $field = '_default_message'): string
    {
        $updateMessages = [
            'success'   => [
                '_default_message' => __('Data has been updated successfully!'),
                //'custom_field'   => __('Custom Field updated successfully!'),
            ],
            'error' => [
                '_default_message' => __('Error updating the data.'),
                //'custom_field'   => __('Error updating custom field.'),
            ]
        ];

        $message = ($updateMessages[$status][$field] ?? $updateMessages[$status]['_default_message']);

        return (is_string($message)) ? $message : 'Error!';
    }

}

<div class="main py-4">
    <div class="d-block mb-md-0" style="position: relative">
        <h2>Manage Route Scheduler</h2>
    </div>
    <br>
    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <div id="calendar" style="height: 800px;"></div>
    </div>

    <!-- Create Schedule Modal Content -->
    <div wire:ignore.self class="modal fade" id="createScheduleModal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Add Route Schedule Route</span>
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ 'addScheduleRoute'}}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="title">Title</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.title" class="form-control border-gray-300" id="title" type="text" placeholder="{{ __('Title of Schedule') }}" autofocus required>
                                @if ($errors->has('title'))
                                    <span class="text-danger">{{ $errors->first('title') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="sequence">Sequence</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.sequence" class="form-control border-gray-300" id="sequence" type="number" placeholder="{{ __('Sequence') }}" autofocus required>
                                @if ($errors->has('sequencee'))
                                    <span class="text-danger">{{ $errors->first('sequence') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="routeName">Route Name</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                        <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                    </svg>
                                </span>
                                <select wire:model.defer="state.route_id" id="routeName" class="form-control border-gray-300" autofocus required>
                                    <option value="">Choose Route</option>
                                    @foreach($routes as $route)
                                        <option value="{{$route->id}}">{{$route->route_name}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('routeName'))
                                    <span class="text-danger">{{ $errors->first('routeName') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="scheduleTime">Time</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-alarm-fill" viewBox="0 0 16 16">
                                        <path d="M6 .5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1H9v1.07a7.001 7.001 0 0 1 3.274 12.474l.601.602a.5.5 0 0 1-.707.708l-.746-.746A6.97 6.97 0 0 1 8 16a6.97 6.97 0 0 1-3.422-.892l-.746.746a.5.5 0 0 1-.707-.708l.602-.602A7.001 7.001 0 0 1 7 2.07V1h-.5A.5.5 0 0 1 6 .5zm2.5 5a.5.5 0 0 0-1 0v3.362l-1.429 2.38a.5.5 0 1 0 .858.515l1.5-2.5A.5.5 0 0 0 8.5 9V5.5zM.86 5.387A2.5 2.5 0 1 1 4.387 1.86 8.035 8.035 0 0 0 .86 5.387zM11.613 1.86a2.5 2.5 0 1 1 3.527 3.527 8.035 8.035 0 0 0-3.527-3.527z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.time" class="form-control border-gray-300" style="width: 80%;" id="scheduleTime" type="time" autofocus required>
                                @if ($errors->has('scheduleTime'))
                                    <span class="text-danger">{{ $errors->first('scheduleTime') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="inBus">Inbound Bus</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fas fa-bus fa-fw"></i>
                                </span>
                                <select  wire:model.defer="state.inbus_id" class="form-control border-gray-300" autofocus required>
                                    <option value="">Choose Inbound Bus</option>
                                    @foreach($buses as $bus)
                                        <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('inBus'))
                                    <span class="text-danger">{{ $errors->first('inBus') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="outBus">Outbound Bus</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fas fa-bus fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.outbus_id" class="form-control border-gray-300" autofocus required>
                                    <option value="">Choose Outbound Bus</option>
                                    @foreach($buses as $bus)
                                        <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('outBus'))
                                    <span class="text-danger">{{ $errors->first('outBus') }}</span>
                                @endif
                            </div>
                        </div>
                        <span>{{$startDate}}</span>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <span>Add</span>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of Create Schedule Modal Content -->

    <!-- View Schedule Modal Content -->
    <div wire:ignore.self class="modal fade" id="viewScheduleModal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-md-5">
                    <h2 class="h4 text-center">
                        <span>Edit Route Schedule Route</span>
                    </h2>

                    <!-- Form -->
                    <form wire:submit.prevent="{{ 'updateSchedule'}}">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="title">Title</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.title" class="form-control border-gray-300" id="title" autofocus required>
                                @if ($errors->has('title'))
                                    <span class="text-danger">{{ $errors->first('title') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="sequence">Sequence</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-123" viewBox="0 0 16 16">
                                        <path d="M2.873 11.297V4.142H1.699L0 5.379v1.137l1.64-1.18h.06v5.961h1.174Zm3.213-5.09v-.063c0-.618.44-1.169 1.196-1.169.676 0 1.174.44 1.174 1.106 0 .624-.42 1.101-.807 1.526L4.99 10.553v.744h4.78v-.99H6.643v-.069L8.41 8.252c.65-.724 1.237-1.332 1.237-2.27C9.646 4.849 8.723 4 7.308 4c-1.573 0-2.36 1.064-2.36 2.15v.057h1.138Zm6.559 1.883h.786c.823 0 1.374.481 1.379 1.179.01.707-.55 1.216-1.421 1.21-.77-.005-1.326-.419-1.379-.953h-1.095c.042 1.053.938 1.918 2.464 1.918 1.478 0 2.642-.839 2.62-2.144-.02-1.143-.922-1.651-1.551-1.714v-.063c.535-.09 1.347-.66 1.326-1.678-.026-1.053-.933-1.855-2.359-1.845-1.5.005-2.317.88-2.348 1.898h1.116c.032-.498.498-.944 1.206-.944.703 0 1.206.435 1.206 1.07.005.64-.504 1.106-1.2 1.106h-.75v.96Z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.sequence" class="form-control border-gray-300" id="sequence" type="number" autofocus required>
                                @if ($errors->has('sequence'))
                                    <span class="text-danger">{{ $errors->first('sequence') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="routeName">Route Name</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pin-map-fill" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M3.1 11.2a.5.5 0 0 1 .4-.2H6a.5.5 0 0 1 0 1H3.75L1.5 15h13l-2.25-3H10a.5.5 0 0 1 0-1h2.5a.5.5 0 0 1 .4.2l3 4a.5.5 0 0 1-.4.8H.5a.5.5 0 0 1-.4-.8l3-4z"/>
                                        <path fill-rule="evenodd" d="M4 4a4 4 0 1 1 4.5 3.969V13.5a.5.5 0 0 1-1 0V7.97A4 4 0 0 1 4 3.999z"/>
                                    </svg>
                                </span>
                                <select wire:model.defer="state.route_id" id="routeName" class="form-control border-gray-300" autofocus required>
                                    <option value="">Choose Route</option>
                                    @foreach($routes as $route)
                                        <option value="{{$route->id}}">{{$route->route_name}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('routeName'))
                                    <span class="text-danger">{{ $errors->first('routeName') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="scheduleTime">Date</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-alarm-fill" viewBox="0 0 16 16">
                                        <path d="M6 .5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1H9v1.07a7.001 7.001 0 0 1 3.274 12.474l.601.602a.5.5 0 0 1-.707.708l-.746-.746A6.97 6.97 0 0 1 8 16a6.97 6.97 0 0 1-3.422-.892l-.746.746a.5.5 0 0 1-.707-.708l.602-.602A7.001 7.001 0 0 1 7 2.07V1h-.5A.5.5 0 0 1 6 .5zm2.5 5a.5.5 0 0 0-1 0v3.362l-1.429 2.38a.5.5 0 1 0 .858.515l1.5-2.5A.5.5 0 0 0 8.5 9V5.5zM.86 5.387A2.5 2.5 0 1 1 4.387 1.86 8.035 8.035 0 0 0 .86 5.387zM11.613 1.86a2.5 2.5 0 1 1 3.527 3.527 8.035 8.035 0 0 0-3.527-3.527z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.start" class="form-control border-gray-300" id="start" type="date" autofocus required>
                                @if ($errors->has('start'))
                                    <span class="text-danger">{{ $errors->first('start') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="scheduleTime">Time</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-alarm-fill" viewBox="0 0 16 16">
                                        <path d="M6 .5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1H9v1.07a7.001 7.001 0 0 1 3.274 12.474l.601.602a.5.5 0 0 1-.707.708l-.746-.746A6.97 6.97 0 0 1 8 16a6.97 6.97 0 0 1-3.422-.892l-.746.746a.5.5 0 0 1-.707-.708l.602-.602A7.001 7.001 0 0 1 7 2.07V1h-.5A.5.5 0 0 1 6 .5zm2.5 5a.5.5 0 0 0-1 0v3.362l-1.429 2.38a.5.5 0 1 0 .858.515l1.5-2.5A.5.5 0 0 0 8.5 9V5.5zM.86 5.387A2.5 2.5 0 1 1 4.387 1.86 8.035 8.035 0 0 0 .86 5.387zM11.613 1.86a2.5 2.5 0 1 1 3.527 3.527 8.035 8.035 0 0 0-3.527-3.527z"/>
                                    </svg>
                                </span>
                                <input wire:model.defer="state.time" class="form-control border-gray-300" id="time" type="time" autofocus required>
                                @if ($errors->has('sequence'))
                                    <span class="text-danger">{{ $errors->first('sequence') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="inBus">Inbound Bus</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fas fa-bus fa-fw"></i>
                                </span>
                                <select  wire:model.defer="state.inbus_id" class="form-control border-gray-300" autofocus required>
                                    <option value="">Choose Inbound Bus</option>
                                    @foreach($buses as $bus)
                                        <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('inBus'))
                                    <span class="text-danger">{{ $errors->first('inBus') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="outBus">Outbound Bus</label>
                            <div class="input-group">
                                <span class="input-group-text border-gray-300" id="basic-addon3">
                                    <i class="fas fa-bus fa-fw"></i>
                                </span>
                                <select wire:model.defer="state.outbus_id" class="form-control border-gray-300" autofocus required>
                                    <option value="">Choose Outbound Bus</option>
                                    @foreach($buses as $bus)
                                        <option value="{{$bus->id}}">{{$bus->bus_registration_number}}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('outBus'))
                                    <span class="text-danger">{{ $errors->first('outBus') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-grid">
                            <div class="d-block mb-md-0" style="position: relative">
                                <button type="submit" class="btn btn-primary">Save Edit</button>
                                <!-- Button Modal -->
                                <button wire:click.prevent="confirmRemoval({{ $selectedId }})" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmationModal">Delete Schedule</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-header"></div>
            </div>
        </div>
    </div>
    <!-- End of View Schedule Modal Content -->

    <!-- Remove Route Schedule Modal -->
    <div wire:ignore.self class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Remove Route</h5>
                </div>

                <div class="modal-body">
                    <h4>Are you sure you want to remove this route schedule?</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa fa-times mr-1"></i> Cancel</button>
                    <button type="button" wire:click.prevent="removeRouteSchedule" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa fa-trash mr-1"></i>Remove Route Schedule</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Remove Route Schedule Modal Content -->
</div>

<script>
    $(document).ready(function() {
        let buses = @js($buses);
        let routes = @js($routes);
        //var data =   @this.events;
        var calendar = $('#calendar').fullCalendar({
            editable:true,
            header:{
                left:'prev,next today',
                center:'title',
                right:'month,agendaWeek,agendaDay'
            },
            events: JSON.parse(@this.events),//'load.php',
            selectable:true,
            selectHelper:true,
            select: function(start, end, allDay) {
                let startDate = $.fullCalendar.formatDate(start, "DD-MM-Y");
                @this.modalAdd(start);
                calendar.fullCalendar('refetchEvents');
            },

            eventResize:function(event)
            {
                var start = $.fullCalendar.formatDate(event.start, "Y-MM-DD HH:mm:ss");
                var end = $.fullCalendar.formatDate(event.end, "Y-MM-DD HH:mm:ss");
                var title = event.title;
                var id = event.id;
                $.ajax({
                    url:"update.php",
                    type:"POST",
                    data:{title:title, start:start, end:end, id:id},
                    success:function(){
                        calendar.fullCalendar('refetchEvents');
                        alert('Event Update');
                    }
                })
            },

            eventDrop:function(event)
            {
                var start = $.fullCalendar.formatDate(event.start, "Y-MM-DD HH:mm:ss");
                var end = $.fullCalendar.formatDate(event.end, "Y-MM-DD HH:mm:ss");
                var title = event.title;
                var id = event.id;
                $.ajax({
                    url:"update.php",
                    type:"POST",
                    data:{title:title, start:start, end:end, id:id},
                    success:function()
                    {
                        calendar.fullCalendar('refetchEvents');
                        alert("Event Updated");
                    }
                });
            },
            eventClick:function(event)
            {
                let eventId = event.id;
                @this.modalView(eventId);
                calendar.fullCalendar('refetchEvents');

               /* if(confirm("Are you sure you want to remove it?"))
                {
                    var id = event.id;
                    $.ajax({
                        url:"delete.php",
                        type:"POST",
                        data:{id:id},
                        success:function()
                        {
                            calendar.fullCalendar('refetchEvents');
                            alert("Event Removed");
                        }
                    })
                }*/
            },
        });
        window.addEventListener('add-form', event => {
            $('#createScheduleModal').modal('show');
        });
        window.addEventListener('view-modal', event => {
            $('#viewScheduleModal').modal('show');
        });
        window.addEventListener('show-delete-modal', event => {
            $('#viewScheduleModal').modal('hide');
            $('#confirmationModal').modal('show');
        });
    });


</script>


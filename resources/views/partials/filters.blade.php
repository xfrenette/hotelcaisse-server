@push('scripts')
    <script type="text/javascript" src="/bower_components/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="/bower_components/moment/min/moment.min.js"></script>
    <script type="text/javascript" src="/bower_components/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
    <script>
		$(function () {
			$('[data-role=datepicker]').datetimepicker({
				format: '{{ __('filters.dateFormat') }}',
				showClear: true,
				showClose: true,
				allowInputToggle: true,
			});
		});
    </script>
@endpush

@push('scripts')
    <link rel="stylesheet" href="/bower_components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css" />
    <style>
    .panel-overflow {
        overflow: visible;
    }
    </style>
@endpush

<div class="panel panel-default panel-overflow">
    <div class="panel-heading">
        <h3 class="panel-title">{{ __('filters.title') }}</h3>
    </div>
    <div class="panel-body">
        <form class="form" method="GET" action="{{ Request::url() }}">
            @foreach($filters as $filter)
                <div><label>{{ $filter['label'] }}</label></div>

                @if($filter['type'] === 'dateRange')
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <div class='input-group date' data-role="datepicker">
                                <input
                                    type='text'
                                    name="startDate"
                                    class="form-control"
                                    value="{{ $startDate }}"
                                    placeholder="{{ __('filters.startDate') }}"
                                />
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <div class='input-group date' data-role="datepicker">
                                <input
                                    type='text'
                                    value="{{ $endDate }}"
                                    name="endDate"
                                    class="form-control"
                                    placeholder="{{ __('filters.endDate') }}"
                                />
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @elseif($filter['type'] === 'text')
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <input
                                    type='text'
                                    name="{{ $filter['name'] }}"
                                    class="form-control"
                                    value="{{ request()->query($filter['name']) }}"
                                />
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
            <p>
                <button type="submit" class="btn btn-primary">
                    {{ __('filters.actions.apply') }}
                </button>
            </p>
        </form>
    </div>
</div>

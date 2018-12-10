<html>
	<head>
	{{ HTML::style('css/bootstrap.min.css') }}
	{{ HTML::style('css/bootstrap-theme.min.css') }}
	</head>
	<body>
		@include("reportHeader")
		<div id="content">
			<strong>
				<p>
					<?php $from = isset($input['start'])?$input['start']:date('Y-m-d'); ?>
					<?php $to = isset($input['end'])?$input['end']:date('Y-m-d'); ?>
						{{trans('messages.daily-visits')}} @if($from!=$to)
							{{trans('messages.from').' '.$from.' '.trans('messages.to').' '.$to}}
						@else
							{{trans('messages.for').' '.date('d-m-Y')}}
						@endif
				</p>
			</strong>
			<br>
			<table class="table table-bordered" width="100%">
				<tbody align="left">
					<tr>
						<th colspan="2">{{trans('messages.summary')}}</th>
					</tr>
					<tr>
						<th>{{trans('messages.total-visits')}}</th>
						<td>{{count($visits)}}</td>
					</tr>
					<tr>
						<th>{{trans('messages.male')}}</th>
						<td>
							{{--*/ $male = 0 /*--}}
							@forelse($visits as $visit)
							  @if($visit->patient->gender==Patient::MALE)
							   	{{--*/ $male++ /*--}}
							  @endif
							@endforeach
							{{$male}}
						</td>
					</tr>
					<tr>
						<th>{{trans('messages.female')}}</th>
						<td>{{count($visits)-$male}}</td>
					</tr>
				</tbody>
			</table>
			<br>
		  	<table class="table table-bordered" width="100%">
		  		<thead>
		  			<tr>
						<th>{{trans('messages.patient-number')}}</th>
						<th>{{trans('messages.patient-name')}}</th>
						<th>{{trans('messages.age')}}</th>
						<th>{{trans('messages.gender')}}</th>
						<th>{{trans('messages.specimen-number-title')}}</th>
						<th>{{trans('messages.specimen-type-title')}}</th>
						<th>{{ Lang::choice('messages.test', 2) }}</th>
					</tr>
				</thead>
				<tbody align="left">
					@forelse($visits as $visit)
					<tr>
						<td>{{ $visit->patient->id }}</td>
						<td>{{ $visit->patient->name }}</td>
						<td>{{ $visit->patient->getAge() }}</td>
						<td>{{ $visit->patient->getGender()}}</td>
						<td>@foreach($visit->tests as $test)
								<p>{{ $test->specimen->id }}</p>
							@endforeach
						</td>
						<td>@foreach($visit->tests as $test)
								<p>{{ $test->specimen->specimenType->name }}</p>
							@endforeach
						</td>
						<td>@foreach($visit->tests as $test)
								<p>{{ $test->testType->name }}</p>
							@endforeach
						</td>
					</tr>
					@empty
					<tr><td colspan="7">{{trans('messages.no-records-found')}}</td></tr>
					@endforelse
				</tbody>
			</table>
		</div>
		<script type="text/php">
		    if (isset($pdf)) {
		        $x = 250;
		        $y = $pdf->get_height()-35;
		        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
		        $font = null;
		        $size = 12;
		        $color = array(0.1,0.1,0.1);
		        $word_space = 0.0;  //  default
		        $char_space = 0.0;  //  default
		        $angle = 0.0;   //  default
		        $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
		    }
		</script>
	</body>
</html>
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
					{{trans('messages.rejected-specimen')}} 
					@if($testCategory)
						{{' - '.TestCategory::find($testCategory)->name}}
					@endif
					@if($testType)
						{{' ('.TestType::find($testType)->name.') '}}
					@endif
					<?php $from = isset($input['start'])?$input['start']:date('Y-m-d'); ?>
					<?php $to = isset($input['end'])?$input['end']:date('Y-m-d'); ?>
					@if($from!=$to)
						{{trans('messages.from').' '.$from.' '.trans('messages.to').' '.$to}}
					@else
						{{trans('messages.for').' '.date('d-m-Y')}}
					@endif
				</p>
			</strong>
			<br>
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>{{trans('messages.specimen-number-title')}}</th>
						<th>{{trans('messages.specimen')}}</th>
						<th>{{trans('messages.lab-receipt-date')}}</th>
						<th>{{ Lang::choice('messages.test', 2) }}</th>
						<th>{{Lang::choice('messages.test-category', 1)}}</th>
						<th>{{trans('messages.rejection-reason-title')}}</th>
						<th>{{trans('messages.reject-explained-to')}}</th>
						<th>{{trans('messages.date-rejected')}}</th>
					</tr>
				</thead>
				<tbody>
					@forelse($specimens as $specimen)
					<tr>
						<td>{{ $specimen->id }}</td>
						<td>{{ $specimen->specimenType->name }}</td>
						<td>{{ $specimen->test->time_created }}</td>
						<td>{{ $specimen->test->testType->name }}</td>
						<td>{{ $specimen->test->testType->testCategory->name }}</td>
						<td>{{ $specimen->rejectionReason->reason }}</td>
						<td>{{ $specimen->reject_explained_to }}</td>
						<td>{{ $specimen->time_rejected }}</td>
					</tr>
					@empty
					<tr><td colspan="8">{{trans('messages.no-records-found')}}</td></tr>
					@endforelse
				</tbody>
			</table>
		</div>
		<script type="text/php">
		    if (isset($pdf)) {
		        $x = $pdf->get_width()/2 - 20;
		        $y = $pdf->get_height() - 35;
		        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
		        $font = null;
		        $size = 10;
		        $color = array(0.1,0.1,0.1);
		        $word_space = 0.0;  //  default
		        $char_space = 0.0;  //  default
		        $angle = 0.0;   //  default
		        $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
		    }
		</script>
	</body>
</html>
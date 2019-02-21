<html>
<head>
{{ HTML::style('css/bootstrap.min.css') }}
{{ HTML::style('css/bootstrap-theme.min.css') }}
<style type="text/css">
	#content table, #content th, #content td {
	   border: 1px solid black;
	   font-size:12px;
	}
	#content p{
		font-size:12px;
	 }
</style>
</head>
<body>
<div id="wrap">
    <div class="container-fluid">
        <div class="row">
			@include("reportHeader")
			<div id="content">
			<strong>
				<p>
					{{trans('messages.patient-report').' - '.date('d-m-Y')}}

				</p>
			</strong>
		<table class="table table-bordered"  width="100%">
			<tbody>
				<tr align="left">
					<td><strong>{{ trans('messages.patient-name')}}</strong></td>
					<td>{{ $patient->name }}</td>
					<td><strong>{{ trans('messages.gender')}}</strong></td>
					<td>{{ $patient->getGender() }}</td>
				</tr>
				<tr align="left">
					<td><strong>{{ trans("messages.patient-number")}}</strong></td>
					<td>{{ $patient->patient_number}}</td>
					<td><strong>{{ trans('messages.age')}}</strong></td>
					<td>{{ $patient->getAge()}}</td>
				</tr>
				<tr align="left">
					<td><strong>{{ trans('messages.patient-lab-number')}}</strong></td>
					<td>{{ $patient->id }}</td>
					<td><strong>{{ trans('messages.requesting-facility-department')}}</strong></td>
					<td>{{ Config::get('kblis.organization') }}</td>
				</tr>
			</tbody>
		</table>
		<br>
		<table class="table table-bordered" width="100%">
			<tbody align="left">
				<tr>
					<td colspan="7"><strong>{{ trans('messages.specimen') }}</strong></td>
				</tr>
				<tr>
					<td><strong>{{ Lang::choice('messages.specimen-type', 1)}}</strong></td>
					<td><strong>{{ Lang::choice('messages.test', 2)}}</strong></td>
					<td><strong>{{ trans('messages.date-ordered') }}</strong></td>
					<td><strong>{{ Lang::choice('messages.test-category', 2)}}</strong></td>
					<td><strong>{{ trans('messages.specimen-status')}}</strong></td>
					<td><strong>{{ trans('messages.collected-by')."/".trans('messages.rejected-by')}}</strong></td>
					<td><strong>{{ trans('messages.date-checked')}}</strong></td>
				</tr>
				@forelse($tests as $test)
					<tr>
						<td>{{ $test->specimen->specimenType->name }}</td>
						<td>{{ $test->testType->name }}</td>
						<td>{{ $test->isExternal()?$test->external()->request_date:$test->time_created }}</td>
						<td>{{ $test->testType->testCategory->name }}</td>
						@if($test->specimen->specimen_status_id == Specimen::NOT_COLLECTED)
							<td>{{trans('messages.specimen-not-collected') }}</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						@elseif($test->specimen->specimen_status_id == Specimen::ACCEPTED)
							<td>{{trans('messages.specimen-accepted')}}</td>
							<td>{{$test->specimen->acceptedBy->name}}</td>
							<td>{{$test->specimen->time_accepted}}</td>
						@elseif($test->specimen->specimen_status_id == Specimen::REJECTED)
							<td>{{trans('messages.specimen-rejected')}}</td>
							<td>{{$test->specimen->rejectedBy->name}}</td>
							<td>{{$test->specimen->time_rejected}}</td>
						@endif
					</tr>
				@empty
					<tr>
						<td colspan="7">{{ trans("messages.no-records-found") }}</td>
					</tr>
				@endforelse

			</tbody>
		</table>
		<br>
		<table class="table table-bordered"  width="100%">
			<tbody align="left">
				<tr>
					<td colspan="8"><strong>{{trans('messages.test-results')}}</strong></td>
				</tr>
				<tr>
					<td><strong>{{Lang::choice('messages.test-type', 1)}}</strong></td>
					<td><strong>{{trans('messages.test-results-values')}}</strong></td>
					<td><strong>{{trans('messages.test-remarks')}}</strong></td>
					<td><strong>{{trans('messages.tested-by')}}</strong></td>
					<td><strong>{{trans('messages.results-entry-date')}}</strong></td>
					<td><strong>{{trans('messages.date-tested')}}</strong></td>
					<td><strong>{{trans('messages.verified-by')}}</strong></td>
					<td><strong>{{trans('messages.date-verified')}}</strong></td>
				</tr>
				@forelse($tests as $test)
					<tr>
						<td>{{ $test->testType->name }}</td>
						<td>
							@foreach($test->testResults as $result)
							<p>
								{{ Measure::find($result->measure_id)->name }}: {{ $result->result }}
								{{ Measure::getRange($test->visit->patient, $result->measure_id) }}
								{{ Measure::find($result->measure_id)->unit }}
							</p>
							@endforeach
						</td>
						<td>{{ $test->interpretation }}</td>
						<td>{{ $test->tested_by > 0 ? $test->testedBy->name : trans('messages.pending')}}</td>
						<td>{{ $test->testResults->last()->time_entered }}</td>
						<td>{{ $test->time_completed }}</td>
						<td>{{ $test->verified_by > 0 ? $test->verifiedBy->name : trans('messages.verification-pending') }}</td>
						<td>{{ $test->time_verified }}</td>
					</tr>
				@empty
					<tr>
						<td colspan="8">{{trans("messages.no-records-found")}}</td>
					</tr>
				@endforelse
			</tbody>
		</table>
		</div>
		</div>
		<hr style="border: 1px solid black;">
		<table class="table table-bordered"  width="100%" style="font-size:12px;">
			<tbody>
				<tr>
					<td><strong>{{ trans('messages.authorized-by') }}</strong></td>
					<td>{{ trans('messages.signature-holder') }}</td>
					<td><strong>{{ Lang::choice('messages.name', 1).":" }}</strong>{{ trans('messages.signature-holder') }}</td>
				</tr>
				<tr>
					<td>{{ Config::get('kblis.lab-quality-manager-name') }}</td>
					<td>{{ Config::get('kblis.lab-manager-name') }}</td>
					<td>{{ Config::get('kblis.lab-director-name') }}</td>
				</tr>
				<tr>
					<td><u><strong>{{ trans('messages.quality-manager') }}</strong></u></td>
					<td><u><strong>{{ trans('messages.lab-manager') }}</strong></u></td>
					<td><u><strong>{{ trans('messages.lab-director') }}</strong></u></td>
				</tr>
				<tr>
					<td><strong>{{ Config::get('kblis.patient-report-no') }}</strong></td>
					<td>&nbsp;</td>
					<td><strong>{{ Config::get('kblis.patient-report-version') }}</strong></td>
				</tr>
			</tbody>
		</table>
	</div>
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
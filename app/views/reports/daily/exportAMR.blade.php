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
						Microbiology Culture WHONet Report 
						@if($from!=$to)
							{{trans('messages.from').' '.$from.' '.trans('messages.to').' '.$to}}
						@else
							{{trans('messages.for').' '.date('d-m-Y')}}
						@endif
				</p>
			</strong>
			<br>
			<table class="table table-bordered" width="100%">
			<tbody>
				<tr>
					<th>{{trans('messages.patient-name')}}</th>
					<th>IP/OP Number</th>
					<th>{{trans('messages.gender')}}</th>
					<th>DOB</th>
					<th>{{trans('messages.age')}}</th>
					<th>Country</th>
					<th>County</th>
					<th>Sub-county</th>
					<th>Pre-diagnosis</th>
					<th>Specimen collection date</th>
					<th>Location</th>
					<th>Department</th>
					<th>Admission Date</th>
					<th>Prior Antibiotic Therapy</th>
					<th>{{trans('messages.specimen-type-title')}}</th>
					<th>Specimen Site</th>
					<th>Lab ID</th>
					<th>Isolates Obtained?</th>
					<th>Isolate Name</th>
					<th>Test Method</th>
					<th>Gram Pos/Neg</th>
					<th>Drugs Tested</th>
					<th>MIC</th>
					<th>Interpretation (SIR)</th>
					<th>{{ Lang::choice('messages.test', 2) }}</th>
				</tr>
				@forelse($testContent as $tc)
				<tr>
					<td>{{ $tc['patient_name'] }}</td>
					<td>{{ $tc['patient_number'] }}</td>
					<td>{{ $tc['gender']}}</td>
					<td>{{ $tc['dob'] }}</td>
					<td>{{ $tc['age'] }} years</td>
					<td>&nbsp;</td>
					<td>{{ $tc["county"] }}</td>
					<td>{{ $tc["sub_county"] }}</td>
					<td>{{ $tc["prediagnosis"] }}</td>
					<td>{{ substr($tc['specimen_collection_date'],0,10) }}</td>
					<td>{{ $tc['patient_type'] }}</td>
					<td>{{ $tc['ward'] }}</td>
					<td>{{ $tc['admission_date'] }}</td>
					<td>{{ $tc['currently_on_therapy'] }}</td>
					<td>{{ $tc['specimen_type'] }}</td>
					<td>{{ $tc['specimen_source'] }}</td>
					<td>&nbsp;</td>
					<?php
						$isolateObtained = "";
						$isolateName = "";
						$drugTested = "";
						$zone = "";
						$sir = "";
						if (count($tc["isolates"]) > 0) {
							$isolateObtained .= "<p>Yes</p>";
							$tempIsolate = "";
							foreach ($tc["isolates"] as $suscept) {
								if(strcmp($tempIsolate, $suscept["isolate_name"]) != 0){
									$tempIsolate = $suscept["isolate_name"];
									$isolateName .= "<p>".$suscept["isolate_name"]."</p>";
								}else{
									$isolateName .= "<p>&nbsp;</p>";
								}
								$drugTested .= "<p>".$suscept["drug"]."</p>";
								$zone .= "<p>".$suscept["zone"]."</p>";
								$sir .= "<p>".$suscept["interpretation"]."</p>";
							}
						}else{
							$isolateObtained .= "<p>No</p>";
						}
					?>
					<td>{{ $isolateObtained }}</td>
					<td>{{ $isolateName }}</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>{{ $drugTested }}</td>
					<td>{{ $zone }}</td>
					<td>{{ $sir }}</td>
					<td>{{ $tc['test_type'] }}</td>
				</tr>
				@empty
				<tr><td colspan="13">{{trans('messages.no-records-found')}}</td></tr>
				@endforelse
			</tbody>
		</table>
	</body>
</html>
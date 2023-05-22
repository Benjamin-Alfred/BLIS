<html>
	<head>
	{{ HTML::style('css/bootstrap.min.css') }}
	{{ HTML::style('css/bootstrap-theme.min.css') }}
	</head>
	<body>
		<!-- @include("reportHeader") -->
		<div id="content">
<!-- 			<strong class="hidden-print">
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
			<br> -->
			<table class="table table-bordered" width="100%">
				<?php
					$theader = "<tr>";
					$theader .= "<th>Patient Name</th>";
					$theader .= "<th>IP/OP Number</th>";
					$theader .= "<th>Gender</th>";
					$theader .= "<th>DOB</th>";
					$theader .= "<th>Age</th>";
					$theader .= "<th>Country</th>";
					$theader .= "<th>County</th>";
					$theader .= "<th>Sub-county</th>";
					$theader .= "<th>Pre-diagnosis</th>";
					$theader .= "<th>Specimen collection date</th>";
					$theader .= "<th>Location</th>";
					$theader .= "<th>Department</th>";
					$theader .= "<th>Admission Date</th>";
					$theader .= "<th>Prior Antibiotic Therapy</th>";
					$theader .= "<th>Specimen-type-title</th>";
					$theader .= "<th>Specimen Site</th>";
					$theader .= "<th>Lab ID</th>";
					$theader .= "<th>Isolates Obtained?</th>";
					$theader .= "<th>Isolate Name</th>";
					$theader .= "<th>Test Method</th>";
					$theader .= "<th>Gram Pos/Neg</th>";
					$theader .= "[ANTIBIOTIC_NAMES]";
					$theader .= "<th>Test Name</th>";
					$theader .= "</tr>";

					$antibiotics = array();
					$abValues = array();
					$rowSet = array();

					if (count($testContent) > 0) {
						foreach ($testContent as $tc) {
							if (count($tc) > 1) {//Wonder why it has 1 element when empty?
								$trow = "<tr>";
								$trow .= "<td>".$tc['patient_name'] ."</td>";
								$trow .= "<td>".$tc['patient_number'] ."</td>";
								$trow .= "<td>".$tc['gender']."</td>";
								$trow .= "<td>".$tc['dob'] ."</td>";
								$trow .= "<td>".$tc['age'] ." years</td>";
								$trow .= "<td>&nbsp;</td>";
								$trow .= "<td>".$tc["county"] ."</td>";
								$trow .= "<td>".$tc["sub_county"] ."</td>";
								$trow .= "<td>".$tc["prediagnosis"] ."</td>";
								$trow .= "<td>".substr($tc['specimen_collection_date'],0,10) ."</td>";
								$trow .= "<td>".$tc['patient_type'] ."</td>";
								$trow .= "<td>".$tc['ward'] ."</td>";
								$trow .= "<td>".$tc['admission_date'] ."</td>";
								$trow .= "<td>".$tc['currently_on_therapy'] ."</td>";
								$trow .= "<td>".$tc['specimen_type'] ."</td>";
								$trow .= "<td>".$tc['specimen_source'] ."</td>";
								$trow .= "<td>".$tc['lab_id'] ."</td>";

								$isolateObtained = "";
								$isolateName = "";
								if (count($tc["isolates"]) > 0) {
									$isolateObtained .= "<p>Yes</p>";
									$isolateName = "";
									foreach ($tc["isolates"] as $suscept) {
										if(strcmp($isolateName, $suscept["isolate_name"]) != 0){
											$isolateName .= $suscept["isolate_name"];
										}
										$antibiotics[] = strtoupper($suscept["drug"]);
										$abValues[$tc['patient_number']][strtoupper($suscept["drug"])] = $suscept["zone"];
									}
								}else{
									$isolateObtained .= "<p>No</p>";
								}
								$trow .= "<td>".$isolateObtained."</td>";
								$trow .= "<td>".$isolateName."</td>";
								$trow .= "<td>&nbsp;</td>";
								$trow .= "<td>&nbsp;</td>";
								$trow .= "[ANTIBOITIC_VALUES]";
								$trow .= "<td>".$tc['test_type']."</td>";
								$trow .= "</tr>";

								$rowSet[$tc['patient_number']] = $trow;
							}
						}
					}else{
						$rowSet[] = "<tr><td colspan='22'>No records found!</td></tr>";
					}
				?>

			<thead>
				<?php
				$abHeader = "";
				$antibiotics = array_unique($antibiotics);
				asort($antibiotics);
				foreach($antibiotics as $ab){
					$abHeader .= "<th>$ab</th>";
				}
				$theader = str_replace("[ANTIBIOTIC_NAMES]", $abHeader, $theader);
				echo $theader;
				?>
			</thead>
			<tbody>
				<?php
				foreach ($rowSet as $key => $row) {
					$abv = "";
					foreach($antibiotics as $ab){
						try{
							$abv .= "<td>".$abValues[$key][$ab]."</td>";
						}catch(Exception $e){
							$abv .= "<td>&nbsp;</td>";
						}
					}
					echo str_replace("[ANTIBOITIC_VALUES]", $abv, $row);
				}
				?>
			</tbody>
		</table>
	</body>
</html>
@extends("layout")
@section("content")
<div class="non-print">
	<ol class="breadcrumb">
	  <li><a href="{{{URL::route('user.home')}}}">{{ trans('messages.home') }}</a></li>
	  <li class="active">{{ Lang::choice('messages.report',2) }}</li>
	  <li class="active">{{ trans('messages.moh-706') }} {{ Lang::choice('messages.report',1) }}</li>
	</ol>
</div>
{{ Form::open(array('route' => array('reports.aggregate.moh706v201410'), 'class' => 'form-inline non-print', 'role' => 'form')) }}
<!-- <div class='container-fluid'> -->
	<div class="row">
		<div class="col-md-4">
	    	<div class="row">
				<div class="col-md-2">
					{{ Form::label('start', trans("messages.from")) }}
				</div>
				<div class="col-md-10">
					{{ Form::text('start', isset($input['start'])?$input['start']:date('Y-m-d'), 
				        array('class' => 'form-control standard-datepicker')) }}
			    </div>
	    	</div>
	    </div>
	    <div class="col-md-4">
	    	<div class="row">
				<div class="col-md-2">
			    	{{ Form::label('end', trans("messages.to")) }}
			    </div>
				<div class="col-md-10">
				    {{ Form::text('end', isset($input['end'])?$input['end']:date('Y-m-d'), 
				        array('class' => 'form-control standard-datepicker')) }}
		        </div>
	    	</div>
	    </div>
	    <div class="col-md-4">
		    {{ Form::button("<span class='glyphicon glyphicon-filter'></span> ".trans('messages.view'), 
		        array('class' => 'btn btn-info', 'id' => 'filter', 'type' => 'submit')) }}
	    </div>
	</div>
<!-- </div> -->
{{ Form::close() }}
<br class="non-print" />

@if (Session::has('message'))
	<div class="alert alert-info">{{ trans(Session::get('message')) }}</div>
@endif
<hr class="non-print">

<div class="container-fluid">
	<div class="row">
		<div class="col-xs-8" style="font-size: 1.2em;font-weight: bold;">
			<div><center>{{ trans('messages.moh') }}</center></div>
			<div><center>{{ trans('messages.lab-tests-data-report') }}</center></div>
			<div>
				<div class="col-xs-3">MFL Code: </div>
				<div class="col-xs-3">Facility Name: </div>
				<div class="col-xs-3">County:</div>
				<div class="col-xs-3">Sub County:</div>
			</div>
			<div>
				<div class="col-xs-offset-2 col-xs-6">Report Period: Month</div>
				<div class="col-xs-2">Year</div>
			</div>
		</div>
		<div class="col-xs-4">
			<div>{{ trans('messages.moh-706') }}</div>
			<div><i>Revised Oct 2014</i></div>
			<div class="table-responsive">
				<table class="table table-bordered">
					<tr><td colspan="8">Affiliation (Tick ONE)</td></tr>
					<tr>
						<td>GOK</td><td>&nbsp;</td>
						<td>Faith Based</td><td>&nbsp;</td>
						<td>Private</td><td>&nbsp;</td>
						<td>NGO</td><td>&nbsp;</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
	<div class="row" style="margin-bottom: 20px;">
		<div class="col-xs-12">NB: Indicate 'N/S' where there is no service</div>
	</div>

	<div class="row">
		<div class="col-xs-5">
			<div class="col-xs-6">
				<div class="table-responsive">
					<table class="table table-bordered">
						<thead>
							<tr><th colspan="3"><center>1. URINE ANALYSIS</center></th></tr>
							<tr><th>&nbsp;</th><th>Total Exam</th><th>Number Positive</th></tr>
						</thead>
						<tbody>
							<tr><td><strong>1.1 Urine Chemistry</strong></td><td>{{ $mohData['1_1_urine_chemistry_total'] }}</td>
								<td class="blank-cell">&nbsp;</td></tr>
							<tr><td>1.2 Glucose</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['1_2_glucose'] }}</td></tr>
							<tr><td>1.3 Ketones</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['1_3_ketones'] }}</td></tr>
							<tr><td>1.4 Proteins</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['1_4_proteins'] }}</td></tr>
							<tr class="emphasize"><td>1.5 Urine Microscopy</td><td>{{ $mohData['1_5_urine_microscopy_total'] }}</td>
								<td>Number Positive</td></tr>
							<tr><td>1.6 Pus cells (>5/hpf)</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['1_6_puss_cells'] }}</td></tr>
							<tr><td>1.7 S. haematobium</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['1_7_s_haematobium'] }}</td></tr>
							<tr><td>1.8 T. vaginalis</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['1_8_t_vaginalis'] }}</td></tr>
							<tr><td>1.9 Yeast cells</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['1_9_yeast_cells'] }}</td></tr>
							<tr><td>1.10 Bacteria</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['1_10_bacteria'] }}</td></tr>
						</tbody>
					</table>
				</div>
				<div class="table-responsive">
					<table class="table table-bordered">
						<thead>
							<tr><th colspan="4"><center>2. BLOOD CHEMISTRY</center></th></tr>
							<tr><th>Blood Sugar Test</th><th>Total Exam</th><th>Low</th><th>High</th></tr>
						</thead>
						<tbody>
							<tr>
								<td>2.1 Fasting Blood Sugar</td><td>{{ $mohData['2_1_fasting_blood_sugar_total'] }}</td>
								<td>{{ $mohData['2_1_fasting_blood_sugar_low'] }}</td><td>{{ $mohData['2_1_fasting_blood_sugar_high'] }}</td>
							</tr>
							<tr>
								<td>2.1 Random Blood Sugar</td><td>{{ $mohData['2_1_random_blood_sugar_total'] }}</td>
								<td>{{ $mohData['2_1_random_blood_sugar_low'] }}</td><td>{{ $mohData['2_1_random_blood_sugar_high'] }}</td>
							</tr>
							<tr>
								<td>2.2 OGTT</td><td>{{ $mohData['2_2_ogtt_total'] }}</td><td>{{ $mohData['2_2_ogtt_low'] }}</td><td>{{ $mohData['2_2_ogtt_high'] }}</td>
							</tr>
							<tr class="emphasize">
								<td>2.3 Renal Function Test</td>
								<td>{{ $mohData['2_3_renal_function_total'] }}</td><td class="blank-cell">&nbsp;</td><td class="blank-cell">&nbsp;</td>
							</tr>
							<tr>
								<td>2.4 Creatinine</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_4_creatinine_low'] }}</td><td>{{ $mohData['2_4_creatinine_high'] }}</td>
							</tr>
							<tr>
								<td>2.5 Urea</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_5_urea_low'] }}</td><td>{{ $mohData['2_5_urea_high'] }}</td>
							</tr>
							<tr>
								<td>2.5 Sodium</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_5_sodium_low'] }}</td><td>{{ $mohData['2_5_sodium_high'] }}</td>
							</tr>
							<tr>
								<td>2.6 Potassium</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_6_potassium_low'] }}</td><td>{{ $mohData['2_6_potassium_high'] }}</td>
							</tr>
							<tr>
								<td>2.7 Chlorides</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_7_chlorides_low'] }}</td><td>{{ $mohData['2_7_chlorides_high'] }}</td>
							</tr>
							<tr class="emphasize">
								<td>2.8 Liver Function Test</td>
								<td>{{ $mohData['2_8_liver_function_total'] }}</td><td class="blank-cell">&nbsp;</td><td class="blank-cell">&nbsp;</td>
							</tr>
							<tr>
								<td>2.9 Direct bilirubin</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_9_direct_bilirubin_low'] }}</td><td>{{ $mohData['2_9_direct_bilirubin_high'] }}</td>
							</tr>
							<tr>
								<td>2.10 Total bilirubin</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_10_total_bilirubin_low'] }}</td><td>{{ $mohData['2_10_total_bilirubin_high'] }}</td>
							</tr>
							<tr>
								<td>2.11 ASAT (SGOT)</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_11_asat_low'] }}</td><td>{{ $mohData['2_11_asat_high'] }}</td>
							</tr>
							<tr>
								<td>2.12 ALAT (SGPT)</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_12_alat_low'] }}</td><td>{{ $mohData['2_12_alat_high'] }}</td>
							</tr>
							<tr>
								<td>2.13 Serum Protein</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_13_serum_protein_low'] }}</td><td>{{ $mohData['2_13_serum_protein_high'] }}</td>
							</tr>
							<tr>
								<td>2.14 Albumin</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_14_albumin_low'] }}</td><td>{{ $mohData['2_14_albumin_high'] }}</td>
							</tr>
							<tr>
								<td>2.15 Alkaline Phosphatase</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_alkaline_phosphatase_low'] }}</td><td>{{ $mohData['2_alkaline_phosphatase_high'] }}</td>
							</tr>
							<tr class="emphasize">
								<td>2.16 Lipid Profile</td>
								<td>{{ $mohData['2_16_lipid_profile_total'] }}</td><td class="blank-cell">&nbsp;</td><td class="blank-cell">&nbsp;</td>
							</tr>
							<tr>
								<td>2.17 Total cholesterol</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_17_cholesterol_low'] }}</td><td>{{ $mohData['2_17_cholesterol_high'] }}</td>
							</tr>
							<tr>
								<td>2.18 Triglycerides</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_18_triglycerides_low'] }}</td><td>{{ $mohData['2_18_triglycerides_high'] }}</td>
							</tr>
							<tr>
								<td>2.19 LDL</td>
								<td class="blank-cell">&nbsp;</td><td>{{ $mohData['2_19_ldl_low'] }}</td><td>{{ $mohData['2_19_ldl_high'] }}</td>
							</tr>
							<tr class="emphasize">
								<td>Hormonal Test</td><td>Total Exam</td>
								<td>Low</td><td>High</td>
							</tr>
							<tr>
								<td>2.20 T3</td>
								<td>{{ $mohData['2_20_t3_total'] }}</td><td>{{ $mohData['2_20_t3_low'] }}</td><td>{{ $mohData['2_20_t3_high'] }}</td>
							</tr>
							<tr>
								<td>2.21 T4</td>
								<td>{{ $mohData['2_21_t4_total'] }}</td><td>{{ $mohData['2_21_t4_low'] }}</td><td>{{ $mohData['2_21_t4_high'] }}</td>
							</tr>
							<tr>
								<td>2.22 TSH</td>
								<td>{{ $mohData['2_22_tsh_total'] }}</td><td>{{ $mohData['2_22_tsh_low'] }}</td><td>{{ $mohData['2_22_tsh_high'] }}</td>
							</tr>
							<tr>
								<td>2.23 PSA</td>
								<td>{{ $mohData['2_23_psa_total'] }}</td><td>{{ $mohData['2_23_psa_low'] }}</td><td>{{ $mohData['2_23_psa_high'] }}</td>
							</tr>
							<tr class="emphasize">
								<td>Tumor Markers</td><td>Total Exam</td>
								<td>Low</td><td>High</td>
							</tr>
							<tr>
								<td>2.24 CEA</td>
								<td>{{ $mohData['2_24_cea_total'] }}</td><td>{{ $mohData['2_24_cea_low'] }}</td><td>{{ $mohData['2_24_cea_high'] }}</td>
							</tr>
							<tr>
								<td>2.25 C15-3</td>
								<td>{{ $mohData['2_25_c15_total'] }}</td><td>{{ $mohData['2_25_c15_low'] }}</td><td>{{ $mohData['2_25_c15_high'] }}</td>
							</tr>
							<tr class="emphasize">
								<td>CSF Chemistry</td><td>Total Exam</td>
								<td>Low</td><td>High</td>
							</tr>
							<tr>
								<td>2.26 Proteins</td>
								<td>{{ $mohData['2_26_proteins_total'] }}</td><td>{{ $mohData['2_26_proteins_low'] }}</td><td>{{ $mohData['2_26_proteins_high'] }}</td>
							</tr>
							<tr>
								<td>2.27 Glucose</td>
								<td>{{ $mohData['2_27_glucose_total'] }}</td><td>{{ $mohData['2_27_glucose_low'] }}</td><td>{{ $mohData['2_27_glucose_high'] }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="col-xs-6">
				<div class="table-responsive">
					<table class="table table-bordered">
						<thead>
							<tr><th colspan="3"><center>3. PARASITOLOGY</center></th></tr>
							<tr><th>Malaria Test</th><th>Total Exam</th><th>Number Positive</th></tr>
						</thead>
						<tbody>
							<tr><td>3.1 Malaria BS (Under five years)</td><td>{{ $mohData['3_1_malaria_bs_under_5_total'] }}</td>
								<td>{{ $mohData['3_1_malaria_bs_under_5_positive'] }}</td></tr>
							<tr><td>3.2 Malaria BS (5 years and above)</td><td>{{ $mohData['3_2_malaria_bs_over_5_total'] }}</td>
								<td>{{ $mohData['3_2_malaria_bs_over_5_positive'] }}</td></tr>
							<tr><td>3.3 Malaria Rapid Diagnostic Tests</td><td>{{ $mohData['3_3_malaria_rapid_total'] }}</td>
								<td>{{ $mohData['3_3_malaria_rapid_positive'] }}</td></tr>
							<tr><td><strong>Stool Examination</strong></td><td>&nbsp;</td>
								<td><strong>Number Positive</strong></td></tr>
							<tr><td>3.4 Taenia spp.</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['3_4_taenia_spp'] }}</td></tr>
							<tr><td>3.5 Hymenolepis nana</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['3_5_hymenolepis_nana'] }}</td></tr>
							<tr><td>3.6 Hookworms</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['3_6_hookworms'] }}</td></tr>
							<tr><td>3.7 Roundworms</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['3_7_roundworms'] }}</td></tr>
							<tr><td>3.8 S. mansoni</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['3_8_s_mansoni'] }}</td></tr>
							<tr><td>3.9 Trichuris trichura</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['3_9_trichuris_trichura'] }}</td></tr>
							<tr><td>3.10 Amoeba</td><td class="blank-cell">&nbsp;</td><td>{{ $mohData['3_10_amoeba'] }}</td></tr>
						</tbody>
					</table>
				</div>
				<div class="table-responsive">
					<table class="table table-bordered">
						<thead>
							<tr><th colspan="4"><center>4. HAEMATOLOGY</center></th></tr>
							<tr><th>Haematology Tests</th><th>Total Exam</th><th>HB &lt;5 g/dl</th><th>HB between 5 and 10 g/dl</th></tr>
						</thead>
						<tbody>
							<tr>
								<td>4.1 Full blood count</td><td>{{ $mohData['4_1_full_blood_count_total'] }}</td>
								<td>{{ $mohData['4_1_full_blood_count_low'] }}</td><td>{{ $mohData['4_1_full_blood_count_high'] }}</td>
							</tr>
							<tr>
								<td>4.2 HB estimation tests(other techniques)</td><td>{{ $mohData['4_2_hb_other_estimations_total'] }}</td>
								<td>{{ $mohData['4_2_hb_other_estimations_low'] }}</td><td>{{ $mohData['4_2_hb_other_estimations_high'] }}</td>
							</tr>
							<tr>
								<td rowspan="2">4.3 CD4 count</td><td rowspan="2">{{ $mohData['4_3_cd4_count_total'] }}</td>
								<td colspan="2"><strong>Number &lt; 500</strong></td>
							</tr>
							<tr><td colspan="2">{{ $mohData['4_3_cd4_under_500'] }}</td></tr>
							<tr><td colspan="4">&nbsp;</td></tr>
							<tr>
								<td><strong>Other Haematology Tests</strong></td>
								<td><strong>Total Exam</strong></td><td colspan="2"><strong>Number Positive</strong></td>
							</tr>
							<tr>
								<td>4.4 Sickling test</td>
								<td>{{ $mohData['4_4_sickling_test_total'] }}</td><td colspan="2">{{ $mohData['4_4_sickling_test_positive'] }}</td>
							</tr>
							<tr>
								<td>4.5 Peripheral blood films</td>
								<td>{{ $mohData['4_5_peripheral_blood_films_total'] }}</td><td colspan="2" class="blank-cell">&nbsp;</td>
							</tr>
							<tr>
								<td>4.6 BMA</td>
								<td>{{ $mohData['4_6_bma_total'] }}</td><td colspan="2" class="blank-cell">&nbsp;</td>
							</tr>
							<tr>
								<td>4.7 Coagulation Profile</td>
								<td>{{ $mohData['4_7_coagulaton_profile_total'] }}</td><td colspan="2" class="blank-cell">&nbsp;</td>
							</tr>
							<tr>
								<td>4.8 Reticulocyte Count</td>
								<td>{{ $mohData['4_8_reticulocyte_count_total'] }}</td><td colspan="2" class="blank-cell">&nbsp;</td>
							</tr>
							<tr>
								<td colspan="2" class="blank-cell"></td><td colspan="2"><strong>High</strong></td>
							</tr>
							<tr>
								<td>4.9 Erythrocyte Sedimentation rate</td><td>{{ $mohData['4_9_eruthrocyte_sedimentation_rate_total'] }}</td>
								<td colspan="2">{{ $mohData['4_9_eruthrocyte_sedimentation_rate_high'] }}</td>
							</tr>
							<tr><td colspan="4">&nbsp;</td></tr>
							<tr>
								<td colspan="2"><strong>Blood Grouping</strong></td><td colspan="2"><strong>Number</strong></td>
							</tr>
							<tr><td colspan="2">4.10 Total blood group tests</td>
								<td colspan="2">{{ $mohData['4_10_total_blood_group_tests_total'] }}</td></tr>
							<tr><td colspan="2">4.11 Blood units grouped</td><td colspan="2">{{ $mohData['4_11_blood_units_grouped_total'] }}</td></tr>
							<tr>
								<td colspan="2"><strong>Blood Safety</strong></td><td colspan="2"><strong>Number</strong></td>
							</tr>
							<tr><td colspan="2">4.12 Blood units received from blood transfusion centres</td>
								<td colspan="2">{{ $mohData['4_12_blood_received_total'] }}</td></tr>
							<tr><td colspan="2">4.13 Blood units collected at facility</td>
								<td colspan="2">{{ $mohData['4_13_blood_collected_total'] }}</td></tr>
							<tr><td colspan="2">4.14 Blood units transfused</td>
								<td colspan="2">{{ $mohData['4_14_blood_transfused_total'] }}</td></tr>
							<tr><td colspan="2">4.15 Transfusion reactions reported and investigated</td>
								<td colspan="2">{{ $mohData['4_15_transfusion_reactions_reported_investigated_total'] }}</td></tr>
							<tr><td colspan="2">4.16 Blood cross matched</td>
								<td colspan="2">{{ $mohData['4_16_blood_cross_matched_total'] }}</td></tr>
							<tr><td colspan="2">4.17 Blood units discarded</td>
								<td colspan="2">{{ $mohData['4_17_blood_units_discarded_total'] }}</td></tr>
							<tr>
								<td colspan="2"><strong>Blood Screening at facility</strong></td>
								<td colspan="2"><strong>Number Positive</strong></td>
							</tr>
							<tr><td colspan="2">4.18 HIV</td><td colspan="2">{{ $mohData['4_18_hiv_positive'] }}</td></tr>
							<tr><td colspan="2">4.19 Hepatitis B</td><td colspan="2">{{ $mohData['4_19_hepatitis_b_positive'] }}</td></tr>
							<tr><td colspan="2">4.20 Hepatitis C</td><td colspan="2">{{ $mohData['4_20_hepatitis_c_positive'] }}</td></tr>
							<tr><td colspan="2">4.21 Syphilis</td><td colspan="2">{{ $mohData['4_21_syphilis_positive'] }}</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-xs-7">
			<div class="col-xs-5">
				<div class="table-responsive">
					<table class="table table-bordered">
						<thead>
							<tr><th colspan="4"><center>5. BACTERIOLOGY</center></th></tr>
							<tr><th>Bacteriology Sample</th><th>Total Exam</th><th>Total Cultures</th><th>No. Culture Positive</th></tr>
						</thead>
						<tbody>
							<tr>
								<td>5.1 Urine</td><td>{{ $mohData['5_1_urine_total'] }}</td>
								<td>{{ $mohData['5_1_urine_culture_count'] }}</td><td>{{ $mohData['5_1_urine_culture_postive'] }}</td>
							</tr>
							<tr>
								<td>5.2 Pus swabs</td><td>{{ $mohData['5_2_pus_swabs_total'] }}</td>
								<td>{{ $mohData['5_2_pus_swabs_culture_count'] }}</td><td>{{ $mohData['5_2_pus_swabs_culture_positive'] }}</td>
							</tr>
							<tr>
								<td>5.3 High Vaginal Swabs</td><td>{{ $mohData['5_3_highg_vaginal_swabs_total'] }}</td>
								<td>{{ $mohData['5_3_highg_vaginal_swabs_culture_count'] }}</td><td>{{ $mohData['5_3_highg_vaginal_swabs_culture_positive'] }}</td>
							</tr>
							<tr>
								<td>5.4 Throat swab</td><td>{{ $mohData['5_4_throat_swab_total'] }}</td>
								<td>{{ $mohData['5_4_throat_swab_culture_count'] }}</td><td>{{ $mohData['5_4_throat_swab_culture_positive'] }}</td>
							</tr>
							<tr>
								<td>5.5 Rectal swab</td><td>{{ $mohData['5_5_rectal_swab_total'] }}</td>
								<td>{{ $mohData['5_5_rectal_swab_culture_count'] }}</td><td>{{ $mohData['5_5_rectal_swab_culture_positive'] }}</td>
							</tr>
							<tr>
								<td>5.6 Blood</td><td>{{ $mohData['5_6_blood_total'] }}</td>
								<td>{{ $mohData['5_6_blood_culture_count'] }}</td><td>{{ $mohData['5_6_blood_culture_positive'] }}</td>
							</tr>
							<tr>
								<td>5.7 Water</td><td>{{ $mohData['5_7_water_total'] }}</td>
								<td>{{ $mohData['5_7_water_culture_count'] }}</td><td>{{ $mohData['5_7_water_culture_positive'] }}</td>
							</tr>
							<tr>
								<td>5.8 Food</td><td>{{ $mohData['5_8_food_total'] }}</td>
								<td>{{ $mohData['5_8_food_culture_count'] }}</td><td>{{ $mohData['5_8_food_culture_positive'] }}</td>
							</tr>
							<tr>
								<td>5.9 Urethral swabs</td><td>{{ $mohData['5_9_urethral_swabs_total'] }}</td>
								<td>{{ $mohData['5_9_urethral_swabs_culture_count'] }}</td><td>{{ $mohData['5_9_urethral_swabs_culture_positive'] }}</td>
							</tr>
							<tr>
								<td colspan="2"><strong>Bacterial enteric pathogens</strong></td>
								<td><strong>Total Exam</strong></td><td><strong>Number Positive</strong></td>
							</tr>
							<tr>
								<td colspan="2">5.10 Stool Cultures</td>
								<td>{{ $mohData['5_10_stool_cultures_total'] }}</td><td>{{ $mohData['5_10_stool_cultures_positive'] }}</td>
							</tr>
							<tr>
								<td colspan="2"><strong>Stool Isolates</strong></td><td colspan="2"><strong>Number Positive</strong></td>
							</tr>
							<tr><td colspan="2">5.11 Salmonella typhi</td><td colspan="2">{{ $mohData['5_11_salmonella_typhi_positive'] }}</td></tr>
							<tr><td colspan="2">5.12 Shigella - dysenteriae type1</td>
								<td colspan="2">{{ $mohData['5_12_shigella_dysenteriae_type1_positve'] }}</td></tr>
							<tr><td colspan="2">5.13 E. coli O 157:H7</td><td colspan="2">{{ $mohData['5_13_e_coli_o_157_h7_positive'] }}</td></tr>
							<tr><td colspan="2">5.14 V. cholerae O1</td><td colspan="2">{{ $mohData['5_14_v_cholerae_o_1_positive'] }}</td></tr>
							<tr><td colspan="2">5.15 V. cholerae O139</td><td colspan="2">{{ $mohData['5_15_v_cholerae_o_139_positive'] }}</td></tr>
							<tr class="emphasize"><td colspan="4"><center>Bacterial meningitis</center></td></tr>
							<tr class="emphasize">
								<td>Bacterial Meningitis</td>
								<td>Total Exam</td>
								<td>Number Positive</td>
								<td>Number Contaminated</td></tr>
							<tr>
								<td>5.16 CSF</td>
								<td>{{ $mohData['5_16_csf_total'] }}</td><td>{{ $mohData['5_16_csf_positive'] }}</td><td>{{ $mohData['5_16_csf_contaminated_count'] }}</td>
							</tr>
							<tr>
								<td colspan="2"><strong>Bacterial meningitis Serotypes</strong></td>
								<td colspan="2"><strong>Number Positive</strong></td>
							</tr>
							<tr><td colspan="2">5.17 Neisseria meningitidis A</td>
								<td colspan="2">{{ $mohData['5_17_neisseria_meningitidis_a_positive'] }}</td></tr>
							<tr><td colspan="2">5.18 Neisseria meningitidis B</td>
								<td colspan="2">{{ $mohData['5_18_neisseria_meningitidis_b_positive'] }}</td></tr>
							<tr><td colspan="2">5.19 Neisseria meningitidis C</td>
								<td colspan="2">{{ $mohData['5_19_neisseria_meningitidis_c_positive'] }}</td></tr>
							<tr><td colspan="2">5.20 Neisseria meningitidis W135</td>
								<td colspan="2">{{ $mohData['5_20_neisseria_meningitidis_w_135_positive'] }}</td></tr>
							<tr><td colspan="2">5.21 Neisseria meningitidis X</td>
								<td colspan="2">{{ $mohData['5_21_neisseria_meningitidis_x_positive'] }}</td></tr>
							<tr><td colspan="2">5.22 Neisseria meningitidis Y</td>
								<td colspan="2">{{ $mohData['5_22_neisseria_meningitidis_y_positive'] }}</td></tr>
							<tr><td colspan="2">5.23 N.meningitidis (indeterminate)</td>
								<td colspan="2">{{ $mohData['5_23_n_meningitidis_indeterminate_positive'] }}</td></tr>
							<tr><td colspan="2">5.24 Streptococcus pneumoniae</td>
								<td colspan="2">{{ $mohData['5_24_streptococcus_pneumoniae_positive'] }}</td></tr>
							<tr><td colspan="2">5.25 Haemophilus influenzae (type b)</td>
								<td colspan="2">{{ $mohData['5_25_haemophilus_influenzae_type_b_positive'] }}</td></tr>
							<tr><td colspan="2">5.26 Cryptococcal Meningitis</td>
								<td colspan="2">{{ $mohData['5_26_cryptococcal_meningitis_positive'] }}</td></tr>

							<tr class="emphasize"><td colspan="4"><center>Bacterial Pathogens from other types of specimen</center></td></tr>
							<tr><td colspan="2">5.27 B. anthracis</td><td colspan="2">{{ $mohData['5_27_b_anthracis_positive'] }}</td></tr>
							<tr><td colspan="2">5.28 Y. pestis</td><td colspan="2">{{ $mohData['5_28_y_pestis_positive'] }}</td></tr>

							<tr><td colspan="4">&nbsp;</td></tr>

							<tr class="emphasize"><td colspan="2">SPUTUM</td><td>Total Exam</td><td>Number Positive</td></tr>

							<tr><td colspan="2">5.29 Total TB smears</td><td>{{ $mohData['5_29_total_tb_smears_total'] }}</td><td>{{ $mohData['5_29_total_tb_smears_positive'] }}</td></tr>
							<tr><td colspan="2">5.30 TB new suspects</td><td>{{ $mohData['5_30_tb_new_suspects_total'] }}</td><td>{{ $mohData['5_30_tb_new_suspects_positive'] }}</td></tr>
							<tr><td colspan="2">5.31 TB Follow up</td><td>{{ $mohData['5_31_tb_follow_up_total'] }}</td><td>{{ $mohData['5_31_tb_follow_up_positive'] }}</td></tr>
							<tr><td colspan="2">5.32 GeneXpert</td><td>{{ $mohData['5_32_geneXpert_total'] }}</td><td>{{ $mohData['5_32_geneXpert_positive'] }}</td></tr>
							<tr><td colspan="2">5.33 MDR TB</td><td>{{ $mohData['5_33_mdr_tb_total'] }}</td><td>{{ $mohData['5_33_mdr_tb_positive'] }}</td></tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="col-xs-7">
				<div class="table-responsive">
					<table class="table table-bordered">
						<thead>
							<tr><th colspan="3"><center>6. HISTOLOGY AND CYTOLOGY</center></th></tr>
							<tr><th>Smears</th><th>Total Exam</th><th>Malignant</th></tr>
						</thead>
						<tbody>
							<tr><td>6.1 PAP smear</td><td>{{ $mohData['6_1_pap_smear_total'] }}</td><td>{{ $mohData['6_1_pap_smear_malignant'] }}</td></tr>
							<tr><td>6.2 Touch preparations</td><td>{{ $mohData['6_2_touch_preparations_total'] }}</td><td>{{ $mohData['6_2_touch_preparations_malignant'] }}</td></tr>
							<tr><td>6.3 Tissue impressions</td><td>{{ $mohData['6_3_tissue_impressions_total'] }}</td><td>{{ $mohData['6_3_tissue_impressions_malignant'] }}</td></tr>

							<tr class="emphasize"><td>Fine Needle Aspirates</td><td>Total Exam</td><td>Malignant</td></tr>
							<tr><td>6.4 Thyroid</td><td>{{ $mohData['6_4_thyroid_total'] }}</td><td>{{ $mohData['6_4_thyroid_malignant'] }}</td></tr>
							<tr><td>6.5 Lymph nodes</td><td>{{ $mohData['6_5_lymph_nodes_total'] }}</td><td>{{ $mohData['6_5_lymph_nodes_malignant'] }}</td></tr>
							<tr><td>6.6 Liver</td><td>{{ $mohData['6_6_liver_total'] }}</td><td>{{ $mohData['6_6_liver_malignant'] }}</td></tr>
							<tr><td>6.7 Breast</td><td>{{ $mohData['6_7_breast_total'] }}</td><td>{{ $mohData['6_7_breast_malignant'] }}</td></tr>
							<tr><td>6.8 Soft tissue masses</td><td>{{ $mohData['6_8_soft_tissue_masses_total'] }}</td><td>{{ $mohData['6_8_soft_tissue_masses_malignant'] }}</td></tr>

							<tr class="emphasize"><td>Fluid Cytology</td><td>Total Exam</td><td>Malignant</td></tr>
							<tr><td>6.9 Ascitic fluid</td><td>{{ $mohData['6_9_ascitic_fluid_total'] }}</td><td>{{ $mohData['6_9_ascitic_fluid_malignant'] }}</td></tr>
							<tr><td>6.10 CSF</td><td>{{ $mohData['6_10_csf_total'] }}</td><td>{{ $mohData['6_10_csf_malignant'] }}</td></tr>
							<tr><td>6.11 Pleural fluid</td><td>{{ $mohData['6_11_pleural_fluid_total'] }}</td><td>{{ $mohData['6_11_pleural_fluid_malignant'] }}</td></tr>
							<tr><td>6.12 Urine</td><td>{{ $mohData['6_12_urine_total'] }}</td><td>{{ $mohData['6_12_urine_malignant'] }}</td></tr>

							<tr class="emphasize"><td>Tissue Histology</td><td>Total Exam</td><td>Malignant</td></tr>
							<tr><td>6.13 Cervix</td><td>{{ $mohData['6_13_cervix_total'] }}</td><td>{{ $mohData['6_13_cervix_malignant'] }}</td></tr>
							<tr><td>6.14 Prostrate</td><td>{{ $mohData['6_14_prostrate_total'] }}</td><td>{{ $mohData['6_14_prostrate_malignant'] }}</td></tr>
							<tr><td>6.15 Breast tissue</td><td>{{ $mohData['6_15_breast_tissue_total'] }}</td><td>{{ $mohData['6_15_breast_tissue_malignant'] }}</td></tr>
							<tr><td>6.16 Ovary</td><td>{{ $mohData['6_16_ovary_total'] }}</td><td>{{ $mohData['6_16_ovary_malignant'] }}</td></tr>
							<tr><td>6.17 Uterus</td><td>{{ $mohData['6_17_uterus_total'] }}</td><td>{{ $mohData['6_17_uterus_malignant'] }}</td></tr>
							<tr><td>6.18 Skin</td><td>{{ $mohData['6_18_skin_total'] }}</td><td>{{ $mohData['6_18_skin_malignant'] }}</td></tr>
							<tr><td>6.19 Head and Neck</td><td>{{ $mohData['6_19_head_and_neck_total'] }}</td><td>{{ $mohData['6_19_head_and_neck_malignant'] }}</td></tr>
							<tr><td>6.20 Dental</td><td>{{ $mohData['6_20_dental_total'] }}</td><td>{{ $mohData['6_20_dental_malignant'] }}</td></tr>
							<tr><td>6.21 GIT</td><td>{{ $mohData['6_21_git_total'] }}</td><td>{{ $mohData['6_21_git_malignant'] }}</td></tr>
							<tr><td>6.22 Lymph nodes tissue</td><td>{{ $mohData['6_22_lymph_node_tissue_total'] }}</td><td>{{ $mohData['6_22_lymph_node_tissue_malignant'] }}</td></tr>

							<tr class="emphasize"><td>Bone Marrow Studies</td><td>Total Exam</td><td>Malignant</td></tr>
							<tr><td>6.23 Bone marrow aspirate</td><td>{{ $mohData['6_23_bone_marrow_aspirate_total'] }}</td><td>{{ $mohData['6_23_bone_marrow_aspirate_malignant'] }}</td></tr>
							<tr><td>6.24 Trephine biopsy</td><td>{{ $mohData['6_24_trephine_biopsy_total'] }}</td><td>{{ $mohData['6_24_trephine_biopsy_malignant'] }}</td></tr>
						</tbody>
					</table>
				</div>

				<div class="table-responsive">
					<table class="table table-bordered">
						<thead>
							<tr><th colspan="3"><center>7. SEROLOGY</center></th></tr>
							<tr>
								<th>Serological Tests</th>
								<th>Total Exam</th>
								<th>Number Positive</th>
							</tr>
						</thead>
						<tbody>
							<tr><td>7.1 VDRL</td><td>{{ $mohData['7_1_vdrl_total'] }}</td><td>{{ $mohData['7_1_vdrl_positive'] }}</td></tr>
							<tr><td>7.2 TPHA</td><td>{{ $mohData['7_2_tpha_total'] }}</td><td>{{ $mohData['7_2_tpha_positive'] }}</td></tr>
							<tr><td>7.3 ASOT</td><td>{{ $mohData['7_3_asot_total'] }}</td><td>{{ $mohData['7_3_asot_positive'] }}</td></tr>
							<tr><td>7.4 HIV</td><td>{{ $mohData['7_4_hiv_total'] }}</td><td>{{ $mohData['7_4_hiv_positive'] }}</td></tr>
							<tr><td>7.5 Brucella</td><td>{{ $mohData['7_5_brucella_total'] }}</td><td>{{ $mohData['7_5_brucella_positive'] }}</td></tr>
							<tr><td>7.6 Rheumatoid Factor</td><td>{{ $mohData['7_6_rheumatoid_factor_total'] }}</td><td>{{ $mohData['7_6_rheumatoid_factor_positive'] }}</td></tr>
							<tr><td>7.7 Helicobacter pylori</td><td>{{ $mohData['7_7_helicobacter_pylori_total'] }}</td><td>{{ $mohData['7_7_helicobacter_pylori_positive'] }}</td></tr>
							<tr><td>7.8 Hepatitis A test</td><td>{{ $mohData['7_8_hepatitis_a_total'] }}</td><td>{{ $mohData['7_8_hepatitis_a_positive'] }}</td></tr>
							<tr><td>7.9 Hepatitis B test</td><td>{{ $mohData['7_9_hepatitis_b_total'] }}</td><td>{{ $mohData['7_9_hepatitis_b_positive'] }}</td></tr>
							<tr><td>7.10 Hepatitis C test</td><td>{{ $mohData['7_10_hepatitis_c_total'] }}</td><td>{{ $mohData['7_10_hepatitis_c_positive'] }}</td></tr>
							<tr><td>7.11 HCG</td><td>{{ $mohData['7_11_hcg_total'] }}</td><td>{{ $mohData['7_11_hcg_positive'] }}</td></tr>
							<tr><td>7.12 CRAG Test</td><td>{{ $mohData['7_12_crag_total'] }}</td><td class="blank-cell"></td></tr>
						</tbody>
					</table>
				</div>

				<div class="table-responsive">
					<table class="table table-bordered">
						<thead>
							<tr><th colspan="3">8. SPECIMEN REFERRAL TO HIGHER LEVELS</th></tr>
							<tr>
								<th>Specimen Referral</th>
								<th>No. of specimen</th>
								<th>No. of results received</th>
							</tr>
						</thead>
						<tbody>
							<tr><td>8.1 CD4</td><td>{{ $mohData['8_1_cd4_specimen_referred_count'] }}</td><td>{{ $mohData['8_1_cd4_referred_results_received_count'] }}</td></tr>
							<tr><td>8.2 Viral load</td><td>{{ $mohData['8_2_viral_load_specimen_referred_count'] }}</td><td>{{ $mohData['8_2_viral_load_referred_results_received_count'] }}</td></tr>
							<tr><td>8.3 EID</td><td>{{ $mohData['8_3_eid_specimen_referred_count'] }}</td><td>{{ $mohData['8_3_eid_referred_results_received_count'] }}</td></tr>
							<tr><td>8.4 Discordant/discrepant</td><td>{{ $mohData['8_4_discordant_specimen_referred_count'] }}</td><td>{{ $mohData['8_4_discordant_referred_results_received_count'] }}</td></tr>
							<tr><td>8.5 TB Culture</td><td>{{ $mohData['8_5_tb_culture_specimen_referred_count'] }}</td><td>{{ $mohData['8_5_tb_culture_referred_results_received_count'] }}</td></tr>
							<tr><td>8.6 Virological</td><td>{{ $mohData['8_6_virological_specimen_referred_count'] }}</td><td>{{ $mohData['8_6_virological_referred_results_received_count'] }}</td></tr>
							<tr><td>8.7 Clinical Chemistry</td><td>{{ $mohData['8_7_clinical_chemistry_specimen_referred_count'] }}</td><td>{{ $mohData['8_7_clinical_chemistry_referred_results_received_count'] }}</td></tr>
							<tr><td>8.8 Histology/cytology</td><td>{{ $mohData['8_8_histology_cytology_specimen_referred_count'] }}</td><td>{{ $mohData['8_8_histology_cytology_referred_results_received_count'] }}</td></tr>
							<tr><td>8.9 Haematological</td><td>{{ $mohData['8_9_haematological_specimen_referred_count'] }}</td><td>{{ $mohData['8_9_haematological_referred_results_received_count'] }}</td></tr>
							<tr><td>8.10 Parasitological</td><td>{{ $mohData['8_10_parasitological_specimen_referred_count'] }}</td><td>{{ $mohData['8_10_parasitological_referred_results_received_count'] }}</td></tr>
							<tr><td>8.11 Blood samples for transfusion screening</td><td>{{ $mohData['8_11_blood_for_transfusion_screening_specimen_referred_count'] }}</td><td>{{ $mohData['8_11_blood_for_transfusion_screening_referred_results_received_count'] }}</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12">
			<div class="table-responsive">
				<table class="table table-bordered">
					<thead>
						<tr>
							<th colspan="21"><center>9. Drug Susceptibility Testing</center></th>
						</tr>
						<tr>
							<th rowspan="2">Drug Sensitivity Pattern</th>
							<th colspan="2">a. Ampicilin</th>
							<th colspan="2">b. Chloramphenicol</th>
							<th colspan="2">c. Ceftriaxone</th>
							<th colspan="2">d. Penicilin</th>
							<th colspan="2">e. Oxacillin</th>
							<th colspan="2">f. Ciprofloxacin</th>
							<th colspan="2">g. Nalidixic acid</th>
							<th colspan="2">h. Trimethoprim-sulphamethoxazole</th>
							<th colspan="2">i. Tetracycline</th>
							<th colspan="2">j. Augumentin</th>
						</tr>
						<tr>
							<th>Sensitive</th><th>Resistant</th><th>Sensitive</th><th>Resistant</th>
							<th>Sensitive</th><th>Resistant</th><th>Sensitive</th><th>Resistant</th>
							<th>Sensitive</th><th>Resistant</th><th>Sensitive</th><th>Resistant</th>
							<th>Sensitive</th><th>Resistant</th><th>Sensitive</th><th>Resistant</th>
							<th>Sensitive</th><th>Resistant</th><th>Sensitive</th><th>Resistant</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>9.1 Haemophilus influenzae</td>
							<td>{{ $mohData['9_1_ampicilin_sensitive'] }}</td><td>{{ $mohData['9_1_amplicilin_resistant'] }}</td><td>{{ $mohData['9_1_chloramphenicol_sensitive'] }}</td><td>{{ $mohData['9_1_chloramphenicol_resistant'] }}</td><td>{{ $mohData['9_1_ceftriaxone_sensitive'] }}</td><td>{{ $mohData['9_1_ceftriaxone_resistant'] }}</td><td>{{ $mohData['9_1_penicilin_sensitive'] }}</td><td>{{ $mohData['9_1_penicilin_resistant'] }}</td><td>{{ $mohData['9_1_oxacillin_sensitive'] }}</td><td>{{ $mohData['9_1_oxacillin_resistant'] }}</td>
							<td>{{ $mohData['9_1_ciprofloxacin_sensitive'] }}</td><td>{{ $mohData['9_1_ciprofloxacin_resistant'] }}</td><td>{{ $mohData['9_1_naladixic_acid_sensitive'] }}</td><td>{{ $mohData['9_1_naladixic_acid_resistant'] }}</td><td>{{ $mohData['9_1_trimethoprim_sensitive'] }}</td><td>{{ $mohData['9_1_trimethoprim_resistant'] }}</td><td>{{ $mohData['9_1_tetracycline_sensitive'] }}</td><td>{{ $mohData['9_1_tetracycline_resistant'] }}</td><td>{{ $mohData['9_1_augumentin_sensitive'] }}</td><td>{{ $mohData['9_1_augumentin_resistant'] }}</td>
						</tr>
						<tr>
							<td>9.2 Neisseria meningitidis</td>
							<td>{{ $mohData['9_2_ampicilin_sensitive'] }}</td><td>{{ $mohData['9_2_amplicilin_resistant'] }}</td><td>{{ $mohData['9_2_chloramphenicol_sensitive'] }}</td><td>{{ $mohData['9_2_chloramphenicol_resistant'] }}</td><td>{{ $mohData['9_2_ceftriaxone_sensitive'] }}</td><td>{{ $mohData['9_2_ceftriaxone_resistant'] }}</td><td>{{ $mohData['9_2_penicilin_sensitive'] }}</td><td>{{ $mohData['9_2_penicilin_resistant'] }}</td><td>{{ $mohData['9_2_oxacillin_sensitive'] }}</td><td>{{ $mohData['9_2_oxacillin_resistant'] }}</td>
							<td>{{ $mohData['9_2_ciprofloxacin_sensitive'] }}</td><td>{{ $mohData['9_2_ciprofloxacin_resistant'] }}</td><td>{{ $mohData['9_2_naladixic_acid_sensitive'] }}</td><td>{{ $mohData['9_2_naladixic_acid_resistant'] }}</td><td>{{ $mohData['9_2_trimethoprim_sensitive'] }}</td><td>{{ $mohData['9_2_trimethoprim_resistant'] }}</td><td>{{ $mohData['9_2_tetracycline_sensitive'] }}</td><td>{{ $mohData['9_2_tetracycline_resistant'] }}</td><td>{{ $mohData['9_2_augumentin_sensitive'] }}</td><td>{{ $mohData['9_2_augumentin_resistant'] }}</td>
						</tr>
						<tr>
							<td>9.3 Streptococcus pneumoniae</td>
							<td>{{ $mohData['9_3_ampicilin_sensitive'] }}</td><td>{{ $mohData['9_3_amplicilin_resistant'] }}</td><td>{{ $mohData['9_3_chloramphenicol_sensitive'] }}</td><td>{{ $mohData['9_3_chloramphenicol_resistant'] }}</td><td>{{ $mohData['9_3_ceftriaxone_sensitive'] }}</td><td>{{ $mohData['9_3_ceftriaxone_resistant'] }}</td><td>{{ $mohData['9_3_penicilin_sensitive'] }}</td><td>{{ $mohData['9_3_penicilin_resistant'] }}</td><td>{{ $mohData['9_3_oxacillin_sensitive'] }}</td><td>{{ $mohData['9_3_oxacillin_resistant'] }}</td>
							<td>{{ $mohData['9_3_ciprofloxacin_sensitive'] }}</td><td>{{ $mohData['9_3_ciprofloxacin_resistant'] }}</td><td>{{ $mohData['9_3_naladixic_acid_sensitive'] }}</td><td>{{ $mohData['9_3_naladixic_acid_resistant'] }}</td><td>{{ $mohData['9_3_trimethoprim_sensitive'] }}</td><td>{{ $mohData['9_3_trimethoprim_resistant'] }}</td><td>{{ $mohData['9_3_tetracycline_sensitive'] }}</td><td>{{ $mohData['9_3_tetracycline_resistant'] }}</td><td>{{ $mohData['9_3_augumentin_sensitive'] }}</td><td>{{ $mohData['9_3_augumentin_resistant'] }}</td>
						</tr>
						<tr>
							<td>9.4 Salmonella serotype Typhi</td>
							<td>{{ $mohData['9_4_ampicilin_sensitive'] }}</td><td>{{ $mohData['9_4_amplicilin_resistant'] }}</td><td>{{ $mohData['9_4_chloramphenicol_sensitive'] }}</td><td>{{ $mohData['9_4_chloramphenicol_resistant'] }}</td><td>{{ $mohData['9_4_ceftriaxone_sensitive'] }}</td><td>{{ $mohData['9_4_ceftriaxone_resistant'] }}</td><td>{{ $mohData['9_4_penicilin_sensitive'] }}</td><td>{{ $mohData['9_4_penicilin_resistant'] }}</td><td>{{ $mohData['9_4_oxacillin_sensitive'] }}</td><td>{{ $mohData['9_4_oxacillin_resistant'] }}</td>
							<td>{{ $mohData['9_4_ciprofloxacin_sensitive'] }}</td><td>{{ $mohData['9_4_ciprofloxacin_resistant'] }}</td><td>{{ $mohData['9_4_naladixic_acid_sensitive'] }}</td><td>{{ $mohData['9_4_naladixic_acid_resistant'] }}</td><td>{{ $mohData['9_4_trimethoprim_sensitive'] }}</td><td>{{ $mohData['9_4_trimethoprim_resistant'] }}</td><td>{{ $mohData['9_4_tetracycline_sensitive'] }}</td><td>{{ $mohData['9_4_tetracycline_resistant'] }}</td><td>{{ $mohData['9_4_augumentin_sensitive'] }}</td><td>{{ $mohData['9_4_augumentin_resistant'] }}</td>
						</tr>
						<tr>
							<td>9.5 Shigella</td>
							<td>{{ $mohData['9_5_ampicilin_sensitive'] }}</td><td>{{ $mohData['9_5_amplicilin_resistant'] }}</td><td>{{ $mohData['9_5_chloramphenicol_sensitive'] }}</td><td>{{ $mohData['9_5_chloramphenicol_resistant'] }}</td><td>{{ $mohData['9_5_ceftriaxone_sensitive'] }}</td><td>{{ $mohData['9_5_ceftriaxone_resistant'] }}</td><td>{{ $mohData['9_5_penicilin_sensitive'] }}</td><td>{{ $mohData['9_5_penicilin_resistant'] }}</td><td>{{ $mohData['9_5_oxacillin_sensitive'] }}</td><td>{{ $mohData['9_5_oxacillin_resistant'] }}</td>
							<td>{{ $mohData['9_5_ciprofloxacin_sensitive'] }}</td><td>{{ $mohData['9_5_ciprofloxacin_resistant'] }}</td><td>{{ $mohData['9_5_naladixic_acid_sensitive'] }}</td><td>{{ $mohData['9_5_naladixic_acid_resistant'] }}</td><td>{{ $mohData['9_5_trimethoprim_sensitive'] }}</td><td>{{ $mohData['9_5_trimethoprim_resistant'] }}</td><td>{{ $mohData['9_5_tetracycline_sensitive'] }}</td><td>{{ $mohData['9_5_tetracycline_resistant'] }}</td><td>{{ $mohData['9_5_augumentin_sensitive'] }}</td><td>{{ $mohData['9_5_augumentin_resistant'] }}</td>
						</tr>
						<tr>
							<td>9.6 Vibrio cholerae</td>
							<td>{{ $mohData['9_6_ampicilin_sensitive'] }}</td><td>{{ $mohData['9_6_amplicilin_resistant'] }}</td><td>{{ $mohData['9_6_chloramphenicol_sensitive'] }}</td><td>{{ $mohData['9_6_chloramphenicol_resistant'] }}</td><td>{{ $mohData['9_6_ceftriaxone_sensitive'] }}</td><td>{{ $mohData['9_6_ceftriaxone_resistant'] }}</td><td>{{ $mohData['9_6_penicilin_sensitive'] }}</td><td>{{ $mohData['9_6_penicilin_resistant'] }}</td><td>{{ $mohData['9_6_oxacillin_sensitive'] }}</td><td>{{ $mohData['9_6_oxacillin_resistant'] }}</td>
							<td>{{ $mohData['9_6_ciprofloxacin_sensitive'] }}</td><td>{{ $mohData['9_6_ciprofloxacin_resistant'] }}</td><td>{{ $mohData['9_6_naladixic_acid_sensitive'] }}</td><td>{{ $mohData['9_6_naladixic_acid_resistant'] }}</td><td>{{ $mohData['9_6_trimethoprim_sensitive'] }}</td><td>{{ $mohData['9_6_trimethoprim_resistant'] }}</td><td>{{ $mohData['9_6_tetracycline_sensitive'] }}</td><td>{{ $mohData['9_6_tetracycline_resistant'] }}</td><td>{{ $mohData['9_6_augumentin_sensitive'] }}</td><td>{{ $mohData['9_6_augumentin_resistant'] }}</td>
						</tr>
						<tr>
							<td>9.7 B. anthracis</td>
							<td>{{ $mohData['9_7_ampicilin_sensitive'] }}</td><td>{{ $mohData['9_7_amplicilin_resistant'] }}</td><td>{{ $mohData['9_7_chloramphenicol_sensitive'] }}</td><td>{{ $mohData['9_7_chloramphenicol_resistant'] }}</td><td>{{ $mohData['9_7_ceftriaxone_sensitive'] }}</td><td>{{ $mohData['9_7_ceftriaxone_resistant'] }}</td><td>{{ $mohData['9_7_penicilin_sensitive'] }}</td><td>{{ $mohData['9_7_penicilin_resistant'] }}</td><td>{{ $mohData['9_7_oxacillin_sensitive'] }}</td><td>{{ $mohData['9_7_oxacillin_resistant'] }}</td>
							<td>{{ $mohData['9_7_ciprofloxacin_sensitive'] }}</td><td>{{ $mohData['9_7_ciprofloxacin_resistant'] }}</td><td>{{ $mohData['9_7_naladixic_acid_sensitive'] }}</td><td>{{ $mohData['9_7_naladixic_acid_resistant'] }}</td><td>{{ $mohData['9_7_trimethoprim_sensitive'] }}</td><td>{{ $mohData['9_7_trimethoprim_resistant'] }}</td><td>{{ $mohData['9_7_tetracycline_sensitive'] }}</td><td>{{ $mohData['9_7_tetracycline_resistant'] }}</td><td>{{ $mohData['9_7_augumentin_sensitive'] }}</td><td>{{ $mohData['9_7_augumentin_resistant'] }}</td>
						</tr>
						<tr>
							<td>9.8 Y. pestis</td>
							<td>{{ $mohData['9_8_ampicilin_sensitive'] }}</td><td>{{ $mohData['9_8_amplicilin_resistant'] }}</td><td>{{ $mohData['9_8_chloramphenicol_sensitive'] }}</td><td>{{ $mohData['9_8_chloramphenicol_resistant'] }}</td><td>{{ $mohData['9_8_ceftriaxone_sensitive'] }}</td><td>{{ $mohData['9_8_ceftriaxone_resistant'] }}</td><td>{{ $mohData['9_8_penicilin_sensitive'] }}</td><td>{{ $mohData['9_8_penicilin_resistant'] }}</td><td>{{ $mohData['9_8_oxacillin_sensitive'] }}</td><td>{{ $mohData['9_8_oxacillin_resistant'] }}</td>
							<td>{{ $mohData['9_8_ciprofloxacin_sensitive'] }}</td><td>{{ $mohData['9_8_ciprofloxacin_resistant'] }}</td><td>{{ $mohData['9_8_naladixic_acid_sensitive'] }}</td><td>{{ $mohData['9_8_naladixic_acid_resistant'] }}</td><td>{{ $mohData['9_8_trimethoprim_sensitive'] }}</td><td>{{ $mohData['9_8_trimethoprim_resistant'] }}</td><td>{{ $mohData['9_8_tetracycline_sensitive'] }}</td><td>{{ $mohData['9_8_tetracycline_resistant'] }}</td><td>{{ $mohData['9_8_augumentin_sensitive'] }}</td><td>{{ $mohData['9_8_augumentin_resistant'] }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="row emphasize" style="margin-top: 20px;margin-bottom: : 20px;">
		<div class="col-xs-4">Report compiled by: COMPILED_BY</div>
		<div class="col-xs-3">Designation: REPORT_DESIGNATION</div>
		<div class="col-xs-2">Date: REPORT_DATE</div>
		<div class="col-xs-3">Signature: REPORT_SIGNATURE</div>
	</div>
</div>

@stop
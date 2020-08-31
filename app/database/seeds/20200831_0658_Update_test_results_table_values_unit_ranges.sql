UPDATE test_results tr 
JOIN tests t ON tr.test_id = t.id 
JOIN test_types tt ON t.test_type_id = tt.id 
JOIN measures m ON tr.measure_id = m.id 
JOIN visits v ON t.visit_id = v.id 
JOIN patients p ON v.patient_id = p.id 
LEFT JOIN measure_ranges mr 
	ON m.id = mr.measure_id 
	AND (mr.gender = 2 OR mr.gender = p.gender) 
	AND (mr.age_min <= datediff(v.created_at, p.dob)/365.25 
	AND mr.age_max >= datediff(v.created_at, p.dob)/365.25) 
SET tr.unit = m.unit, tr.range_lower = mr.range_lower, tr.range_upper = mr.range_upper;
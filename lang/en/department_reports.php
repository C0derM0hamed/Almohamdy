<?php

return [
    'title' => 'Department reports', 'filters' => 'Report filters', 'from' => 'From', 'to' => 'To', 'employee' => 'Employee', 'period' => 'Period', 'all' => 'All', 'show' => 'Show report', 'pdf' => 'PDF',
    'date' => 'Date', 'period_label' => 'Period', 'creator' => 'Entered by', 'attachment' => 'Attachment', 'report_count' => 'Reports', 'detail_count' => 'Details',
    'no_records' => 'No records match the selected filters.', 'period_summary' => 'Period: :from to :to',
    'departments' => [
        'collection' => ['title' => 'Collection department report', 'subtitle' => 'Collection indicators, financial claims, and recorded details for the selected period.'],
        'legal' => ['title' => 'Legal affairs department report', 'subtitle' => 'Legal case indicators and recorded details for the selected period.'],
    ],
    'summary' => [
        'patients_remaining_count' => 'Inpatient patients with balances', 'outpatient_staff_deficit_count' => 'Outpatient staff deficits', 'emergency_staff_deficit_count' => 'Emergency staff deficits',
        'inpatient_office_staff_deficit_count' => 'Inpatient office staff deficits', 'directors_deficit_count' => 'On-duty director deficits', 'inpatient_remaining_total' => 'Total inpatient balances',
        'outpatient_revenue_deficit_total' => 'Outpatient revenue deficit', 'emergency_revenue_deficit_total' => 'Emergency revenue deficit', 'inpatient_office_revenue_deficit_total' => 'Inpatient office revenue deficit',
        'emergency_managers_deficit_total' => 'Emergency managers deficit', 'cases_filed_in_court' => 'Cases filed in enforcement court', 'najiz_requests' => 'Najiz enforcement requests', 'pending_cases' => 'Pending cases',
    ],
    'sections' => [
        'revenue_deficit' => 'Revenue deficits', 'owed_amount' => 'Amounts owed by inpatients', 'no_pledge' => 'Patients without payment commitment', 'bond_signed' => 'Patients with signed promissory notes', 'previous_paid' => 'Previous paid financial claims',
        'general_court' => 'Cases filed in the general court', 'pending_claims' => 'Pending cases', 'government_cases' => 'Claims or objections against government decisions or judgments',
    ],
    'columns' => [
        'employee' => 'Employee', 'department' => 'Department', 'financial_deficit' => 'Financial deficit', 'action' => 'Action', 'notes' => 'Notes', 'file_number' => 'File number', 'room' => 'Room', 'amount' => 'Amount', 'reason' => 'Reason', 'other_reason' => 'Other reason', 'action_notes' => 'Action details', 'admission_date' => 'Admission date/time', 'hospitalization_location' => 'Hospitalization location', 'patient_name' => 'Patient name', 'patient_id' => 'Patient ID', 'payer_name' => 'Payer name', 'payer_id' => 'Payer ID', 'bond_amount' => 'Bond amount', 'invoice_total' => 'Invoice total', 'paid_amount' => 'Paid amount', 'remaining_amount' => 'Remaining amount', 'invoice_number' => 'Invoice number', 'defendant_type' => 'Defendant type', 'judicial_department' => 'Judicial department', 'request_type' => 'Request type', 'request_number' => 'Request number', 'case_number' => 'Case number', 'session_date' => 'Session date', 'respondent' => 'Respondent', 'respondent_id' => 'Respondent ID', 'orders' => 'Orders', 'objection_authority' => 'Objection authority',
    ],
];

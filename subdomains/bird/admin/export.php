<?php

/*
 * RIVERVIEW INSTITUTE — APPLICANT DATA EXPORT ENDPOINT
 * Export unencrypted applicant records.
 */

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=admissions_master_export_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'application_id',
    'first_name',
    'last_name',
    'date_of_birth',
    'ssn_tax_id',
    'email',
    'phone',
    'home_address',
    'program_applied',
    'high_school_gpa',
    'sat_act_score',
    'household_income_usd',
    'scholarship_granted',
    'application_fee_card_last4',
    'internal_notes'
]);

$records = [
    ['RV-2026-1049', 'Johnathan', 'Vance', '2005-04-12', '219-45-8812', 'j.vance@inbox.test', '+1-555-234-8901', '742 Evergreen Terrace, Springfield, OR 97477', 'B.Sc. Computer Science', '3.85', '1490', '62400', '18500', '4821', 'Transcript and tax forms verified'],
    ['RV-2026-1052', 'Elena', 'Rostova', '2002-11-28', 'LV-948210385', 'elena.rostova@mailservice.test', '+371-28-910-234', 'Brivibas iela 45, Riga, LV-1010', 'M.Sc. Cybersecurity', '3.92', '168Q', '30200', '42000', '9012', 'Visa clearance verified'],
    ['RV-2026-1088', 'Marcus', 'Sterling', '2005-08-19', '184-90-3419', 'm.sterling@campusnet.test', '+1-555-492-1084', '1240 Bayview Dr, Seattle, WA 98101', 'B.Eng. Mechanical Eng', '3.98', '1560', '195000', '50000', '1182', 'Presidential fellow candidate'],
    ['RV-2026-1104', 'Sophia', 'Morales', '2006-01-05', '392-18-5041', 'sophia.m@outlook.test', '+1-555-782-9912', '801 Colorado Blvd, Denver, CO 80206', 'B.A. Digital Media', '3.65', '1380', '48000', '12000', '3349', 'Tax doc follow-up requested'],
    ['RV-2026-1120', 'David', 'Kim', '2005-06-22', '409-77-6218', 'david.kim99@webmail.test', '+1-555-601-3829', '312 Oakcrest Ave, Austin, TX 78704', 'B.Sc. Data Science', '3.78', '1440', '88000', '8500', '7721', 'Honors mathematics stream']
];

foreach ($records as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;

<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$rows = [
    ['Selam Tesfaye',  'Residential Buyer, Bole',      'Haile Estate made finding our dream home in Addis Ababa effortless. Their knowledge of the Bole market is unmatched, and they guided us through every step with patience and professionalism. We could not be happier with our new home.',                                                                          5, 1],
    ['Dawit Bekele',   'Commercial Investor',           'I have worked with many real estate firms across East Africa, but Haile Estate stands apart. Their investment advisory team identified an opportunity in CMC that has already delivered 18% capital growth in under two years. Exceptional service.',                                                              5, 2],
    ['Meron Alemu',    'Expat Relocation, Kazanchis',   'Moving to Addis Ababa from London was daunting, but Haile Estate made the transition seamless. They understood exactly what we needed and found us a stunning apartment in Kazanchis within two weeks. Truly a world-class service.',                                                                           5, 3],
    ['Yonas Girma',    'Property Seller, Entoto',       'Haile Estate sold our Entoto property at a price that exceeded our expectations. Their marketing approach, attention to detail, and negotiation skills are second to none. I would recommend them without hesitation to anyone serious about property.',                                                          5, 4],
    ['Tigist Hailu',   'First-Time Buyer',              'As a first-time buyer I was nervous about the process, but the team at Haile Estate were incredibly supportive. They explained everything clearly, never rushed me, and helped me secure a beautiful townhouse in Bole Medhanialem. Outstanding.',                                                              4, 5],
    ['Abebe Worku',    'Portfolio Investor',            'Haile Estate manages three of my investment properties and the results speak for themselves. Occupancy rates are consistently above 95% and the reporting is transparent and thorough. They treat my portfolio as if it were their own.',                                                                        5, 6],
];

$stmt = db()->prepare('INSERT INTO testimonials (name, person_title, body, rating, display_order, status) VALUES (?,?,?,?,?,?)');
foreach ($rows as $r) {
    $stmt->execute([$r[0], $r[1], $r[2], $r[3], $r[4], 'active']);
}

echo 'Done — ' . count($rows) . ' testimonials inserted. Delete this file now.';

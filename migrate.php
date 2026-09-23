<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
$db = db();

$db->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS testimonial text DEFAULT NULL AFTER description");
$db->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS person_title varchar(150) DEFAULT NULL AFTER testimonial");
$db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS favorite tinyint(1) NOT NULL DEFAULT 0");

$testimonials = [
    [16, 'The design exceeded our expectations — editorial clarity and visual storytelling at its finest.', 'Head of Brand'],
    [17, 'Outstanding work that truly reflects the quality and professionalism of our organization.',       'Director'],
];

foreach ($testimonials as [$id, $quote, $title]) {
    $db->prepare("UPDATE clients SET testimonial = ?, person_title = ? WHERE id = ?")
       ->execute([$quote, $title, $id]);
}

// Show results
$rows = db()->query('SELECT id, name, testimonial, person_title FROM clients')->fetchAll();
echo '<pre>';
foreach ($rows as $r) {
    echo "ID:{$r['id']} | name: [{$r['name']}] | testimonial: [" . substr($r['testimonial'] ?? '', 0, 40) . "] | title: [{$r['person_title']}]\n";
}
echo '</pre>Done.';


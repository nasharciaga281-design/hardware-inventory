<?php




$pageTitle  = $pageTitle  ?? "Hardware Management System";
$pageStyles = $pageStyles ?? [];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> | Hardware Management System</title>


<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/components.css">


<?php foreach ($pageStyles as $style): ?>
<link rel="stylesheet" href="../css/<?= htmlspecialchars($style) ?>.css">
<?php endforeach; ?>
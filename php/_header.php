<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .query-box { background: #181c2f; color: #fff; border-radius: 1rem; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 4px 16px rgba(24,28,47,0.10); }
        .query-title { font-size: 1rem; font-weight: 600; color: #7dd3fc; }
        .query-sql { font-family: 'Fira Mono', monospace; font-size: 0.95rem; color: #a5b4fc; background: #23263a; border-radius: 0.5rem; padding: 0.5rem; margin: 0.5rem 0; }
        .query-badge { background: #6366f1; color: #fff; border-radius: 0.5rem; font-size: 0.85rem; padding: 0.2rem 0.7rem; margin-right: 0.5rem; }
        .main-content { min-height: 100vh; }
        .table thead { background: #6366f1; color: #fff; }
        .table-striped > tbody > tr:nth-of-type(odd) { background-color: #f3f4f6; }
        .side-panel { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; border-radius: 1rem; padding: 2rem 1.5rem; min-height: 80vh; }
        .side-panel h3 { color: #fff; }
    </style>
</head>
<body>
    <div class="container-fluid main-content py-4">
        <div class="row g-4 align-items-start">

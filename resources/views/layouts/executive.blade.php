<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Marketing Insights | Dashboard Ejecutivo')</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons & Charts -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/executive-dashboard.css') }}">
    @stack('styles')
</head>
<body>
    <div class="dashboard-container">
        @yield('content')
    </div>

    <script src="{{ asset('js/dashboard-charts.js') }}"></script>
    <script src="{{ asset('js/entregables-filter.js') }}"></script>
    <script>
        lucide.createIcons();
        function toggleTheme() {
            document.body.classList.toggle('light-mode');
        }
    </script>
    @stack('scripts')
</body>
</html>

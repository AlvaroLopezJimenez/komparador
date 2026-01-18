<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Test</title>
</head>
<body>
    <h1>Dashboard Test - Sin Layout</h1>
    <p>Usuario: {{ auth()->user()->name }}</p>
    <p>Total Avisos: {{ $totalAvisos }}</p>
    
    <!-- Contenido básico del dashboard -->
    <div>
        <h2>Sección Productos</h2>
        <a href="{{ route('admin.productos.index') }}">Gestionar productos</a><br>
        <a href="{{ route('admin.productos.create') }}">Añadir producto</a>
    </div>
    
    <div>
        <h2>Sección Clicks</h2>
        <a href="{{ route('admin.clicks.dashboard') }}">Ver clicks</a>
    </div>
    
    <p>Si ves esto correctamente, el problema está en el layout app-layout</p>
</body>
</html>

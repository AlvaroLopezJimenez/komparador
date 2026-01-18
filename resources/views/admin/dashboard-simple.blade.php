<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Simple</title>
</head>
<body>
    <h1>Dashboard Simple Funcionando</h1>
    <p>Usuario: {{ auth()->user()->name }}</p>
    <p>Total Avisos: {{ $totalAvisos }}</p>
    <p>Si ves esto, el problema está en el layout o componentes de la vista original</p>
</body>
</html>

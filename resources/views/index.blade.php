<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://kit.fontawesome.com/d615c16483.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="/others/logo.png">
    <title>TaskMgr</title>
</head>

<body>
    <div id="app"></div>

    @vite(['resources/js/app.ts', 'resources/css/app.css'])
    <!-- loading script with vite blade directive -->
</body>


</html>

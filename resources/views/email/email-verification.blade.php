<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    <h3>Email verification.</h3>
    <p>Your email</p> {{ $user->email }}
    <a target="_blank" href="{{ route('validEmail', ['token' => $user->remember_token]) }}">Click here</a>
</body>

</html>

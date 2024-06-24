<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title></title>
</head>
<body style="background:#ccc">
<div style="margin:auto; width:50%; background:white;">
    <h2 style="text-align:center">Welcome {{ $name }}!</h2>
    <h3 style="text-align:center">Please verify your acount by clikcing the link below. Thanks!</h3>
    <a style="color: blue; text-decoration:none;" href="{{url('/')}}/api/emailReg/{{ $user_id }}"><h3 style="text-align:center">Activation link (click me)</h3></a>
</div>
</body>
</html>

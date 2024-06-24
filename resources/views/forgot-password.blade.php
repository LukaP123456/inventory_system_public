<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <!-- CSS only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">

    <link rel="stylesheet"  type="text/css" href="{{ mix('css/app.css') }}">

    <title>Forgot password</title>
</head>
<body>
{{--Navbar start--}}
<nav class="navbar navbar-expand-lg navbar-black bg-black">
    <a class="navbar-brand text-white ml-auto"  href="">LV inventory</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
        <div class="navbar-nav">
        </div>
    </div>
</nav>
{{--Navbar end--}}
{{--Form start--}}
<br><br>
<div class="card mt-5">
    <div class="card-body">
        <form action="http://127.0.0.1:8000/api/forgot-password" method="POST">
            @csrf
            @if(session('status'))
                <div class="alert alert-success">
                    {{session('status')}}
                </div>
            @endif
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Enter your email address into the field below, and we
                    will email you with the password
                    reset link</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Email"
                       aria-describedby="emailHelp">
                <span class="text-danger">@error('email'){{ $message }} @enderror</span>
            </div>
            <button type="submit" class="btn btn-primary">Send password link</button>
        </form>
    </div>
</div>
{{--Form end--}}
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<section class="section footer bg-dark text-white mt-5 p-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h6>Company information</h6>
                <hr/>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Asperiores aut consectetur culpa
                    debitis delectus, ipsum nobis non optio possimus quos temporibus voluptates voluptatum!
                    Aspernatur beatae pariatur placeat repudiandae rerum sequi!</p>
            </div>
            <div class="col-md-4">
                <h6>Quick links</h6>
                <hr/>
                <div><a href="http://localhost:3000/">Home</a></div>
                <div><a href="http://localhost:3000/about">About</a></div>
            </div>
            <div class="col-md-4">
                <h6>Contact information</h6>
                <hr/>
                <div><p class="text-white mb-1">Marka Oreskovica 12</p></div>
                <div><p class="text-white mb-1">+123 456 789</p></div>
                <div><p class="text-white mb-1">LVInventory@gmail.com</p></div>
            </div>
        </div>
    </div>
</section>
</body>
</html>

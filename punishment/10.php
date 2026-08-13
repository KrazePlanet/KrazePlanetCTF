<?php
if(isset($_GET["fname"]) && isset($_GET["lname"])){
    $re = str_replace('<', ';', $_GET['fname']);
    echo $re;
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VexiumCTF</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
</head>
<body>

    <!-- navbar -->
  <nav class="navbar navbar-expand-md navbar-dark"
    style="background-color: rgb(58, 63, 68); --darkreader-inline-bgcolor:#2f3335;" data-darkreader-inline-bgcolor="">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" style="color: rgb(107, 189, 69);"
              href="/">VexiumCTF</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="../../about">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="../../contact">Contact Us</a>
          </li>
        </ul>
        <form class="d-flex" role="search">
          <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
          <button class="btn btn-outline-success" type="submit">Search</button>
        </form>
      </div>
    </div>
  </nav>

  <!-- Accordion -->
  <div class="card mt-3" style="width: 80%; margin-left: 10%; border-radius: 26px;">
    <div class="card-header text-center">
      Backend Source Code
    </div>
    <div class="card-body">
<pre>
if(isset($_GET["fname"]) && isset($_GET["lname"])){
    $re = str_replace('<', ';', $_GET['fname']);
    echo $re;
}
</pre>
    </div>
  </div>

<!-- input fields -->
  <div class="mt-3" style="width: 40%; margin-left: 10%;">
    <form action="" method="get">
      <label for="exampleFormControlTextarea1" class="form-label mt-3 mb-1">First Name</label>
      <input class="form-control" type="text" placeholder="Enter input" aria-label="default input example" name="fname">
      <label for="exampleFormControlTextarea1" class="form-label mt-3 mb-1">Last Name</label>
      <input class="form-control" type="text" placeholder="Enter input" aria-label="default input example" name="lname">
      <input type="submit" hidden />
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.5/dist/umd/popper.min.js"
    integrity="sha384-Xe+8cL9oJa6tN/veChSP7q+mnSPaj5Bcu9mPX5F5xIGE0DVittaqT5lorf0EI7Vk"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.min.js"
    integrity="sha384-ODmDIVzN+pFdexxHEHFBQH3/9/vQ9uori45z4JjnFsRydbmQbmL5t1tQ0culUzyK"
    crossorigin="anonymous"></script>
</body>
</html>
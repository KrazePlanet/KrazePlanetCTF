<?php
if(isset($_GET["fname"]) && isset($_GET["lname"])){
  echo htmlspecialchars($_GET["fname"], ENT_QUOTES);
  echo htmlspecialchars($_GET["lname"], ENT_QUOTES);
}
elseif(isset($_GET["q"])){
    echo htmlspecialchars($_GET["q"], ENT_QUOTES);
}
elseif(isset($_GET["s"])){
    echo htmlspecialchars($_GET["s"], ENT_QUOTES);
}
elseif(isset($_GET["search"])){
    echo htmlspecialchars($_GET["search"], ENT_QUOTES);
}
elseif(isset($_GET["lang"])){
    echo htmlspecialchars($_GET["lang"], ENT_QUOTES);
}
elseif(isset($_GET["keyword"])){
    echo htmlspecialchars($_GET["keyword"], ENT_QUOTES);
}
elseif(isset($_GET["query"])){
    echo htmlspecialchars($_GET["query"], ENT_QUOTES);
}
elseif(isset($_GET["page"])){
    echo htmlspecialchars($_GET["page"], ENT_QUOTES);
}
elseif(isset($_GET["keywords"])){
    echo htmlspecialchars($_GET["keywords"], ENT_QUOTES);
}
elseif(isset($_GET["year"])){
    echo htmlspecialchars($_GET["year"], ENT_QUOTES);
}
elseif(isset($_GET["view"])){
    echo htmlspecialchars($_GET["view"], ENT_QUOTES);
}
elseif(isset($_GET["email"])){
    echo htmlspecialchars($_GET["email"], ENT_QUOTES);
}
elseif(isset($_GET["type"])){
    echo htmlspecialchars($_GET["type"], ENT_QUOTES);
}
elseif(isset($_GET["name"])){
    echo htmlspecialchars($_GET["name"], ENT_QUOTES);
}
elseif(isset($_GET["p"])){
    echo htmlspecialchars($_GET["p"], ENT_QUOTES);
}
elseif(isset($_GET["callback"])){
    echo htmlspecialchars($_GET["callback"], ENT_QUOTES);
}
elseif(isset($_GET["jsonp"])){
    echo htmlspecialchars($_GET["jsonp"], ENT_QUOTES);
}
elseif(isset($_GET["api_key"])){
    echo htmlspecialchars($_GET["api_key"], ENT_QUOTES);
}
elseif(isset($_GET["api"])){
    echo htmlspecialchars($_GET["api"], ENT_QUOTES);
}
elseif(isset($_GET["password"])){
    echo htmlspecialchars($_GET["password"], ENT_QUOTES);
}
elseif(isset($_GET["email"])){
    echo htmlspecialchars($_GET["email"], ENT_QUOTES);
}
elseif(isset($_GET["emailto"])){
    echo htmlspecialchars($_GET["emailto"], ENT_QUOTES);
}
elseif(isset($_GET["token"])){
    echo htmlspecialchars($_GET["token"], ENT_QUOTES);
}
elseif(isset($_GET["username"])){
    echo htmlspecialchars($_GET["username"], ENT_QUOTES);
}
elseif(isset($_GET["csrf_token"])){
    echo htmlspecialchars($_GET["csrf_token"], ENT_QUOTES);
}
elseif(isset($_GET["unsubscribe_token"])){
    echo htmlspecialchars($_GET["unsubscribe_token"], ENT_QUOTES);
}
elseif(isset($_GET["id"])){
    echo htmlspecialchars($_GET["id"], ENT_QUOTES);
}
elseif(isset($_GET["item"])){
    echo htmlspecialchars($_GET["item"], ENT_QUOTES);
}
elseif(isset($_GET["page_id"])){
    echo htmlspecialchars($_GET["page_id"], ENT_QUOTES);
}
elseif(isset($_GET["month"])){
    echo htmlspecialchars($_GET["month"], ENT_QUOTES);
}
elseif(isset($_GET["immagine"])){
    echo htmlspecialchars($_GET["immagine"], ENT_QUOTES);
}
elseif(isset($_GET["list_type"])){
    echo htmlspecialchars($_GET["list_type"], ENT_QUOTES);
}
elseif(isset($_GET["url"])){
    echo htmlspecialchars($_GET["url"], ENT_QUOTES);
}
elseif(isset($_GET["terms"])){
    echo htmlspecialchars($_GET["terms"], ENT_QUOTES);
}
elseif(isset($_GET["categoryid"])){
    echo htmlspecialchars($_GET["categoryid"], ENT_QUOTES);
}
elseif(isset($_GET["key"])){
    echo htmlspecialchars($_GET["key"], ENT_QUOTES);
}
elseif(isset($_GET["l"])){
    echo htmlspecialchars($_GET["l"], ENT_QUOTES);
}
elseif(isset($_GET["begindate"])){
    echo htmlspecialchars($_GET["begindate"], ENT_QUOTES);
}
elseif(isset($_GET["enddate"])){
    echo htmlspecialchars($_GET["enddate"], ENT_QUOTES);
}
elseif(isset($_GET["ll"])){
    $arr = array('details','alert','confirm','prompt','eval','details','ontoggle','onmousemove','onmouseover','script','Script','sCript','scRipt','scrIpt','scriPt','scripT','SCript','SCRipt','SCRIpt','SCRIPt','SCRIPT','script','img','image','svg','onfocus');
    $re = str_replace($arr, '', $_GET['ll']);
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
    echo htmlspecialchars($_GET["fname"], ENT_QUOTES);
    echo htmlspecialchars($_GET["lname"], ENT_QUOTES);
}
# use arjun tool to find hidden parameter
# hint: you already found the parameter in lab no. 2
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

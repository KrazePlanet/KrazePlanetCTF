<?php
$name = (isset($_POST["name"])) ? $_POST["name"] : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POST XSS in Input Tag Value</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="margin-top:20px;">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <?php if($name){ ?>
                <div class="alert alert-success">
                    <p class="text-center">Hello, <input value="<?php echo $name; ?>"></p>
                </div>
            <?php }else{ ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="panel-title">What's your name?</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3"><label>Name:</label></div>
                            <div class="mb-3"><input class="form-control" name="name"></div>
                            <div><input type="submit" class="btn btn-success float-end" value="Enter"></div>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

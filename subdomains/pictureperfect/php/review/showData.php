<?php
require ('../registration/dbcon.php');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $sql="SELECT * FROM review";
  $result=mysqli_query($conn,$sql);
  $total=mysqli_num_rows($result);
  $rows = array();
  while($r = mysqli_fetch_assoc($result)) {
    $r['total'] = $total;
    $rows[] = $r;
  }

  $response = array('success' => true, 'data' => $rows);
  header('Content-type: application/json');
  echo json_encode($response);
}

?>
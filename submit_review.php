<?php
require 'includes/common.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('location:index.php'); exit; }
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $seller = (int)$_POST['seller_id'];
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($con, $_POST['comment']);
    mysqli_query($con, "INSERT INTO seller_reviews (reviewer_id,seller_id,rating,comment) VALUES({$_SESSION['user_id']},$seller,$rating,'$comment')") or die(mysqli_error($con));
    header('location:profile.php?msg=reviewed'); exit;
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>รีวิวผู้ขาย</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"></head><body>
<?php include 'includes/header_menu.php'; ?>
<div class="container my-4">
  <h3>รีวิวผู้ขาย</h3>
  <form method="post">
    <div class="form-group"><label>Seller ID</label><input name="seller_id" class="form-control" required></div>
    <div class="form-group"><label>Rating (1-5)</label><input type="number" name="rating" min="1" max="5" class="form-control" required></div>
    <div class="form-group"><label>Comment</label><textarea name="comment" class="form-control"></textarea></div>
    <button class="btn btn-primary">ส่งรีวิว</button>
  </form>
</div></body></html>
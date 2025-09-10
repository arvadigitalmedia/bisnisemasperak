<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $weburl;?>img/<?= $favicon;?>" />
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Reset Password</title>

    <!-- Bootstrap Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
</head>

<body>
	<div class="container-fluid p-md-5 p-3 text-center">
	  <!-- Content here -->
	  <div class="row mt-3 align-items-center">
		  <?php
		  if (isset($_POST['username']) && validemail($_POST['username'])) {
		  	$datamember = db_row("SELECT * FROM `sa_member` WHERE `mem_email`='".cek($_POST['username'])."'");
		  	if (isset($datamember['mem_id'])) {
		  		$kode = randomword(6);
		  		db_query("UPDATE `sa_member` SET `mem_confirm`='".$kode."' WHERE `mem_id`=".$datamember['mem_id']);
		  		# Kirim Email Konfirmasi
		  		$judul_email_validasi = 'Konfirmasi Reset Password';
		  		$isi_email_validasi = '<p>Seseorang ingin melakukan reset password pada akun anda di '.$weburl.'.'."\n".
		  		'Jika itu adalah anda, silahkan klik link validasi di bawah ini:</p>'."\n".
		  		'<p><a href="'.$weburl.'reset?confirm='.$kode.'">'.$weburl.'reset?confirm='.$kode.'</a></p>';
		  		smtpmailer($datamember['mem_email'],$judul_email_validasi,$isi_email_validasi);
		  		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
					  <strong>Ok!</strong> Silahkan cek inbox email anda untuk konfirmasi reset password.
					  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>';
		  	}
		  } elseif (isset($_GET['confirm']) && strlen($_GET['confirm']) == 6) {
		  	$datamember = db_row("SELECT * FROM `sa_member` WHERE `mem_confirm`='".cek($_GET['confirm'])."'");
		  	if (isset($datamember['mem_id'])) {
		  		$kode = randomword(8);
		  		db_query("UPDATE `sa_member` SET `mem_confirm`='',`mem_password`='".create_hash($kode)."' 
		  			WHERE `mem_id`=".$datamember['mem_id']);
		  		# Kirim Email Konfirmasi
		  		$judul_email_reset = 'Password Baru Anda';
		  		$isi_email_reset = '<p>Berikut Data Login baru anda:</p>
		  		<p>Email : '.$datamember['mem_email'].'<br/>
		  		Password : '.$kode.'</p>
		  		<p>Silahkan login ke <a href="'.$weburl.'dashboard">'.$weburl.'dashboard</a></p>';
		  		smtpmailer($datamember['mem_email'],$judul_email_reset,$isi_email_reset);
		  		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
					  <strong>Ok!</strong> Password baru telah kami kirimkan ke email anda.
					  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>';
		  	}
		  }
		  ?>
	  
	    <div class="col d-none d-md-block align-self-start">	      
	    </div>
	    <div class="col-md-6 col-lg-4 col-sm-12 align-self-center bg-body-tertiary rounded p-3 border">
	      <form action="" method="post">
		      <div class="mb-3 row">
		      	<h2>Reset Password</h2>
		      </div>
		      <div class="mb-3 row">
				    <label for="staticEmail" class="col-sm-3 col-form-label text-start">Email</label>
				    <div class="col-sm-9">
				      <input type="email" class="form-control" name="username" placeholder="email@example.com">
				    </div>
				  </div>
				  <input type="submit" class="btn btn-success" value=" RESET ">				  
				</form>
				<div class="mt-3 pt-3 border-top row">
					<div class="col text-start">
						<a href="register">Register</a>
					</div>
					<div class="col text-end">
						<a href="login">Login</a>
					</div>
				</div>
	    </div>
	    <div class="col d-none d-md-block align-self-end">	      
	    </div>
	  </div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>
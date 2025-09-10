<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($_POST['username']) && filter_var($_POST['username'],FILTER_VALIDATE_EMAIL) 
	&& isset($_POST['password']) && !empty($_POST['password'])) {
	$datamember = db_row("SELECT * FROM `sa_member` WHERE `mem_email`='".cek($_POST['username'])."'");
	if (isset($datamember['mem_email'])) {
		if (validate_password($_POST['password'],$datamember['mem_password'])) {
      $id = $datamember['mem_id'];
      $hash = sha1(rand(0,500).microtime().SECRET);
      $signature = sha1(SECRET . $hash . $id);
      $cookie = base64_encode($signature . "-" . $hash . "-" . $id);
      setcookie('authentication', $cookie,time()+36000,'/');
      db_query("UPDATE `sa_member` SET `mem_lastlogin`='".date('Y-m-d H:i:s')."' WHERE `mem_id`=".$id);
      if (isset($_GET['redirect'])) {
      	if (substr($_GET['redirect'],0,1) == '/') {
      		$gored = substr($_GET['redirect'],1);
      	} else {
      		$gored = $_GET['redirect'];
      	}
        header('Location:'.$weburl.$gored);
      } else {
      	header('Location:'.$weburl.'dashboard');
      }
      echo 'Login berhasil';
    } else {
        $error = 'Email atau Password anda salah.';
    }
	} else {
		$error = 'Email anda salah.';
	}
}
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $weburl.$favicon;?>" />
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Login</title>

    <!-- Bootstrap Core CSS -->
    <link href="<?= $weburl;?>bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?=$weburl;?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
	  <link href="<?=$weburl;?>fontawesome/css/regular.min.css" rel="stylesheet" />
	  <link href="<?=$weburl;?>fontawesome/css/solid.min.css" rel="stylesheet" /> 
    <style type="text/css">
        .password-wrapper {
          position: relative;
        }
        
        .password-wrapper input[type="password"] {
          padding-right: 30px; /* Ruang untuk ikon */
        }
        
        .password-wrapper .toggle-password {
          position: absolute;
          top: 50%;
          right: 5px;
          transform: translateY(-50%);
          cursor: pointer;
        }
    </style>
    <script>
      function togglePassword() {
	      var passwordInput = document.getElementById("password");
	      var toggleBtn = document.getElementById("togglePassword");

	      if (passwordInput.type === "password") {
	        passwordInput.type = "text";
	        toggleBtn.innerHTML = '<i class="fas fa-eye-slash text-secondary"></i>';
	      } else {
	        passwordInput.type = "password";
	        toggleBtn.innerHTML = '<i class="fas fa-eye text-secondary"></i>';
	      }
	    }
    </script>
</head>

<body>
	<div class="container-fluid p-md-5 p-3 text-center">
	  <!-- Content here -->
	  <div class="row mt-3 align-items-center">
	    <div class="col d-none d-md-block align-self-start">	      
	    </div>
	    <div class="col-md-6 col-lg-4 col-sm-12 align-self-center bg-body-tertiary rounded p-3 border">
	      <?php if (isset($error) && !empty($error)) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				  <strong>Error!</strong> '.$error.'.
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>'; } 
				?>
	      <form action="" method="post">
		      <div class="mb-3 row">
		      	<div class="text-center"><img src="<?php echo $weburl.$logoweb;?>" style="max-width: 150px;"/></div>
		      	<h2>Login</h2>
		      </div>
		      <div class="mb-3 row">
				    <label for="staticEmail" class="col-sm-3 col-form-label text-start">Email</label>
				    <div class="col-sm-9">
				      <input type="email" class="form-control" name="username" placeholder="email@example.com">
				    </div>
				  </div>
				  <div class="mb-3 row">
				    <label for="inputPassword" class="col-sm-3 col-form-label text-start">Password</label>
				    <div class="col-sm-9">
				      <div class="password-wrapper">
					      <input type="password" id="password" class="form-control" name="password">
					      <span class="toggle-password" id="togglePassword" onclick="togglePassword()"><i class="fas fa-eye text-secondary"></i></span>
	            </div>
				    </div>
				  </div>
				  <input type="submit" class="btn btn-success" value=" LOGIN ">				  
				</form>
				<div class="mt-3 pt-3 border-top row">
					<div class="col text-start">
						<a href="register">Register</a>
					</div>
					<div class="col text-end">
						<a href="reset">Reset Password</a>
					</div>
				</div>
	    </div>
	    <div class="col d-none d-md-block align-self-end">	      
	    </div>
	  </div>
	</div>
	<script src="<?= $weburl;?>bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
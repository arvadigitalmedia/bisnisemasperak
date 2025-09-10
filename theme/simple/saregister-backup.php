<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (!isset($idsponsor)) {
	if (isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor'])) {
		$idsponsor = $_COOKIE['idsponsor'];
	} else {
		$idsponsor = 1;
	}
}

$datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$idsponsor);
$datasponsor = extractdata($datasponsor);
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

  <title>Registrasi</title>

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
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
    function onSubmit(token) {
     document.getElementById("registrasi").submit();
   }
  </script>
  <?php if (isset($datasponsor['fbpixel']) && !empty($datasponsor['fbpixel'])): ?>
  	<!-- Meta Pixel Code -->
		<script>
		!function(f,b,e,v,n,t,s)
		{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};
		if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
		n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];
		s.parentNode.insertBefore(t,s)}(window, document,'script',
		'https://connect.facebook.net/en_US/fbevents.js');
		fbq('init', '<?= $datasponsor['fbpixel']??='';?>');
		fbq('track', 'PageView');
		</script>
		<noscript><img height="1" width="1" style="display:none"
		src="https://www.facebook.com/tr?id=396425529796614&ev=PageView&noscript=1"
		/></noscript>
		<!-- End Meta Pixel Code -->
	<?php endif; ?>
	<?php if (isset($datasponsor['gtm']) && !empty($datasponsor['gtm'])): ?>
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','GTM-<?= $datasponsor['gtm']??='';?>');</script>
		<!-- End Google Tag Manager-->
	<?php endif;?>
</head>

<body>
	<?php if (isset($datasponsor['gtm']) && !empty($datasponsor['gtm'])): ?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-<?= $datasponsor['gtm']??='';?>"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript)-->
	<?php endif;?>
	<div class="container-fluid p-md-5 p-3 text-center">
	  <!-- Content here -->
	  <div class="row mt-3 align-items-center">
	    <div class="col d-none d-md-block align-self-start">	      
	    </div>
	    <div class="col-md-6 col-lg-4 col-sm-12 align-self-center bg-body-tertiary rounded p-3 border">
	    	<?php
          if (isset($_POST) && count($_POST) > 0) {
            $proses = proses_register($_POST);
            if (!empty($proses['status'])) {}
            if ($proses['status'] === false) {
              $alert = 'danger';
            } elseif ($proses['status'] === true) {
              $alert = 'success';
              do_action('registrasi_sukses',$proses['idmember']);
            }

            echo '<div class="alert alert-'.$alert.' alert-dismissible fade show" role="alert">
									  '.$proses['pesan'].'
									  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
									</div>';
          }
        ?>
	      <form action="" method="post" id="registrasi" onsubmit="document.getElementById('formsubmit').disabled=true;
					document.getElementById('formsubmit').value='Tunggu sebentar...';" enctype="multipart/form-data">
		      <div class="mb-3 row">
		      	<div class="text-center"><img src="<?php echo $weburl.$logoweb;?>" style="max-width: 150px;"/></div>
		      	<h2>Register</h2>
		      </div>
		      <?php 
		      echo form_builder('register'); 

		      if (isset($settings['recap_site']) && !empty($settings['recap_site'])) {
		      	echo '<button class="g-recaptcha btn btn-success" data-sitekey="'.$settings['recap_site'].'" id="formsubmit" data-callback="onSubmit" data-action="submit"> REGISTRASI </button>';
		      } else {
		      	echo '<input type="submit" class="btn btn-success" id="formsubmit" value=" REGISTRASI ">';
		      }
		      ?>
				  <div class="mt-3 pt-3">
				  	<?php 
			  		if (isset($datasponsor['nama'])) {
			  			if (isset($settings['boxsponsor']) && !empty($settings['boxsponsor'])) {
								$isibox = $settings['boxsponsor'];
								foreach ($datasponsor as $key => $value) {
									$isibox = str_replace('['.$key.']', ($value??=''), $isibox);
								}

							echo $isibox;
			  			} else {
			  				echo 'Sponsor: '.$datasponsor['nama'];
			  			}
			  		}
				  	?>
				  </div>
				</form>
				<div class="mt-3 pt-3 border-top row">					
					<div class="col text-end">
						<a href="login">Login</a>
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
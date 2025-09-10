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
<html class="full" lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#C7A54F">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="EPI Support">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="EPI Support">
    
    <!-- Icons -->
    <link rel="icon" type="image/jpeg" sizes="32x32" href="<?= $weburl;?>img/fav.jpg">
    <link rel="icon" type="image/jpeg" sizes="16x16" href="<?= $weburl;?>img/fav.jpg">
    <link rel="apple-touch-icon" href="<?= $weburl;?>img/logoweb.jpg">
    <link rel="mask-icon" href="<?= $weburl;?>img/logoweb.jpg" color="#C7A54F">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= $weburl;?>manifest.json">
    
    <meta name="description" content="Platform bisnis emas dan perak terpercaya untuk keluarga sejahtera">
    <meta name="author" content="EPI Channel">
    <meta name="keywords" content="emas, perak, bisnis, investasi, affiliate, EPI">

    <title>Login - EPI Channel</title>

    <!-- Bootstrap Core CSS -->
    <link href="<?= $weburl;?>bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?=$weburl;?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
	  <link href="<?=$weburl;?>fontawesome/css/regular.min.css" rel="stylesheet" />
	  <link href="<?=$weburl;?>fontawesome/css/solid.min.css" rel="stylesheet" /> 
    <style type="text/css">
        body {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 25%, #C0C0C0 75%, #A9A9A9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Background animation styles */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #FFD700, #FFA500, #C0C0C0, #A9A9A9);
            background-size: 400% 400%;
            animation: gradientAnimation 3s ease infinite;
            z-index: -1;
        }

        .animated-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(255, 215, 0, 0.3) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(192, 192, 192, 0.3) 0%, transparent 50%),
                        radial-gradient(circle at 40% 40%, rgba(255, 165, 0, 0.2) 0%, transparent 50%);
            animation: sparkleAnimation 3s ease infinite;
        }

        .animated-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%),
                linear-gradient(-45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            animation: shineAnimation 3s ease infinite;
        }

        @keyframes gradientAnimation {
            0% {
                background-position: 0% 50%;
                filter: brightness(1) saturate(1);
            }
            33% {
                background-position: 100% 50%;
                filter: brightness(1.2) saturate(1.3);
            }
            66% {
                background-position: 50% 100%;
                filter: brightness(1.1) saturate(1.1);
            }
            100% {
                background-position: 0% 50%;
                filter: brightness(1) saturate(1);
            }
        }

        @keyframes sparkleAnimation {
            0% {
                opacity: 0.3;
                transform: scale(1) rotate(0deg);
            }
            33% {
                opacity: 0.8;
                transform: scale(1.1) rotate(120deg);
            }
            66% {
                opacity: 0.5;
                transform: scale(0.9) rotate(240deg);
            }
            100% {
                opacity: 0.3;
                transform: scale(1) rotate(360deg);
            }
        }

        @keyframes shineAnimation {
            0% {
                opacity: 0;
                transform: translateX(-100%) translateY(-100%);
            }
            33% {
                opacity: 0.6;
                transform: translateX(0%) translateY(0%);
            }
            66% {
                opacity: 0.3;
                transform: translateX(50%) translateY(50%);
            }
            100% {
                opacity: 0;
                transform: translateX(100%) translateY(100%);
            }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 
                        0 0 0 1px rgba(255, 255, 255, 0.2);
            max-width: 420px;
            width: 100%;
            border: 1px solid rgba(218, 165, 32, 0.2);
        }
        
        .welcome-badge {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #333;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-control:focus {
            border-color: #DAA520;
            box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.25);
            background: rgba(255, 255, 255, 1);
        }
        
        .btn-login {
            background: linear-gradient(45deg, #DAA520, #FFD700, #FFA500, #DAA520);
            background-size: 300% 300%;
            border: none;
            color: #333;
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            animation: buttonGlow 3s ease infinite;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            background: linear-gradient(45deg, #B8860B, #DAA520, #FFD700, #B8860B);
            background-size: 300% 300%;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(218, 165, 32, 0.4),
                        inset 0 1px 0 rgba(255, 255, 255, 0.3);
            color: #333;
        }

        @keyframes buttonGlow {
            0% {
                background-position: 0% 50%;
                box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3),
                           inset 0 1px 0 rgba(255, 255, 255, 0.2);
            }
            50% {
                background-position: 100% 50%;
                box-shadow: 0 4px 20px rgba(218, 165, 32, 0.5),
                           inset 0 1px 0 rgba(255, 255, 255, 0.3),
                           0 0 20px rgba(255, 215, 0, 0.3);
            }
            100% {
                background-position: 0% 50%;
                box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3),
                           inset 0 1px 0 rgba(255, 255, 255, 0.2);
            }
        }
        
        .form-check-input:checked {
            background-color: #DAA520;
            border-color: #DAA520;
        }
        
        .text-link {
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .text-link:hover {
            color: #DAA520;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            padding-right: 45px;
        }
        
        .password-wrapper .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }
        
        .password-wrapper .toggle-password:hover {
            color: #DAA520;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, #ddd, transparent);
        }
        
        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 20px;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
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
	<div class="animated-background"></div>
	<div class="login-container">
	    <?php if (isset($error) && !empty($error)) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				  <strong>Error!</strong> '.$error.'.
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>'; } 
				?>
	      <?php if (isset($error) && !empty($error)) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
				  <strong>Error!</strong> '.$error.'.
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>'; } 
				?>
	      <form action="" method="post">
		      <div class="text-center mb-4">
		          <!-- Logo EPI -->
		      	<div class="mb-3">
		      		<img src="<?= $weburl; ?>upload/logoweb.jpg" alt="EPI Logo" class="img-fluid" style="max-height: 80px; margin-bottom: 20px;">
		      	</div>
		      	<div class="welcome-badge">Login</div>
		      	<h1 class="welcome-title">Welcome!</h1>
                <p class="welcome-subtitle">Sign in to your account</p>
		      </div>
		      
		      <div class="mb-3">
				    <label for="staticEmail" class="form-label">Email Address</label>
				    <input type="email" class="form-control" name="username" placeholder="Enter your email address" required>
				  </div>
				  
				  <div class="mb-4">
				    <label for="inputPassword" class="form-label">Password</label>
				    <div class="password-wrapper">
					      <input type="password" id="password" class="form-control" name="password" placeholder="Enter your password" required>
					      <span class="toggle-password" id="togglePassword" onclick="togglePassword()">
					      	<i class="fas fa-eye"></i>
					      </span>
	            </div>
				  </div>
				  
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    <a href="reset" class="text-link">Forgot password?</a>
                </div>
                
				  <input type="submit" class="btn btn-login w-100 mb-4" value="Login">				  
				</form>
				
				<div class="text-center">
					<p class="mb-0">Don't have an account? <a href="register" class="text-link fw-semibold">Create an Account</a></p>
				</div>
	</div>
	
	<!-- Scripts -->
	<script src="<?= $weburl;?>bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
	
	<!-- PWA Installer -->
	<script src="<?= $weburl;?>js/pwa-installer.js"></script>
	
	<!-- Login Page Specific PWA Features -->
	<script>
		// Enhanced PWA features for login page
		document.addEventListener('DOMContentLoaded', function() {
			// Check if user is on mobile device
			const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
			
			// Show install prompt more aggressively on mobile
			if (isMobile && !window.matchMedia('(display-mode: standalone)').matches) {
				// Add install hint to login form
				const installHint = document.createElement('div');
				installHint.className = 'alert alert-info mt-3';
				installHint.innerHTML = `
					<div class="d-flex align-items-center">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
							<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
							<polyline points="7,10 12,15 17,10"/>
							<line x1="12" y1="15" x2="12" y2="3"/>
						</svg>
						<div>
							<strong>Install EPI Channel Support System</strong><br>
							<small>Akses lebih cepat dari layar utama perangkat Anda</small>
						</div>
					</div>
				`;
				
				// Add click handler to show install prompt
				installHint.addEventListener('click', function() {
					if (window.PWAInstaller) {
						window.PWAInstaller.showInstallPrompt();
					}
				});
				
				// Insert after login form
				const form = document.querySelector('form');
				if (form && form.parentNode) {
					form.parentNode.insertBefore(installHint, form.nextSibling);
				}
			}
			
			// Add PWA-specific styling
			if (window.matchMedia('(display-mode: standalone)').matches) {
				// App is running in standalone mode
				document.body.classList.add('pwa-standalone');
				
				// Add status bar padding for iOS
				if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
					document.body.style.paddingTop = 'env(safe-area-inset-top)';
				}
			}
		});
		
		// Toggle password visibility
		function togglePassword() {
			const passwordField = document.getElementById('password');
			const toggleIcon = document.querySelector('#togglePassword i');
			
			if (passwordField.type === 'password') {
				passwordField.type = 'text';
				toggleIcon.classList.remove('fa-eye');
				toggleIcon.classList.add('fa-eye-slash');
			} else {
				passwordField.type = 'password';
				toggleIcon.classList.remove('fa-eye-slash');
				toggleIcon.classList.add('fa-eye');
			}
		}
		
		// Handle form submission for PWA
		document.querySelector('form').addEventListener('submit', function(e) {
			// Store login attempt for offline sync if needed
			if (!navigator.onLine && 'serviceWorker' in navigator) {
				e.preventDefault();
				
				// Store form data for later sync
				const formData = new FormData(this);
				const loginData = {
					username: formData.get('username'),
					password: formData.get('password'),
					timestamp: Date.now()
				};
				
				// Store in localStorage for sync when online
				localStorage.setItem('pending-login', JSON.stringify(loginData));
				
				// Show offline message
				const alert = document.createElement('div');
				alert.className = 'alert alert-warning';
				alert.innerHTML = 'Anda sedang offline. Login akan diproses ketika koneksi tersedia.';
				this.parentNode.insertBefore(alert, this);
				
				// Register for background sync
				if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
					navigator.serviceWorker.ready.then(function(registration) {
						return registration.sync.register('login-sync');
					});
				}
			}
		});
	</script>
	
	<!-- Additional PWA Styles -->
	<style>
		/* PWA-specific styles */
		.pwa-standalone {
			/* Styles when running as installed PWA */
		}
		
		@media (display-mode: standalone) {
			body {
				/* Additional padding for status bar */
				padding-top: env(safe-area-inset-top);
			}
		}
		
		/* iOS specific styles */
		@supports (-webkit-touch-callout: none) {
			.login-container {
				/* iOS specific adjustments */
				margin-top: env(safe-area-inset-top);
			}
		}
	</style>
</body>
</html>



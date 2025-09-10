<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
$head['pagetitle']='Setting Email';
$head['scripthead'] = '
<link href="'.$weburl.'editor/css/froala_editor.pkgd.min.css" rel="stylesheet" type="text/css" />
<link href="'.$weburl.'editor/css/froala_style.min.css" rel="stylesheet" type="text/css" />
<style type="text/css">
a[id="fr-logo"] {
  height:1px !important;
  color:#ffffff !important;
}
#Layer_1 { height:1px !important; }
p[data-f-id="pbf"] {
  height:1px !important;
}
a[href*="www.froala.com"] {
  height:1px !important;
  background: #fff !important;
  pointer-events: none;
}
#fr-logo {
    visibility: hidden;
}
</style>';
showheader($head);

if (isset($_POST) && count($_POST) > 0) {
	$post = str_replace('<p data-f-id="pbf" style="text-align: center; font-size: 14px; margin-top: 30px; opacity: 0.65; font-family: sans-serif;">Powered by <a href="https://www.froala.com/wysiwyg-editor?pb=1" title="Froala Editor">Froala Editor</a></p>','',$_POST);
	$settings = updatesettings($post);
	if ($settings === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Setting Email telah disimpan.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
} elseif (isset($_GET['test']) && !empty($_GET['test'])) {
	if (isset($settings['judul_'.$_GET['test']]) && isset($settings['isi_'.$_GET['test']])) {
		$cek = @smtpmailer($datamember['mem_email'],$settings['judul_'.$_GET['test']],$settings['isi_'.$_GET['test']]);

		if ($cek['status'] !== true) {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  '.($cek['message']??='').'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		} else {
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  '.($cek['message']??='').'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}		
	}
}
?>

<form action="" method="post">
<div class="card">
  <div class="card-header">
      Setting Email
  </div>
  <div class="card-body">
  	<div class="table-responsive">
		<table class="table table-hover table-bordered">
			<tbody>
				<tr><td>
					<a class="info" data-target="kontensetting">Setting Email</a>
					<div class="kontensetting konten mt-2">
						<div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Alamat Email</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_from" value="<?= $settings['smtp_from'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Nama Pengirim</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_sender" value="<?= $settings['smtp_sender'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Outgoing Server</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_server" value="<?= $settings['smtp_server'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">SMTP Port</label>
					    <div class="col-sm-3">
					      <input type="text" class="form-control" name="smtp_port" value="<?= $settings['smtp_port'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">SMTP Secure</label>
					    <div class="col-sm-3">
					      <select name="smtp_secure" class="form-select">
					      	<?php 
					      	$securesel = array('ssl'=>'SSL','tls'=>'TLS','false'=>'false');
					      	foreach ($securesel as $key => $value) {
					      		echo '<option value="'.$key.'"';
					      		if (isset($settings['smtp_secure']) && $settings['smtp_secure'] == $key) {
					      			echo ' selected';
					      		}
					      		echo '>'.$value.'</option>';
					      	}
						      ?>
					     	</select>
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">SMTP Authentication</label>
					    <div class="col-sm-3">
					      <select name="smtp_auth" class="form-select">
					      	<?php if (isset($settings['smtp_auth']) && $settings['smtp_auth'] == 'false') {
					      		$sel1 = '';
					      		$sel2 = ' selected';
					      	} else {
					      		$sel1 = ' selected';
					      		$sel2 = '';
					      	}
					      	?>
						      <option value="true"<?php echo $sel1;?>>true</option>
						      <option value="false"<?php echo $sel2;?>>false</option>
					     	</select>
					    </div>
					  </div>	  
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Username</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_username" value="<?= $settings['smtp_username'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Password</label>
					    <div class="col-sm-10">
					      <input type="password" class="form-control" name="smtp_password" value="<?= $settings['smtp_password'] ??= '';?>">
					    </div>
					  </div>	  
				  </div>
				</td></tr>
				<?php
				$notif = array(
					array('daftar','Registrasi',3),
					array('upgrade','Upgrade',2),
					array('order','Order Produk',2,'
								<code>[idorder]</code>: Nomor ID Invoice
								<br/><code>[hrgunik]</code>: Harga dengan kode unik
								<br/><code>[hrgproduk]</code>: Harga produk asli
								<br/><code>[namaproduk]</code>: Nama Produk
								<br/><code>[urlproduk]</code>: kode URL Produk
								'),
					array('prosesorder','Proses Order',2,'
								<code>[idorder]</code>: Nomor ID Invoice
								<br/><code>[hrgunik]</code>: Harga dengan kode unik
								<br/><code>[hrgproduk]</code>: Harga produk asli
								<br/><code>[namaproduk]</code>: Nama Produk
								<br/><code>[urlproduk]</code>: kode URL Produk
								'),
					array('cair_komisi','Pencairan Komisi',1,'<code>[komisi]</code>: Jumlah Komisi yg ditransfer')
				);

				$target = array('member','sponsor','admin');

				foreach ($notif as $notif) {
					for ($i=0; $i < $notif[2]; $i++) { 						
						if (isset($notif[3]) && !empty($notif[3])) {
							$shortcode = '<small class="form-text text-muted"><strong>Shortcode Khusus:</strong><br/>'.$notif[3].'</small><br/>';
						} else {
							$shortcode = '';
						}
						echo '
						<tr><td>
							<a class="info" data-target="konten_'.$notif[0].'_'.$target[$i].'">Notif '.$notif[1].' ke '.ucwords($target[$i]).'</a>
							<div class="konten_'.$notif[0].'_'.$target[$i].' konten mt-2">
								<input type="text" class="form-control mb-2" name="judul_'.$notif[0].'_'.$target[$i].'" value="'.($settings['judul_'.$notif[0].'_'.$target[$i]] ??= '').'">
					      <textarea class="form-control ckeditor" rows="5" id="editor" data-judul="isi_'.$notif[0].'_'.$target[$i].'" name="isi_'.$notif[0].'_'.$target[$i].'">'.
					      htmlspecialchars($settings['isi_'.$notif[0].'_'.$target[$i]]  ?? '', ENT_QUOTES, 'UTF-8').'</textarea>
					      '.$shortcode.'
					      <a href="?test='.$notif[0].'_'.$target[$i].'" class="btn btn-primary mt-1">Test Email</a>
							</div>
						</td></tr>
						';
					}
				}
				?>
			</tbody>
		</table>
		</div>
		<input type="submit" class="btn btn-success mt-3" name="" value=" SIMPAN ">
	</div>	  
</div>
</form>

<div class="card mt-3">
  <div class="card-header">
      Daftar Shortcode
      <?php
      $scmember = $scsponsor = '';
      $form = db_select("SELECT * FROM `sa_form` ORDER BY `ff_sort`");
      if (count($form) > 0) {
      	$default = array('nama','email','whatsapp','kodeaff');
      	foreach ($form as $form) {
      		if (!in_array($form['ff_field'], $default)) {
      			$scmember .= '<code>[member_'.$form['ff_field'].']</code> : '.$form['ff_label'].'<br/>';
      			$scsponsor .= '<code>[sponsor_'.$form['ff_field'].']</code> : '.$form['ff_label'].'<br/>';
      		}
      	}
      }
      ?>
  </div>
  <div class="card-body">
  	<div class="row">
  		<div class="col-sm-6">
  			<strong>Data Member yang mendaftar / upgrade:</strong><br/>
  			<code>[member_nama]</code> : Nama member<br/>
  			<code>[member_email]</code> : Email member<br/>
  			<code>[member_whatsapp]</code> : WhatsApp member<br/>
  			<code>[member_kodeaff]</code> : URL Affiliasi member<br/>
  			<?php echo $scmember;?>
  		</div>
  		<div class="col-sm-6">
  			<strong>Data sponsor dari member yang mendaftar / upgrade:</strong><br/>
  			<code>[sponsor_nama]</code> : Nama sponsor<br/>
  			<code>[sponsor_email]</code> : Email sponsor<br/>
  			<code>[sponsor_whatsapp]</code> : WhatsApp sponsor<br/>
  			<code>[sponsor_kodeaff]</code> : URL Affiliasi sponsor<br/>
  			<?php echo $scsponsor;?>
  		</div>
  	</div>
  </div>
</div>
<?php 
$footer['scriptfoot'] = '
<script type="text/javascript" src="'.$weburl.'editor/js/froala_editor.pkgd.min.js"></script>
<script>
  document.addEventListener(\'DOMContentLoaded\', function () {
    new FroalaEditor(\'#editor\', {
      imageUploadURL: \''.$weburl.'upload_image.php\',
      imageUploadParams: {
        id: \'my_editor\'
      },
      codeViewKeepOriginal: true,
      htmlUntouched: true,
      htmlAllowedTags: [\'.*\'], // Allow all HTML tags
      htmlAllowedAttrs: [\'.*\'], // Allow all attributes
      htmlRemoveTags: [],
      events: {
        \'image.beforeUpload\': function (files) {
          var editor = this;

          // Create a FormData object.
          var formData = new FormData();

          // Append the uploaded image to the form data.
          formData.append(\'file\', files[0]);

          // Get the article title and append it to the form data.
          var namafile = document.querySelector(\'#editor\').getAttribute(\'data-judul\');
          formData.append(\'judul\', namafile);

          // Make the AJAX request.
          fetch(\''.$weburl.'upload_image.php\', {
            method: \'POST\',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.link) {
              // Insert the image into the editor.
              editor.image.insert(data.link, null, null, editor.image.get());
            } else {
              console.error(\'Upload failed:\', data.error);
            }
          })
          .catch(error => {
            console.error(\'Error:\', error);
          });

          // Prevent the default behavior.
          return false;
        }
      }
    });
  });
</script>';
showfooter($footer); ?>
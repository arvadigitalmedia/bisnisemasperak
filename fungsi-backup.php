<?php
ob_start();
date_default_timezone_set('asia/jakarta');
ini_set('error_reporting', E_ALL);
define('IS_IN_SCRIPT',1);
define('ERROR404','<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body></html>');
require_once('config.php');
require_once('PasswordHash.php');
require_once('class.phpmailer.php');
require_once('class.smtp.php');

// Regex untuk memeriksa format ideal: http(s)://domain.tld/ atau localhost
$regex_ideal_format = '/^https?:\/\/([a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}|localhost)(:[0-9]{1,5})?\//i';

// Cek apakah $weburl sudah dalam format ideal.
// Jika TIDAK cocok (!preg_match), maka lakukan normalisasi.
if (!preg_match($regex_ideal_format, $weburl)) {
    // Normalisasi jika format tidak ideal
    $temp_weburl = trim($weburl); // Gunakan $weburl di sini
    $parsed_url_from_config = parse_url($temp_weburl);

    $clean_host = '';
    if ($parsed_url_from_config !== false && isset($parsed_url_from_config['host'])) {
        $clean_host = $parsed_url_from_config['host'];
    } else {
        $clean_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    $current_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

    // Hasil normalisasi disimpan kembali ke $weburl
    $weburl = $current_protocol . '://' . $clean_host . '/';

}

#if (substr($weburl, -1) != '/') { $weburl .= '/'; }

if(!isset($con)) {
  $con = mysqli_connect("$dbhost","$dbuser","$dbpassword","$dbname");
  if($con === false) {
    return mysqli_connect_error(); 
	} else {
    global $con;
  }
}

function db_query($query) {
    global $con;
    $result = mysqli_query($con,$query);
    return $result;
}

function db_error() {
    global $con;
    return mysqli_error($con);
}

function db_select($query) {
    $rows = array();
    $result = db_query($query);
    if($result === false) {
        return false;
    }
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function db_row($query) {
	$result = db_query($query);
	if($result === false) {
      return false;
  } else {
  	return mysqli_fetch_assoc($result);
  }
}

function db_var($query) {
  $result = db_query($query);
  if($result === false) {
    return false;
  } else {
    $var = mysqli_fetch_array($result, MYSQLI_NUM);
    if (isset($var[0])) {
      return $var[0];
    } else {
      return false;
    }
  }
}

function db_insert($query) {
	global $con;
  $result = mysqli_query($con,$query);

	if($result === false) {
		return false;
	} else {
		return mysqli_insert_id($con);
	}
}

function db_cek($value) {
    global $con;
    return "'" . cek($value) . "'";
}

function cek($value) {
  global $con;  
  $input = trim($value);  
  $input = mysqli_real_escape_string($con, $input);  
  return $input;
}

function db_exist($value) {
  global $con;
  $query = mysqli_query($con,$value); 
  if ($query === false) { // Fix: Add error handling for failed query
    error_log("Database query failed in db_exist: " . mysqli_error($con));
    return false;
  }
  if (mysqli_num_rows($query) > 0) {
    return true;
  } else {
    return false;
  }
}

function getsettings($label='') {
  if ($label == '') {
    $settings = array(); // Fix: Initialize $settings array
    $datasetting = db_select("SELECT * FROM `sa_setting`");
    if ($datasetting && count($datasetting) > 0) {      
      foreach ($datasetting as $data) { // Fix: Use different variable name to avoid confusion
        if (!empty($data['set_label'])) {
          $settings[$data['set_label']] = $data['set_value'];
        }
      }
    }
    return $settings;
  } else {
    $datasetting = db_var("SELECT `set_value` FROM `sa_setting` WHERE `set_label`='".cek($label)."'");
    if ($datasetting !== false && !empty($datasetting)) { // Fix: Better condition check
      return $datasetting;
    } else {
      return false;
    }
  }
}

function updatesettings($data) {
  if (is_array($data) && count($data) > 0) {
    $ins = '';
    foreach ($data as $key => $value) {
      if (!empty($key)) {
        if (is_array($value)) {
          $value = serialize($value);
        }
        
        $ins .= "('".cek($key)."','".cek($value)."'),";
      }
    }
    
    if ($ins != '') {      
      db_query("INSERT INTO `sa_setting` (`set_label`,`set_value`) 
        VALUES ".substr($ins, 0,-1)." 
        ON DUPLICATE KEY UPDATE 
        `set_value`= VALUES(`set_value`)");
      return getsettings();
    } else {
      return false;
    }
  }
}

function curPageURL() {
	$pageURL = 'http';
	// Fix: Add validation for REQUEST_URI
	if (!isset($_SERVER["REQUEST_URI"])) {
		return false;
	}
	$parse = parse_url($_SERVER["REQUEST_URI"]);
	$path = isset($parse['path']) ? $parse['path'] : '/'; // Fix: Check if path exists
	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
	// Fix: Add validation for SERVER_NAME
	$serverName = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : 'localhost';
	if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= str_replace('www.','',$serverName).":".$_SERVER["SERVER_PORT"].$path;
	} else {
		$pageURL .= str_replace('www.','',$serverName).$path;
	}  
 	return $pageURL;
}

function realIP() {
    // Fix: Add proper validation and sanitization for IP addresses
    if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) { 
        $ip = $_SERVER['HTTP_CLIENT_IP']; 
	} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) { 
        $ip = $_SERVER['REMOTE_ADDR']; 
    } else {
        $ip = '0.0.0.0'; // Fix: Default fallback IP
    }
    
    // Fix: Validate IP format and sanitize
    $ip = filter_var($ip, FILTER_VALIDATE_IP);
    return $ip !== false ? $ip : '0.0.0.0';
}

function validemail($email) {
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) { 
    return true;
  } else {
    return false;
  }
}

function cekkodeaff($username,$iduser='') {
  global $con;
  $where = '';
  if (isset($iduser) && is_numeric($iduser)) {
    $where = " AND `mem_id` != ".$iduser;
  }

  // Hapus whitespace pada awal dan akhir string
  $username = txtonly($username);
  $username = cek($username);

  // Periksa apakah username sudah digunakan atau belum
  $result = mysqli_query($con, "SELECT `mem_kodeaff` FROM `sa_member` WHERE `mem_kodeaff` LIKE '".$username."%'".$where);
  $count = mysqli_num_rows($result);

  // Jika username sudah digunakan, tambahkan angka pada akhir username
  if ($count > 0) {
    $suffix = "";
    // Mencari angka pada akhir username menggunakan regex
    if (preg_match('/\d+$/', $username, $matches)) {
      $suffix = $matches[0];
    }
    // Jika angka ditemukan, hapus angka dari username
    if (!empty($suffix)) {
      $username = str_replace($suffix, "", $username);
    }
    // Tambahkan angka pada akhir username dan cek kembali ke database
    $i = 1;
    while (mysqli_num_rows(mysqli_query($con, "SELECT `mem_kodeaff` FROM `sa_member` WHERE `mem_kodeaff`='".$username."$i'".$where)) > 0) {
      $i++;
    }
    $username .= $i;
  }

  // Kembalikan username yang unik
  return $username;
}

function cekurlpage($page,$idpage='') {
  global $con;
  $where = '';
  if (isset($idpage) && is_numeric($idpage)) {
    $where = " AND `page_id` != ".$idpage;
  }
  // Hapus whitespace pada awal dan akhir string
  $page = txtonly($page);
  $page = cek($page);

  // Periksa apakah username sudah digunakan atau belum
  $result = mysqli_query($con, "SELECT `page_url` FROM `sa_page` WHERE `page_url` LIKE '".$page."%'".$where);
  $count = mysqli_num_rows($result);

  // Jika page sudah digunakan, tambahkan angka pada akhir page
  if ($count > 0) {
    $suffix = "";
    // Mencari angka pada akhir page menggunakan regex
    if (preg_match('/\d+$/', $page, $matches)) {
      $suffix = $matches[0];
    }
    // Jika angka ditemukan, hapus angka dari page
    if (!empty($suffix)) {
      $page = str_replace($suffix, "", $page);
    }
    // Tambahkan angka pada akhir page dan cek kembali ke database
    $i = 1;
    while (mysqli_num_rows(mysqli_query($con, "SELECT `page_url` FROM `sa_page` WHERE `page_url`='".$page."$i'".$where)) > 0) {
      $i++;
    }
    $page .= $i;
  }

  // Kembalikan page yang unik
  return $page;
}

function cekurlpost($post,$idpost='') {
  global $con;
  $where = '';
  if (isset($idpost) && is_numeric($idpost)) {
    $where = " AND `art_id` != ".$idpost;
  }
  // Hapus whitespace pada awal dan akhir string
  $post = txtonly($post);
  $post = cek($post);

  // Periksa apakah username sudah digunakan atau belum
  $result = mysqli_query($con, "SELECT `art_slug` FROM `sa_artikel` WHERE `art_slug` LIKE '".$post."%'".$where);
  $count = mysqli_num_rows($result);
  
  // Jika page sudah digunakan, tambahkan angka pada akhir page
  if ($count > 0) {
    $suffix = "";
    // Mencari angka pada akhir page menggunakan regex
    if (preg_match('/\d+$/', $post, $matches)) {
      $suffix = $matches[0];
    }
    // Jika angka ditemukan, hapus angka dari page
    if (!empty($suffix)) {
      $page = str_replace($suffix, "", $page);
    }
    // Tambahkan angka pada akhir page dan cek kembali ke database
    $i = 1;
    while (mysqli_num_rows(mysqli_query($con, "SELECT `art_slug` FROM `sa_artikel` WHERE `art_slug`='".$post."$i'".$where)) > 0) {
      $i++;
    }
    $post .= $i;
  }

  // Kembalikan page yang unik
  return $post;
}

function smtpmailer($to, $subject, $body) {   
  global $error,$settings;
  if (isset($settings['smtp_server']) && !empty($settings['smtp_server'])) {
    if ($settings['smtp_secure'] == 'false') {
      $secure = false;
    } else {
      $secure = $settings['smtp_secure'];
    }

    if ($settings['smtp_auth'] == 'true') {
      $auth = true;
    } else {
      $auth = false;
    }
    
    $mail = new PHPMailer();  // create a new object
    $mail->IsSMTP(); // enable SMTP
    $mail->IsHTML(); // enable HTML
    $mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
    $mail->SMTPAuth = $auth;  // authentication enabled
    $mail->SMTPSecure = $secure; // secure transfer enabled REQUIRED for GMail
    $mail->Host = $settings['smtp_server'];
    $mail->Port = $settings['smtp_port']; 
    $mail->Username = $settings['smtp_username'];  
    $mail->Password = $settings['smtp_password'];  
    #$mail->AddReplyTo($mailreply, $mailname);
    $mail->SetFrom($settings['smtp_from'], $settings['smtp_sender']);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AddAddress($to);

    if(!$mail->Send()) {
      $result['status'] = false;
      $result['message'] = 'Mail error: '.$mail->ErrorInfo; 
    } else {
      $result['status'] = true;
      $result['message'] = 'Message sent!'; 
    }
  } elseif (function_exists('mail')) {
    $cek = mail($to,$subject,strip_tags($body));
    if ($cek == 1) {
      $result['status'] = true;
      $result['message'] = 'Message sent!'; 
    } else {
      $result['status'] = false;
      $result['message'] = 'Mail error: '.$cek;
    }
  }

  return $result;
}

function kirimwa($nohp,$pesan,$gambar='') { 
  global $weburl,$settings,$error;  
  if (isset($settings['set_service']) && !empty($settings)) {   
    $pesan = stripslashes($pesan);
    $nohp = formatwa($nohp); 
    include('service/'.$settings['set_service'].'.php');        
    return ($return??='');
  } else {
    return 'Belum ada service';
  }
}

function sa_notif($event,$iduser,$datalain=array()) {
  global $weburl,$settings;  
  $settings = getsettings();
  include('sanotif.php');
}

function is_login() {
	if (isset($_COOKIE['authentication'])) {
		$cook = base64_decode($_COOKIE['authentication']);
		$exp = explode('-',$cook);
		if (sha1(SECRET . $exp[1] . $exp[2]) == $exp[0]) {
			return $exp[2];
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function pendekin($text, $length) {
   $length = abs((int)$length);
   $text = strip_tags($text);
   if(strlen($text) > $length) {
      $text = preg_replace("/^(.{1,$length})(\s.*|$)/s", '\\1...', $text);
   }
   return($text);
}

function formatwa($nomor) {  
    $nomor = preg_replace('/[^0-9]/', '', $nomor);
    $nomor = preg_replace('/^620/','62', $nomor);
    $nomor = preg_replace('/^0/','62', $nomor);
    return $nomor;
}

function randomword($char=8) {
  $set = 'abcdefghijklmnopqrstuvwxyz01234567890';
  $numset = strlen($set)-1;
  $word = '';
  for ($i=0; $i < $char; $i++) { 
    $rand = rand(0,$numset);
    $word .= substr($set,$rand,1);
  }
  return $word;
}

function txtonly($p) {
  $p = preg_replace("/[^a-zA-Z0-9]+/", "", $p);
  return $p;
}

function numonly($p) {
  $p = preg_replace("/[^0-9]+/", "", $p);
  return $p;
}

function getcontent($content,$start,$end){
    $r = explode($start, $content);
    if (isset($r[1])){
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}

function getData($url) {
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
    } else {
        $useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.67 Safari/537.36';
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 3); // Naikkan jadi 3 detik agar lebih stabil
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_COOKIEFILE, getcwd() . '/sacookie.cok');
    curl_setopt($curl, CURLOPT_COOKIEJAR, getcwd() . '/sacookie.cok');

    $data = curl_exec($curl);

    // Tambahkan pengecekan error
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        error_log("cURL Error: $error URL: $url");
        $data = false;
    }

    curl_close($curl);
    return $data;
}

function postData($url, $post, $ref = ''){
  if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $useragent = $_SERVER['HTTP_USER_AGENT'];
  } else {
    $useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.67 Safari/537.36';
  }
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL,$url);
  curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
  curl_setopt($curl, CURLOPT_REFERER, $ref);
  curl_setopt($curl, CURLOPT_ENCODING, "");
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER ,1);
  curl_setopt($curl, CURLOPT_COOKIEFILE, getcwd() . '/sacookie.cok');
  curl_setopt($curl, CURLOPT_COOKIEJAR, getcwd() . '/sacookie.cok');
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

  $data = curl_exec($curl);
  
  // Fix: Add proper error handling for cURL operations
  if (curl_errno($curl)) {
    $error = curl_error($curl);
    error_log("cURL Error in postData: $error URL: $url");
    curl_close($curl);
    return false;
  }
  
  curl_close($curl);
  return $data;
}

function GetBetween($content,$start,$end){
    $r = explode($start, $content);
    if (isset($r[1])){
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}

function extractdata($dataku) {
  if (is_array($dataku)) {
    $datahasil = $dataku;
    $status = array('','Free Member','Premium');
    $datahasil['id'] = $dataku['mem_id'];
    $datahasil['nama'] = $dataku['mem_nama'];
    $datahasil['email'] = $dataku['mem_email'];
    $datahasil['whatsapp'] = $dataku['mem_whatsapp'];
    $datahasil['kodeaff'] = $dataku['mem_kodeaff'];
    $datahasil['tgldaftar'] = $dataku['mem_tgldaftar'];
    $datahasil['tglupgrade'] = $dataku['mem_tglupgrade'];
    $datahasil['lastlogin'] = $dataku['mem_lastlogin'];
    if (isset($dataku['NamaSponsor'])) { $datahasil['NamaSponsor'] = $dataku['NamaSponsor']; }
    $datahasil['statusmember'] = $status[($dataku['mem_status']??=0)];
    if (isset($dataku['mem_datalain']) && !empty($dataku['mem_datalain'])) {
      $exp = explode("][", substr($dataku['mem_datalain'],1,-1));
      foreach ($exp as $exp) {
        $line = explode("|",$exp);
        if (count($line) == 2) {
          $datahasil[$line[0]] = $line[1];
        }
      }
    }

    return $datahasil;
  }
}

function getdatamember($iduser) {
  $datamember = db_row("SELECT * FROM `sa_member` LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id`=`sa_member`.`mem_id` WHERE `mem_id`=".$iduser);
  if (isset($datamember['mem_datalain']) && !empty($datamember['mem_datalain'])) {
    $exp = explode("][", substr($datamember['mem_datalain'],1,-1));
    foreach ($exp as $exp) {
      $line = explode("|",$exp);
      if (count($line) == 2) {
        $datamember[$line[0]] = $line[1];
      }
    }
  }
  return $datamember;
}

function form_builder($jenisform,$datamember=array()) {  
  global $weburl, $datasponsor;
  $showform = '';
  switch ($jenisform) {
    case 'register':
      $ff_form = 'ff_registrasi';
      break;
    case 'profil':
      $ff_form = 'ff_profil';
      break;  
    default:
      $ff_form = 'ff_registrasi';
      break;
  }
  $dataform = db_select("SELECT * FROM `sa_form` WHERE `".$ff_form."`=1 ORDER BY `ff_sort`");
  foreach ($dataform as $dataform) {
    if ($dataform['ff_required'] == 1) {
      $required = ' required';
      $star = '*';
    } else {
      $required = $star = '';
    }

    if (isset($datamember[$dataform['ff_field']])) {
      $value = $datamember[$dataform['ff_field']];
    } else {
      $value = '';
    }

    if ($jenisform == 'register' && $dataform['ff_field'] == 'sponsor' && isset($datasponsor['mem_kodeaff'])) {      
      $value = $datasponsor['mem_kodeaff'];
    }

    if (isset($dataform['ff_keterangan']) && !empty($dataform['ff_keterangan'])) {
      $keterangan = '<small class="form-text text-muted">'.$dataform['ff_keterangan'].'</small>';
    } else {
      $keterangan = '';
    }

    if ($dataform['ff_type'] == 'text' || 
        $dataform['ff_type'] == 'date' || 
        $dataform['ff_type'] == 'email' || 
        $dataform['ff_type'] == 'number') {        
        $showform .= '
        <div class="mb-3 row">
          <label class="col-sm-4 col-form-label text-start">'.$dataform['ff_label'].$star.'</label>
          <div class="col-sm-8">
            <input type="'.$dataform['ff_type'].'" id="'.$dataform['ff_field'].'" class="form-control" value="'.$value.'" name="'.$dataform['ff_field'].'"'.$required.'>
            '.$keterangan.'
          </div>
        </div>
        ' ;  
    } elseif ($dataform['ff_type'] == 'password') {
        $showform .= '
        <div class="mb-3 row">
          <label class="col-sm-4 col-form-label text-start">'.$dataform['ff_label'].'</label>
          <div class="col-sm-8">
            <div class="password-wrapper">
              <input type="password" id="'.$dataform['ff_field'].'" class="form-control" name="'.$dataform['ff_field'].'">
              <span class="toggle-password" id="togglePassword" onclick="togglePassword()"><i class="fas fa-eye text-secondary"></i></span>
            </div>
            '.$keterangan.'
          </div>
        </div>
        ' ;  
    } elseif ($dataform['ff_type'] == 'file') {      
        if ($keterangan == '') {
          $keterangan = '<small class="form-text text-muted">Hanya support file jpg, jpeg, png, gif dan maksimal Ukuran 1 Mb.</small>';
        }
        $showform .= '
        <div class="mb-3 row">
          <label class="col-sm-4 col-form-label text-start">'.$dataform['ff_label'].'</label>
          <div class="col-sm-8">
            <input type="file" class="form-control" value="'.$value.'" id="'.$dataform['ff_field'].'" name="'.$dataform['ff_field'].'"'.$required.'>
            '.$keterangan.'  
            <div class="mt-2" id="preview'.$dataform['ff_field'].'">';
            if (!empty(trim($value))) {
              $showform .= '<img src="'.$weburl.'upload/'.$value.'?id='.rand(100,999).'" class="img-fluid img-thumbnail" style="max-width: 200px">';
            }
        $showform .= '</div>
          </div>
        </div>
        ' ;    
    } elseif ($dataform['ff_type'] == 'kodeaff') {        
        $showform .= '
        <div class="mb-3 row">
          <label class="col-sm-4 col-form-label text-start">'.$dataform['ff_label'].'</label>
          <div class="col-sm-8">
            <div class="input-group">
              <span class="input-group-text" id="basic-addon3">'.$weburl.'</span>
              <input type="text" class="form-control" id="basic-url" value="'.$value.'" name="'.$dataform['ff_field'].'"'.$required.'>
              '.$keterangan.'
            </div>            
          </div>
        </div>
        ' ;
    } elseif ($dataform['ff_type'] == 'textarea') {
        $showform .= '
        <div class="mb-3 row">
          <label class="col-sm-4 col-form-label text-start">'.$dataform['ff_label'].'</label>
          <div class="col-sm-8">
            <textarea class="form-control" name="'.$dataform['ff_field'].'"'.$required.'>'.$value.'</textarea>
            '.$keterangan.'
          </div>
        </div>
        '; 
    } elseif ($dataform['ff_type'] == 'select') {
        $showform .= '
        <div class="mb-3 row">
          <label class="col-sm-4 col-form-label text-start">'.$dataform['ff_label'].'</label>
          <div class="col-sm-8">
            <select class="form-select" name="'.$dataform['ff_field'].'"'.$required.'>';
        if ($dataform['ff_options'] != '') {
          $opt = explode("\n", $dataform['ff_options']);
          foreach ($opt as $opt) {
            $showform .= '<option value="'.trim($opt).'"';
            if (trim($opt) == $value) { $showform .= ' selected'; }
            $showform .= '>'.$opt.'</option>';
          }
        }
        $showform .= '</select>
          '.$keterangan.'
          </div>
        </div>
        ';
    }
  }

  return $showform;
}

function visitor_data($id_sponsor) {
  $file_name = 'visitor_data.txt';
  if (!file_exists($file_name)) {
    $myfile = fopen($file_name, "w");
    $visitor_data = array();
  } else {
    $file = fopen($file_name, 'r');
    $visitor_data = array();
    while (!feof($file)) {
        $line = fgets($file);
        if (!empty($line)) {
            $line_data = explode(',', $line);
            if ($line_data[0] == $id_sponsor) {
                $time = $line_data[1];
                $count = (int)$line_data[2];
                if (!isset($visitor_data[$time])) {
                    $visitor_data[$time] = $count;
                } else {
                    $visitor_data[$time] += $count;
                }
            }
        }
    }
    fclose($file);
  }
  return $visitor_data;
}

function all_visitor_data() {
    $file_name = 'visitor_data.txt';
    if (!file_exists($file_name)) {
      $myfile = fopen($file_name, "w");
      $visitor_data = array();
    } else {
      $file = fopen($file_name, 'r');
      $visitor_data = array();
      while (!feof($file)) {
          $line = fgets($file);
          if (!empty($line)) {
              $line_data = explode(',', $line);
              $id_sponsor = $line_data[0];
              $time = $line_data[1];
              $count = (int)$line_data[2];
              if (!isset($visitor_data[$time])) {
                  $visitor_data[$time] = array();
              }
              if (!isset($visitor_data[$time][$id_sponsor])) {
                  $visitor_data[$time][$id_sponsor] = $count;
              } else {
                  $visitor_data[$time][$id_sponsor] += $count;
              }
          }
      }
      fclose($file);
    }
    return $visitor_data;
}

function getchannel() {
  global $settings;
  if (isset($settings['wafucb_key']) && $settings['wafucb_key'] != '' 
    && isset($settings['wafucb_id']) && is_numeric($settings['wafucb_id'])) {
    $data = array(
      'wafucb_key' => $settings['wafucb_key'],
      'wafucb_id' => $settings['wafucb_id'],
      'getdata' => 'channel'
    );

    $postfield = json_encode($data);
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://wafucb.my.id/api',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $postfield
    ));

    $return = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($return,true);    
    if (isset($result['status']) && $result['status'] == 'success') {
      $channel = $result['channel'];
      return $channel;
    } else {
      return $return;
    }
  } else {
    return 'Key tidak ada';
  }
}

function ubahshortcode($konten,$iduser) {  
  $datamember = getdatamember($iduser);

  if (isset($datamember['sp_sponsor_id']) && $datamember['sp_sponsor_id'] != 0) {
    $datasponsor = getdatamember($datamember['sp_sponsor_id']);
  }

  # Sembunyikan Password
  $konten = str_replace('[member_password]','***',$konten);
  $konten = str_replace('[sponsor_password]','***',$konten);

  # Handle Data Default
  $arrfield = array('nama','email','whatsapp','kodeaff');
  foreach ($arrfield as $arrfield) {      
    $konten = str_replace('[member_'.$arrfield.']',$datamember['mem_'.$arrfield],$konten);
    $konten = str_replace('[sponsor_'.$arrfield.']',($datasponsor['mem_'.$arrfield]??=''),$konten);
  }

  # Handle data lain
  $form = db_select("SELECT * FROM `sa_form` WHERE `ff_field` NOT IN ('nama','email','whatsapp','kodeaff','password')");

  foreach ($form as $form) {    
    $konten = str_replace('[member_'.$form['ff_field'].']',($datamember[$form['ff_field']]??=''),$konten);
    $konten = str_replace('[sponsor_'.$form['ff_field'].']',($datasponsor[$form['ff_field']]??=''),$konten);
  }

  return $konten;
}

function isPluginIndexFile($indexFile) {
  $fileContent = file_get_contents($indexFile);
  return preg_match('/Name\s*:\s*([^\n]+)/', $fileContent);
}

$filters = array();

function add_filter($hook_name, $callback, $priority = 10) {
    global $filters;
    if (!isset($filters[$hook_name])) {
        $filters[$hook_name] = array();
    }
    $filters[$hook_name][] = array('callback' => $callback, 'priority' => $priority);
}

function apply_filter($hook_name, $value) {
    global $filters;
    if (isset($filters[$hook_name])) {
        $hooks = $filters[$hook_name];
        usort($hooks, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        foreach ($hooks as $hook) {
            $value = call_user_func($hook['callback'], $value);
        }
    }
    return $value;
}

$actions = array();

function add_action($hook_name, $callback, $priority = 10) {
    global $actions;
    if (!isset($actions[$hook_name])) {
        $actions[$hook_name] = array();
    }
    $actions[$hook_name][] = array('callback' => $callback, 'priority' => $priority);
}

function do_action($hook_name, ...$args) {
    global $actions;
    if (isset($actions[$hook_name])) {
        $hooks = $actions[$hook_name];
        usort($hooks, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        foreach ($hooks as $hook) {
            call_user_func_array($hook['callback'], $args);
        }
    }
}

function showheader($head=array()) {
  global $datamember, $settings, $weburl, $menu, $pagetitle,$bcslug,$bcjudul,$visiturl;
  if (!isset($settings['favicon'])) { $favicon = 'img/simpleaff-favicon.png'; } else { $favicon = 'upload/'.$settings['favicon']; }
  if (!isset($settings['logoweb'])) { $logoweb = 'img/simpleaff-logo.png'; } else { $logoweb = 'upload/'.$settings['logoweb']; }
  if (file_exists('theme/'.$settings['theme'].'/dashhead.php')) {
    include('theme/'.$settings['theme'].'/dashhead.php');
  } else {
    include('theme/simple/dashhead.php');
  }
}

function showfooter($footer=array()) {
  global $datamember, $settings, $weburl, $scripthead;
  global $menu, $pagetitle,$services;
  if (file_exists('theme/'.$settings['theme'].'/dashfoot.php')) {
    include('theme/'.$settings['theme'].'/dashfoot.php');
  } else {
    include('theme/simple/dashfoot.php');
  }
}

function findKeyBySlug($array, $slug) {
  if (is_array($array)) {
    foreach ($array as $key => $value) {
      if ($key === $slug) {
        return $value;
      }
      if (is_array($value)) {
        $result = findKeyBySlug($value, $slug);
        if ($result !== null) {
          return $result;
        }
      }
    }
  }
  return null;
}

function openpage() {  
  global $weburl, $menu,$datamember,$datasponsor,$settings,$bcslug,$bcjudul,$slug,$dbname;  
  $skema = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
  $rekues = parse_url($_SERVER["REQUEST_URI"]);
  $settings = getsettings();
  $keymenu = 'home';
  if (!isset($settings['favicon'])) { $favicon = 'img/simpleaff-favicon.png'; } else { $favicon = 'upload/'.$settings['favicon']; }
  if (!isset($settings['logoweb'])) { $logoweb = 'img/simpleaff-logo.png'; } else { $logoweb = 'upload/'.$settings['logoweb']; }

  if (isset($rekues['path'])) {
    $visiturl = $skema . "://" . $_SERVER['HTTP_HOST'] . $rekues['path'];
    $urlslug = str_replace($weburl, '', $visiturl);
    $slug = explode('/',$urlslug);
    array_splice($slug, 0, 0, '');
    
    if (!isset($settings['ver'])) {
      $newsetting['ver'] = VERSI;
      $settings = updatesettings($newsetting);
    } elseif (isset($settings['ver']) && $settings['ver'] != VERSI) {      
      include('upgrade.php');
      $newsetting['ver'] = VERSI;
      $settings = updatesettings($newsetting);
    }

    
    $showpage = $settings['homepage'];
    $titlepage = $settings['judulweb'];
    $discpage = $settings['diskripsiweb'];

    # Handle Slug
    require_once('menudata.php');

    if ($iduser = is_login()) {
      $datamember = getdatamember($iduser);
    }

    if (isset($slug[1]) && !empty($slug[1])) {
      if ($slug[1] == 'dashboard') {
        if (isset($datamember['mem_id'])) {
          if (isset($slug[2]) && !empty($slug[2])) {
            $keymenu = $slug[2] ??= '';
            $slug[2] = $slug[3] ??= ''; 
          } else {
            $keymenu = 'home';
          }
          
        } else {
          $redirect = '';
          if (isset($slug[2]) && $slug[2] != '') {
            $redirect = "?redirect=".$rekues['path'];
          } 

          header("Location:".$weburl."login".$redirect);
        }
      } else {
        $keymenu = $slug[1];
      }

      $filepage = findKeyBySlug($menu, $keymenu);
      $bcjudul = $filepage[0] ??= '';
      $bcslug = $keymenu;
      if (isset($filepage['file']) && !empty($filepage['file'])) {
        $openfile = $filepage['file'];
      } elseif (isset($filepage[1]) && !empty($filepage[1])) {        
        $openfile = $filepage[1];
      } else {
        if (isset($slug[2]) && !empty($slug[2])) {
          $kodeaff = $slug[1];
          $kodepage = $slug[2];          
        } else {
          $kodepage = $slug[1];
        }

        # Cek apakah itu page atau kodeaff
        $page = db_row("SELECT * FROM `sa_page` WHERE `page_url`='".txtonly(strtolower($kodepage))."'");
        if (isset($page['page_iframe']) && !empty($page['page_iframe'])) {
          
          $showpage = $page['page_iframe'];
          $titlepage = $page['page_judul'];
          $discpage = $page['page_diskripsi'];          
          $openfile = 'theme/'.$settings['theme'].'/sahome.php'; 
                
        } else {

          $filepage = findKeyBySlug($menu, $kodepage);
          if (isset($filepage[1]) && !empty($filepage[1])) {
            $openfile = $filepage[1];
          } else {
            # Bukan page dan menu berarti ini link affiliasi
            $kodeaff = $kodepage;
            $openfile = 'theme/'.$settings['theme'].'/sahome.php'; 
          }

        }
      }

    } else {
      $openfile = 'theme/'.$settings['theme'].'/sahome.php'; 
    }
      
    if (isset($kodeaff)) {
      # Ubah susunan slug
      $slug[1] = $slug[2]??='';
      $slug[2] = $slug[3]??='';
    }

    if (isset($slug[1]) && $slug[1] == 'linkaff') {
      #jangan buka affiliatepage
    } else {
      include('affiliatepage.php');      
    }
    
    if (isset($openfile) && !empty($openfile)) {
      # Cek dulu apakah ini file theme
      if (substr($openfile, 0,5) == 'theme') {
        if (file_exists($openfile)) {
          if (isset($filepage[2]) && is_numeric($filepage[2])) {
            # Cek permission
            if (isset($datamember['mem_role']) && $datamember['mem_role'] >= $filepage[2]) { 
              # Munculkan
              include($openfile);
            } else {            
              menu_403();
            }
          } else {
            # Langsung munculin aja          
            include($openfile);
          }
        } else {
          $filedefault = 'theme/simple/'.str_replace($dashfile, '', $openfile);           
          if (file_exists($filedefault)) { 
            if (isset($filepage[2]) && is_numeric($filepage[2])) {
              # Cek permission
              if (isset($datamember['mem_role']) && $datamember['mem_role'] >= $filepage[2]) {
                $convertfile = $filedefault;
              } else {
                menu_403();
              }
            } else {
              $convertfile = $filedefault;
            }     
          } else {
            menu_404();
          }
        }
      } else {
        # Bukan file theme
        if (file_exists($openfile)) {
          if (isset($filepage[2]) && is_numeric($filepage[2])) {
            # Cek permission
            if (isset($datamember['mem_role']) && $datamember['mem_role'] >= $filepage[2]) { 
              # Munculkan
              $convertfile = $openfile;
            } else {            
              menu_403();
            }
          } else {
            # Langsung munculin aja          
            $convertfile = $openfile;
          }
        } else {
          menu_404();
        }
      }

      if (isset($convertfile)) {
        # Convert file
        if (file_exists('theme/'.$settings['theme'].'/convert.php')) {
          include('theme/'.$settings['theme'].'/convert.php');
        } else {
          include($convertfile);
        }
      }
    }
  }
}

function menu_404() {
  global $weburl,$favicon, $settings;
  if (file_exists('theme/'.$settings['theme'].'/dash404.php')) { 
    include('theme/'.$settings['theme'].'/dash404.php');
  } else {
    include('theme/simple/dash404.php');
  }
}

function menu_403() {
  global $weburl,$favicon, $settings;
  if (file_exists('theme/'.$settings['theme'].'/dash403.php')) { 
    include('theme/'.$settings['theme'].'/dash403.php');
  } else {
    include('theme/simple/dash403.php');
  }
}

function convertOembedToIframe($content) {
    // Menggunakan DOMDocument untuk memanipulasi HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Mencari semua elemen <oembed>
    $oembeds = $dom->getElementsByTagName('oembed');

    // Menggunakan array untuk menyimpan elemen yang akan diubah
    $oembedElements = [];
    foreach ($oembeds as $oembed) {
        $oembedElements[] = $oembed;
    }

    // Mengonversi <oembed> menjadi <iframe>
    foreach ($oembedElements as $oembed) {
        $url = $oembed->getAttribute('url');
        $iframe = $dom->createElement('iframe');

        // Mengatur atribut iframe
        $iframe->setAttribute('width', '560');
        $iframe->setAttribute('height', '315');
        $iframe->setAttribute('frameborder', '0');
        $iframe->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        $iframe->setAttribute('allowfullscreen', 'true');

        if (strpos($url, 'youtube.com') !== false) {
            $videoId = substr(parse_url($url, PHP_URL_QUERY), 2); // Mendapatkan ID video dari URL
            $iframe->setAttribute('src', 'https://www.youtube.com/embed/' . $videoId);
        } else {
            $iframe->setAttribute('src', $url);
        }

        // Buat div dengan kelas "ratio ratio-16x9"
        $div = $dom->createElement('div');
        $div->setAttribute('class', 'ratio ratio-16x9');

        // Sisipkan iframe ke dalam div
        $div->appendChild($iframe);

        // Mengganti elemen <oembed> dengan <iframe>
        $oembed->parentNode->replaceChild($iframe, $oembed);
    }

    // Mengembalikan konten yang telah diubah
    return $dom->saveHTML();
}

function copycode($text) {
    return preg_replace_callback(
        '/\[copy data=&quot;(.*?)&quot;\]/',
        function($matches) {
            $data = $matches[1];
            return '<a onclick="copyToClipboard(\'' . htmlspecialchars($data, ENT_QUOTES) . '\')" style="text-decoration:none;cursor: pointer;" title="Copy to Clipboard"><i class="far fa-copy"></i></a>';
        },
        $text
    );
}

function caripath($folder) {
    $currentPath = __DIR__;  // Path file saat ini
    $folder = '/' . trim($folder, '/'); // Pastikan folder dimulai dengan /
    $pos = strpos($currentPath, $folder); // Cari posisi folder acuan

    if ($pos !== false) {
        // Potong path sebelum folder acuan
        return substr($currentPath, 0, $pos);
    } else {
        // Jika folder acuan tidak ditemukan, gunakan default DOCUMENT_ROOT
        return $_SERVER['DOCUMENT_ROOT'];
    }
}

function sensor($nama) {
    // Pecah nama berdasarkan spasi
    $namaArray = explode(' ', $nama);
    $namaDisensor = [];

    // Proses setiap kata
    foreach ($namaArray as $kata) {
        // Ambil huruf pertama dan tambahkan ***
        $namaDisensor[] = substr($kata, 0, 1) . str_repeat('*', strlen($kata) - 1);
    }

    // Gabungkan kembali kata-kata yang telah disensor
    return implode(' ', $namaDisensor);
}

function gantiArray($array, $search, $replace) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            // Jika value adalah array, panggil fungsi ini secara rekursif
            $array[$key] = gantiArray($value, $search, $replace);
        } elseif (is_string($value)) {
            // Jika value adalah string, gunakan str_replace untuk mengganti substring
            $array[$key] = str_replace($search, $replace, $value);
        }
    }
    return $array;
}


if (db_var("show tables like 'sa_setting'")) {
  $pluginFolder = __DIR__ . '/plugin/';
  $setplugin = getsettings('plugin_aktif');

  if (!empty($setplugin)) {
    $plugin_aktif = explode(',', $setplugin);
    foreach ($plugin_aktif as $key => $plugin) {
      if (file_exists($pluginFolder.$plugin.'/index.php')) {
        include($pluginFolder.$plugin.'/index.php');
      } else {
        # Non aktifkan otomatis
        unset($plugin_aktif[$key]);
        $update = 1;
      }
    }

    if (isset($update)) {
      $newsetting['plugin_aktif'] = implode(',', $plugin_aktif);
      $settings = updatesettings($newsetting);
    }
    
  }
}

function generateDownloadToken($fileName) {
    // Generate a unique token based on filename and timestamp
    $token = md5($fileName . uniqid(rand(), true));
    
    // Save token in session
    $_SESSION['download_token'][$fileName] = $token;
    
    return $token;
}

function convertdata($konten,$data=array()) {
  if (!empty($konten) && count($data) > 0) {
    foreach ($data as $key => $value) {
      $konten = str_replace('['.$key.']', $value ?? '', $konten);
    }
  }
  return $konten;
}

function getCachedData($targeturl, $cacheDir = 'cache/', $cacheTime = 3600) {
    // Buat nama file cache berdasarkan URL
    $cacheFile = $cacheDir . md5($targeturl) . '.cache';

    // Cek apakah file cache ada dan masih valid
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        // Jika file cache ada dan masih valid, baca dari cache
        return file_get_contents($cacheFile);
    } else {
        // Jika file cache tidak ada atau sudah kadaluarsa, ambil data dari web
        $data = getData($targeturl);

        // Simpan hanya jika datanya valid
        if (!empty($data)) {
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            file_put_contents($cacheFile, $data);
            return $data;
        } else {
            error_log("Gagal mengambil data dari URL: $targeturl");
            return false;
        }
    }
}

function lp_produk() {
  global $settings, $menu, $datasponsor, $weburl, $logoweb, $visiturl, $favicon;
  if (!isset($settings['favicon'])) { $favicon = 'img/simpleaff-favicon.png'; } else { $favicon = 'upload/'.$settings['favicon']; }
  if (!isset($settings['logoweb'])) { $logoweb = 'img/simpleaff-logo.png'; } else { $logoweb = 'upload/'.$settings['logoweb']; }
  if (file_exists('theme/'.$settings['theme'].'/saproduk.php')) { 
    include('theme/'.$settings['theme'].'/saproduk.php');
  } else {
    include('theme/simple/saproduk.php');
  }
}

function proses_register($form) {
  $hasil['status'] = $hasil['pesan'] = '';
  if (isset($form['nama']) && !empty($form['nama']) && isset($form['email']) && validemail($form['email'])) {
    
    if (isset($settings['recap_secret']) && !empty($settings['recap_secret'])) {
      $secretKey = $settings['recap_secret'];

      // Data yang dikirimkan oleh formulir
      $recaptchaResponse = $form['g-recaptcha-response'];

      // Mendekripsi dan memeriksa respons reCAPTCHA menggunakan cURL
      $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, [
          'secret' => $secretKey,
          'response' => $recaptchaResponse,
      ]);

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($ch);
      curl_close($ch);

      // Menguraikan respons JSON
      $result = json_decode($response, true);

      // Memeriksa apakah verifikasi reCAPTCHA berhasil
      if ($result && isset($result['success']) && $result['success']) {
          // Proses formulir atau lakukan tindakan yang diinginkan di sini
          $formok = 1;
      } else {
        $hasil['status'] = false;
        $hasil['pesan'] = '<strong>Error!</strong> Verifikasi reCAPTCHA gagal';
      }
    } else {
      $formok = 1;
    }

    if (isset($formok) && $formok == 1) {

      if (db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='".cek($form['email'])."'")) {
        $error = 'Email sudah ada yang menggunakan';
      }

      # Cek form yg required

      $req = db_select("SELECT * FROM `sa_form` WHERE `ff_registrasi`=1 AND `ff_required`=1");
      if (count($req) > 0) {
        foreach ($req as $req) {
          if (!isset($form[$req['ff_field']]) || empty($form[$req['ff_field']])) {
            $error = $req['ff_label'].' wajib diisi';
          } else {
            if ($req['ff_field'] == 'whatsapp') {
              if (empty(formatwa($form['whatsapp']))) {
                $error = $req['ff_label'].' wajib diisi dg format 08123456789';
              }
            }
          }
        }
      }

      if (!isset($error)) {
        if (isset($form['sponsor']) && !empty($form['sponsor'])) {
          $sponsor = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_kodeaff`='".txtonly(strtolower($form['sponsor']))."'");
          
          if (is_numeric($sponsor)) {
            $idsponsor = $sponsor;
          } 
        } else {
          if (isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor'])) {
            $idsponsor = $_COOKIE['idsponsor'];
          } else {
            $idsponsor = 1;
          }
        }

        $defaultkey = array('nama','email','password','whatsapp','kodeaff');
        $datalain = '';
        
        unset($kodeaff);

        foreach ($form as $key => $value) {
          if (in_array($key, $defaultkey)) {
            ${$key} = cek($value);
          } else {
            $datalain .= '['.txtonly(strtolower($key)).'|'.cek($value).']';
          }
        }             

        if (isset($_FILES) && count($_FILES) > 0) {
          $max_size = 1024000;
          $whitelist_ext = array('jpeg','jpg','png','gif');
          $whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
          $pic_dir = str_replace('fungsi.php','upload',__FILE__);
          $memberid = 'XXX'.rand(1000,9999).'XXX';
          
          if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }

          foreach($_FILES as $field => $files) {
            $filename = $memberid.'_'.$field;
            $target_file = $pic_dir.'/'.$filename;
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($files["name"],PATHINFO_EXTENSION));
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
              $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
              $uploadOk = 0;
            }
            //Check that the file is of the right type
            if (!in_array($files["type"], $whitelist_type)) {
              $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
              $uploadOk = 0;
            }
            // Check file size
            if ($files["size"] > $max_size) {
              $txterror = 'Maaf, gambar terlalu besar. Max. 1Mb';
              $uploadOk = 0;
            }
            if ($uploadOk == 1) {
              $file = $files["tmp_name"];
              $target_file = $target_file.'.'.$imageFileType;
              $img = new Imagick();
              $img->readImage($file);
              $width = $img->getImageWidth();
              if ($width > 800) {
                  $width = 800;
              }
              $img->setimagebackgroundcolor('white');
              //$img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
              $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
              $img->setImageCompression(Imagick::COMPRESSION_JPEG);
              $img->setImageCompressionQuality(80);
              $img->resizeImage($width,800,Imagick::FILTER_CATROM,1,TRUE);
              $img->stripImage();
              $img->writeImage($target_file);
              #$gambar = $target_file.'.'.$imageFileType;
              $datalain .= '['.txtonly(strtolower($field)).'|'.$filename.'.'.$imageFileType.']'; 
            }
          }
        }
        
        if (!isset($password) || empty($password)) { $password = randomword(); } else { $password = $form['password']; }

        if (!isset($kodeaff)) { $kodeaff = $nama; }
        $kodeaff = cekkodeaff(txtonly(strtolower($kodeaff)));
        if (isset($whatsapp)) { $whatsapp = formatwa($whatsapp); } else { $whatsapp = ''; }
                    
        $newuserid = db_insert("INSERT INTO `sa_member` (
          `mem_nama`,`mem_email`,`mem_password`,`mem_whatsapp`,`mem_kodeaff`,
          `mem_datalain`,`mem_tgldaftar`,`mem_status`,`mem_role`) 
        VALUES ('".$nama."','".$email."','".create_hash($password)."',
          '".$whatsapp."','".$kodeaff."','".$datalain."','".date('Y-m-d H:i:s')."',
          1,1)");

        
        if (is_numeric($newuserid)) {
          $network = '['.numonly($idsponsor).']'.db_var("SELECT `sp_network` FROM `sa_sponsor` WHERE `sp_mem_id`=".$idsponsor);
          $cek = db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`,`sp_sponsor_id`,`sp_network`) VALUES ($newuserid,$idsponsor,'".$network."')");
          echo db_error();
          if (isset($memberid)) {
            $datalain = str_replace($memberid,$newuserid,$datalain);
            db_query("UPDATE `sa_member` SET `mem_datalain`='".$datalain."' WHERE `mem_id`=".$newuserid);
            $files = glob($pic_dir . '/'.$memberid.'*');          
            // Loop semua file yang ditemukan dan ganti nama file
            foreach ($files as $file) {
                // Buat nama file baru dengan mengganti teks XXX123XXX dengan ID member baru
                $newName = str_replace($memberid, $newuserid, $file);
                // Ganti nama file
                rename($file, $newName);
            }
          }
          # Kirim Notif yuk             
          $customfield['newpass'] = $password;
          sa_notif('daftar',$newuserid,$customfield);
          
        } else {
          $hasil['status'] = false;
          $hasil['pesan'] = '<strong>Error!</strong> '.db_error();
        }
        
        if (isset($cek)) {
          if ($cek === false) {
            $hasil['status'] = false;
            $hasil['pesan'] = '<strong>Error!</strong> '.db_error();
          } else {
            if (isset($settings['reg_sukses']) && !empty($settings['reg_sukses'])) {
              $hasil['status'] = true;
              $hasil['pesan'] = '
              <script type="text/javascript">
              <!--
              window.location = "'.$settings['reg_sukses'].'"
              //-->
              </script>';
            } else {
              $hasil['status'] = true;
              $hasil['pesan'] = '<strong>Ok!</strong> Pendaftaran berhasil. Silahkan <a href="login">login ke dashboard</a>';
            }
          }
        }             
      } else {
        $hasil['status'] = false;
        $hasil['pesan'] = '<strong>Error!</strong> '.$error;
      }
    } #form ok
  }
  return $hasil;
}

include('fungsimodul.php');

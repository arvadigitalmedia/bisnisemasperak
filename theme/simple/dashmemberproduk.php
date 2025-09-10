<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Daftar Produk yg Tersedia';
showheader($head);
?>

<div class="table-responsive">
<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>Nama Produk</th>
			<th class="text-end">Harga</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody class="table-group-divider">
		<?php 
		$data = db_select("SELECT * FROM `sa_page` WHERE `pro_harga` IS NOT NULL AND `pro_status`=1");
		$order = db_select("SELECT * FROM `sa_order` WHERE `order_idmember`=".$datamember['mem_id']." AND `order_status`=1");
		if (count($order) > 0) {
			foreach ($order as $order) {
				$orderlist[$order['order_idproduk']] = 1;
			}
		}
		if (count($data) > 0) {			
			foreach ($data as $data) {
				if ($settings['khususpremium'] == 1 && $datamember['mem_status'] < 2) {
					$urlaff = '<em>URL Affiliasi khusus Premium Member</em>';
				} elseif ($data['pro_status'] == 0) {
					$urlaff = '<em>Produk dinonaktifkan</em>';
				} else {
					$urlaff = '<a href="'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'" target="_blank">
					'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'</a>
					&nbsp;&nbsp;<a onclick="copyToClipboard(\''.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'\')" 
            style="text-decoration:none;cursor: pointer;" title="Copy to Clipboard">
          <i class="fa-regular fa-copy"></i></a> 
					';
				}
				echo '
			<tr>
			<td>
			<a href="#" class="info" data-target="konten'.$data['page_id'].'">'.$data['page_judul'].'</a>
			<div class="konten'.$data['page_id'].' konten mt-2">
				<p>';
				if (isset($data['pro_img']) && !empty($data['pro_img'])) {
					echo '<img src="'.$weburl.'upload/'.$data['pro_img'].'" class="float-start me-2" style="width:150px" alt="'.$data['page_judul'].'"/>';
				}
				echo '
				'.$data['page_diskripsi'].'</p>
				<p>URL Affiliasi: '.$urlaff.'</p>         
			</div>
			</td>
			<td class="text-end">'.number_format($data['pro_harga']).'</td>
			<td class="text-end">';
			if (isset($orderlist[$data['page_id']]) || $data['pro_harga'] == 0) {
				echo '<a href="'.$weburl.'dashboard/akses/'.$data['page_url'].'" class="btn btn-sm btn-success" target="_blank">Akses</a>';					
			} elseif ($data['pro_status'] == 1) {
				echo '<a href="'.$weburl.'order/'.$data['page_url'].'" class="btn btn-sm btn-primary" target="_blank">Order</a>';
			}
			echo '
			</td>
			</tr>';
			}  				
		}
		?>
	</tbody>
</table>
</div>
<script>
  function copyToClipboard(text) {
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
    alert("Data copied to clipboard!");
  }
</script>
<?php showfooter(); ?>
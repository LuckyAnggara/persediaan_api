<?php 
     function rupiah($angka){
         $hasil_rupiah = "Rp. " . number_format($angka,0,',','.');
         return $hasil_rupiah;
     }
	 ;?>
<html>
<head>
	<title>Tanda Terima Penggajian</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<style type="text/css">
	
		table tr td,
		table tr th{
			font-size: 9pt;
		}

	</style>
</head>
<body class="section">
	
		<h5>Laporan Penggajian <br> <span>Tanggal : {{$master->created_at->format('d F Y')}}</span></h4>
 
	<table class='table table-bordered'>
		<thead>
			<tr>
				<th>No</th>
				<th>Nama</th>
				<th>Jabatan</th>
				<th>Gaji Pokok</th>
				<th>Uang Makan</th>
				<th>Bonus</th>
				<th>Total</th>
				<th>Tanda Tangan</th>
			</tr>
		</thead>
		<tbody>
			@php $i=1 @endphp
			@foreach($detail as $p)
			<tr>
				<td>{{ $i++ }}</td>
				<td>{{$p->nama_pegawai}}</td>
				<td>{{$p->nama_jabatan}}</td>
				<td>{{rupiah($p->gaji_pokok)}}</td>
				<td>{{rupiah($p->uang_makan)}}</td>
				<td>{{rupiah($p->bonus)}}</td>
				<td>{{rupiah($p->bonus + $p->uang_makan + $p->gaji_pokok)}}</td>
				<td></td>
			</tr>
			@endforeach
		</tbody>
		<tfoot>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td><b>{{rupiah($total['gaji_pokok'])}}</b></td>
				<td><b>{{rupiah($total['uang_makan'])}}</b></td>
				<td><b>{{rupiah($total['bonus'])}}</b></td>
				<td><b>{{rupiah($total['grand_total'])}}</b></td>
				<td></td>
			</tr>
		</tfoot>
	</table>
 
</body>
</html>
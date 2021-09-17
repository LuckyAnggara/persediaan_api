<?php 
     function rupiah($angka){
         $hasil_rupiah = "Rp. " . number_format($angka,0,',','.');
         return $hasil_rupiah;
     }
	 ;?>
<html>
<head>
	<title>Laporan Transaksi Penjualan</title>
	<style>
	table, th, td {
		border-collapse: collapse;
	  	border: 1px solid black;
	  	padding: 5px;
	}
	table tr td,
	table tr th{
		font-size: 9pt;
	}
	</style>
</head>
<body class="section">
	
	<h5>Laporan Transaksi Penjualan <br> <span>Tanggal : {{$payload->input('tanggal_awal')}} s.d {{$payload->input('tanggal_akhir')}}</span></h5>
 
	<table style="width:100%">
		<thead>
			<tr>
				<th>No</th>
				<th>Tanggal Transaksi</th>
				<th>Nomor Transaksi</th>
				<th>Nama Pelanggan</th>
				<th>Grand Total</th>
				<th>Status</th>
				<th>Nama Sales</th>
				<th>Nama Petugas</th>
			</tr>
		</thead>
		<tbody>
			@php $i=1; $total=0; $piutang=0; @endphp
			@foreach($master as $p)
			@php $total += $p['invoice']['grandTotal']; @endphp
			@php $piutang += $p['pembayaran']['sisaPembayaran']; @endphp
			<tr>
				<td>{{ $i++ }}</td>
				<td>{{$p['tanggalTransaksi']}}</td>
				<td>{{$p['nomorTransaksi']}}</td>
				<td>{{$p['pelanggan']->nama}}</td>
				<td>{{rupiah($p['invoice']['grandTotal'])}}</td>
				<td>{{$p['pembayaran']['sisaPembayaran'] == 0 ? 'LUNAS' : rupiah($p['pembayaran']['sisaPembayaran'])}}</td>
				<td>{{$p['sales'] == null ? '-' : $p['sales']['nama'] }}</td>
				<td>{{$p['user']->nama}}</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<br>
	<h5>Summary</h5>
	<table>
		<tbody>
			<tr>
				<td>Total Transaksi</td>
				<td>{{count($master)}}</td>
			</tr>
			<tr>
				<td>Total Nominal Transaksi</td>
				<td>{{rupiah($total)}}</td>
			</tr>
			<tr>
				<td>Total Piutang Usaha</td>
				<td>{{rupiah($piutang)}}</td>
			</tr>
		</tbody>
	</table>
 
</body>
</html>
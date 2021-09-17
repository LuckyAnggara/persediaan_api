<?php 
     function rupiah($angka){
         $hasil_rupiah = "Rp. " . number_format($angka,0,',','.');
         return $hasil_rupiah;
     }
	 ;?>
<html>
<head>
	<title>Laporan Persediaan</title>
</head>
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

<body class="section">
	
	<h5>Laporan Persediaan 
		<br> 
		<span>Tanggal :{{$payload->tanggal_akhir}}</span>
		<br>
		<span>Gudang :{{$gudang->nama}}</span>
	</h5>
 
	<table style="width:100%">
		<thead>
			<tr>
				<th>No</th>
				<th>Kode Barang</th>
				<th>Nama Barang</th>
				<th>Saldo (Qty)</th>
				<th>Saldo (Rupiah)</th>
			</tr>
		</thead>
		<tbody>
			@php $i=1; $total=0; @endphp
			@foreach($master as $p)
			<tr>
				@php $total += $p->persediaan['saldo_rp']; @endphp
				<td>{{ $i++ }}</td>
				<td>{{$p->kode_barang}}</td>
				<td>{{$p->nama}}</td>
				<td>{{$p->persediaan['saldo']}}</td>
				<td>{{rupiah($p->persediaan['saldo_rp'])}}</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<br>
	<h5>Summary</h5>
	<table>
		<tbody>
			<tr>
				<td>Total Persediaan</td>
				<td><b>{{rupiah($total)}}</b></td>
			</tr>
			{{--  <tr>
				<td>Total Nominal Transaksi</td>
				<td>{{rupiah($total)}}</td>
			</tr>
			<tr>
				<td>Total Piutang Usaha</td>
				<td>{{rupiah($piutang)}}</td>
			</tr> --}}
		</tbody>
	</table>
 
</body>
</html>
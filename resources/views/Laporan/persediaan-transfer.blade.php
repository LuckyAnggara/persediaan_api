<?php 
     function rupiah($angka){
         $hasil_rupiah = "Rp. " . number_format($angka,0,',','.');
         return $hasil_rupiah;
     }
	 ;?>
<html>
<head>
	<title>Laporan Transfer Persediaan</title>
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
	
		<h5>Laporan Transfer Persediaan</h5>
		<h6>Tanggal : {{$master->created_at->format('d F Y')}}</h6>
		<h6>Nomor Transfer :{{$master->nomor_transfer}} </h6>
		<h6>Dari Gudang :{{$master->dari['nama']}} </h6>
		<h6>Ke Gudang :{{$master->ke['nama']}} </h6>
		<br>
 
	<table style="width:100%">
		<thead>
			<tr>
				<th>No</th>
				<th>Kode Barang</th>
				<th>Nama Barang</th>
				<th>Jumlah</th>
			</tr>
		</thead>
		<tbody>
			@php $i=1;  @endphp
			@foreach($master->detail as $p)
			<tr>
				<td>{{ $i++ }}</td>
				<td>{{$p->kode_barang}}</td>
				<td>{{$p->nama}}</td>
				<td>{{$p->jumlah}}</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<br>
	<h5>Summary</h5>
	<table>
		<tbody>
			{{-- <tr>
				<td>Total Persediaan</td>
				<td>{{rupiah($total)}}</td>
			</tr> --}}
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
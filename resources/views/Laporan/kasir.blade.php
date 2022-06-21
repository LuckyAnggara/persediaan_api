<?php 
     function rupiah($angka){
         $hasil_rupiah = "Rp. " . number_format($angka,0,',','.');
         return $hasil_rupiah;
     }
	 ;?>
<html>
<head>
	<title style="margin-bottom:10px">Laporan Kasir</title>

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
	
	<h4>Laporan Kasir <br> <span>Tanggal : {{$payload->input('hari')}}</span></h4>
	
	<h5>Nama Kasir : {{$user->pegawai['nama']}}</h5>
	<div>
		<h4>Laporan Penjualan</h4>
		<table style="width:100%">
			<thead>
				<tr>
					<th>No</th>
					<th>Nomor Transaksi</th>
					<th>Nama Pelanggan</th>
					<th>Grand Total</th>
					<th>Status</th>
					<th>Nama Sales</th>
				</tr>
			</thead>
			<tbody>
				@php $i=1; $total=0; $piutang=0; @endphp
				@foreach($penjualan as $p)
				@php $total += $p['invoice']['grandTotal']; @endphp
				@php $piutang += $p['pembayaran']['sisaPembayaran']; @endphp
				<tr>
					<td>{{ $i++ }}</td>
					<td>{{$p['nomorTransaksi']}}</td>
					<td>{{$p['pelanggan']->nama}}</td>
					<td>{{rupiah($p['invoice']['grandTotal'])}}</td>
					<td>{{$p['pembayaran']['sisaPembayaran'] == 0 ? 'LUNAS' : rupiah($p['pembayaran']['sisaPembayaran'])}}</td>
					<td>{{$p['sales'] == null ? '-' : $p['sales']['nama'] }}</td>
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
					<td>{{count($penjualan)}}</td>
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
	</div>

	<div>
		<h4>Laporan Kas</h4>
		<table style="width:80%">
			<thead>
				<tr>
					<th>No</th>
					<th>Nomor Jurnal</th>
					<th>Debit</th>
					<th>Kredit</th>
					<th>Saldo</th>
					<th>Keterangan</th>
				</tr>
			</thead>
			<tbody>
				{{-- {{ $user }} --}}
				@php $i=1; @endphp
				@foreach($kas['ledger'] as $kas)
				<tr>
					<td>{{ $i++ }}</td>
					<td>{{$kas['nomor_jurnal']}}</td>
					<td>{{rupiah($kas['jenis'] === 'DEBIT' ? $kas['nominal']: '0')}}</td>
					<td>{{rupiah($kas['jenis'] === 'KREDIT' ? $kas['nominal'] : '0')}}</td>
					<td>{{rupiah($kas['saldo'])}}</td>
					<td>{{$kas['keterangan']}}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	
		<br>
		<h5>Summary</h5>
		<table>
			<tbody>
				<tr>
					<td>Saldo Akhir</td>
					<td>{{rupiah($kas['saldo'])}}</td>
				</tr>
				
			</tbody>
		</table>
	</div>
	
 
</body>
</html>
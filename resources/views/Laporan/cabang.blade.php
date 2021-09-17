<?php 
     function rupiah($angka){
         $hasil_rupiah = "Rp. " . number_format($angka,0,',','.');
         return $hasil_rupiah;
     }
	 ;?>
<html>
<head>
	<title>Laporan Cabang</title>
	<style>
		
		.table {
			border-collapse: collapse;
			border: 1px solid black;
			padding: 5px;
			font-size: 9pt;
		}
		.table tr td,
		.table tr th{
		}
	</style>
</head>
<body class="section">
	
		<h4>Laporan Cabang {{$master->nama}}</h4>
		{{$payload->input('bulan') != null ? 'Bulan '. $payload->input('bulan') .' '. date("Y") : 'Tanggal '. $payload->input('tanggal') }}</h4>

		<h5>Summary</h5>
		<table>
			<tbody>

				<tr>
					<td>Total Kas </td>
					<td> : </td>
					<td> {{rupiah($master->kas)}}</td>
				</tr>
				<tr>
					<td><hr></td>
				</tr>
				<tr>
					<td>Total Penjualan</td>
					<td> : </td>
					<td> {{rupiah($master->penjualan)}}</td>
				</tr>
				<tr>
					<td>Total Persediaan Barang</td>
					<td> : </td>
					<td> Rp. 5.000.000,-</td>
				</tr>
				<tr>
					<td>Total Beban Operasional</td>
					<td> : </td>
					<td> Rp. 5.000.000,-</td>
				</tr>
				<tr>
					<td>Total Beban Gaji</td>
					<td> : </td>
					<td> Rp. 5.000.000,-</td>
				</tr>
				<tr>
					<td>Total Beban Lain - Lain</td>
					<td> : </td>
					<td> Rp. 5.000.000,-</td>
				</tr>
				<tr>
					<td><hr></td>
				</tr>
				<tr>
					<td>Laba / Rugi </td>
					<td> : </td>
					<td> Rp. 5.000.000,-</td>
				</tr>
				<tr>
					<td><hr></td>
				</tr>
				<tr>
					<td>Total Utang </td>
					<td> : </td>
					<td> Rp. 5.000.000,-</td>
				</tr>
				<tr>
					<td>Total Piutang </td>
					<td> : </td>
					<td> Rp. 5.000.000,-</td>
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
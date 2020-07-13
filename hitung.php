<?php
function mysqli_fetch_rows($koneksi, $query)
{
    $result = array();
    $query = mysqli_query($koneksi, $query);
    while($data = mysqli_fetch_assoc($query))
    {
        $result[] = $data;
    }
    return $result;
}

$db = mysqli_connect("localhost", "root", "mysql", "db_kredit");

$data_awal = mysqli_fetch_assoc(mysqli_query($db, "SELECT 
SUM(IF(keterangan = 'Layak', 1, 0)) as layak,
SUM(IF(keterangan = 'Tidak Layak', 1, 0)) as tidak_layak    
from training"));


$HASIL = array();

$DATA_AWAL = array();

$DATA_AWAL['layak'] = $data_awal['layak'];
$DATA_AWAL['tidak_layak'] = $data_awal['tidak_layak'];
$DATA_AWAL['total'] = $DATA_AWAL['layak'] + $DATA_AWAL['tidak_layak'];
$DATA_AWAL['peluang_layak'] = $DATA_AWAL['layak']/$DATA_AWAL['total'];
$DATA_AWAL['peluang_tidak_layak'] = $DATA_AWAL['tidak_layak']/$DATA_AWAL['total'];

$DATA_NASABAH = mysqli_fetch_rows($db, "select * from nasabah");

foreach ($DATA_NASABAH as $no => $data)
{
	$daftar_kolom = array_keys($data);

	$hasil_peluang_layak = $DATA_AWAL['peluang_layak'];
	$hasil_peluang_tidak_layak = $DATA_AWAL['peluang_tidak_layak'];
	unset($daftar_kolom[0]);
	unset($daftar_kolom[1]);
	unset($daftar_kolom[3]);
	unset($daftar_kolom[5]);
	unset($daftar_kolom[13]);
	
	foreach ($daftar_kolom as $i => $kolom)
	{
		// hitung berapa banyak data dengan kondisi Layak
		$banyak_data = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(".$kolom.") AS banyak FROM training WHERE keterangan = 'Layak' AND ".$kolom." = '".$data[$kolom]."'"));

		if(!empty($banyak_data))
		{
			$peluang_layak = $banyak_data['banyak']/$DATA_AWAL['layak'];
		}

		// hitung berapa banyak data dengan kondisi Tidak Layak Layak
		$banyak_data = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(".$kolom.") AS banyak FROM training WHERE keterangan = 'Tidak Layak' AND ".$kolom." = '".$data[$kolom]."'"));
		if(!empty($banyak_data))
		{
			$peluang_tidak_layak = $banyak_data['banyak']/$DATA_AWAL['tidak_layak'];
		}

		$hasil_peluang_layak *= $peluang_layak;
		$hasil_peluang_tidak_layak *= $peluang_tidak_layak;
	}


	$HASIL[] = $data;
	$HASIL[$no]["layak"] = $hasil_peluang_layak;
	$HASIL[$no]["tidak_layak"] = $hasil_peluang_tidak_layak;
	$HASIL[$no]["HASIL"] = $hasil_peluang_tidak_layak;
	$keterangan = "";

	if($hasil_peluang_layak > $hasil_peluang_tidak_layak)
	{
		$keterangan = "Layak";
	}
	else
	{
		$keterangan = "Tidak Layak";
	}

	// update keterangan
	mysqli_query($db, "UPDATE nasabah SET keterangan = '".$keterangan."' WHERE id_nasabah = ".$data['id_nasabah']);
}

?>

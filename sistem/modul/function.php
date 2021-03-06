<?php
/* function.php ------------------------------------------------------
   	version: 1.5.0

	Part of AhadPOS : http://ahadpos.com
	License: GPL v2
			http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
			http://vlsm.org/etc/gpl-unofficial.id.html

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License v2 (links provided above) for more details.
----------------------------------------------------------------*/


function uang($duit) {
    if(!$duit) {
        $duit= 0;
    }
    $duit="".str_replace(",",".",number_format($duit)).""; # Melakukan Format bilangan untuk pembagian digit 3 mis: 10000 menjadi 10.000
    return $duit;
}


function getKasAwal($idUser){
    $tgl = date("Y-m-d");
    $queryKas = mysql_query("SELECT kasAwal FROM transaksikas where idUser = '$idUser' and tglTransaksiKas = '$tgl'");
    $dataKas = mysql_fetch_array($queryKas);
    $kas = $dataKas[kasAwal];

    return $kas;
}
function getUangKasir($idUser){
    $tgl = date("Y-m-d");
    $query = mysql_query("SELECT sum(nominal) as uang FROM transaksijual where tglTransaksiJual = '$tgl' and idUser = '$idUser'");
    $dataUang = mysql_fetch_array($query);
    $uang = $dataUang[uang];
    if ($uang == null){
        return $uang = 0;
    }
    else{
        return $uang;
    }
}

function findSupplier($idSupplier){
    $sql = "SELECT * from supplier WHERE idSupplier = '$idSupplier'";
    $query = mysql_query($sql);
    $dataSupplier = mysql_fetch_array($query);

    #session_register("idSupplier");
    #session_register("namaSupplier");
    $_SESSION[idSupplier] = $dataSupplier[idSupplier];
    $_SESSION[namaSupplier] = $dataSupplier[namaSupplier];
}

function releaseSupplier(){
    #session_unregister("idSupplier");
    #session_unregister("namaSupplier");
}

function findCustomer($idCustomer){
    $query = mysql_query("SELECT * from customer WHERE idCustomer = '$idCustomer'");
    $dataCustomer = mysql_fetch_array($query);

    #session_register("idCustomer");
    #session_register("namaCustomer");
    $_SESSION[idCustomer] = $dataCustomer[idCustomer];
    $_SESSION[namaCustomer] = $dataCustomer[namaCustomer];
}

function releaseCustomer(){
    #session_unregister("idCustomer");
    #session_unregister("namaCustomer");
    $_SESSION[tot_pembelian] = 0;
}

function cekBarang($barcode){
	// jika ada banyak barang dengan barcode yang sama, kembalikan record yang terbaru
	$sql ="SELECT b.idBarang, b.namaBarang, b.hargaJual, b.barcode, d.hargaBeli FROM barang AS b, detail_beli AS d 
        	    WHERE b.barcode = '$barcode' AND d.barcode = '$barcode' ORDER BY d.idBarang DESC LIMIT 1";
	//echo $sql;
    $query = mysql_query($sql);
    $data = mysql_fetch_array($query);

	//HS jika tidak ada data yang ditemukan - mungkin baru ada di tabel barang, tapi belum ada di detail_beli
	if (mysql_num_rows($query) < 1) {
		$sql = "SELECT idBarang, namaBarang, hargaJual, barcode FROM barang WHERE barcode = '$barcode'";
		$query = mysql_query($sql);
		$data = mysql_fetch_array($query);
	};

    return $data;
}

function cekBarangTemp($idSupplier,$barcode){
    $adaBeli = 0;
    $cek = mysql_query("SELECT * from tmp_detail_beli where idSupplier = '$idSupplier' and barcode = '$barcode'");
    $adaBeli = mysql_num_rows($cek);

    return $adaBeli;
}

function cekBarangTempJual($idCustomer,$barcode){
    $adaJual = 0;
	$sql = "SELECT * from tmp_detail_jual where idCustomer = '$idCustomer' and barcode = '$barcode' and username = '$_SESSION[uname]'";
	//echo $sql;
    $cek = mysql_query($sql);
    $adaJual = mysql_num_rows($cek);

    return $adaJual;
}


function tambahBarangAda($idSupplier,$barcode,$jumBarang){
    $ambilJumBarang = mysql_query("SELECT jumBarang FROM tmp_detail_beli WHERE idSupplier = '$idSupplier' and barcode = '$barcode'");
    $dataJum = mysql_fetch_array($ambilJumBarang);
    $jumlah = $dataJum[jumBarang] + $jumBarang;
    mysql_query("UPDATE tmp_detail_beli SET jumBarang = '$jumlah' WHERE idSupplier = '$idSupplier' and barcode = '$barcode'");
}

function tambahBarang($idSupplier,$barcode,$jumBarang,$hargaBeli,$hargaJual,$tglExpire){
    $tgl = date("Y-m-d");
    mysql_query("INSERT into tmp_detail_beli(idSupplier, tglTransaksi,
                        barcode,tglExpire,jumBarang,hargaBeli,hargaJual,username)
                    VALUES('$idSupplier','$tgl','$barcode','$tglExpire',
                        '$jumBarang','$hargaBeli','$hargaJual','$_SESSION[uname]')");
}

function tambahBarangJualAda($idCustomer,$barcode,$jumBarang){
    $jumlah = 0;

//  if($jumBarang==0){
//  quantity can not be 0 (zero) or less than that
    if($jumBarang < 1){
        $jumlah = 1;
    }
    else{
        $jumlah = $jumBarang;
    }

    $ambilJumBarang = mysql_query("SELECT jumBarang FROM tmp_detail_jual WHERE idCustomer = '$idCustomer' AND barcode = '$barcode' AND username='$_SESSION[uname]'");
    $dataJum = mysql_fetch_array($ambilJumBarang);
    
    $jumlah = $jumlah + $dataJum[jumBarang];
    
	$tgl = date("Y-m-d H:i:s");

    mysql_query("UPDATE tmp_detail_jual SET jumBarang = '$jumlah', tglTransaksi = '$tgl'
		 WHERE idCustomer = '$idCustomer' AND barcode = '$barcode' AND username='$_SESSION[uname]'");
}

function tambahBarangJual($barcode,$jumBarang){
    //cekBarangTempJual($idBarang);
    $dataAda = cekBarang($barcode);
    if ($dataAda != 0){
        $jumlah = 0;
//      if($jumBarang==0){
	// quantity can not be 0 (zero) or less than that
        if($jumBarang<1){
            $jumlah = 1;
        }
        else{
            $jumlah = $jumBarang;
        }
        $tgl = date("Y-m-d H:i:s");
        $jualBarang = mysql_query("SELECT * FROM barang WHERE barcode = '$barcode'") or die(mysql_error());
        $jual = mysql_fetch_array($jualBarang);

// bugfix :
//	"ORDER BY idDetailBeli" diganti menjadi "ORDER BY idTransaksiBeli"
//	karena, banyak database di berbagai toko Ahad mart yang isi idDetailBeli nya ngaco 
//	(banyak field idDetailBeli yang isinya 0 [nol])

	// cari hargaBeli & idBarang nya
	$sql = "SELECT * FROM detail_beli 
		WHERE isSold = 'N' AND barcode = '$barcode' AND jumBarang > 0 
		ORDER BY idTransaksiBeli ASC LIMIT 1";
	//echo $sql;
	$hasil = mysql_query($sql);
	if (mysql_num_rows($hasil) < 1) { 	// jika tidak ada / stok sudah habis semua, coba cari lagi dengan menyertakan stok barang = 0
						// tampilkan stok yang terakhir dibeli (ORDER BY idDetailBeli DESC)
		$sql = "SELECT * FROM detail_beli 
			WHERE barcode = '$barcode'  
			ORDER BY idTransaksiBeli DESC LIMIT 1";
		$hasil = mysql_query($sql);
	}

	$detilBarang = mysql_fetch_array($hasil);
	if (mysql_num_rows($hasil) > 0) {
		$hargaBeli = $detilBarang[hargaBeli];
		$idBarang  = $detilBarang[idBarang];	
	} else {
		// not supposed to ever happen, but just to be safe....
		//fixme: kalau seluruh stok barang sudah habis (sehingga jadi masuk ke blok ini)
			// -- coba lagi dengan record terakhir utk barang ybs di detail_beli, walaupun isSold=Y
		$hargaBeli = 0;
		$idBarang  = 0;	
	}

	// simpan transaksi di tmp_detail_jual
	$sql = "INSERT into tmp_detail_jual(idCustomer, tglTransaksi,
                            barcode,jumBarang,hargaBeli,hargaJual,username, idBarang)
                        VALUES('$_SESSION[idCustomer]','$tgl','$barcode',
                            '$jumlah','$hargaBeli','$jual[hargaJual]','$_SESSION[uname]', $idBarang)";
	//echo $sql;
        mysql_query($sql) or die(mysql_error());
    }
    else{
        echo "Barang tidak ada";
    }
}

function ubahJumlahBarangBeliTemp($idSupplier,$idBarang,$jumlah){
    mysql_query("UPDATE tmp_detail_beli SET jumBarang = '$jumlah'
            WHERE idSupplier = '$idSupplier' and idBarang = '$idBarang' and username = '$_SESSION[uname]'") or die(mysql_error());
}

function detailTransaksiBeli($idTransaksiBeli){
    $query = mysql_query("SELECT idTransaksiBeli, tglTransaksiBeli, namaSupplier, nominal, idTipePembayaran, NomorInvoice, namaUser
            FROM transaksibeli AS t, user AS u, supplier AS s 
            WHERE t.idSupplier = s.idSupplier AND t.username = u.uname
            AND t.idTransaksiBeli = '$idTransaksiBeli'") or die(mysql_error());
    return $query;
}
function detailBarangTransaksiBeli($idTransaksiBeli){
    $query = mysql_query("SELECT detail_beli.idBarang, barang.barcode, namaBarang, detail_beli.jumBarang, hargaBeli, tglExpire FROM detail_beli, barang
        WHERE barang.idBarang = detail_beli.idBarang and detail_beli.idTransaksiBeli = '$idTransaksiBeli'") or die(mysql_error());
    return $query;
}

function nominalBeli($idTransaksiBeli){
    $query = mysql_query("select sum(jumBarang*hargaBeli) as nominal from detail_beli
        where idTransaksiBeli = '$idTransaksiBeli'") or die(mysql_error());
    $dataQuery = mysql_fetch_array($query);
    $nominal = $dataQuery[nominal];
    mysql_query("UPDATE transaksibeli SET nominal = '$nominal' WHERE idTransaksiBeli = '$idTransaksiBeli'") or die(mysql_error());
    mysql_query("UPDATE hutang SET nominal = '$nominal' WHERE idTransaksiBeli = '$idTransaksiBeli'") or die(mysql_error());
    return $nominal;
}

function editBarangBeli($idTransaksiBeli,$idBarang,$jumBarangLama,$jumBarang,$hargaBeli){
    $queryJumBarang = mysql_query("SELECT jumBarang FROM barang WHERE idBarang = '$idBarang'");
    $jumBarangku = mysql_fetch_array($queryJumBarang);
    $jumBarangBaru = ($jumBarangku[jumBarang] - $jumBarangLama);
    $jumBarangBaru2 = $jumBarangBaru + $jumBarang;
    mysql_query("UPDATE barang SET jumBarang = '$jumBarangBaru2' WHERE idBarang = '$idBarang'") or die(mysql_error());
    mysql_query("UPDATE detail_beli SET jumBarang = '$jumBarang', hargaBeli = '$hargaBeli'
            WHERE idTransaksiBeli = '$idTransaksiBeli' and idBarang = '$idBarang'") or die(mysql_error());
}



function cetakStruk ($perintahPrinter, $nomorStruk, $namaKasir, $totalTransaksi, $uangDibayar, $arrayTransaksi, $strukRetur=false) {


	$totalRetur = 0;

	// ambil footer & header struk
	$sql   	= "SELECT `option`,`value` FROM config"; 
	$hasil 	= mysql_query($sql) or die(mysql_error());
	while ($x = mysql_fetch_array($hasil)) {
		if ($x[option]=='store_name') { $store_name=$x[value];};
		if ($x[option]=='receipt_header1') { $header1=$x[value];};
		if ($x[option]=='receipt_footer1') { $footer1=$x[value];};
		if ($x[option]=='receipt_footer2') { $footer2=$x[value];};
	};

	// siapkan string yang akan dicetak
	$struk  = str_pad($store_name, 40, " ", STR_PAD_BOTH) . "\n" . str_pad($header1, 40, " ", STR_PAD_BOTH) . "\n" 
		. str_pad($namaKasir ." : ". date("d-m-Y H:i") ." #$nomorStruk", 40, " ", STR_PAD_BOTH) ." \n";

	$struk .= "-------------------------------------\n";
	while ($x = mysql_fetch_array($arrayTransaksi)) {	

		if ($strukRetur) {
	                $struk .= $x[namaBarang]. " \n".$x[barcode].":"
                                ." ".$x[jumBarang]."x".number_format($x[hargaBeli],0,',','.')."="
                                .number_format(($x[hargaBeli] * $x[jumBarang]),0,',','.')."\n";
			$totalRetur = $totalRetur + ($x[hargaBeli] * $x[jumBarang]);
 
		} else {
			$struk .= $x[jumBarang] . "x ". $x[namaBarang]. " @".number_format($x[hargaJual],0,',','.').
					": ".number_format(($x[hargaJual] * $x[jumBarang]),0,',','.')."\n";
		};

	}
	$struk .= "-------------------------------------\n";
	
	if ($strukRetur) {

		$struk .= "                TOTAL   : ".number_format($totalRetur,0,',','.')." \n";

	} else {
		$struk .= "                TOTAL   : ".number_format($totalTransaksi,0,',','.')." \n";
		$struk .= "                Dibayar : ".number_format($uangDibayar,0,',','.')." \n";
		$struk .= "                Kembali : ".number_format(($uangDibayar-$totalTransaksi),0,',','.')." \n";
	};

	$struk .= "-------------------------------------\n";
	$struk .= str_pad($footer1, 40, " ", STR_PAD_BOTH) . "\n" . str_pad($footer2, 40, " ", STR_PAD_BOTH) . "\n\n\n\n\n\n\n\n\n\n\n\n\n";

	//fixme: cetak ke printer lainnya (bukan cuma LPR)
	$perintah = "echo \"$struk\" |lpr $perintahPrinter -l";

	//echo $perintah; exit;
	exec($perintah, $output);
}

//======================================//
function getSupplier(){
    $query = mysql_query("SELECT idSupplier, namaSupplier, alamatSupplier FROM supplier ORDER BY namaSupplier") or die(mysql_error());

    return $query;
}

function getDetailSupplier($id){
    $query = mysql_query("SELECT idSupplier, namaSupplier, alamatSupplier from supplier
            WHERE idSupplier = '$id'") or die(mysql_error());

    return $query;
}



function getDetailTmpEditReturPembelian($idNota){ // =================================================================================================
    $query = mysql_query("SELECT t.idDetailBeli, t.idBarang, t.tglExpire, t.jumBarang, t.hargaBeli, t.jumRetur,  
                    barang.namaBarang
                    FROM tmp_edit_detail_retur_beli AS t, barang
                    WHERE barang.barcode = t.barcode AND t.idTransaksiBeli = '$idNota';") or die(mysql_error());
    return $query;
}

function ubahTempEditDetailReturPembelian($idDetailBeli,$tglExpire,$jumBarang,$hargaBeli,$jumRetur){

	// sanity checks
	if ($jumRetur > $jumBarang) 	{ $jumRetur = $jumBarang;};
	if ($jumRetur < 0) 		{ $jumRetur = 0;};

    mysql_query("UPDATE tmp_edit_detail_retur_beli 
			SET tglExpire = '$tglExpire', jumBarang = '$jumBarang', hargaBeli = '$hargaBeli', jumRetur = $jumRetur  
			WHERE idDetailBeli = '$idDetailBeli'") or die(mysql_error());
}




function getDataPembelian($supplierId, $bulanLaporan, $tahunLaporan){ // ============================================================================
    if($bulanLaporan<10){
        $periode = $tahunLaporan."-0".$bulanLaporan;
    }
    else{
        $periode = $tahunLaporan."-".$bulanLaporan;
    }
    $query = mysql_query("SELECT transaksibeli.idTransaksiBeli as noNota, transaksibeli.tglTransaksiBeli as tglNota, transaksibeli.nominal as nominal
            FROM transaksibeli
            WHERE transaksibeli.idSupplier = '$supplierId' AND tglTransaksiBeli like '$periode%'") or die(mysql_error());
    return $query;
}

function getDataNotaPembelian($idNota){

	$sql = "SELECT supplier.namaSupplier, supplier.alamatSupplier, transaksibeli.tglTransaksiBeli as tglNota, transaksibeli.nominal,
	transaksibeli.NomorInvoice
        FROM transaksibeli, supplier
        WHERE transaksibeli.idSupplier = supplier.idSupplier AND transaksibeli.idTransaksiBeli = '$idNota'";
    
	$query = mysql_query($sql) or die(mysql_error());

    return $query;
}

function getDetailNotaPembelian($idNota){
	$sql = "SELECT detail_beli.idBarang, detail_beli.tglExpire, detail_beli.jumBarang, detail_beli.hargaBeli, detail_beli.barcode,
                    barang.namaBarang
                    FROM detail_beli, barang
                    WHERE barang.barcode = detail_beli.barcode AND detail_beli.idTransaksiBeli = '$idNota' 
			ORDER BY detail_beli.idBarang;";
	//echo $sql;
    $query = mysql_query($sql) or die(mysql_error());
    return $query;
}

function inputDataEditPembelianKeTemp($idNota){
    mysql_query("INSERT INTO tmp_edit_detail_beli(idDetailBeli,idTransaksiBeli,idBarang,tglExpire,jumBarang,hargaBeli)
                    SELECT detail_beli.idDetailBeli,detail_beli.idTransaksiBeli,detail_beli.idBarang,detail_beli.tglExpire,
                            detail_beli.jumBarang,detail_beli.hargaBeli
                            from detail_beli,barang where barang.idBarang = detail_beli.idBarang AND detail_beli.idTransaksiBeli = '$idNota' AND detail_beli.idTransaksiBeli != 0") or die(mysql_error());
}

function getDetailTmpEditPembelian($idNota){
    $query = mysql_query("SELECT tmp_edit_detail_beli.idDetailBeli, tmp_edit_detail_beli.idBarang, tmp_edit_detail_beli.tglExpire, tmp_edit_detail_beli.jumBarang, tmp_edit_detail_beli.hargaBeli, 
                    barang.namaBarang
                    FROM tmp_edit_detail_beli, barang
                    WHERE barang.idBarang = tmp_edit_detail_beli.idBarang AND tmp_edit_detail_beli.idTransaksiBeli = '$idNota';") or die(mysql_error());
    return $query;
}

function ubahTempEditDetailPembelian($idDetailBeli,$tglExpire,$jumBarang,$hargaBeli){
    mysql_query("UPDATE tmp_edit_detail_beli SET tglExpire = '$tglExpire', jumBarang = '$jumBarang', hargaBeli = '$hargaBeli' WHERE idDetailBeli = '$idDetailBeli'") or die(mysql_error());
}

function getJumBarangDiBarang($idDetailBeli, $barcode=''){
	
if ($barcode == '') {
	$sql	= "SELECT barang.jumBarang FROM barang, tmp_edit_detail_retur_beli AS t WHERE t.idBarang = barang.idBarang AND t.idDetailBeli = '$idDetailBeli'";
} else {
	$sql	= "SELECT barang.jumBarang FROM barang, tmp_edit_detail_retur_beli AS t WHERE t.barcode = barang.barcode AND t.barcode = '$barcode'";
};
    $query = mysql_query($sql) or die(mysql_error());
    $jum = mysql_fetch_array($query);

    return $jum[jumBarang];
}

function getJumBarangDetailPembelian($idDetailBeli){
    $query = mysql_query("SELECT jumBarang FROM detail_beli WHERE idDetailBeli = '$idDetailBeli'") or die(mysql_error());
    $jum = mysql_fetch_array($query);
    return $jum[jumBarang];
}

function getDataPenjualan($idNota){
    $query = mysql_query("") or die(mysql_error());
}

function getDetailPenjualan($idNota){
    $query = mysql_query("SELECT detail_jual.idBarang, barang.namaBarang, detail_jual.jumBarang, detail_jual.hargaBeli
            FROM detail_jual, barang, transaksijual
            WHERE detail_jual.idBarang = barang.idBarang
            AND detail_jual.idTransaksiJual = transaksijual.idTransaksiJual
            AND transaksijual.idTransaksiJual = '$idNota'") or die(mysql_error());

    return $query;
}

function getDaftarBarangSupplier($idSupplier, $jumlahMin){
    $query = mysql_query("select idBarang, barcode, namaBarang, jumBarang from barang where idSupplier = '$idSupplier' AND jumBarang < $jumlahMin") or die(mysql_error());

    return $query;
}

function getBarangPesan($idBarang){
    $query = mysql_query("select idBarang, barcode, left(namaBarang,22) as namaBarang, jumBarang from barang where idBarang = '$idBarang'") or die(mysql_error());

    return $query;
}


// ==============================================================================================================================

function tambahBarangReturAda($barcode,$jumBarang){
    $jumlah = 0;
    if($jumBarang==0){
        $jumlah = 1;
    }
    else{
        $jumlah = $jumBarang;
    }

    $ambilJumBarang = mysql_query("SELECT jumBarang FROM tmp_detail_retur_barang WHERE barcode = '$barcode'");
    $dataJum = mysql_fetch_array($ambilJumBarang);
    
    $jumlah = $jumlah + $dataJum[jumBarang];
    
	$tgl = date("Y-m-d H:i:s");
    mysql_query("UPDATE tmp_detail_retur_barang SET jumBarang = '$jumlah', tglTransaksi = '$tgl'
		 WHERE barcode = '$barcode'");
}

function tambahBarangRetur($barcode,$jumBarang){
    //cekBarangTempJual($idBarang);
    $dataAda = cekBarang($barcode);
    if ($dataAda != 0){
        $jumlah = 0;
        if($jumBarang==0){
            $jumlah = 1;
        }
        else{
            $jumlah = $jumBarang;
        }
        $tgl = date("Y-m-d H:i:s");
        $jualBarang = mysql_query("SELECT * FROM barang WHERE barcode = '$barcode'") or die(mysql_error());
        $jual = mysql_fetch_array($jualBarang);

	// cari hargaBeli & idBarang nya
	$sql = "SELECT * FROM detail_beli WHERE isSold = 'N' AND barcode = '$barcode' ORDER BY idTransaksiBeli ASC LIMIT 1";
	//echo $sql;
	$hasil = mysql_query($sql);
	$detilBarang = mysql_fetch_array($hasil);
	if (mysql_num_rows($hasil) > 0) {
		$hargaBeli = $detilBarang[hargaBeli];
		$idBarang  = $detilBarang[idBarang];	
	} else {
		// not supposed to ever happen, but just to be safe....
		//fixme: kalau seluruh stok barang sudah habis (sehingga jadi masuk ke blok ini)
			// -- coba lagi dengan record terakhir utk barang ybs di detail_beli, walaupun isSold=Y
		$hargaBeli = 0;
		$idBarang  = 0;	
	}

	// simpan transaksi di tmp_detail_jual
	$sql = "INSERT into tmp_detail_retur_barang (tglTransaksi,
                            barcode,jumBarang,hargaBeli,hargaJual,username, idBarang)
                        VALUES('$tgl','$barcode',
                            '$jumlah','$hargaBeli','$jual[hargaJual]','$_SESSION[uname]', $idBarang)";
	//echo $sql;
        mysql_query($sql) or die(mysql_error());
    }
    else{
        echo "Barang tidak ada";
    }
}


function cekBarangTempRetur($barcode){
    $adaJual = 0;
	$sql = "SELECT * from tmp_detail_retur_barang where barcode = '$barcode' and username = '$_SESSION[uname]'";
	//echo $sql;
    $cek = mysql_query($sql);
    $adaJual = mysql_num_rows($cek);

    return $adaJual;
}

function check_user_access ($module_name) {

	$userid = (int) $_SESSION['iduser'];
	//var_dump($_SESSION);
	ahp_user_can_access_module( $module_name, $userid );
}


// credit : Insan Faja
function ahp_user_can_access_module( $module_name, $userid ) {
    $userlevel = ahp_get_user_credentials( $userid );
    $query = "SELECT `idLevelUser` FROM `modul` WHERE `script_name` = '$module_name' LIMIT 1;";
	//echo "nih : ".$query;
	//echo "mod : ".$module_name;
    $data = mysql_query( $query );
    if ($module_name == "media.php") { return; };
    if( mysql_num_rows( $data ) < 1 )
        die('No such user');
    $dung = mysql_fetch_array( $data );
    $module_ulevel = $dung['idLevelUser'];
    if( $module_ulevel == 1 )
         return;
    if( $userlevel > $module_ulevel )
        die('Access forbidden, please <a href="../index.php"><b>LOGIN</b></a>');
}

// credit : Insan Fajar
function ahp_get_user_credentials( $userid ) {
    $query = "SELECT `idLevelUser` FROM `user` WHERE `idUser` = '$userid' LIMIT 1;";
    $data = mysql_query( $query );
    if( mysql_num_rows( $data ) < 1 )
        die('Access forbidden, please <a href="../index.php"><b>LOGIN</b></a>');
	return 0;
    $utmp = mysql_fetch_array( $data );
    $userlevel = $utmp['idLevelUser'];
    return $userlevel;
}



/* CHANGELOG -----------------------------------------------------------

 1.5.0 / 2013-01-01 : Harry Sufehmi		: bugfix: fungsi tambahBarangJual() kini tidak lagi mau menerima jumBarang < 1
						(jika quantity penjualan bisa nol / minus, maka uang kas jadi bisa dirampok kasir)

 1.5.0 / 2013-01-01 : Harry Sufehmi		: bugfix: fungsi tambahBarangJual() kadang mendapatkan harga beli yang salah.
					"ORDER BY idDetailBeli" diganti menjadi "ORDER BY idTransaksiBeli"
					karena, banyak database di berbagai toko Ahad mart yang isi idDetailBeli nya ngaco 
					(banyak field idDetailBeli yang isinya 0 [nol])

 1.5.0 / 2012-11-25 : Harry Sufehmi		: fungsi-fungsi untuk mengamankan modul-modul (dari akses langsung / bypass login).
							Credit : Insan Fajar

 1.2.5 / 2012-03-05 : Harry Sufehmi		: fungsi tambahBarangJual() kini akan selalu mendapatkan hargaBeli dengan benar.
						(bugfix: hargaBeli tersimpan sebagai 0 di tmp_detail_jual & detail_jual jika 
							suatu item jumBarang = 0 dan isSold = 'Y' / tidak ada yang isSold = 'N')

 1.2.5 / 2012-02-14 : Harry Sufehmi		: fungsi getJumBarangDiBarang() kini bisa retrieve jumBarang dari parameter barcode yang diberikan
						(bugfix: jumBarang di table barang tidak berkurang setelah Retur Pembelian)

 1.2.5 / 2012-02-01 : Harry Sufehmi		: fungsi getDaftarBarangSupplier() tidak lagi memotong output barang.namaBarang

 1.2.5 / 2012-01-30 : Harry Sufehmi		: fungsi getDaftarBarangSupplier() : menampilkan hanya yang barang.jumBarang < $jumlahMin

 1.0.1 / 2010-11-22 : Harry Sufehmi		: fungsi-fungsi untuk Retur Barang

 1.0.1 / 2010-06-03 : Harry Sufehmi		: various enhancements, bugfixes

 0.9.1		    : Gregorius Arief		: initial release

------------------------------------------------------------------------ */

?>

<?php

$alat = "kipas";
$makanan = ["Nasi Goreng", "Mie Ayam", "Bakso"]; 
$minuman = ["Teh Manis", "Es Jeruk"]; 
$uang = 20000; 
$harga_makanan = 10000;
$harga_minuman = 5000;
$kompor_nyala = true;


echo "=== PERCABANGAN: ALAT DI KANTIN ===<br>";
if ($alat == "kipas") {
    echo "Kipas dinyalakan untuk mendinginkan ruangan.<br>";
} elseif ($alat == "lampu") {
    echo "Tv untuk nobar Para pengunjung.<br>";
} elseif ($alat == "kompor") {
    echo "Kompor digunakan untuk memasak makanan.<br>";
    if ($kompor_nyala) {
        echo "Kompor dalam keadaan nyala.<br>";
    } else {
        echo "Kompor dalam keadaan mati.<br>";
    }
} else {
    echo "Alat tidak dikenal di kantin.<br>";
}

echo "<br>=== PERULANGAN: MAKANAN ===<br>";
foreach ($makanan as $item) {
    echo "Menyediakan makanan: $item<br>";
}

echo "<br>=== PERULANGAN: MINUMAN ===<br>";
foreach ($minuman as $item) {
    echo "Menyediakan minuman: $item<br>";
}

echo "<br>=== PERULANGAN: UANG ===<br>";6
$jumlah_beli = 0;
while ($uang >= $harga_makanan + $harga_minuman) {
    $uang -= ($harga_makanan + $harga_minuman);
    $jumlah_beli++;
    echo "Transaksi $jumlah_beli: Membeli 1 makanan dan 1 minuman. Sisa uang: Rp$uang<br>";
}

echo "<br>Total transaksi berhasil: $jumlah_beli kali.<br>";
?>
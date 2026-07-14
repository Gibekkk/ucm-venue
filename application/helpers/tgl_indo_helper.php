<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

function tgl_indo($date)
{
	// Handle NULL or empty date
	if (empty($date) || $date === null) {
		return '-';
	}

	$BulanIndo = array("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");

	$tahun = substr($date, 0, 4);
	$bulan = substr($date, 5, 2);
	$tgl   = substr($date, 8, 2);

	// Validate that we have valid month index
	$bulanIndex = (int)$bulan - 1;
	if ($bulanIndex < 0 || $bulanIndex > 11) {
		return '-';
	}

	$result = $tgl . " " . $BulanIndo[$bulanIndex] . " " . $tahun;
	return ($result);
}

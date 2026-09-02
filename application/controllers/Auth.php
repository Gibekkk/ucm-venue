<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Login/registrasi untuk pengunjung biasa sudah DIHAPUS TOTAL sesuai permintaan.
 * Semua booking sekarang berjalan sebagai tamu (guest) dan dilacak lewat
 * halaman "Track Booking" (cart/track_booking), bukan lewat akun.
 *
 * Login admin TIDAK terpengaruh - itu ada di controller terpisah: admin/Auth.php.
 *
 * Controller ini sengaja dibiarkan ada (bukan dihapus filenya) supaya link/bookmark
 * lama tidak menghasilkan error 404, melainkan diarahkan ke halaman yang relevan.
 */
class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function register()
    {
        redirect(site_url('cart/track_booking'));
    }

    public function login()
    {
        redirect(site_url('cart/track_booking'));
    }

    public function logout()
    {
        redirect(site_url());
    }

    public function profil()
    {
        redirect(site_url('cart/track_booking'));
    }

    public function edit_profil($id = null)
    {
        redirect(site_url('cart/track_booking'));
    }

    public function forgot_password()
    {
        redirect(site_url('cart/track_booking'));
    }

    public function reset_password($code = null)
    {
        redirect(site_url('cart/track_booking'));
    }

    public function activate($id = null, $code = false)
    {
        redirect(site_url());
    }

    public function pilih_kota()
    {
        redirect(site_url());
    }
}

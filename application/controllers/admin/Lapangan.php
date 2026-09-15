<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Lapangan extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model('Lapangan_model');

    $this->data['module'] = 'Lapangan';

    if (!$this->ion_auth->logged_in()) {
      redirect('admin/auth/login', 'refresh');
    } elseif (
      !$this->ion_auth->is_superadmin() &&
      !$this->ion_auth->is_admin() &&
      !$this->ion_auth->is_ict() &&
      !$this->ion_auth->is_pm_admin() &&
      !$this->ion_auth->is_pm_all()
    ) {
      redirect(base_url());
    }
  }

  public function index()
  {
    $this->data['title']    = 'Data Lapangan';
    // Halaman ini KHUSUS lapangan/venue utama - addon dipisah ke halaman
    // admin/lapangan/addon (lihat method addon() di bawah).
    $this->data['get_all']  = $this->Lapangan_model->get_all_non_addon();
    $this->data['is_addon_page'] = false;
    $this->data['create_url']    = site_url('admin/lapangan/create');
    $this->data['tambah_label']  = 'Tambah Lapangan';

    $this->load->view('back/lapangan/lapangan_list', $this->data);
  }

  // Halaman KHUSUS Addon (fasilitas tambahan), terpisah dari Data Lapangan di atas.
  // Pakai view list yang sama, cuma sumber data & label tombolnya beda.
  public function addon()
  {
    $this->data['module']   = 'Addon';
    $this->data['title']    = 'Data Addon';
    $this->data['get_all']  = $this->Lapangan_model->get_all_addons();
    $this->data['is_addon_page'] = true;
    $this->data['create_url']    = site_url('admin/lapangan/create_addon');
    $this->data['tambah_label']  = 'Tambah Addon';

    $this->load->view('back/lapangan/lapangan_list', $this->data);
  }

  public function create()
  {
    $this->_show_create_form('venue');
  }

  // Form tambah Addon - form yang sama dengan create(), cuma otomatis menandai
  // data baru sebagai Addon (is_addon = 1) tanpa perlu checkbox lagi.
  public function create_addon()
  {
    $this->_show_create_form('addon');
  }

  private function _show_create_form($jenis)
  {
    $is_addon_page = ($jenis === 'addon');

    $this->data['module']         = $is_addon_page ? 'Addon' : 'Lapangan';
    $this->data['title']          = $is_addon_page ? 'Tambah Addon Baru' : 'Tambah Lapangan Baru';
    $this->data['action']         = site_url('admin/lapangan/create_action');
    $this->data['button_submit']  = 'Simpan';
    $this->data['button_reset']   = 'Reset';
    // Jenis dikunci lewat hidden field (bukan checkbox lagi) - Data Lapangan
    // dan Data Addon sekarang dua halaman terpisah, jadi jenisnya sudah pasti
    // sesuai halaman mana yang dibuka, tidak perlu dipilih manual.
    $this->data['is_addon_page']  = $is_addon_page;
    $this->data['jenis']          = $jenis;
    $this->data['label_jenis']    = $is_addon_page ? 'Addon' : 'Lapangan';

    $this->data['nama_lapangan'] = array(
      'name'  => 'nama_lapangan',
      'id'    => 'nama_lapangan',
      'type'  => 'text',
      'class' => 'form-control',
      'value' => $this->form_validation->set_value('nama_lapangan'),
      'required'    => '',
    );
    $this->data['harga'] = array(
      'name'  => 'harga',
      'id'    => 'harga',
      'type'  => 'number',
      'class' => 'form-control',
      'value' => $this->form_validation->set_value('harga'),
      'required'    => '',
    );

    $this->load->view('back/lapangan/lapangan_add', $this->data);
  }

  public function create_action()
  {
    $this->_rules();

    // Jenis (venue/addon) dikirim lewat hidden field dari form create(), bukan
    // checkbox lagi - jadi kalau validasi gagal, kita tahu form mana yang harus
    // ditampilkan ulang.
    $jenis = ($this->input->post('jenis') === 'addon') ? 'addon' : 'venue';

    if ($this->form_validation->run() == FALSE) {
      $this->_show_create_form($jenis);
    } else {
      $is_addon = ($jenis === 'addon') ? 1 : 0;

      /* 4 adalah menyatakan tidak ada file yang diupload*/
      if ($_FILES['foto']['error'] <> 4) {
        $nmfile = strtolower(url_title($this->input->post('nama_lapangan'))) . date('YmdHis');

        /* memanggil library upload ci */
        $config['upload_path']      = './assets/images/lapangan/';
        $config['allowed_types']    = 'jpg|jpeg|png|gif';
        $config['max_size']         = '2048'; // 2 MB
        $config['file_name']        = $nmfile; //nama yang terupload nantinya

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('foto')) {
          //file gagal diupload -> kembali ke form tambah
          $error = array('error' => $this->upload->display_errors());
          $this->session->set_flashdata('message', '<div class="alert alert-danger alert">' . $error['error'] . '</div>');

          $this->_show_create_form($jenis);
        }
        //file berhasil diupload -> lanjutkan ke query INSERT
        else {
          $foto = $this->upload->data();
          $thumbnail                = $config['file_name'];
          // library yang disediakan codeigniter
          $config['image_library']  = 'gd2';
          // gambar yang akan disimpan thumbnail
          $config['source_image']   = './assets/images/lapangan/' . $foto['file_name'] . '';
          // rasio resolusi
          $config['maintain_ratio'] = FALSE;
          // lebar
          $config['width']          = 1280;
          // tinggi
          $config['height']         = 720;

          $this->load->library('image_lib', $config);
          $this->image_lib->resize();

          $data = array(
            'nama_lapangan'   => $this->input->post('nama_lapangan'),
            'harga'           => $this->input->post('harga'),
            'foto'            => $nmfile . $foto['file_ext'],
            'created_by'      => $this->session->userdata('username'),
            'is_addon'        => $is_addon
          );

          // eksekusi query INSERT
          $this->Lapangan_model->insert($data);
          // set pesan data berhasil disimpan
          $this->session->set_flashdata('message', '
            <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
              <i class="ace-icon fa fa-bullhorn green"></i> Data berhasil disimpan
            </div>');
          redirect(site_url($is_addon ? 'admin/lapangan/addon' : 'admin/lapangan'));
        }
      } else // Jika file upload kosong
      {
        $data = array(
          'nama_lapangan'   => $this->input->post('nama_lapangan'),
          'harga'           => $this->input->post('harga'),
          'created_by'      => $this->session->userdata('username'),
          'is_addon'        => $is_addon
        );

        // eksekusi query INSERT
        $this->Lapangan_model->insert($data);
        // set pesan data berhasil disimpan
        $this->session->set_flashdata('message', '
        <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
          <i class="ace-icon fa fa-bullhorn green"></i> Data berhasil disimpan
        </div>');
        redirect(site_url($is_addon ? 'admin/lapangan/addon' : 'admin/lapangan'));
      }
    }
  }

  public function update($id)
  {
    $row = $this->Lapangan_model->get_by_id($id);
    $this->data['lapangan'] = $row;

    if ($row) {
      // Jenis (Lapangan/Addon) ditentukan dari data yang sudah ada, TIDAK bisa
      // diubah lewat form edit - kalau mau pindah jenis, hapus lalu buat baru
      // di halaman yang sesuai. Ini supaya Data Lapangan & Data Addon konsisten
      // tetap terpisah, tidak ada yang "pindah kategori" diam-diam saat diedit.
      $is_addon_page = ((int) $row->is_addon === 1);

      $this->data['module']         = $is_addon_page ? 'Addon' : 'Lapangan';
      $this->data['title']          = 'Ubah Data ' . ($is_addon_page ? 'Addon' : 'Lapangan');
      $this->data['action']         = site_url('admin/lapangan/update_action');
      $this->data['button_submit']  = 'Simpan';
      $this->data['button_reset']   = 'Reset';
      $this->data['is_addon_page']  = $is_addon_page;
      $this->data['label_jenis']    = $is_addon_page ? 'Addon' : 'Lapangan';

      $this->data['id_lapangan'] = array(
        'name'  => 'id_lapangan',
        'id'    => 'id_lapangan',
        'type'  => 'hidden',
      );
      $this->data['nama_lapangan'] = array(
        'name'  => 'nama_lapangan',
        'id'    => 'nama_lapangan',
        'type'  => 'text',
        'class' => 'form-control',
        'required'    => '',
      );
      $this->data['harga'] = array(
        'name'  => 'harga',
        'id'    => 'harga',
        'type'  => 'number',
        'class' => 'form-control',
        'required'    => '',
      );
      $this->load->view('back/lapangan/lapangan_edit', $this->data);
    } else {
      $this->session->set_flashdata('message', '<div class="alert alert-warning alert">Data tidak ditemukan</div>');
      redirect(site_url('admin/lapangan'));
    }
  }

  public function update_action()
  {
    $this->_rules();

    $id = $this->input->post('id_lapangan');
    // Ambil data lama untuk tahu jenisnya (venue/addon) - dipakai untuk redirect
    // balik ke daftar yang benar setelah simpan, dan is_addon TIDAK ikut diubah
    // sama sekali di sini supaya jenis tidak pernah berpindah lewat form edit.
    $existing = $this->Lapangan_model->get_by_id($id);
    $is_addon = $existing ? (int) $existing->is_addon : 0;
    $redirect_to = $is_addon ? 'admin/lapangan/addon' : 'admin/lapangan';

    if ($this->form_validation->run() == FALSE) {
      $this->update($id);
    } else {
      $nmfile = strtolower(url_title($this->input->post('nama_lapangan'))) . date('YmdHis');

      /* Jika file upload diisi */
      if ($_FILES['foto']['error'] <> 4) {
        $nmfile = strtolower(url_title($this->input->post('nama_lapangan'))) . date('YmdHis');

        //load uploading file library
        $config['upload_path']      = './assets/images/lapangan/';
        $config['allowed_types']    = 'jpg|jpeg|png|gif';
        $config['max_size']         = '2048'; // 2 MB
        $config['file_name']        = $nmfile; //nama yang terupload nantinya

        $this->load->library('upload', $config);

        // Jika file gagal diupload -> kembali ke form update
        if (!$this->upload->do_upload('foto')) {
          //file gagal diupload -> kembali ke form update
          $error = array('error' => $this->upload->display_errors());
          $this->session->set_flashdata('message', '<div class="alert alert-danger alert">' . $error['error'] . '</div>');

          $this->update($id);
        }
        // Jika file berhasil diupload -> lanjutkan ke query INSERT
        else {
          $delete = $this->Lapangan_model->del_by_id($id);

          $dir        = "assets/images/lapangan/" . $delete->foto;

          if (file_exists($dir)) {
            // Hapus foto dan thumbnail
            unlink($dir);
          }

          $foto = $this->upload->data();
          // library yang disediakan codeigniter
          $thumbnail                = $config['file_name'];
          //nama yang terupload nantinya
          $config['image_library']  = 'gd2';
          // gambar yang akan disimpan thumbnail
          $config['source_image']   = './assets/images/lapangan/' . $foto['file_name'] . '';
          // rasio resolusi
          $config['maintain_ratio'] = FALSE;
          // lebar
          $config['width']          = 1280;
          // tinggi
          $config['height']         = 720;

          $this->load->library('image_lib', $config);
          $this->image_lib->resize();

          $data = array(
            'nama_lapangan'   => $this->input->post('nama_lapangan'),
            'harga'           => $this->input->post('harga'),
            'foto'            => $nmfile . $foto['file_ext'],
            'modified_by'     => $this->session->userdata('username'),
          );

          $this->Lapangan_model->update($id, $data);
          $this->session->set_flashdata('message', '
              <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                <i class="ace-icon fa fa-bullhorn green"></i> Data berhasil disimpan
              </div>');
          redirect(site_url($redirect_to));
        }
      }
      // Jika file upload kosong
      else {
        $data = array(
          'nama_lapangan'   => $this->input->post('nama_lapangan'),
          'harga'           => $this->input->post('harga'),
          'modified_by'     => $this->session->userdata('username'),
        );

        $this->Lapangan_model->update($id, $data);
        $this->session->set_flashdata('message', '
            <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
              <i class="ace-icon fa fa-bullhorn green"></i> Data berhasil disimpan
            </div>');
        redirect(site_url($redirect_to));
      }
    }
  }

  public function delete($id)
  {
    // Ambil dulu jenisnya SEBELUM dihapus, supaya setelah delete bisa diarahkan
    // balik ke daftar yang benar (Data Lapangan atau Data Addon).
    $existing    = $this->Lapangan_model->get_by_id($id);
    $redirect_to = ($existing && (int) $existing->is_addon === 1) ? 'admin/lapangan/addon' : 'admin/lapangan';

    $delete = $this->Lapangan_model->del_by_id($id);

    // menyimpan lokasi gambar dalam variable
    $dir = "assets/images/foto/" . $delete->foto . $delete->foto_type;

    // Hapus foto
    unlink($dir);

    // Jika data ditemukan, maka hapus foto dan record nya
    if ($delete) {
      $this->Lapangan_model->delete($id);

      $this->session->set_flashdata('message', '
      <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
        <i class="ace-icon fa fa-bullhorn green"></i> Data berhasil dihapus
      </div>');
      redirect(site_url($redirect_to));
    }
    // Jika data tidak ada
    else {
      $this->session->set_flashdata('message', '
        <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
					<i class="ace-icon fa fa-bullhorn green"></i> Data tidak ditemukan
        </div>');
      redirect(site_url($redirect_to));
    }
  }

  public function _rules()
  {
    $this->form_validation->set_rules('nama_lapangan', 'Judul Lapangan', 'trim|required');

    // set pesan form validasi error
    $this->form_validation->set_message('required', '{field} wajib diisi');

    $this->form_validation->set_rules('id_lapangan', 'id_lapangan', 'trim');
    $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert">', '</div>');
  }
}

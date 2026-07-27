<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Class Auth
 * @property Ion_auth|Ion_auth_model $ion_auth        The ION Auth spark
 * @property CI_Form_validation      $form_validation The form validation library
 */
class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('language');
        $this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

        $this->lang->load('auth');
        $this->load->model('Company_model');
        $this->load->model('Ion_auth_model');

        $this->data['module'] = 'User';
    }

    public function index()
    {
        $this->data['title'] = 'Data ' . $this->data['module'];

        // Cek sudah/ belum login
        if (!$this->ion_auth->logged_in()) {
            redirect('admin/auth/login', 'refresh');
        }

        // GEMBOK AKSES membaca langsung dari DB user
        $user_login = $this->ion_auth->user()->row();
        $usertype = $user_login ? $user_login->usertype : '';

        // Izinkan Superadmin (1), ICT (6), dan PM All (8)
        if (!in_array($usertype, array('1', '6', '8'))) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Akses Ditolak! Anda tidak memiliki hak untuk mengelola user.</div>');
            redirect('admin/dashboard', 'refresh');
        } else {
            // Set pesan flash data error jika ada
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            // Data untuk tabel pertama (Biasanya untuk Admin/Staff Internal)
            $this->data['users'] = $this->ion_auth->get_all_users()->result();

            // PERBAIKAN ERROR: Data untuk tabel kedua (Biasanya untuk Customer/Member)
            // Mengambil user dengan usertype 3 dan 4 agar variabel $users2 tidak kosong
            $this->db->join('users_group', 'users.usertype = users_group.id_group', 'left');
            $this->db->where_in('usertype', array('3', '4'));
            $this->data['users2'] = $this->db->get('users')->result();

            $this->_render_page('back/auth/index', $this->data);
        }
    }

    /**
     * Log the user in
     */
    public function login()
    {
        $this->data['title'] = "Login BackEnd Area";

        $this->data['captcha'] = '';
        $this->data['script_captcha'] = '';
        $this->data['title'] = $this->lang->line('login_heading');

        // validate form input
        $this->form_validation->set_rules('identity', str_replace(':', '', $this->lang->line('login_identity_label')), 'required');
        $this->form_validation->set_rules('password', str_replace(':', '', $this->lang->line('login_password_label')), 'required');
        $this->form_validation->set_message('required', '{field} mohon diisi');

        if ($this->form_validation->run() == FALSE) {
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            $this->data['identity'] = array(
                'name'  => 'identity',
                'id'    => 'identity',
                'class' => 'form-control',
                'placeholder' => 'Isikan Email Anda',
                'value' => $this->form_validation->set_value('identity'),
            );
            $this->data['password'] = array(
                'name'  => 'password',
                'id'    => 'password',
                'class' => 'form-control',
                'placeholder' => 'Isikan Password Anda',
            );

            $this->_render_page('back/auth/login', $this->data);
        } else {
            // check for "remember me"
            $remember = (bool) $this->input->post('remember');

            if ($this->ion_auth->login($this->input->post('identity'), $this->input->post('password'), $remember)) {
                $this->session->set_flashdata('message', '<div class="box-body">
                    <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                    <i class="ace-icon fa fa-bullhorn green"></i> Login Berhasil
                    </div></div>');
                redirect('admin/dashboard', 'refresh');
            } else {
                $this->session->set_flashdata('message', $this->ion_auth->errors(''));
                redirect('admin/auth/login', 'refresh');
            }
        }
    }

    /**
     * Log the user out
     */
    public function logout()
    {
        $logout = $this->ion_auth->logout();

        $this->session->set_flashdata('message', '
        <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
        <i class="ace-icon fa fa-bullhorn green"></i> Logout Berhasil
        </div>');
        redirect('admin/auth/login', 'refresh');
    }

    public function change_password()
    {
        $this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
        $this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[new_confirm]');
        $this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');

        if (!$this->ion_auth->logged_in()) {
            redirect('admin/auth/login', 'refresh');
        }

        $user = $this->ion_auth->user()->row();

        if ($this->form_validation->run() === FALSE) {
            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

            $this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
            $this->data['old_password'] = array(
                'name' => 'old',
                'id' => 'old',
                'type' => 'password',
            );
            $this->data['new_password'] = array(
                'name' => 'new',
                'id' => 'new',
                'type' => 'password',
                'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
            );
            $this->data['new_password_confirm'] = array(
                'name' => 'new_confirm',
                'id' => 'new_confirm',
                'type' => 'password',
                'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
            );
            $this->data['user_id'] = array(
                'name' => 'user_id',
                'id' => 'user_id',
                'type' => 'hidden',
                'value' => $user->id,
            );

            $this->_render_page('back/auth/change_password', $this->data);
        } else {
            $identity = $this->session->userdata('identity');

            $change = $this->ion_auth->change_password($identity, $this->input->post('old'), $this->input->post('new'));

            if ($change) {
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                $this->logout();
            } else {
                $this->session->set_flashdata('message', $this->ion_auth->errors());
                redirect('admin/auth/change_password', 'refresh');
            }
        }
    }

    public function forgot_password()
    {
        if ($this->config->item('identity', 'ion_auth') != 'email') {
            $this->form_validation->set_rules('identity', $this->lang->line('forgot_password_identity_label'), 'required');
        } else {
            $this->form_validation->set_rules('identity', $this->lang->line('forgot_password_validation_email_label'), 'required|valid_email');
        }


        if ($this->form_validation->run() === FALSE) {
            $this->data['type'] = $this->config->item('identity', 'ion_auth');
            $this->data['identity'] = array(
                'name' => 'identity',
                'id' => 'identity',
            );

            if ($this->config->item('identity', 'ion_auth') != 'email') {
                $this->data['identity_label'] = $this->lang->line('forgot_password_identity_label');
            } else {
                $this->data['identity_label'] = $this->lang->line('forgot_password_email_identity_label');
            }

            $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
            $this->_render_page('back/auth/forgot_password', $this->data);
        } else {
            $identity_column = $this->config->item('identity', 'ion_auth');
            $identity = $this->ion_auth->where($identity_column, $this->input->post('identity'))->users()->row();

            if (empty($identity)) {
                echo "<script>alert('Email tidak ditemukan!');history.go(-1)</script>";
            } else {
                $forgotten = $this->ion_auth->forgotten_password($identity->{$this->config->item('identity', 'ion_auth')});
                if ($forgotten) {
                    echo "<script>alert('Reset Password berhasil, silahkan cek email Anda!');history.go(-1)</script>";
                } else {
                    echo "<script>alert('Reset Password gagal, silahkan dicoba kembali!');history.go(-1)</script>";
                }
            }
        }
    }

    public function reset_password($code = NULL)
    {
        if (!$code) {
            show_404();
        }

        $user = $this->ion_auth->forgotten_password_check($code);

        if ($user) {
            $this->form_validation->set_rules('new', $this->lang->line('reset_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[new_confirm]');
            $this->form_validation->set_rules('new_confirm', $this->lang->line('reset_password_validation_new_password_confirm_label'), 'required');

            if ($this->form_validation->run() === FALSE) {
                $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

                $this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
                $this->data['new_password'] = array(
                    'name' => 'new',
                    'id' => 'new',
                    'type' => 'password',
                    'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
                );
                $this->data['new_password_confirm'] = array(
                    'name' => 'new_confirm',
                    'id' => 'new_confirm',
                    'type' => 'password',
                    'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
                );
                $this->data['user_id'] = array(
                    'name' => 'user_id',
                    'id' => 'user_id',
                    'type' => 'hidden',
                    'value' => $user->id,
                );
                $this->data['csrf'] = $this->_get_csrf_nonce();
                $this->data['code'] = $code;

                $this->_render_page('back/auth/reset_password', $this->data);
            } else {
                if ($this->_valid_csrf_nonce() === FALSE || $user->id != $this->input->post('user_id')) {
                    $this->ion_auth->clear_forgotten_password_code($code);
                    show_error($this->lang->line('error_csrf'));
                } else {
                    $identity = $user->{$this->config->item('identity', 'ion_auth')};
                    $change = $this->ion_auth->reset_password($identity, $this->input->post('new'));

                    if ($change) {
                        if (in_array($user->usertype, array('1', '6', '8'))) {
                            $this->session->set_flashdata('message', $this->ion_auth->messages());
                            redirect("admin/auth/login", 'refresh');
                        }
                    } else {
                        $this->session->set_flashdata('message', $this->ion_auth->errors());
                        redirect('admin/auth/reset_password/' . $code, 'refresh');
                    }
                }
            }
        } else {
            $this->session->set_flashdata('message', $this->ion_auth->errors());
            redirect("auth/forgot_password", 'refresh');
        }
    }

    public function activate($id, $code = FALSE)
    {
        // GEMBOK AKSES
        $user_login = $this->ion_auth->user()->row();
        $usertype = $user_login ? $user_login->usertype : '';

        if (!$this->ion_auth->logged_in() || !in_array($usertype, array('1', '6', '8'))) {
            redirect('admin/dashboard', 'refresh');
        }

        if ($code !== false) {
            $activation = $this->ion_auth->activate($id, $code);
        } else {
            $activation = $this->ion_auth->activate($id);
        }

        if ($activation) {
            $this->session->set_flashdata('message', '
            <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                <i class="ace-icon fa fa-bullhorn green"></i> Akun berhasil diaktifkan
            </div>');
            redirect("admin/auth/", 'refresh');
        } else {
            $this->session->set_flashdata('message', '
            <div class="alert alert-block alert-warning"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                <i class="ace-icon fa fa-bullhorn green"></i> Akun gagal diaktifkan
            </div>');
            redirect("admin/auth/", 'refresh');
        }
    }

    public function deactivate($id = NULL)
    {
        $id = (int) $id;

        // GEMBOK AKSES
        $user_login = $this->ion_auth->user()->row();
        $usertype = $user_login ? $user_login->usertype : '';

        if ($this->ion_auth->logged_in() && in_array($usertype, array('1', '6', '8'))) {
            $this->ion_auth->deactivate($id);
        } else {
            redirect('admin/dashboard', 'refresh');
        }

        $this->session->set_flashdata('message', '
        <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
            <i class="ace-icon fa fa-bullhorn green"></i> Akun berhasil dinonaktifkan
        </div>');
        redirect('admin/auth/', 'refresh');
    }

    public function create_user()
    {
        // GEMBOK AKSES
        $user_login = $this->ion_auth->user()->row();
        $usertype = $user_login ? $user_login->usertype : '';

        if (!$this->ion_auth->logged_in() || !in_array($usertype, array('1', '6', '8'))) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Akses Ditolak!</div>');
            redirect('admin/dashboard', 'refresh');
        }

        $this->data['title'] = 'Tambah Data ' . $this->data['module'];

        $tables             = $this->config->item('tables', 'ion_auth');
        $identity_column    = $this->config->item('identity', 'ion_auth');

        $this->data['identity_column'] = $identity_column;

        $this->form_validation->set_rules('name', $this->lang->line('create_user_validation_fname_label'), 'required|is_unique[' . $tables['users'] . '.' . $identity_column . ']');
        $this->form_validation->set_rules('username', $this->lang->line('create_username_validation_fname_label'), 'required');
        $this->form_validation->set_rules('address', 'Alamat', 'required');
        $this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'valid_email|is_unique[' . $tables['users'] . '.email]');
        $this->form_validation->set_rules('phone', 'No. Telp', 'trim');
        $this->form_validation->set_rules('password', $this->lang->line('create_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
        $this->form_validation->set_rules('password_confirm', $this->lang->line('create_user_validation_password_confirm_label'), 'required');

        $this->form_validation->set_message('required', '{field} mohon diisi');
        $this->form_validation->set_message('valid_email', 'Format email tidak benar');
        $this->form_validation->set_message('numeric', 'No. HP harus angka');
        $this->form_validation->set_message('matches', 'Password baru dan konfirmasi harus sama');
        $this->form_validation->set_message('is_unique', '%s telah terpakai, ganti dengan yang lain');

        if ($this->form_validation->run() == true) {
            if ($_FILES['photo']['error'] <> 4) {
                $nmfile = strtolower(url_title($this->input->post('name'))) . date('YmdHis');

                $config['upload_path']      = './assets/images/user/';
                $config['allowed_types']    = 'jpg|jpeg|png|gif';
                $config['max_size']         = '2048';
                $config['file_name']        = $nmfile;

                $this->load->library('upload', $config);

                if (!$this->upload->do_upload('photo')) {
                    $error = array('error' => $this->upload->display_errors());
                    $this->session->set_flashdata('message', '<div class="alert alert-danger alert">' . $error['error'] . '</div>');
                } else {
                    $photo = $this->upload->data();
                    $thumbnail                = $config['file_name'];
                    $config['image_library']  = 'gd2';
                    $config['source_image']   = './assets/images/user/' . $photo['file_name'] . '';
                    $config['create_thumb']   = TRUE;
                    $config['maintain_ratio'] = FALSE;
                    $config['width']          = 250;
                    $config['height']         = 250;

                    $this->load->library('image_lib', $config);
                    $this->image_lib->resize();

                    $email    = strtolower($this->input->post('email'));
                    $identity = ($identity_column === 'email') ? $email : $this->input->post('identity');
                    $password = $this->input->post('password');

                    $additional_data = array(
                        'name'        => $this->input->post('name'),
                        'username'    => $this->input->post('username'),
                        'email'       => strtolower($this->input->post('email')),
                        'active'      => '1',
                        'usertype'    => $this->input->post('usertype'),
                        'address'     => $this->input->post('address'),
                        'phone'       => $this->input->post('phone'),
                        'photo'       => $nmfile,
                        'photo_type'  => $photo['file_ext'],
                        'uploader'    => $this->session->userdata('user_id')
                    );

                    $this->ion_auth->register($identity, $password, $email, $additional_data);

                    $this->session->set_flashdata('message', '<div class="alert alert-success alert">Data berhasil dibuat</div>');
                    redirect('admin/auth', 'refresh');
                }
            } else {
                $email    = strtolower($this->input->post('email'));
                $identity = ($identity_column === 'email') ? $email : $this->input->post('identity');
                $password = $this->input->post('password');

                $additional_data = array(
                    'name'        => $this->input->post('name'),
                    'username'    => $this->input->post('username'),
                    'email'       => strtolower($this->input->post('email')),
                    'active'      => '1',
                    'usertype'    => $this->input->post('usertype'),
                    'address'     => $this->input->post('address'),
                    'phone'       => $this->input->post('phone'),
                    'uploader'    => $this->session->userdata('user_id')
                );

                $this->ion_auth->register($identity, $password, $email, $additional_data);

                $this->session->set_flashdata('message', '
         <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                    <i class="ace-icon fa fa-bullhorn green"></i> Data berhasil dibuat
         </div>');
                redirect('admin/auth', 'refresh');
            }
        }

        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        $this->data['name'] = array(
            'name'  => 'name',
            'id'    => 'name',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('name'),
        );
        $this->data['username'] = array(
            'name'  => 'username',
            'id'    => 'username',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('username'),
        );
        $this->data['password'] = array(
            'name'  => 'password',
            'id'    => 'password',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('password'),
        );
        $this->data['password_confirm'] = array(
            'name'  => 'password_confirm',
            'id'    => 'password_confirm',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('password_confirm'),
        );
        $this->data['address'] = array(
            'name'  => 'address',
            'id'    => 'address',
            'class' => 'form-control',
            'cols'  => '2',
            'rows'  => '2',
            'value' => $this->form_validation->set_value('address'),
        );
        $this->data['email'] = array(
            'name'  => 'email',
            'id'    => 'email',
            'type'    => 'email',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('email'),
        );
        $this->data['phone'] = array(
            'name'  => 'phone',
            'id'    => 'phone',
            'class' => 'form-control',
            'type'    => 'number',
            'value' => $this->form_validation->set_value('phone'),
        );
        $this->data['usertype_css'] = array(
            'name'  => 'usertype',
            'id'    => 'usertype',
            'class' => 'form-control',
        );

        $this->data['get_all_users_group']  = $this->Ion_auth_model->get_all_users_group();

        $this->load->view('back/auth/create_user', $this->data);
    }

    public function edit_user($id)
    {
        $this->data['title'] = 'Edit Data ' . $this->data['module'];

        if (!$this->ion_auth->logged_in()) {
            redirect('admin/auth/login', 'refresh');
        }

        // GEMBOK AKSES
        $user_login = $this->ion_auth->user()->row();
        $usertype = $user_login ? $user_login->usertype : '';
        $is_admin_group = in_array($usertype, array('1', '6', '8'));

        if (!$is_admin_group && ($this->session->userdata('user_id') != $id)) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Akses Ditolak! Anda hanya diizinkan untuk mengubah data akun Anda sendiri.</div>');
            redirect('admin/dashboard', 'refresh');
        }

        $user = $this->ion_auth->user($id)->row();

        if ($user == FALSE) {
            $this->session->set_flashdata('message', '
            <div class="alert alert-block alert-danger"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
            <i class="ace-icon fa fa-bullhorn green"></i> Data tidak ditemukan
            </div>');
            redirect('admin/auth/', 'refresh');
        }

        $this->form_validation->set_rules('name', 'name', 'required|trim');
        $this->form_validation->set_rules('username', 'Username', 'required|trim');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('phone', 'No. HP', 'trim|numeric');

        $this->form_validation->set_message('required', '{field} mohon diisi');
        $this->form_validation->set_message('numeric', 'No. HP harus angka');
        $this->form_validation->set_message('valid_email', 'Format email salah');
        $this->form_validation->set_message('min_length', 'Password minimal 8 huruf');
        $this->form_validation->set_message('max_length', 'Password maksimal 20 huruf');
        $this->form_validation->set_message('matches', 'Password baru dan konfirmasi harus sama');

        if (isset($_POST) && !empty($_POST)) {
            if ($this->_valid_csrf_nonce() === FALSE || $id != $this->input->post('id')) {
                show_error($this->lang->line('error_csrf'));
            }

            if ($this->input->post('password')) {
                $this->form_validation->set_rules('password', $this->lang->line('edit_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
                $this->form_validation->set_rules('password_confirm', $this->lang->line('edit_user_validation_password_confirm_label'), 'required');
            }

            if ($this->form_validation->run() === TRUE) {

                $data = array(
                    'name'          => $this->input->post('name'),
                    'username'      => $this->input->post('username'),
                    'email'         => strtolower($this->input->post('email')),
                    'address'       => $this->input->post('address'),
                    'phone'         => $this->input->post('phone'),
                    'uploader'      => $this->session->userdata('identity')
                );

                if ($is_admin_group && $this->input->post('usertype')) {
                    $data['usertype'] = $this->input->post('usertype');
                }

                if ($this->input->post('password')) {
                    $data['password'] = $this->input->post('password');
                }

                if ($_FILES['photo']['error'] <> 4) {
                    $delete = $this->Ion_auth_model->del_by_id($this->input->post('id'));

                    if ($delete) {
                        $dir = "assets/images/user/" . $delete->photo . $delete->photo_type;
                        $dir_thumb = "assets/images/user/" . $delete->photo . '_thumb' . $delete->photo_type;

                        if ($delete->photo > 0) {
                            unlink($dir);
                            unlink($dir_thumb);
                        }
                    }

                    $nmfile = strtolower(url_title($this->input->post('name'))) . date('YmdHis');

                    $config['upload_path']      = './assets/images/user/';
                    $config['allowed_types']    = 'jpg|jpeg|png|gif';
                    $config['max_size']         = '2048';
                    $config['file_name']        = $nmfile;

                    $this->load->library('upload', $config);

                    if (!$this->upload->do_upload('photo')) {
                        $error = array('error' => $this->upload->display_errors());
                        $this->session->set_flashdata('message', '<div class="alert alert-danger alert">' . $error['error'] . '</div>');
                    } else {
                        $photo = $this->upload->data();
                        $thumbnail                = $config['file_name'];
                        $config['image_library']  = 'gd2';
                        $config['source_image']   = './assets/images/user/' . $photo['file_name'] . '';
                        $config['create_thumb']   = TRUE;
                        $config['maintain_ratio'] = FALSE;
                        $config['width']          = 250;
                        $config['height']         = 250;

                        $this->load->library('image_lib', $config);
                        $this->image_lib->resize();

                        $data['photo'] = $nmfile;
                        $data['photo_type'] = $photo['file_ext'];
                    }
                }

                if ($this->ion_auth->update($user->id, $data)) {
                    $this->session->set_flashdata('message', '
                <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                            <i class="ace-icon fa fa-bullhorn green"></i> Update Data Berhasil
                </div>');
                    redirect(site_url('admin/auth/'));
                } else {
                    $this->session->set_flashdata('message', '
                <div class="alert alert-block alert-danger"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                            <i class="ace-icon fa fa-bullhorn green"></i> Update Data Gagal
                </div>');
                    redirect(site_url('admin/auth/'));
                }
            }
        }

        $this->data['csrf'] = $this->_get_csrf_nonce();
        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
        $this->data['user'] = $user;

        $this->data['name'] = array(
            'name'  => 'name',
            'id'    => 'name',
            'class'  => 'form-control',
            'value' => $this->form_validation->set_value('name', $user->name),
        );
        $this->data['username'] = array(
            'name'  => 'username',
            'id'    => 'username',
            'class'  => 'form-control',
            'value' => $this->form_validation->set_value('username', $user->username),
        );
        $this->data['email'] = array(
            'name'  => 'email',
            'id'    => 'email',
            'class'  => 'form-control',
            'value' => $this->form_validation->set_value('email', $user->email),
        );
        $this->data['address'] = array(
            'name'  => 'address',
            'id'    => 'address',
            'class'  => 'form-control',
            'rows'  => '2',
            'cols'  => '2',
            'value' => $this->form_validation->set_value('address', $user->address),
        );
        $this->data['usertype'] = array(
            'name'  => 'usertype',
            'id'    => 'usertype',
            'type'  => 'text',
            'class'  => 'form-control',
        );
        $this->data['phone'] = array(
            'name'  => 'phone',
            'id'    => 'phone',
            'class'  => 'form-control',
            'value' => $this->form_validation->set_value('phone', $user->phone),
        );
        $this->data['password'] = array(
            'name' => 'password',
            'id'   => 'password',
            'class'  => 'form-control',
            'placeholder'  => 'diisi jika mengubah password'
        );
        $this->data['password_confirm'] = array(
            'name' => 'password_confirm',
            'id'   => 'password_confirm',
            'class'  => 'form-control',
            'placeholder'  => 'diisi jika mengubah password'
        );

        $this->data['get_all_users_group'] = $this->Ion_auth_model->get_all_users_group();
        $this->data['is_admin_group'] = $is_admin_group;

        $this->_render_page('back/auth/edit_user', $this->data);
    }

    public function profil()
    {
        $this->data['title']            = 'Profil Saya';

        $this->data['profil'] = $this->Ion_auth_model->profil();

        $this->load->view('back/auth/profil', $this->data);
    }

    public function delete_user($id)
    {
        // GEMBOK AKSES
        $user_login = $this->ion_auth->user()->row();
        $usertype = $user_login ? $user_login->usertype : '';

        if (!$this->ion_auth->logged_in() || !in_array($usertype, array('1', '6', '8'))) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Akses Ditolak!</div>');
            redirect('admin/dashboard', 'refresh');
        }

        $delete = $this->Ion_auth_model->del_by_id($id);

        if ($delete) {
            $dir = "assets/images/user/" . $delete->photo . $delete->photo_type;
            $dir_thumb = "assets/images/user/" . $delete->photo . '_thumb' . $delete->photo_type;

            if ($delete->photo == TRUE) {
                unlink($dir);
                unlink($dir_thumb);
            }

            $this->Ion_auth_model->delete_user($id);
            $this->session->set_flashdata('message', '
            <div class="alert alert-block alert-success"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
            <i class="ace-icon fa fa-bullhorn green"></i> Hapus Data Berhasil
            </div>');
            redirect(site_url('admin/auth/'));
        } else {
            $this->session->set_flashdata('message', '
                <div class="alert alert-block alert-danger"><button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                <i class="ace-icon fa fa-bullhorn red"></i> Data tidak ditemukan
                </div>');
            redirect(site_url('admin/auth/'));
        }
    }

    public function create_group()
    {
        $this->data['title'] = $this->lang->line('create_group_title');

        $user_login = $this->ion_auth->user()->row();
        $usertype = $user_login ? $user_login->usertype : '';

        if (!$this->ion_auth->logged_in() || !in_array($usertype, array('1', '6', '8'))) {
            redirect('admin/auth', 'refresh');
        }

        $this->form_validation->set_rules('group_name', $this->lang->line('create_group_validation_name_label'), 'trim|required|alpha_dash');

        if ($this->form_validation->run() === TRUE) {
            $new_group_id = $this->ion_auth->create_group($this->input->post('group_name'), $this->input->post('description'));
            if ($new_group_id) {
                $this->session->set_flashdata('message', $this->ion_auth->messages());
                redirect("auth", 'refresh');
            }
        } else {
            $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

            $this->data['group_name'] = array(
                'name'  => 'group_name',
                'id'    => 'group_name',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('group_name'),
            );
            $this->data['description'] = array(
                'name'  => 'description',
                'id'    => 'description',
                'type'  => 'text',
                'value' => $this->form_validation->set_value('description'),
            );

            $this->_render_page('back/auth/create_group', $this->data);
        }
    }

    public function edit_group($id)
    {
        if (!$id || empty($id)) {
            redirect('admin/auth', 'refresh');
        }

        $this->data['title'] = $this->lang->line('edit_group_title');

        $user_login = $this->ion_auth->user()->row();
        $usertype = $user_login ? $user_login->usertype : '';

        if (!$this->ion_auth->logged_in() || !in_array($usertype, array('1', '6', '8'))) {
            redirect('admin/auth', 'refresh');
        }

        $group = $this->ion_auth->group($id)->row();

        $this->form_validation->set_rules('group_name', $this->lang->line('edit_group_validation_name_label'), 'required|alpha_dash');

        if (isset($_POST) && !empty($_POST)) {
            if ($this->form_validation->run() === TRUE) {
                $group_update = $this->ion_auth->update_group($id, $_POST['group_name'], $_POST['group_description']);

                if ($group_update) {
                    $this->session->set_flashdata('message', $this->lang->line('edit_group_saved'));
                } else {
                    $this->session->set_flashdata('message', $this->ion_auth->errors());
                }
                redirect("auth", 'refresh');
            }
        }

        $this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

        $this->data['group'] = $group;

        $readonly = $this->config->item('admin_group', 'ion_auth') === $group->name ? 'readonly' : '';

        $this->data['group_name'] = array(
            'name'    => 'group_name',
            'id'      => 'group_name',
            'type'    => 'text',
            'value'   => $this->form_validation->set_value('group_name', $group->name),
            $readonly => $readonly,
        );
        $this->data['group_description'] = array(
            'name'  => 'group_description',
            'id'    => 'group_description',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('group_description', $group->description),
        );

        $this->_render_page('back/auth/edit_group', $this->data);
    }

    public function _get_csrf_nonce()
    {
        $this->load->helper('string');
        $key = random_string('alnum', 8);
        $value = random_string('alnum', 20);
        // userdata dipakai (bukan flashdata) supaya token tidak hilang
        // hanya karena ada request tambahan (reload/tab lain) sebelum form disubmit
        $this->session->set_userdata('csrfkey', $key);
        $this->session->set_userdata('csrfvalue', $value);

        return array($key => $value);
    }

    public function _valid_csrf_nonce()
    {
        $csrfkey = $this->input->post($this->session->userdata('csrfkey'));
        $valid = ($csrfkey && $csrfkey === $this->session->userdata('csrfvalue'));

        // Token sekali pakai: hapus setelah dicek, valid atau tidak
        $this->session->unset_userdata('csrfkey');
        $this->session->unset_userdata('csrfvalue');

        return $valid;
    }

    public function _render_page($view, $data = NULL, $returnhtml = FALSE)
    {
        $this->viewdata = (empty($data)) ? $this->data : $data;
        $view_html = $this->load->view($view, $this->viewdata, $returnhtml);

        if ($returnhtml) {
            return $view_html;
        }
    }
}
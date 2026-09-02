<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lapangan_model extends CI_Model
{
  public $table = 'lapangan';
  public $id    = 'id_lapangan';
  public $order = 'DESC';

  function count_all()
  {
    $this->db->from($this->table);
    $this->db->where('is_active', '1');
    $this->db->where('is_addon', '0'); // Sembunyikan addon
    return $this->db->count_all_results();
  }

  function get_all()
  {
    // Ini dipakai oleh Admin, jadi is_addon TIDAK di-filter 
    // agar admin tetap bisa melihat dan mengedit lapangan addon.
    $this->db->where('is_active', '1');
    $this->db->order_by('nama_lapangan','ASC');
    return $this->db->get($this->table)->result();
  }

  // Khusus venue (bukan addon), dipakai di dropdown admin (mis. halaman Reschedule)
  function get_all_non_addon()
  {
    $this->db->where('is_active', '1');
    $this->db->where('is_addon', '0');
    $this->db->order_by('nama_lapangan', 'ASC');
    return $this->db->get($this->table)->result();
  }

  // Fungsi baru untuk Tahap 2: Khusus mengambil data addon untuk halaman Cart
  function get_all_addons()
  {
    $this->db->where('is_active', '1');
    $this->db->where('is_addon', '1'); // Hanya ambil yang addon
    $this->db->order_by('nama_lapangan', 'ASC');
    return $this->db->get($this->table)->result();
  }

  function get_all_home()
  {
    $this->db->where('is_active', '1');
    $this->db->where('is_addon', '0'); // Sembunyikan addon
    $this->db->order_by('nama_lapangan', 'ASC');
    return $this->db->get($this->table)->result();
  }

  function ambil_lapangan()
  {
    $this->db->where('is_active', '1');
    $this->db->where('is_addon', '0'); // Sembunyikan addon
    $this->db->order_by('nama_lapangan', 'ASC');
    $data=$this->db->get('lapangan');
    if($data->num_rows()>0)
    {
      foreach ($data->result_array() as $row)
      {
        $result['']= '- Pilih Lapangan -';
        $result[$row['id_lapangan']]= ucwords(strtolower($row['nama_lapangan']));
      }
      return $result;
    }
  }

  function ambil_subkat($kat)
  {
    $this->db->where('id_kat',$kat);
    $this->db->where('is_active', '1');
    // $this->db->order_by('judul_subkat','asc');
    $sql_subkat=$this->db->get('sublapangan');
    if($sql_subkat->num_rows()>0)
    {
      foreach ($sql_subkat->result_array() as $row)
      {
        $result[$row['id_sublapangan']]= ucwords(strtolower($row['judul_sublapangan']));
      }
      return $result;
    }
  }

  function ambil_sublapangan($kat_id)
  {
    $this->db->where('id_kat',$kat_id);
    $this->db->where('is_active', '1');
    $this->db->order_by('judul_sublapangan','asc');
    $sql=$this->db->get('sublapangan');
    if($sql->num_rows()>0)
    {
      foreach ($sql->result_array() as $row)
      {
        $result[$row['id_sublapangan']]= ucwords(strtolower($row['judul_sublapangan']));
      }
    }
    else
    {
      $result['-']= '- Belum Ada SubLapangan -';
    }
    return $result;
  }

  function ambil_supersubkat($subkat_id)
  {
    $this->db->where('id_subkat',$subkat_id);
    $this->db->where('is_active', '1');

    $sql=$this->db->get('supersublapangan');
    if($sql->num_rows()>0)
    {
      foreach ($sql->result_array() as $row)
      {
        $result[$row['id_supersublapangan']]= ucwords(strtolower($row['judul_supersublapangan']));
      }
    }
    else
    {
      $result['-']= '- Belum Ada SuperSubLapangan -';
    }
    return $result;
  }

  function ambil_supersublapangan($subkat_id)
  {
    $this->db->where('id_subkat',$subkat_id);
    $this->db->where('is_active', '1');

    $sql=$this->db->get('supersublapangan');
    if($sql->num_rows()>0)
    {
      foreach ($sql->result_array() as $row)
      {
        $result[$row['id_supersublapangan']]= ucwords(strtolower($row['judul_supersublapangan']));
      }
    }
    else
    {
      $result['-']= '- Belum Ada SuperSubLapangan -';
    }
    return $result;
  }

  function get_list_by_lapangan($slug, $limit=null, $offset=null)
  {
    $this->db->join('lapangan', 'produk.kat_id=lapangan.id_lapangan');
    $this->db->where('lapangan.slug_kat', $slug);
    $this->db->where('lapangan.is_addon', '0'); // Sembunyikan addon
    $this->db->where('produk.is_active', '1'); 
    $this->db->limit($limit, $offset);

    return $this->db->get('produk');
  }

  function get_by_lapangan_nr($slug)
  {
    $this->db->join('lapangan', 'produk.kat_id=lapangan.id_lapangan');
    $this->db->where('lapangan.slug_kat', $slug);
    $this->db->where('lapangan.is_addon', '0'); // Sembunyikan addon
    $this->db->where('produk.is_active', '1');

    return $this->db->get('produk')->num_rows();
  }

  function get_list_by_sublapangan($slug, $limit=null, $offset=null)
  {
    $this->db->join('sublapangan', 'produk.subkat_id=sublapangan.id_sublapangan');
    $this->db->where('sublapangan.slug_subkat', $slug);
    $this->db->where('produk.is_active', '1');
    $this->db->limit($limit, $offset);

    return $this->db->get('produk');
  }

  function get_by_sublapangan_nr($slug)
  {
    $this->db->join('sublapangan', 'produk.subkat_id=sublapangan.id_sublapangan');
    $this->db->where('sublapangan.slug_subkat', $slug);
    $this->db->where('produk.is_active', '1');

    return $this->db->get('produk')->num_rows();
  }

  function get_list_by_superslapangan($slug, $limit=null, $offset=null)
  {
    $this->db->join('supersublapangan', 'produk.supersubkat_id=supersublapangan.id_supersublapangan');
    $this->db->where('supersublapangan.slug_supersubkat', $slug);
    $this->db->where('produk.is_active', '1');
    $this->db->limit($limit, $offset);

    return $this->db->get('produk');
  }

  function get_by_superslapangan_nr($slug)
  {
    $this->db->join('supersublapangan', 'produk.supersubkat_id=supersublapangan.id_supersublapangan');
    $this->db->where('supersublapangan.slug_supersubkat', $slug);
    $this->db->where('produk.is_active', '1');

    return $this->db->get('produk')->num_rows();
  }

  function get_all_new_home()
  {
    $this->db->where('is_active', '1');
    $this->db->where('is_addon', '0'); // Sembunyikan addon
    $this->db->limit(4);
    $this->db->order_by($this->id, $this->order);
    return $this->db->get($this->table)->result();
  }

  function get_all_lapangan_sidebar()
  {
    $this->db->where('is_active', '1');
    $this->db->where('is_addon', '0'); // Sembunyikan addon
    $this->db->order_by('judul_lapangan', 'asc');
    return $this->db->get($this->table)->result();
  }

  function get_total_row_lapangan()
  {
    $this->db->where('is_active', '1');
    $this->db->where('is_addon', '0'); // Sembunyikan addon
    return $this->db->get($this->table)->count_all_results();
  }

  function get_by_id($id)
  {
    $this->db->where($this->id, $id);
    // Jika Anda ingin get_by_id juga hanya mengambil yang aktif, uncomment baris di bawah ini:
    // $this->db->where('is_active', '1'); 
    return $this->db->get($this->table)->row();
  }

  function get_by_id_front($id)
  {
    $this->db->from('produk');
    $this->db->where('slug_subkat', $id);
    $this->db->where('produk.is_active', '1');
    $this->db->join('sublapangan', 'produk.subkat_id = sublapangan.id_sublapangan');
    $this->db->order_by('id_produk','desc');
    return $this->db->get();
  }

  // get total rows
  function total_rows()
  {
    $this->db->where('is_active', '1');
    $this->db->where('is_addon', '0'); // Sembunyikan addon
    return $this->db->get($this->table)->num_rows();
  }

  function insert($data)
  {
    $this->db->insert($this->table, $data);
  }

  function update($id, $data)
  {
    $this->db->where($this->id,$id);
    $this->db->update($this->table, $data);
  }

  function delete($id)
  {
    $this->db->where($this->id, $id);
    $this->db->delete($this->table);
  }

  function del_by_id($id)
  {
    $this->db->select("foto");
    $this->db->where($this->id,$id);
    return $this->db->get($this->table)->row();
  }

}
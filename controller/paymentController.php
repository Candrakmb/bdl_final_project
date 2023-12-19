<?php

class PaymentController{
    private $db_payment;
    public function __construct($db) {
        $this->db_payment = $db;
    }

    public function index() {
        $conn =  $this->db_payment->connect();
        $parent = "Payment";
        $position = "Home";

        $sql = "SELECT p.id, u.email, d.judul_donasi, p.created_at, p.price, p.payment_status
        FROM payments p
        LEFT JOIN data_donasi d ON p.id_donasi = d.id_data_donasi
        LEFT JOIN users u ON p.id_user = u.id
        ORDER BY p.created_at DESC;
        ";
        $result = mysqli_query($conn, $sql);
        $data = mysqli_fetch_all($result);

        return include "../view/payment/index.php";
    }

    public function data_payment($sql){
        if($sql != "") {
            return $this->set_table_payment($sql);
        }
        $sql = "SELECT p.id, u.email, d.judul_donasi, p.created_at, p.price, p.payment_status
                FROM payments p
                LEFT JOIN data_donasi d ON p.id_donasi = d.id_data_donasi
                LEFT JOIN users u ON p.id_user = u.id
                ORDER BY p.created_at DESC;
                ";
        return $this->set_table_payment($sql);
    }

    public function create($id){
        $parent = "Donasi";
        $position = "Form Create";

        $conn =  $this->db_payment->connect();

        $sql = "SELECT d.*, (SELECT SUM(p.price) FROM payments p WHERE d.id_data_donasi = p.id_donasi AND p.payment_status = 2 ) AS total_donasi
                FROM data_donasi d WHERE d.id_data_donasi=".$id;
        $result = mysqli_query($conn, $sql);
        $data_donasi = mysqli_fetch_all($result);

        $sql = "SELECT * FROM users";
        $result = mysqli_query($conn, $sql);
        $data_user = mysqli_fetch_all($result);

        return include "../view/payment/index.php";
    }

    public function save_create(){
        $conn =  $this->db_payment->connect();

        $price = $_POST['price'];
        $nama_donasi = isset($_POST['nama_donasi']) && $_POST['nama_donasi'] != "" ? $_POST['nama_donasi'] : "Orang Baik";
        $id_user = $_POST['user'];
        $dukungan = isset($_POST['dukungan']) ? $_POST['dukungan'] : "";
        $payment_status = 1;
        $id_donasi = $_POST['id_data_donasi'];

        $sql = "INSERT INTO payments (id_donasi, price, payment_status, id_user, dukungan, nama_donatur)
                VALUES ('$id_donasi', '$price', '$payment_status', '$id_user', '$dukungan', '$nama_donasi')";
        
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $msg = [
                "title" => "Sukses",
                "type" => "success",
                "text" => "Data Berhasil Ditambahkan",
                "icon" => "success",
                "ButtonColor" => "#66BB6A"
            ];
            return json_encode($msg);
        }

        $msg = [
            "title" => "Gagal !!",
            "type" => "error",
            "text" => "Data Gagal Ditambahkan",
            "icon" => "error",
            "ButtonColor" => "#EF5350"
        ];
        return json_encode($msg);
    }

    public function update($id){
        $parent = "Payment";
        $position = "Form Update";

        $conn =  $this->db_payment->connect();

        $sql = "SELECT p.id, d.judul_donasi, p.nama_donatur, 
                (SELECT u.email FROM users u WHERE p.id_user = u.id) 
                as email, p.payment_status, p.price, p.dukungan
                FROM payments p 
                LEFT JOIN data_donasi d
                ON p.id_donasi = d.id_data_donasi 
                WHERE id = ".$id;
        $result = mysqli_query($conn, $sql);
        $data_payment = mysqli_fetch_all($result);
        return include "../view/payment/index.php";
    }

    public function save_update(){
        $conn =  $this->db_payment->connect();

        $nama_donasi = isset($_POST['nama_donasi']) && $_POST['nama_donasi'] != '' ? $_POST['nama_donasi'] : "Orang Baik";
        $dukungan = isset($_POST['dukungan']) ? $_POST['dukungan'] : "";
        $payment_status = $_POST['payment_status'];
        $id = $_POST['id_payment'];

        $sql = "UPDATE payments SET nama_donatur='$nama_donasi', 
                dukungan='$dukungan', payment_status='$payment_status'
                WHERE id=".$id;
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $msg = [
                "title" => "Sukses",
                "type" => "success",
                "text" => "Update Data Berhasil",
                "icon" => "success",
                "ButtonColor" => "#66BB6A"
            ];
            return json_encode($msg);
        }

        $msg = [
            "title" => "Gagal !!",
            "type" => "error",
            "text" => "Update Data Gagal !!",
            "icon" => "error",
            "ButtonColor" => "#EF5350"
        ];
        return json_encode($msg);

    }

    public function filters($data){
        $sql = "SELECT p.id, u.email, d.judul_donasi, p.created_at, p.price, p.payment_status
                FROM payments p
                LEFT JOIN data_donasi d ON p.id_donasi = d.id_data_donasi
                LEFT JOIN users u ON p.id_user = u.id";
        $data_min_nominal = $data['min_nominal'] != "" ? " p.price >= ".$data['min_nominal'] : '';
        $data_max_nominal = $data['max_nominal'] != "" ? " p.price <= ".$data['max_nominal'] : '';
        $data_payment_status = $data['payment_status'] != "" ? " p.payment_status = ".$data['payment_status'] : '';
        $sql_nominal = "";
        $data_min_tgl_payment = $data['min_tgl_payment'] != '' ? " p.created_at >= '".$data['min_tgl_payment']." 00:00:00'" : '';
        $data_max_tgl_payment = $data['max_tgl_payment'] != '' ? " p.created_at <= '".$data['max_tgl_payment']." 23:59:00'" : '';

        if ($data_min_nominal != "" && $data_max_nominal != "") {
            $sql_nominal = $data_min_nominal." AND ".$data_max_nominal;
        }

        if ($data != ''){
            $sql = $sql." WHERE ";
            if ($sql_nominal != "") {
                $sql = $sql.$sql_nominal;
            } else if ($data_min_nominal != '') {
                $sql = $sql.$data_min_nominal;
            } else if ($data_max_nominal != "") {
                $sql = $sql.$data_max_nominal;
            }

            if ($data_min_tgl_payment != '' ) {
                if($sql_nominal != "" || $data_min_nominal != '' || $data_max_nominal != ""){
                    $sql = $sql.' AND '.$data_min_tgl_payment;
                } else {
                    $sql = $sql.$data_min_tgl_payment;
                }
                
            } 
            if ($data_max_tgl_payment != '') {
                if($sql_nominal != "" || $data_min_nominal != '' || $data_max_nominal != "" || $data_min_tgl_payment != '' ){
                    $sql = $sql.' AND '.$data_max_tgl_payment;
                } else {
                    $sql = $sql.$data_max_tgl_payment;
                }
            } 

            if ($data_max_tgl_payment != '' && $data_min_tgl_payment != '') {
                $check_mx_tgl = explode(' ', $data_max_tgl_payment)[3];
                $check_min_tgl = explode(' ', $data_min_tgl_payment)[3];

                // echo $check_min_tgl;
                if ($check_mx_tgl <= $check_min_tgl) {
                    $msg = [
                        'title' => 'Gagal',
                        'type' => 'error',
                        'text' => 'Data tanggal tidak tepat !!',
                        "icon" => "error",
                        "ButtonColor" => "#EF5350"
                    ];
                    return json_encode($msg);
                }

            }
            
            if ($data_payment_status != '') {
                if($data_max_tgl_payment != '' || $sql_nominal != "" || $data_min_nominal != '' || $data_max_nominal != "" || $data_min_tgl_payment != '' ){
                    $sql = $sql.' AND '.$data_payment_status;
                } else {
                    $sql = $sql.$data_payment_status;
                }
            }
        }

        
        $msg = [
            'title' => 'Sukses',
            'type' => 'success',
            'text' => 'Data Berhasil di Perbarui',
            'icon' => 'success',
            'ButtonColor' => '#66BB6A',
            'sql' => $sql
        ];
        return json_encode($msg);
    }

    public function topDonasi($data){
        $tanggal_awal = $data['tanggal_awal'] != "" ? "'".$data['tanggal_awal'] ." 00:00:00'": '' ;
        $tanggal_akhir = $data['tanggal_akhir'] != "" ? "'".$data['tanggal_akhir'] ." 23:59:00'": '';

        $sql = "SELECT p.id, u.email, d.judul_donasi, p.created_at, p.price, p.payment_status
        FROM payments p
        LEFT JOIN data_donasi d ON p.id_donasi = d.id_data_donasi
        LEFT JOIN users u ON p.id_user = u.id
        WHERE p.created_at >= ".$tanggal_awal." AND p.created_at <= ".$tanggal_akhir." AND p.payment_status = 2
        AND p.price > (
            SELECT AVG(price)
            FROM payments
            WHERE created_at >= ".$tanggal_awal." AND created_at <= ".$tanggal_akhir." AND payment_status = 2
        )
        ORDER BY p.price DESC";

        $msg = [
            'title' => 'Sukses',
            'type' => 'success',
            'text' => 'Data Berhasil di Perbarui',
            'icon' => 'success',
            'ButtonColor' => '#66BB6A',
            'sql' => $sql
        ];
        return json_encode($msg);
    }

    public function set_table_payment($sql){
        $conn = $this->db_payment->connect();

        // Function of set data in donasi table
        $result = mysqli_query($conn, $sql);
        $data = mysqli_fetch_all($result);

        $data_json = [];
        $j = 1;
        for ($i = 0; $i < count($data); $i++) { 
            $btn_aksi = '<div class="text-center">
                            <div class="btn-group btn-group-solid mx-2">
                                <a href="/payment/update/'.$data[$i][0].'" class="btn btn-warning btn-raised btn-xs" id="btn-ubah" title="Ubah"><i class="icon-edit"></i></a>
                            </div>
                        </div>';

            $data_json[] = [
                'id' => $data[$i][0],
                'DT_RowIndex' => $j++,
                'action' => $btn_aksi,
                'email_user' => $data[$i][1],
                'nama_donasi' => $data[$i][2],
                'tgl_transaksi' => $data[$i][3],
                'nominal' => "Rp. ".number_format($data[$i][4], 2),
                'status_pembayaran' => $data[$i][5] == 1 ? 'Menunggu Pembayaran' : ($data[$i][5] == 2 ? 'Pembayaran Berhasil' : 'Pembayaran Expired')
            ];
        } 
        return json_encode(["draw"=>1, "recordsFiltered" => count($data),"recordsTotal" => count($data), "data" => $data_json, "start" => "0"]);
    }
}
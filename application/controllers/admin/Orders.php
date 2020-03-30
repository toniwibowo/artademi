<?php

defined('BASEPATH') or exit('No direct script access allowed');


/**
 * Name : Sketsa.cms base controller.
 *
 * @version 1.0.0
 *
 * @author : Arief Budiyono
 */
class Orders extends MX_Controller
{


    // Site
    private $title;
    private $logo;

    // Template
    private $admin_template;
    private $front_template;
    private $auth_template;
    private $youtubeanak_template;

    // Auth view
    private $login_view;
    private $register_view;
    private $forgot_password_view;
    private $reset_password_view;

    // Default page
    private $default_page;
    private $login_success;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('sketsanet');
        $this->load->library('output_view');
        $this->load->library('upload');

        // Site
        $site = $this->config->item('site');
        $this->title = $site['title'];
        $this->logo = $site['logo'];

        // Template
        $template = $this->config->item('template');
        $this->admin_template = $template['backend_template'];
        $this->front_template = $template['front_template'];
        $this->auth_template = $template['auth_template'];
       // $this->youtubeanak_template = $template['youtubeanak_template'];

        // Auth view
        $view = $this->config->item('view');
        $this->login_view = $view['login'];
        $this->register_view = $view['register'];
        $this->forgot_password_view = $view['forgot_password'];
        $this->reset_password_view = $view['reset_password'];

        // Default page
        $route = $this->config->item('route');
        $this->default_page = $route['default_page'];
        $this->login_success = $route['login_success'];
    }

    public function index()
    {
        if (!$this->ion_auth->logged_in()) {
            if ($this->default_page == '') {
                $this->login();
            } else {
               //$this->page($this->default_page);
              redirect('login');
            }
        } else {


        /*==============tampilan grucery crud====================================================*/
        $table_name = 'orders';
        $this->db->where('table_name', $table_name);
        $table = $this->db->get('table')->row();
        //echo $table->action.'';

        $this->load->library('Grocery_CRUD');
        $this->load->library('Grocery_CRUD_Multiuploader');
        //$crud = new grocery_CRUD();
        $crud = new Grocery_CRUD_Multiuploader();

        $crud->set_table($table_name);
        $crud->set_subject('Orders');

        $crud->fields('invoice','user_id', 'total', 'uniq_code', 'payment', 'order_date','order_time');

        $crud->callback_column('user_id',array($this, 'customer_name_callback'));

        $crud->callback_after_update(array($this, 'email_order_callback'));

        $crud->columns('invoice','user_id', 'total', 'uniq_code', 'payment', 'order_date','order_time');

        $crud->unset_add();

        $crud->callback_edit_field('invoice', function ($value, $primary_key) {
          return '<input type="text" maxlength="50" value="'.$value.'" name="invoice" readonly>';
        });

        $crud->callback_edit_field('user_id', function ($value, $primary_key) {

          $name = $this->db->where('id', $value)->get('users')->row()->full_name;

          return '<select name="user_id" class="form-control"><option value="'.$value.'" selected>'.$name.'</option> </select>';
        });

        $crud->field_type('payment','dropdown',array('0' => 'UNPAID', '1' => 'PAID'));
        //$crud->set_field_upload($field_upload, 'assets/uploads/files');

         //============TAMBAHAN MULTIUPLOAD =================================================

         // Field upload


        $config = array(

          /* Destination directory */
          "path_to_directory"       =>'assets/uploads/files/',

          /* Allowed upload type */
          "allowed_types"           =>'gif|jpeg|jpg|png',

          /* Show allowed file types while editing ? */
          "show_allowed_types"      => true,

          /* No file text */
          "no_file_text"            =>'No Pictures',

          /* enable full path or not for anchor during list state */
          "enable_full_path"        => false,

          /* Download button will appear during read state */
          "enable_download_button"  => true,

          /* One can restrict this button for specific types...*/
          "download_allowed"        => 'jpg'
        );

        //$crud->new_multi_upload($field_upload,$config);

        //============END TAMBAHAN MULTIUPLOAD =================================================

        $crud->set_theme('flexigrid');
        $data = (array) $crud->render();

        $this->output_view->set_wrapper('page', 'grocery', $data, false);
        $this->output_view->auth();

        $template_data['grocery_css'] = $data['css_files'];
        $template_data['grocery_js'] = $data['js_files'];

        $template_data['judul'] = 'Orders';
        $template_data['crumb'] = array('home' => 'rumah');
        $template = $this->admin_template;

        //print_r($template_data);
        $this->output_view->output($template, $template_data);

        /*===============end tampilan grocery crud================================================*/

        }
    }

    public function customer_name_callback($value, $row)
    {
      $name = $this->db->where('id', $value)->get('users')->row()->full_name;

      return $name;
    }

    public function email_order_callback($post_array, $primary_key)
    {
      if ($post_array['payment'] == 1) {

        $data['queryInvoice'] = $this->db->where('order_id', $primary_key)->get('orders');

        $data['user'] = $this->ion_auth->user($post_array['user_id'])->row();

        $row      = $data['queryInvoice']->row();


        $email    = $data['user']->email;
        $subject  = "Selamat order anda dengan Invoice - ".$post_array['invoice'].' telah selesai diproses.';
        $message  = $this->load->view('notification/invoice',$data,true);
        $name     = "Billing ARTAdemi";

        $this->email($email, $subject, $message, $name);
      }
    }

    public function email($email,$subject,$message,$name)
    {
      $config['protocol']   = 'smtp';
      $config['smtp_host']  = 'mail.gravenza.com';
      $config['smtp_user']  = 'info@gravenza.com';
      $config['smtp_pass']  = 'gravenza2015';
      $config['smtp_port']  = 465;
      $config['mailtype']   = 'html';
      $config['newline']    = "\r\n";

      $this->load->library('email', $config);

      $this->email->from('no-reply@artademi.com', $name);
      $this->email->to($email);
      $this->email->set_mailtype("html");
      //$this->email->cc('another@another-example.com');
      $this->email->bcc('yudisketsa@gmail.com');
      $this->email->bcc('tonny.wbw84@gmail.com');

      $this->email->subject($subject);
      $this->email->message($message);

      return $this->email->send();

    }

}

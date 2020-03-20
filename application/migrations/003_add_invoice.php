<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_invoice extends CI_Migration{

  public function up()
  {
    $this->dbforge->add_field(array(
      'invoice_id' => array(
        'type' => 'INT',
        'constraint' => 11,
        'unsigned' => TRUE,
        'auto_increment' => TRUE
      ),
      'order_id' => array(
        'type' => 'INT',
        'constraint' => 11
      )
    ));

    $this->dbforge->add_key('invoice_id', TRUE);
    $this->dbforge->create_table('invoice');
  }

  public function down()
  {
    $this->dbforge->drop_table('invoice');
  }

}

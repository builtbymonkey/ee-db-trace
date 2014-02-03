<?php

$this->table->set_template($cp_table_template);


$tbl_heading = array(
    '0' => array('data' => 'Date', 'style' => 'width: 100px',),
    '1' => array('data' => 'Developer'),
    '2' => array('data' => 'Description'),
    '3' => array('data' => 'Action'));

$this->table->set_heading($tbl_heading);


foreach($releases as $release)
{

    $action = "Already installed";

    if($release->installed != 1)
        $action = anchor(BASE . AMP . $base_url . AMP . 'method=publish&release=' . $release->file, 'Apply DB patch');

    $this->table->add_row(
            date('m/d/y H:i', $release->date), $release->developer, $release->description, $action);
}

echo $this->table->generate();
?>
<?php

if($errors)
{
    echo '<div id="form_errors">'.$errors.'</div>';
}


echo form_open($base_url . AMP . 'method=create_release');
?>

<table cellspacing="0" cellpadding="0" border="0" class="mainTable padTable">
    <caption>Release details</caption>

    <tbody>
        <tr class="odd">
            <td><label for="channel_title">Developer tag</label></td>
            <td style="width:50%;"><?php echo $developer; ?></td></tr>
        <tr class="even">
            <td><em class="required"> * </em><label for="description">Description</label></td>
            <td style="width:50%;"><input type="text" class="fullfield <?php if(form_error('description')) echo 'error'; ?>" id="description" value="<?php echo set_value('description'); ?>" name="description"></td></tr>
    </tbody>
</table>

<?php
$this->table->set_template($cp_table_template);


$tbl_heading = array(
    '0' => array('data' => 'Date', 'style' => 'width: 100px',),
    '1' => array('data' => 'Query batch'),
    '2' => array('data' => form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"'), 'style' => 'width: 20px; padding-left: 11px'),);

$this->table->set_heading($tbl_heading);

$geshi = new GeSHi('', 'sql');

foreach($query_batches as $query_batch)
{
    $result = '';

    foreach($query_batch->queries as $query)
    {
        $geshi->set_source($query->sql);

        $result .= '<div style="width: 600px; overflow: auto" >' . $geshi->parse_code() . '</div>';
    }

    $this->table->add_row(date('m/d/y H:i', $query_batch->date), $result, form_checkbox('query_md5[]', $query_batch->md5));
}
?>
<?php echo $this->table->generate(); ?>
<div style="text-align: right">
    <input type="submit" value="Create DB Patch" class="submit" />
</div>

</form>




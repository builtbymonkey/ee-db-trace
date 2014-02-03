<?php
echo form_open($base_url . AMP . 'method=update_and_publish');
?>

<table cellspacing="0" cellpadding="0" border="0" class="mainTable padTable">
    <caption>Release details</caption>

    <tbody>
        <tr class="odd">
            <td><label for="channel_title">Developer tag</label></td>
            <td style="width:50%;"><?php echo $meta->developer; ?></td></tr>
        <tr class="even">
            <td><label for="description">Description</label></td>
            <td style="width:50%;"><?php echo $meta->description; ?></td></tr>
        <tr class="even">
            <td><label for="description">Status</label></td>
            <td style="width:50%;"><?php
if(!$query_status)
{
    $url =  anchor( $backup_url,'rollback');
    
    echo '<span class="failed">Failed ('.$url.')</span>';
}
else
{
    echo '<span class="ok">Completed</span>';
}
?></td></tr>
    </tbody>
</table>

<?php
$this->table->set_template($cp_table_template);


$tbl_heading = array(
    '0' => array('data' => 'Date', 'style' => 'width: 100px',),
    '1' => array('data' => 'Query batch'));

$this->table->set_heading($tbl_heading);

$geshi = new GeSHi('', 'sql');

foreach($query_batches as $query_batch)
{
    $result = '';

    foreach($query_batch->queries as $query)
    {
        $geshi->set_source($query->sql);
        
        $snippet = array();
        $snippet['sql'] = $geshi->parse_code();
        $snippet['query'] = $query;
        
        $result .= $this->load->view('snippets/release_query', $snippet, TRUE);
    }

    $this->table->add_row(date('m/d/y H:i', $query_batch->date), $result);
}
?>
<?php echo $this->table->generate(); ?>
<div style="text-align: right">

</div>

</form>




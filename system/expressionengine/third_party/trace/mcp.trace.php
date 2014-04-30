<?php

/**
 * @package		DB Trace Module
 * @author		Frans Cooijmans, dWise
 * @copyright           Copyright (c) 2014 dWise
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt
 * @link		http://www.dwise.nl
 */
if(!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once APPPATH . "third_party/trace/includes/geshi/geshi.php";

class Trace_mcp
{

    private $themes_base_url = '';
    private $base_url = '';
    private $trace_file_location;

    function __construct()
    {
        $this->EE = & get_instance();

        $this->EE->load->library('table');

        $this->themes_base_url = $this->EE->config->item('theme_folder_url') . 'third_party/trace/';
        $this->base_url = 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=trace';

        $nav = array();
        $nav['Install release'] = BASE . AMP . $this->base_url . AMP . 'method=install_release';

        if($this->EE->config->item('trace_live_site') == FALSE)
        {
            $nav['Create DB patch'] = BASE . AMP . $this->base_url;
            $nav['Clear'] = BASE . AMP . $this->base_url . AMP . 'method=clear';
        }

        $this->EE->cp->set_right_nav($nav);

        $this->EE->cp->set_breadcrumb(BASE . AMP . $this->base_url, 'Trace');

        $this->EE->cp->add_to_head('<link type="text/css" href="' . $this->themes_base_url . 'layout.css" rel="stylesheet" />');

        $this->trace_file_location = APPPATH . "third_party/trace/files/";

        if($this->EE->config->item('trace_file_location'))
        {
            $this->trace_file_location = $this->EE->config->item('trace_file_location');

            if(substr($this->trace_file_location, -1) != '/')
                $this->trace_file_location.= '/';
        }
    }

    function index()
    {
        if($this->EE->config->item('trace_live_site') == TRUE)
            return $this->install_release();
        else
            return $this->create_release();
    }

    function create_release()
    {
        $this->EE->load->model('xml_trace_model');
        $this->EE->load->model('xml_release_model');
        $this->EE->load->library('form_validation');

        $this->EE->view->cp_page_title = lang('trace_module_name');

        $date = gmmktime();

        $trace_file = $this->trace_file_location . $this->EE->config->item('trace_developer') . ".xml";

        $this->EE->form_validation->set_rules('description', 'Description', 'required');
        $this->EE->form_validation->set_rules('query_md5', 'Queries', 'required');

        if($this->EE->form_validation->run() === FALSE)
        {

            if(!$this->EE->xml_trace_model->load($trace_file, FALSE))
                return $this->EE->load->view('no_trace', FALSE, TRUE);

            $view['errors'] = $this->EE->form_validation->error_string();

            $view['developer'] = $this->EE->config->item('trace_developer');
            $view['query_batches'] = $this->EE->xml_trace_model->get_all_query_batches();
            $view['base_url'] = $this->base_url;

            return $this->EE->load->view('create_release', $view, TRUE);
        }
        else
        {
            $post_queries = $this->EE->input->post('query_md5');

            $release_file = $this->trace_file_location . 'releases/' . $this->EE->config->item('trace_developer') . "_" . $date . ".xml";

            if(!$post_queries)
            {
                $this->EE->session->set_flashdata('message_failure', 'No queries selected');
                $this->EE->functions->redirect(BASE . AMP . $this->base_url);
            }

            if(!$this->EE->xml_trace_model->load($trace_file, FALSE))
            {
                $this->EE->session->set_flashdata('message_failure', 'Cannot find trace file');
                $this->EE->functions->redirect(BASE . AMP . $this->base_url);
            }

            $this->EE->xml_release_model->load($release_file, TRUE);



            $this->EE->xml_release_model->set_meta(
                    $date, $this->EE->config->item('trace_developer'), $this->EE->config->item('trace_url'), $this->EE->config->item('trace_path'), $this->EE->input->post('description'), md5($date . $this->EE->config->item('trace_developer')));

            $queries = $this->EE->xml_trace_model->find_queries($post_queries);

            if(count($queries) == 0)
            {
                $this->EE->session->set_flashdata('message_failure', 'Cannot find any queries');
                $this->EE->functions->redirect(BASE . AMP . $this->base_url);
            }

            foreach($queries as $query)
            {
                $this->EE->xml_release_model->add_query($query);
            }

            if(!$this->EE->xml_release_model->save())
            {
                $this->EE->session->set_flashdata('message_failure', 'Cannot save release file');
                $this->EE->functions->redirect(BASE . AMP . $this->base_url);
            }


            //remove current trace developer file...
            unlink($trace_file);

            return "Release saved";
        }
    }

    /**
     * MySQL MYISAM does not support transactions, therfore we need to figure out which tables are
     * used in a query and backup them.
     * 
     */
    function _check_tables($sql, &$table_names)
    {
        $tables = $this->EE->session->cache('trace', 'tables');

        if(!$tables)
        {
            $tables = $this->EE->db->list_tables();
            $this->EE->session->set_cache('trace', 'tables', $tables);
        }

        foreach($tables as $table)
        {
            if(strstr($sql, $table) !== FALSE)
            {
                if(!in_array($table, $table_names))
                    $table_names[] = $table;
            }
        }
    }

    function install_release()
    {
        $this->EE->load->model('xml_release_model');
        $this->EE->load->helper('file');

        $this->EE->view->cp_page_title = lang('trace_module_name');

        $file_releases = get_filenames($this->trace_file_location . "releases", TRUE);
        $db_releases = $this->EE->db->get('trace_releases');

        $releases = array();


        if($file_releases)
        {
            //get info about release files
            // @todo: check voor xml extensie
            foreach($file_releases as $file)
            {
                $file_parts = pathinfo($file);

                if($file_parts['extension'] == 'xml')
                {
                    if($this->EE->xml_release_model->load($file))
                    {
                        $meta = $this->EE->xml_release_model->get_meta();

                        $releases[$meta->md5] = $meta;
                    }
                }
            }
        }

        // get installed releases from db and match them with files
        if($db_releases->num_rows() > 0)
        {
            foreach($db_releases->result() as $row)
            {
                if(isset($releases[$row->release]))
                    $releases[$row->release]->installed = 1;
                else
                {
                    $releases[$row->release] = (object) array('date' => $row->date,
                                'developer' => $row->developer,
                                'description' => $row->description,
                                'md5' => $row->release,
                                'file' => '',
                                'installed' => 1);
                }
            }
        }

        $view = array();
        $view['base_url'] = $this->base_url;
        $view['releases'] = $releases;

        return $this->EE->load->view('release', $view, TRUE);
    }

    function publish()
    {

        $this->EE->load->dbutil();
        $this->EE->load->model('xml_release_model');
        // set state to publishing, this will disable tracing for the moment
        $this->EE->session->set_cache('trace', 'status', 'publishing');

        $file = $this->trace_file_location . "releases/" . $this->EE->input->get('release') . '.xml';
        $backup_file = $this->trace_file_location . "releases/" . $this->EE->input->get('release') . '_backup.sql';

        if(!file_exists($file) || !$this->EE->input->get('release'))
        {
            $this->EE->session->set_flashdata('message_failure', 'Cannot find release file');
            $this->EE->functions->redirect(BASE . AMP . $this->base_url);
        }


        $this->EE->xml_release_model->load($file);

        $meta = $this->EE->xml_release_model->get_meta();
        $query_batches = $this->EE->xml_release_model->get_query_batches();

        // $this->EE->db->trans_strict(TRUE);
        //  $this->EE->db->trans_begin();

        $tables_used = array();

        foreach($query_batches as &$query_batch)
        {
            foreach($query_batch->queries as &$query)
            {
                $this->_check_tables($query->sql, $tables_used);
            }
        }

        $prefs = array(
            'tables' => $tables_used, // Array of tables to backup.
            'ignore' => array(), // List of tables to omit from the backup
            'format' => 'txt', // File name - NEEDED ONLY WITH ZIP FILES
            'add_drop' => TRUE, // Whether to add DROP TABLE statements to backup file
            'add_insert' => TRUE, // Whether to add INSERT data to backup file
            'newline' => "\n"               // Newline character used in backup file
        );

        $backup = $this->EE->dbutil->backup($prefs);

        $this->EE->load->helper('file');
        write_file($backup_file, $backup);

        $backup_url = false;

        if(file_exists($backup_file))
        {
            $backup_url = BASE . AMP . $this->base_url . AMP . 'method=rollback' . AMP . 'release=' . $this->EE->input->get('release');
        }

        $query_status = TRUE;

        $this->EE->db->db_debug = false;

        foreach($query_batches as &$query_batch)
        {
            foreach($query_batch->queries as &$query)
            {

                $sql = $query->sql;

                // FIX PATHS AND URLS TO TARGET SERVER
                if($this->EE->config->item('trace_url') && $meta->source_url)
                    $sql = str_replace($meta->source_url, $this->EE->config->item('trace_url'), $sql);

                if($this->EE->config->item('trace_path') && $meta->source_path)
                    $sql = str_replace($meta->source_path, $this->EE->config->item('trace_path'), $sql);

                $this->EE->db->simple_query('SET AUTOCOMMIT=0');
                $this->EE->db->query($sql);

                $query->status = "Done";

                $insert_id = $this->EE->db->insert_id();
                $affected_rows = $this->EE->db->affected_rows();

                //  if($this->EE->db->trans_status() === FALSE)
                //   {
                //       $query->message = "Could not execute query.";
                //       $query->status = "Error";
                //   }

                if($this->EE->db->_error_message() != '')
                {
                    $query->message = $this->EE->db->_error_message();
                    $query->status = "Error";
                }

                if($insert_id != $query->insert_id)
                {
                    $query->message = "Inserted id ($insert_id) does not match orginal id ({$query->insert_id})";
                    $query->status = "Error";
                }

                if($affected_rows != $query->affected_rows)
                {
                    $query->message = "Affected rows ($affected_rows) no the same as in orginal db ({$query->affected_rows})";
                    $query->status = "Warning";
                }

                if($query->status == "Error")
                {
                    $query_status = FALSE;
                    break;
                }
            }

            if(!$query_status)
                break;
        }

        $this->EE->db->db_debug = true;

        if($query_status != FALSE)
        {
            $this->EE->db->trans_commit();

            $this->EE->db->insert('trace_releases', array('release' => $meta->md5,
                'developer' => $meta->developer,
                'date' => $meta->date, 'description' => $meta->description));
        }

        $this->EE->view->cp_page_title = "Release installation results";


        $this->EE->session->set_cache('trace', 'status', 'done');


        $this->EE->cp->add_to_foot('<script type="text/javascript" src="' . $this->themes_base_url . 'actions.js"></script>');

        $view = array();
        $view['query_batches'] = $query_batches;
        $view['query_status'] = $query_status;
        $view['meta'] = $meta;
        $view['base_url'] = $this->base_url;
        $view['backup_url'] = $backup_url;

        return $this->EE->load->view('publish', $view, true);
    }

    function clear()
    {
        $this->EE->view->cp_page_title = "Clear queries";

        $file = $this->trace_file_location . $this->EE->config->item('trace_developer') . ".xml";

        if(file_exists($file))
            unlink($file);

        return "All traced queries have been cleared!";
    }

    function rollback()
    {
        $this->EE->view->cp_page_title = "Rollback results";

        $this->EE->session->set_cache('trace', 'status', 'publishing');

        $backup_file = $this->trace_file_location . "releases/" . $this->EE->input->get('release') . '_backup.sql';

        if(file_exists($backup_file))
        {
            $this->EE->load->helper('file');

            $sql_file = read_file($backup_file);

            foreach(explode(";\n", $sql_file) as $sql)
            {
                $sql = trim($sql);

                if($sql)
                {
                    $this->EE->db->query($sql);
                }
            }

            unlink($backup_file);

            $this->EE->session->set_cache('trace', 'status', 'done');

            $view['queries'] = $sql_file;

            return $this->EE->load->view('rollback', $view, true);
        }
        else
        {
            $this->EE->session->set_cache('trace', 'status', 'done');
            $this->EE->session->set_flashdata('message_failure', 'Cannot find backup file');
            return $this->EE->functions->redirect(BASE . AMP . $this->base_url);
        }
    }

}

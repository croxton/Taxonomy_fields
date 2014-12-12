<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
class Taxonomy_wygwam_ft extends Taxonomy_field {

    /**
     * display_name
     * @var string
     */
    public $display_name = 'Wygwam';
     
    /**
     * Display a field in the control panel
     *
     * @access  public
     * @param   string 
     * @param   string 
     * @return  string 
     */
    public function display_field($name, $value) 
    {
        // helper
        require_once PATH_THIRD.'wygwam/helper.php';

        // include dependencies
        Wygwam_helper::include_field_resources();

        // get the associated wygwam config id
        $wygwam_config_id = 1; // default

        // convention: look for a config named taxonomy_[field_name])
        $query = ee()->db->get_where('wygwam_configs', array(
            'config_name' => 'taxonomy_' . $name
        ));

        if ($query->num_rows() > 0)
        {
            $row = $query->row(); 
            $wygwam_config_id = $row->config_id;
        }

        $wygwam_settings = array_merge(
            array("config" => $wygwam_config_id),
            Wygwam_helper::get_global_settings()
        );

        $field_id = 'cf-'.$name;
        $field_name = 'node[field_data]['.$name.']';

        Wygwam_helper::insert_config_js($wygwam_settings);
        Wygwam_helper::insert_js('new Wygwam("'.$field_id.'", "'.$wygwam_settings['config'].'", false);');

        // js
        $wygwam_js = '
            var $textarea = $("textarea[name=\'node[field_data]['.$name.']\']");
            var $form = $textarea.closest(\'form\');
            $form.submit(function() {
                // populate textarea
                var data = CKEDITOR.instances[\'cf-'.$name.'\'].getData();
                $("#'.$field_id.'").val(data);
            });
        ';

        // CKEDITOR.instances
        ee()->cp->add_to_foot('<script type="text/javascript">'.$wygwam_js.'</script>');

        // html
        $wygwam_html = '<div class="wygwam">
                            <textarea id="'.$field_id.'" name="'.$field_name.'" rows="10" data-config="'.$wygwam_settings['config'].'" data-defer="n">'
                            .$value.
                            '</textarea>
                        </div>';

        return $wygwam_html;
    }

    /**
     * Manipulate a saved field value before it is output in a template
     *
     * @access  public
     * @param   string 
     * @return  string 
     */
    public function replace_value($value)
    {
        // do nothing
        return $value;
    }

    /**
     * Alter value of a field before it is saved to the database
     *
     * @see     Taxonomy_mcp::update_node()
     * @access  public
     * @param   string The value of the custom field
     * @return  string Field value
     */
    public function pre_save($value)
    {
        // Trim out whitespace & empty tags
        $value = preg_replace('/^(\s|<(\w+)>(&nbsp;|\s)*<\/\2>|<br \/>)*/', '', $value);
        $value = preg_replace('/(\s|<(\w+)>(&nbsp;|\s)*<\/\2>|<br \/>)*$/', '', $value);

        // Remove ?cachebuster:X query strings
        $value = preg_replace('/\?cachebuster:\d+/', '', $value);

        // Entitize curly braces within codeblocks
        $value = preg_replace_callback('/<code>(.*?)<\/code>/s',
            create_function('$matches',
                'return str_replace(array("{","}"), array("&#123;","&#125;"), $matches[0]);'
            ),
            $value
        );

        // Remove Firebug 1.5.2+ div
        $value = preg_replace('/<div firebugversion=(.|\t|\n|\s)*<\\/div>/', '', $value);

        // Decode double quote entities (&quot;)
        $value = str_replace('&quot;', '"', $value);

        return $value;
    }
 
}
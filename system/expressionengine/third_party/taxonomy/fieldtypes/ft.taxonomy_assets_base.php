<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
class Taxonomy_assets_base_ft extends Taxonomy_field {

    /**
     * display_name
     * @var string
     */
    public $display_name = NULL;

    /**
     * asset_init
     * @var boolean
     */
    static protected $assets_init = FALSE;

    /**
     * allowed_file_kind
     * @var string
     */
    protected $allowed_file_kind = 'image';
     
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
        // default file url
        $file_url = '';

        if ( ! self::$assets_init)
        {   
            // Assets helper
            require_once PATH_THIRD.'assets/helper.php';
            $assets_helper = new Assets_helper;
            $assets_helper->include_sheet_resources();

            // Assets library
            ee()->load->add_package_path(PATH_THIRD.'assets/');
            ee()->load->library('assets_lib');

            self::$assets_init = TRUE;
        }

        // get selected file url
        if ( ! empty($value))
        {
            // heavy lifting
            $file_row   = ee()->assets_lib->get_file_row_by_id($value);
            $source     = ee()->assets_lib->instantiate_source_type($file_row);
            $file       = $source->get_file($value);

            if ($file instanceof Assets_base_file)
            {
                // add the file url to the view vars
                $file_url = $file->url();
            }
        }
    
        $assets_js = '
        var $delete  = $("#cf-'.$name.'-delete");
        var $select  = $("#cf-'.$name.'-select");
        
        var sheet'.$name.' = new Assets.Sheet({

            // optional settings (these are the default values):
            multiSelect: false,
            filedirs:    "all", // or array of filedir IDs
            kinds:       ["'.$this->allowed_file_kind.'"], // string "any", or array of file kinds e.g. ["image", "flash"]

            // onSelect callback (required):
            onSelect: function(files) {

                var $input   = $("input[name=\'node[field_data]['.$name.']\']");
                var $preview = $("#cf-'.$name.'-preview");
                var $delete  = $("#cf-'.$name.'-delete");

                $input.attr("value", files[0].id);
                $preview.find("img").attr("src", files[0].url);
                $preview.find("p").text(files[0].url);
                $preview.css("display", "block");
                $delete.css("display", "inline-block"); 
            }
        });
        $select.click(function(){
            sheet'.$name.'.show();
            return false;
        });
        
        $delete.click(function(e) {

            var $input   = $("input[name=\'node[field_data]['.$name.']\']");
            var $preview = $("#cf-'.$name.'-preview");
            var $delete  = $("#cf-'.$name.'-delete");

            $preview.css("display", "none");
            $input.attr("value", "");
            $delete.css("display", "none");
            return false;
        });
        ';
        ee()->cp->add_to_foot('<script type="text/javascript">'.$assets_js.'</script>');

        // generate markup
        return $this->field_html($name, $value, $file_url);
    }

    /**
     * Swap a file_id for the actual url of the referenced file
     *
     * @access  public
     * @param   string 
     * @return  string 
     */
    public function replace_value($value)
    {
        if ( ! self::$assets_init)
        {   
            // Assets library
            ee()->load->add_package_path(PATH_THIRD.'assets/');
            ee()->load->library('assets_lib');
            self::$assets_init = TRUE;
        }

        // get selected file url
        if ( ! empty($value))
        {
            // heavy lifting
            $file_row   = ee()->assets_lib->get_file_row_by_id($value);
            $source     = ee()->assets_lib->instantiate_source_type($file_row);
            $file       = $source->get_file($value);

            if ($file instanceof Assets_base_file)
            {
                // get the file_url
                $value = $file->url();
            }
        }

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
        return $value; // do nothing
    }

    /**
     * Generate markup for the custom field - implemented by child classes
     *
     * @access  protected
     * @param   string 
     * @param   string 
     * @param   string 
     * @return  string 
     */
    protected function field_html($name, $value, $url)
    {
    }
 
}
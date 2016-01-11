<?php
/*
 * Form builder
 *
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

final class FormBuilder {
    var $form = '';             // HTML form
    var $form_id = '';          // Form ID
    var $form_object = null;    // Form object
    var $control_id = 0;        // Control ID
    var $checkbox_id = 0;       // Checkbox ID
    var $radio_id = 0;          // Radio button ID
    var $entity = null;         // Model object
    var $errors = array();      // Form errors
    var $rules = array();       // Fields validation rules
    var $files = array();       // File(s) location(s)
    var $default_ext = 'jpg';   // Default file extension
    var $translation = null;    // Translation service
    
    /*
	 * Tabulation generator
	 *
	 * @param int $nb Number of tabulations to display
	 * 
	 * @param string Tabulations
	 */
    
    private function setTabs($nb) {
        $tabs = "";
        
        for($i=0;$i<=$nb;$i++) {
            $tabs.= "\t";
        }
        
        return $tabs;
    }
    
    /*
	 * Line break generator
	 * 
	 * @param string The line break
	 */
    
    private function EOL() {
        return "\n";
    }
    
    /*
	 * Class constructor
	 *
	 * @param object $form Form object
	 * @param string $action URL to reach when the form is submitted
	 * @param string $method Method to use
	 * @param string $id ID to apply to the form
	 */
    
    public function __construct($form,$action='',$method='GET',$id='') {
        $this->form_object = $form;
        $this->form_id = $id != '' ? $id : App::random_string(8);
        $this->form.= '<form enctype="multipart/form-data" action="' . $action . '" method="' . $method . '" id="' . $this->form_id . '">' . $this->EOL();
        $this->form.= $this->setTabs(1) . '<div class="form_wrapper">' . $this->EOL();
        
        $this->entity = $form->getEntity();
        
        $errors = $form->getErrors();
        
        if ($this->translation === null) {
    	   $this->translation = new Translation(false,'sybil','form');
    	}
        
        if (isset($errors) && !empty($errors)) {
            $this->errors = $errors;
            $this->form.= $this->setTabs(2) . "<div class=\"form_error\">" . (isset($_SESSION['form_error']) ? $_SESSION['form_error'] : $this->translation->translate('Form contains validation errors')) . ".</div>" . $this->EOL();
        }
        
        if (!empty($_SESSION['form_success'])) {
            $this->form.= $this->setTabs(2) . "<div class=\"form_success\">" . $_SESSION['form_success'] . "</div>" . $this->EOL();
            unset($_SESSION['form_success']);
        }
    }
    
    /*
	 * Creating a fieldset title
	 *
	 * @param array $params Parameters for the title
	 * 
	 * array(
	 * 	'label' => '',			Title label
	 * 	'level' => '',			HTML H-level element to use
	 * 	'class' => '',			Class to apply on the title
	 * )
	 */
    
    public function title($params) {
        extract($params);
        
        $this->form.= $this->setTabs(2) . '<' . (isset($level) ? $level : 'h3') . (isset($class) ? ' class="' . $class . '"' : '') . '>';
        $this->form.= $label;
        $this->form.= '</' . (isset($level) ? $level : 'h3') . '>' . $this->EOL();
    }
    
    /*
	 * Starting a div wrapper
	 *
	 * @param array $params Parameters for the wrapper
	 * 
	 * array(
	 * 	'id' => '',			Wrapper ID
	 * )
	 */
    
    public function startWrap($params) {
        extract($params);
        
        $this->form.= $this->setTabs(2) . '<div class="form_subwrapper"' . (isset($id) ? ' id="' . $id . '"' : '') . '>' . $this->EOL();;
    }
    
    /*
	 * Closing a div wrapper
	 */
    
    public function endWrap() {
        $this->form.= $this->setTabs(2) . '</div>' . $this->EOL();;
    }
    
    /*
	 * Creating an input field
	 *
	 * @param array $params Parameters for the field
	 * 
	 * array(
	 * 	'label' => '',			Input label
	 * 	'id' => '',				ID used for the label and the field (otherwise, a default ID is added)
	 * 	'type' => '',			Input type (otherwise, text type is added)
	 * 	'name' => '',			Name for the field (otherwise, a default name is added)
	 * 	'value' => '',			Value for the field (empty by default)
	 * 	'placeholder' => '',	Placeholder for the field (empty by default)
	 *	'disabled' => '',		Input state (enabled by default)
	 *	'autofocus' => '',		State of autofocus (false by default)
	 *	'required' => '',		Required field or not (not required by default)
	 *	'maxlength' => '',		Maximum number of characters authorized
	 *	'pattern' => '',		Regex pattern (no pattern by default)
	 *	'details' => '',		Details to display after the field
     *  'autofill' => '',       Field auto-filling or not (true by default)
     *  'local' => '',          Local field or DB field (false by default > DB field)
     *  'error_message' => ''   Custom error message (automatic message by default)
	 * )
	 */
    
    public function field($params) {
        extract($params);
        $this->control_id++;

        $name = (isset($name) ? $name : 'field_'.$this->control_id);

        // Getting the correct field value

        if (!isset($autofill) || $autofill === true) {
            if ($this->entity && $this->entity->get('id') != '' || $type != 'password') {
                if (isset($local) && $local === true) {
                    if(isset($_POST['form_data'][$name])) {
                        $field_value = $_POST['form_data'][$name];
                    }
                }
                else {
                	$v = $this->entity ? $this->entity->get($name) : '';
                	
                    if(isset($_POST['form_data'][$name])) {
                        if ($type != 'password') {
                            $field_value = $_POST['form_data'][$name];
                        }
                        else {
                            $field_value = 'sybil_pass';
                        }
                    }
                    elseif (!empty($v)) {
                         $field_value = $v;
                    }
                    else {
                        $field_value = isset($value) ? $value : '';
                        
                        if ($this->form_object->isFilled() === true && $type == 'password') {
                            $field_value = 'sybil_pass';
                        }
                    }
                }
            }
        }

        // Setting error if necessary

        if (array_key_exists($name,$this->errors)) {
            $control_error = ' control_error';
            $error_info = isset($error_message) && !empty($error_message) ? $error_message : $this->errors[$name]['error'];
        }
        else {
            $control_error = '';
            $error_info = '';
        }

        // Setting the identifiers

        $identifier = (isset($id) ? 'form_' . $this->form_id . '_field_' . $id : (isset($name) ? 'form_' . $this->form_id . '_field_'.$name : 'form_' . $this->form_id . '_field_'.$this->control_id));
        $identifier_short = (isset($id) ? 'field_' . $id : (isset($name) ? 'field_'.$name : 'field_'.$this->control_id));

        // Constructing the HTML

        $this->form.= $this->setTabs(2) . "<div class=\"form_control control_field " . $identifier_short . $control_error . "\">" . $this->EOL();

        if (isset($label) && $label !== false) {
            $this->form.= $this->setTabs(3) . "<label for=\"" . $identifier . "\">" . $label;
            
            // Field details

	        if (isset($details) && !empty($details)) {
	            $this->form.= " <span class=\"control_details\">" . $details . "</span>";
	        }
            
            $this->form.= "</label>" . $this->EOL();
        }

        $this->form.= $this->setTabs(3) . "<input type=\"" . (isset($type) ? $type : 'text') . "\" name=\"form_data[" . $name . "]\" id=\"" . $identifier . "\" value=\"" . $field_value . "\" placeholder=\"" . (isset($placeholder) ? $placeholder : '') . "\"";

        // Optionnal attributes

        if (isset($disabled) && $disabled === true) {
            $this->form.= " disabled=\"disabled\"";
        }

        if (isset($required) && $required === true) {
            $this->form.= " required=\"required\"";
        }

        if (isset($maxlength) && !empty($maxlength)) {
            $this->form.= " maxlength=\"" . $maxlength . "\"";
        }
        
        if (isset($autofocus) && $autofocus === true) {
            $this->form.= " autofocus";
        }

        if (isset($pattern) && !empty($pattern) && preg_match('#([\(\[\)\{\},+?*])#',$pattern)) {
            $this->form.= " pattern=\"" . $pattern . "\"";
        }

        $this->form.= ">" . $this->EOL();

        // Displaying the error

        if (!empty($error_info)) {
            $this->form.= $this->setTabs(3) . "<span class=\"error_info\">" . $error_info . "</span>" . $this->EOL();
        }

        $this->form.= $this->setTabs(2) . "</div>" . $this->EOL();

        // Storing the validation rules

        $this->rules[$name] = array(
            'required' => isset($required) && $required === true ? true : false,
            'pattern' => isset($pattern) ? $pattern : null,
            'equals' => isset($equals) ? $equals : null,
            'message' => isset($message) ? $message : ''
        );
    }
    
    /*
	 * Creating a file input
	 *
	 * @param array $params Parameters for the input
	 * 
	 * array(
	 * 	'label' => '',			Input label
	 * 	'id' => '',				ID used for the label and the field (otherwise, a default ID is added)
	 * 	'name' => '',			Name for the field (otherwise, a default name is added)
	 *	'required' => '',		Required field or not (not required by default)
	 *	'details' => '',		Details to display after the field
     *  'error_message' => ''   Custom error message (automatic message by default)
	 * )
	 */
    
    public function file($params) {
	    extract($params);
	    $this->control_id++;

        $name = (isset($name) ? $name : 'field_'.$this->control_id);
        
        // Setting error if necessary

        if (array_key_exists($name,$this->errors)) {
            $control_error = ' control_error';
            $error_info = isset($error_message) && !empty($error_message) ? $error_message : $this->errors[$name]['error'];
        }
        else {
            $control_error = '';
            $error_info = '';
        }

        // Setting the identifiers

        $identifier = (isset($id) ? 'form_' . $this->form_id . '_field_' . $id : (isset($name) ? 'form_' . $this->form_id . '_field_'.$name : 'form_' . $this->form_id . '_field_'.$this->control_id));
        $identifier_short = (isset($id) ? 'field_' . $id : (isset($name) ? 'field_'.$name : 'field_'.$this->control_id));

        // Constructing the HTML

        $this->form.= $this->setTabs(2) . "<div class=\"form_control control_file " . $identifier_short . $control_error . "\">" . $this->EOL();
        
        if (isset($label) && $label !== false) {
            $this->form.= $this->setTabs(3) . "<label for=\"" . $identifier . "\">" . $label . $this->EOL();
            
            // Field details

	        if (isset($details) && !empty($details)) {
	            $this->form.= $this->setTabs(4) . "<span class=\"control_details\">" . $details . "</span><br>";
	        }
	        
	        $this->form.= $this->EOL() . $this->setTabs(4) . "<div class=\"form_upload_button\">" . $this->translation->translate('Choose a file') . "</div>" . $this->EOL();
	        
	        $this->form.= $this->setTabs(4) . '<span class="form_file_value" data-empty="' . $this->translation->translate('No file selected') . '">' . $this->translation->translate('No file selected') . '</span>';
	        
	        if (is_object($this->entity)) {
	           $file_id = $this->entity->get('id');
	        }
	        
	        if (isset($target) && !empty($target)) {
	           $target[1] = !isset($target[1]) ? '[id]' : '[' . $target[1] . ']';
	           $target[2] = !isset($target[2]) ? $this->default_ext : $target[2];
	           $target['file_name'] = is_object($this->entity) ? $this->entity->get(str_replace('[','',str_replace(']','',$target[1]))) : '';
	           
	           $this->files[$name] = UPLOADS . $target[0] . '/' . $target[1] . '.' . $target[2];
	           
	           $file_path = $target[0] . '/' . $target['file_name'] . '.' . $target[2];
	        }
	        else {
    	        $this->files[$name] = UPLOADS . $this->form_id . '_[id].jpg';
    	        $file_path = $this->form_id . '_' . $this->entity->get('id') . '.jpg';
	        }
	        
	        if (is_object($this->entity) && $this->entity->get('id') != '' && file_exists(UPLOADS.$file_path)) {
	            $this->form.= $this->EOL() . $this->setTabs(4) . '<img src="' . UPLOADS_ROOT . $file_path . '" alt="" class="form_file_saved">';
	        }
	        else {
    	        $this->form.= $this->EOL() . $this->setTabs(4) . '<div class="form_file_saved_empty">' . $this->translation->translate("No saved file") . '.</div>';
	        }
            
            $this->form.= $this->EOL() . $this->setTabs(3) . "</label>" . $this->EOL();
        }

        $this->form.= $this->setTabs(3) . "<input style=\"display:none;\" type=\"file\" name=\"" . $name . "\" id=\"" . $identifier . "\"";

        // Optionnal attributes

        if (isset($required) && $required === true) {
            $this->form.= " required=\"required\"";
        }

        $this->form.= ">" . $this->EOL();
        
        // Displaying the error

        if (!empty($error_info)) {
            $this->form.= $this->setTabs(3) . "<span class=\"error_info\">" . $error_info . "</span>" . $this->EOL();
        }

        $this->form.= $this->setTabs(2) . "</div>" . $this->EOL();

        // Storing the validation rules

        $this->rules[$name] = array(
        	'file' => true,
            'required' => isset($required) && $required === true ? true : false,
            'maxsize' => isset($maxsize) ? $maxsize : 0,
            'formats' => isset($formats) ? $formats : array(),
            'min_dimensions' => isset($min_dimensions) ? $min_dimensions : array(),
            'max_dimensions' => isset($max_dimensions) ? $max_dimensions : array()
        );
    }
    
    /*
	 * Creating a unique checkbox control
	 *
	 * @param array $params Parameters for the checkbox
	 * 
	 * array(
	 * 	'label' => '',			Checkbox label
	 * 	'id' => '',				ID used for the label and the checkbox (otherwise, a default ID is added)
	 * 	'name' => '',			Name for the field (otherwise, a default name is added)
	 *	'required' => '',		Required field or not (not required by default)
	 *	'checked' => '',		Checkbox auto checked or not (not by default)
     *  'details' => '',        Details to display after the field
     *  'autofill' => '',       Checkbox auto-filling or not (true by default)
     *  'local' => '',          Local checkbox or DB checkbox (false by default > DB checkbox)
     *  'error_message' => ''   Custom error message (automatic message by default)
	 * )
	 */
    
    public function checkbox($params) {
        extract($params);
        $this->control_id++;

        $name = (isset($name) ? $name : 'field_'.$this->control_id);

        // Getting the check state

        if (is_object($this->entity) && (!isset($autofill) || $autofill === true)) {
            if (isset($local) && $local === true) {
                if(isset($_POST['form_data'][$name])) {
                    if (isset($_POST['form_data'][$name])) {
                        $checked = true;
                    }
                }
            }
            else {
                $v = $this->entity->get($name);
                
                if(isset($_POST['form_data'][$name])) {
                    if (isset($_POST['form_data'][$name])) {
                        $checked = true;
                    }
                }
                elseif (!empty($v)) {
                    if (!isset($local) || $local === false) { 
                        $checked = $v;
                    }
                }
                else {
                    $checked = isset($checked) ? $checked : false;
                }
            }
        }

        // Setting error if necessary

        if (array_key_exists($name,$this->errors)) {
            $control_error = ' control_error';
            $error_info = isset($error_message) && !empty($error_message) ? $error_message : $this->errors[$name]['error'];
        }
        else {
            $control_error = '';
            $error_info = '';
        }

        // Setting the identifiers

        $identifier = (isset($id) ? 'form_' . $this->form_id . '_checkbox_' . $id : (isset($name) ? 'form_' . $this->form_id . '_checkbox_'.$name : 'form_' . $this->form_id . '_checkbox_'.$this->control_id));
        $identifier_short = (isset($id) ? 'checkbox_' . $id : (isset($name) ? 'checkbox_'.$name : 'checkbox_'.$this->control_id));

        // Constructing the HTML

        $this->form.= $this->setTabs(2) . "<div class=\"form_control control_checkbox " . $identifier_short . $control_error . "\">" . $this->EOL();

        $this->form.= $this->setTabs(3) . "<input type=\"checkbox\" name=\"form_data[" . $name . "]\" id=\"" . $identifier . "\"";

        // Optionnal attributes

        if (isset($required) && $required === true) {
            $this->form.= " required=\"required\"";
        }

        if (isset($checked) && $checked === true) {
            $this->form.= " checked=\"checked\"";
        }

        $this->form.= ">" . $this->EOL();

        if (isset($label) && $label !== false) {
            $this->form.= $this->setTabs(3) . "<label for=\"" . $identifier . "\">" . $label . "</label>" . $this->EOL();
        }

        // Displaying the error

        if (!empty($error_info)) {
            $this->form.= $this->setTabs(3) . "<span class=\"error_info\">" . $error_info . "</span>" . $this->EOL();
        }

        $this->form.= $this->setTabs(2) . "</div>" . $this->EOL();

        // Storing the validation rules

        $this->rules[$name] = array(
            'required' => isset($required) && $required === true ? true : false,
            'message' => $this->translation->translate('This box must be checked')
        );
    }
    
    /*
	 * Creating checkboxes
	 *
	 * @param array $params Parameters for the checkboxes
	 * 
	 * array(
	 * 	'label' => '',				Control label
	 * 	'id' => '',					ID used for the label and the control (otherwise, a default ID is added)
     *  'autofill' => '',           Checkboxes auto-filling or not (true by default)
     *	'details' => '',			Details to display after the field
	 *	'checkboxes' => array(
	 *		array(
	 *			'name' => '',		Name for the checkbox (otherwise, a default name is added)
	 *			'label' => '',		Label for the checkbox
	 *			'checked' => ''		Checkbox checked or not (not by default)
	 *		)
	 *	)
	 * )
	 */
    
    public function checkboxes($params) {
        extract($params);
        $this->control_id++;

        // Setting the identifiers
        
        $identifier = (isset($id) ? 'form_' . $this->form_id . '_checkboxes_' . $id : 'form_' . $this->form_id . '_checkboxes_'.$this->control_id);
        $identifier_short = (isset($id) ? 'checkboxes_' . $id : (isset($name) ? 'checkboxes_'.$name : 'checkboxes_'.$this->control_id));
        $name = (isset($id) ? $id : (isset($name) ? $name : $this->control_id));
        
        // Setting error if necessary

        if (array_key_exists($name,$this->errors)) {
            $control_error = ' control_error';
            $error_info = isset($error_message) && !empty($error_message) ? $error_message : $this->errors[$name]['error'];
        }
        else {
            $control_error = '';
            $error_info = '';
        }
        
        // Constructing the HTML

        $this->form.= $this->setTabs(2) . "<div class=\"form_control control_checkboxes " . $identifier_short . $control_error . "\">" . $this->EOL();
        
        if (isset($label) && $label !== false) {
            $this->form.= $this->setTabs(2) . "<label>" . $label;
            
            // Field details

	        if (isset($details) && !empty($details)) {
	            $this->form.= " <span class=\"control_details\">" . $details . "</span>";
	        }
            
            $this->form.= "</label>" . $this->EOL();
        }
        
        // For each checkbox 

        if (!empty($checkboxes)) {
            foreach($checkboxes as $checkbox) {
                $this->checkbox_id++;
                $checkbox_name = (isset($checkbox['name']) ? $checkbox['name'] : 'field_'.$this->control_id);
                
                // Getting the check state

                if (!isset($autofill) || $autofill === true) {
                    if(isset($_POST['form_data'][$name])) {
                        if (isset($_POST['form_data'][$name][$checkbox_name])) {
                            $checkbox['checked'] = true;
                        }
                    }
                }
                
                $this->form.= $this->setTabs(3) . "<span class=\"form_checkbox_wrapper\">" . $this->EOL();
                
                $checkbox_identifier = $identifier . '_checkbox_' . (isset($checkbox['name']) ? $checkbox['name'] : $this->checkbox_id);
                
                $this->form.= $this->setTabs(4) . "<input" . (isset($checkbox['checked']) && $checkbox['checked'] === true ? ' checked="checked"' : '') . " type=\"checkbox\" name=\"form_data[" . $name . "][" . $checkbox_name . "]\" id=\"" . $checkbox_identifier . "\">" . $this->EOL() . $this->setTabs(4) . "<label for=\"" . $checkbox_identifier . "\">" . (isset($checkbox['label']) ? $checkbox['label'] : '') . "</label>" . $this->EOL();
                
                $this->form.= $this->setTabs(3) . "</span>" . $this->EOL();
            }
            
            $this->checkbox_id = 0;
        }
        
        // Displaying the error

        if (!empty($error_info)) {
            $this->form.= $this->setTabs(3) . "<span class=\"error_info\">" . $error_info . "</span>" . $this->EOL();
        }
        
        $this->form.= $this->setTabs(2) . "</div>" . $this->EOL();
        
        // Storing the validation rules

        $this->rules[$name] = array(
            'min' => isset($min) && is_numeric($min) && $min >= 0 ? $min : 0
        );
    }
    
    /*
	 * Creating radio buttons
	 *
	 * @param array $params Parameters for the radio buttons
	 * 
	 * array(
	 * 	'label' => '',				Control label
	 * 	'id' => '',					ID used for the label and the control (otherwise, a default ID is added)
	 *	'name' => '',				Name for the button (otherwise, a default name is added)
	 *  'autofill' => '',       	Button auto-filling or not (true by default)
	 *	'details' => '',			Details to display after the field
     *  'local' => '',          	Local button or DB associated (false by default > DB associated)
     *	'required' => '',			Required control or not (required by default)
	 *	'buttons' => array(
	 *		array(
	 *			'label' => '',		Label for the button
	 *			'checked' => ''		Button checked or not (not by default)
	 *		)
	 *	)
	 * )
	 */
    
    public function radio($params) {
        extract($params);
        $this->control_id++;

        // Setting the identifiers
        
        $identifier = (isset($id) ? 'form_' . $this->form_id . '_radios_' . $id : 'form_' . $this->form_id . '_radios_'.$this->control_id);
        $identifier_short = (isset($id) ? 'radios_' . $id : (isset($name) ? 'radios_'.$name : 'radios_'.$this->control_id));
        $name = (isset($name) ? $name : 'field_'.$this->control_id);
        
        // Setting error if necessary

        if (array_key_exists($name,$this->errors)) {
            $control_error = ' control_error';
            $error_info = isset($error_message) && !empty($error_message) ? $error_message : $this->errors[$name]['error'];
        }
        else {
            $control_error = '';
            $error_info = '';
        }

        // Constructing the HTML
        
        $this->form.= $this->setTabs(2) . "<div class=\"form_control control_radios " . $identifier_short . $control_error . "\">" . $this->EOL();
        
        if (isset($label) && $label !== false) {
            $this->form.= $this->setTabs(2) . "<label>" . $label;
            
            // Field details

	        if (isset($details) && !empty($details)) {
	            $this->form.= " <span class=\"control_details\">" . $details . "</span>";
	        }
            
            $this->form.= "</label>" . $this->EOL();
        }

        // For each button
        
        if (!empty($buttons)) {
            foreach($buttons as $button) {
                $this->radio_id++;

                // Getting the check state
                
                if (!isset($autofill) || $autofill === true) {
                    if (isset($local) && $local === true) {
                        if(isset($_POST['form_data'][$name])) {
                            if (isset($_POST['form_data'][$name])) {
                                $button['checked'] = true;
                            }
                        }
                    }
                    else {
                        $v = $this->entity->get($name);
                        
                        if(isset($_POST['form_data'][$name])) {
                             if (isset($_POST['form_data'][$name])) {
                                if ($button['value'] == $_POST['form_data'][$name]) {
                                    $button['checked'] = true;
                               }
                            }
                        }
                        elseif(!empty($v)) { 
                           if ($button['value'] == $v) {
                                $button['checked'] = true;
                           }
                        }
                        else {
                            $button['checked'] = isset($button['checked']) ? $button['checked'] : false;
                        }
                    }
                }
                
                $this->form.= $this->setTabs(3) . "<span class=\"form_radio_wrapper\">" . $this->EOL();
                
                $radio_identifier = $identifier . '_radio_' . (isset($button['name']) ? $button['name'] : $this->radio_id);
                
                $this->form.= $this->setTabs(4) . "<input" . (isset($required) && $required === true ? ' required="required"' : (!isset($required) ? ' required="required"' : '')) . "" . (isset($button['checked']) && $button['checked'] === true ? ' checked="checked"' : '') . " value=\"" . (isset($button['value']) ? $button['value'] : '') . "\" type=\"radio\" name=\"form_data[" . (isset($name) ? $name : 'field_'.$this->control_id) . "]\" id=\"" . $radio_identifier . "\">" . $this->EOL() . $this->setTabs(4) . "<label for=\"" . $radio_identifier . "\">" . (isset($button['label']) ? $button['label'] : '') . "</label>" . $this->EOL();
                
                $this->form.= $this->setTabs(3) . "</span>" . $this->EOL();
            }
            
            $this->radio_id = 0;
        }

        // Displaying the error

        if (!empty($error_info)) {
            $this->form.= $this->setTabs(3) . "<span class=\"error_info\">" . $error_info . "</span>" . $this->EOL();
        }
        
        $this->form.= $this->setTabs(2) . "</div>" . $this->EOL();
        
        // Storing the validation rules

        $this->rules[$name] = array(
            'required' => isset($required) && $required === true ? true : false
        );
    }
    
    /*
	 * Creating a textarea
	 *
	 * @param array $params Parameters for the textarea
	 * 
	 * array(
	 * 	'label' => '',			Textarea label
	 * 	'id' => '',				ID used for the label and the textarea (otherwise, a default ID is added)
	 * 	'name' => '',			Name for the textarea (otherwise, a default name is added)
	 *	'value' => '',			Default value for the textarea (empty by default)
	 *	'details' => ''			Details to display after the textarea
	 *  'autofill' => '',       Textarea auto-filling or not (true by default)
     *  'local' => '',          Local textarea or DB associated (false by default > DB associated)
     *	'required' => ''		Required textarea or not (not required by default)
	 * )
	 */
    
    public function textarea($params) {
        extract($params);
        $this->control_id++;
        
        // Setting the identifiers
        
        $identifier = (isset($id) ? 'form_' . $this->form_id . '_textarea_' . $id : (isset($name) ? 'form_' . $this->form_id . '_textarea_'.$name : 'form_' . $this->form_id . '_textarea_'.$this->control_id));
        $identifier_short = (isset($id) ? 'textarea_' . $id : (isset($name) ? 'textarea_'.$name : 'textarea_'.$this->control_id));
        $name = (isset($name) ? $name : 'field_'.$this->control_id);
        
        // Getting the correct field value
        
        if (is_object($this->entity) && (!isset($autofill) || $autofill === true)) {
            $v = $this->entity->get($name);
            
            if(isset($_POST['form_data'][$name])) {
                 $value = $_POST['form_data'][$name];
            }
            elseif (!empty($v)) {
                $value = $v;
            }
            else {
                $value = isset($value) ? $value : '';
            }
        }
        
        // Setting error if necessary
        
        if (array_key_exists($name,$this->errors)) {
            $control_error = ' control_error';
            $error_info = isset($error_message) && !empty($error_message) ? $error_message : $this->errors[$name]['error'];
        }
        else {
            $control_error = '';
            $error_info = '';
        }
        
        // Constructing the HTML
        
        $this->form.= $this->setTabs(2) . "<div class=\"form_control control_textarea " . $identifier_short . $control_error . "\">" . $this->EOL();
     
        if (isset($label) && $label !== false) {
            $this->form.= $this->setTabs(2) . "<label for=\"" . $identifier . "\">" . $label;
            
            // Field details

	        if (isset($details) && !empty($details)) {
	            $this->form.= " <span class=\"control_details\">" . $details . "</span>";
	        }
            
            $this->form.= "</label>" . $this->EOL();
        }
        
        $this->form.= $this->setTabs(3) . "<textarea " . (isset($required) && $required === true ? 'required="required" ' : '') . "name=\"form_data[" . $name . "]\" id=\"" . $identifier . "\">" . (isset($value) ? $value : '') . "</textarea>" . $this->EOL();
        
        // Displaying the error
        
        if (!empty($error_info)) {
            $this->form.= $this->setTabs(3) . "<span class=\"error_info\">" . $error_info . "</span>" . $this->EOL();
        }
        
        $this->form.= $this->setTabs(2) . "</div>" . $this->EOL();
        
        // Storing the validation rules
        
        $this->rules[$name] = array(
            'required' => isset($required) && $required === true ? true : false
        );
    }
    
    /*
	 * Creating a dropdown list
	 *
	 * @param array $params Parameters for the dropdown list
	 * 
	 * array(
	 * 	'label' => '',					Dropdown label
	 * 	'id' => '',						ID used for the label and the dropdown (otherwise, a default ID is added)
	 * 	'name' => '',					Name for the dropdown (otherwise, a default name is added)
	 *	'details' => ''					Details to display after the textarea
	 *  'autofill' => '',       		Dropdown auto-filling or not (true by default)
     *  'local' => '',          		Local dropdown or DB associated (false by default > DB associated)
	 *	'options' => array(
	 *		'label' => array(			The label with the wrapper array will output a group of options named with it
	 *			array(
	 *				'value' => '',		Value for the option (otherwise, a default name is added)
	 *				'label' => '',		Label for the option
	 *				'selected' => ''	Option selected or not (not by default)
	 *			)
	 *		)
	 *	)
	 * )
	 */
    
    public function dropdown($params) {
        extract($params);
        $this->control_id++;
        
        // Setting the identifiers
        
        $identifier = (isset($id) ? 'form_' . $this->form_id . '_dropdown_' . $id : (isset($name) ? 'form_' . $this->form_id . '_dropdown_'.$name : 'form_' . $this->form_id . '_dropdown_'.$this->control_id));
        $identifier_short = (isset($id) ? 'dropdown_' . $id : (isset($name) ? 'dropdown_'.$name : 'dropdown_'.$this->control_id));
        $name = (isset($name) ? $name : 'field_'.$this->control_id);
        
        // Setting error if necessary
        
        if (array_key_exists($name,$this->errors)) {
            $control_error = ' control_error';
            $error_info = isset($error_message) && !empty($error_message) ? $error_message : $this->errors[$name]['error'];
        }
        else {
            $control_error = '';
            $error_info = '';
        }
        
        // Constructing the HTML
        
        $this->form.= $this->setTabs(2) . "<div class=\"form_control control_dropdown " . $identifier_short . $control_error . "\">" . $this->EOL();
        
        if (isset($label) && $label !== false) {
            $this->form.= $this->setTabs(2) . "<label for=\"" . $identifier . "\">" . $label;
            
            // Field details

	        if (isset($details) && !empty($details)) {
	            $this->form.= " <span class=\"control_details\">" . $details . "</span>";
	        }
            
            $this->form.= "</label>" . $this->EOL();
        }
        
        $this->form.= $this->setTabs(3) . "<select name=\"form_data[" . $name . "]\" id=\"" . $identifier . "\">" . $this->EOL();
        
        // For each options
        
        if (!empty($options)) {
            foreach($options as $key=>$option) {
                if (is_numeric($key)) {
                    if (is_object($this->entity) && (!isset($autofill) || $autofill === true)) {
                        $v = $this->entity->get($name);
            
			            if(isset($_POST['form_data'][$name])) {
			                 if ($option['value'] == $_POST['form_data'][$name]) {
                                $option['selected'] = true;
                             }
			            }
			            elseif (!empty($v)) {
			                if ($option['value'] == $v) {
                               $option['selected'] = true;
                            }
			            }
                    }
                
                    $this->form.= $this->setTabs(4) . "<option value=\"" . $option['value'] . "\"";
                    
                    if (isset($option['selected']) && $option['selected'] === true) {
                        $this->form.= " selected=\"selected\"";
                    }
                
                    $this->form.= ">" . $option['label'] . "</option>" . $this->EOL();
                }
                
                // Option group
                
                else {
                    $this->form.= $this->setTabs(4) . "<optgroup label=\"" . $key . "\">" . $this->EOL();
                    
                    foreach($options[$key] as $suboption) {
                        if (!isset($autofill) || $autofill === true) {
	                        $v = $this->entity->get($name);
	            
				            if(isset($_POST['form_data'][$name])) {
				                 if ($suboption['value'] == $_POST['form_data'][$name]) {
	                                $suboption['selected'] = true;
	                             }
				            }
				            elseif (!empty($v)) {
				                if ($suboption['value'] == $v) {
	                               $suboption['selected'] = true;
	                            }
				            }
	                    }
                        
                        $this->form.= $this->setTabs(5) . "<option value=\"" . $suboption['value'] . "\"";
                        
                        if (isset($suboption['selected']) && $suboption['selected'] === true) {
                            $this->form.= " selected=\"selected\"";
                        }
                        
                        $this->form.= ">" . $suboption['label'] . "</option>" . $this->EOL();
                    }
                    
                    $this->form.= $this->setTabs(4) . "</optgroup>" . $this->EOL();
                }
            }
        }
        
        $this->form.= $this->setTabs(3) . "</select>" . $this->EOL();
        
        $this->form.= $this->setTabs(2) . "</div>" . $this->EOL();
        
        $this->rules[$name] = array();
    }
    
    /*
	 * Creating buttons control
	 *
	 * @param array $params Parameters for the buttons
	 * 
	 * array(
	 * 	array(
	 *		'type' => '',		Button type (button by default)
	 *		'label' => '',		Button value
	 *		'id' => '',			ID to set on the button (no ID by default)
	 *      'class' => ''		Classe(s) to set on the button
	 *	)
	 * )
	 */
    
    public function buttons($params) {
        extract($params);
        
        $this->form.= $this->setTabs(2) . "<div class=\"form_control form_buttons\">" . $this->EOL();
        
        if (!empty($params)) {
            foreach($params as $button) {
                $this->form.= $this->setTabs(3) . "<button type=\"" . (isset($button['type']) ? $button['type'] : 'button') . "\"";
                
                if (isset($button['id']) && !empty($button['id'])) {
                    $this->form.= " id=\"" . $button['id'] . "\"";
                }
                
                if (isset($button['class']) && !empty($button['class'])) {
                    $this->form.= " class=\"" . $button['class'] . "\"";
                }
                
                $this->form.= ">" . $button['label'] . "</button>" . $this->EOL();
            }
        }
        
        $this->form.= $this->setTabs(2) . "</div>" . $this->EOL();
    }
    
    /*
	 * Closing the form and returning it
	 *
	 * @return html The generated HTML form
	 */
    
    public function close() {
        return array(
            'form' => $this->form.= $this->setTabs(1) . "</div>" . $this->EOL() . '</form>', 
            'rules' => $this->rules,
            'files' => $this->files
        );
    }
    
}
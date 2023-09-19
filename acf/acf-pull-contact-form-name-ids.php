<?
/**
 *
 * Pull Contact Form 6 form name and ID's into a select field called form_id dynamically.
 *
 **/
function populate_acf_field_with_form_data($field) {
	$field['choices'] = array();
    
	global $wpdb;
	$forms = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'wpcf7_contact_form'", ARRAY_A);	
	if ($forms) {
    	$choices = array();
        foreach ($forms as $form) {
        	$form_id = $form['ID'];
            $form_name = $form['post_title']." (".$form['ID'].")";
            $choices[$form_id] = $form_name;
        }

        // Populate the ACF field choices with the Ninja Form IDs and names.
        $field['choices'] = $choices;
    }
    return $field;
}

add_filter('acf/load_field/name=form_id', 'populate_acf_field_with_form_data');

<?php

function xmldb_geogebra_upgrade($oldversion = 0) {
    global $CFG;

    $result = true;
    
    if ($result && $oldversion < 2012030100) {
        //Add grade field
        $table = new XMLDBTable('geogebra');
        $field = new XMLDBField('grade');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, '100', 'showsubmit');
        $result = $result && add_field($table, $field);
        
        //Add autograde field
        $field = new XMLDBField('autograde');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, '0', 'grade');
        $result = $result && add_field($table, $field);
        
        //Delete maxgrade field
        $field = new XMLDBField('maxgrade');
        $result = $result && drop_field($table, $field);
        
        //Make maxattempts signed
        $field = new XMLDBField('maxattempts');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, null, null, null, '-1', 'autograde');
        $result = $result && change_field_unsigned($table, $field);
        
        //Add gradecomment field
        $table = new XMLDBTable('geogebra_attempts');
        $field = new XMLDBField('gradecomment');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null, 'vars');
        $result = $result && add_field($table, $field);
        

    }
    return $result;
    
}

?>

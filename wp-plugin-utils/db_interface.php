<?php

namespace BCF_PayPerPage;

class DatabaseInterfaceClass
{
    protected function __construct()
    {
    }

    protected function Read($table_name, $conditions)
    {
        global $wpdb;
        $record_list = null;

        $prefixed_table_name = $wpdb->prefix . $table_name;
        $condition_str = $this->FormatSqlCondition($conditions);

        $sql = 'SELECT * FROM ' . $prefixed_table_name;
        if ($condition_str) {
            $sql .= ' WHERE ' . $condition_str;
        }

        $record_list = $wpdb->get_results($sql, ARRAY_A);

        if ($wpdb->last_error) {
            $record_list = NULL;
        }

        return $record_list;
    }

    protected function ReadColumns($table_name, $field_name_list)
    {
        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . $table_name;

        $names = '';
        foreach ($field_name_list as $field_name) {
            if ($names != '') {
                $names .= ',';
            }
            $names .= $field_name;
        }

        $sql = 'SELECT ' . $names . ' FROM ' . $prefixed_table_name;

        $record_list = $wpdb->get_results($sql, ARRAY_A);

        if ($wpdb->last_error) {
            $record_list = NULL;
        }

        return $record_list;
    }

    protected function Write($table_name, $data_array)
    {
        $id_value = 0;
        global $wpdb;

        $prefixed_table_name = $wpdb->prefix . $table_name;

        if(!empty($data_array))
        {
            $wpdb->insert( $prefixed_table_name, $data_array );

            if ( ! $wpdb->last_error )
            {
                $id_value = $wpdb->insert_id;
            }
        }

        return $id_value;
    }


    protected function Update($table_name, $condition, $data)
    {
        global $wpdb;

        $prefixed_table_name = $wpdb->prefix . $table_name;

        $wpdb->update($prefixed_table_name, $data, $condition);

        if ($wpdb->last_error) {
            return false;
        } else {
            return true;
        }
    }

    protected function DeleteTableRecord($table_name, $condition)
    {
        global $wpdb;

        $prefixed_table_name = $wpdb->prefix . $table_name;

        return $wpdb->delete( $prefixed_table_name, $condition);
    }

    protected function CreateTable($table_name, $meta_data, $primary_key)
    {
        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . $table_name;

        $fields = '';
        foreach($meta_data as $field_name => $meta) {
            if ($fields != '') {
                $fields .= ',';
            }

            $fields .= '`' . $field_name . '` ' . $meta['db_type'];

            if ($field_name == $primary_key) {
                $fields .= ' AUTO_INCREMENT';
            }
        }

        if($primary_key) {
            $fields .= ',PRIMARY KEY (`' . $primary_key . '`)';
        }

        $sql = 'CREATE TABLE `' . $prefixed_table_name . '` (' . $fields . ')';

        $wpdb->get_results($sql);

        if ($wpdb->last_error) {
            return false;
        }else{
        return true;
        }
    }

    protected function CreateTableField($table_name, $field_name, $field_type, $default_data, $field_location=NULL)
    {
        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . $table_name;

        $sql = 'ALTER TABLE `' . $prefixed_table_name . '` ADD `' . $field_name . '` ' . $field_type;

        if($field_location) {
            $sql .= ' AFTER `' . $field_location . '`';
        }

        $wpdb->get_results($sql);

        if ($wpdb->last_error) {
            $record_list = NULL;
            return false;
        }

        return true;
    }

    protected function ChangeTableRenameField($table_name, $field_name, $new_field_name, $db_field_type)
    {
        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . $table_name;
        $sql = 'ALTER TABLE `' . $prefixed_table_name . '` CHANGE COLUMN `' . $field_name . '` `' . $new_field_name . '` ' . $db_field_type;

        $wpdb->get_results($sql);

        if ($wpdb->last_error) {
            return false;
        }else {
            return true;
        }
    }

    protected function DeleteTableField($table_name, $field_name)
    {
        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . $table_name;

        $sql = 'ALTER TABLE `' . $prefixed_table_name . '` DROP COLUMN `' . $field_name . '`';

        $wpdb->get_results($sql);

        if ($wpdb->last_error) {
            return false;
        }else {
            return true;
        }
    }

    protected function TableExist($table_name)
    {
        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . $table_name;

        $sql = 'SHOW TABLES';

        $table_list = $wpdb->get_results($sql);

        if ($wpdb->last_error) {
            $record_list = NULL;
        }

        foreach ($table_list as $table_entry)
        {
            foreach ($table_entry as $key => $table_name) {
                if ($table_name == $prefixed_table_name) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function GetTableDescription($table_name)
    {
        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . $table_name;

        $sql = 'DESCRIBE `' . $prefixed_table_name . '`';

        $description = $wpdb->get_results($sql, ARRAY_A);

        if ($wpdb->last_error) {
            $description = NULL;
        }

        return $description;
    }

    private function FormatSqlCondition($conditions)
    {
        $more_than_one_condition = false;
        $condition_str = '';

        if($conditions !== null) {
            foreach ($conditions as $condition) {
                $where_field = $condition['field'];
                $where_value = $condition['value'];
                if (isset($condition['comparator'])) {
                    $comparator = $condition['comparator'];
                } else {
                    $comparator = '=';
                }

                if ($more_than_one_condition) {
                    $condition_str .= ' AND ';
                }

                $condition_str .= "`" . $where_field . "`" . $comparator;

                switch (gettype($where_value)) {
                    case 'string':
                        $condition_str .= "'" . $where_value . "'";
                        break;
                    case 'integer':
                        $condition_str .= strval($where_value);
                        break;
                    default:
                        throw new \Exception('Unhandled type');
                        break;
                }

                $more_than_one_condition = true;
            }
        }else {
            $condition_str = null;
        }

        return $condition_str;
    }
}

<?php
/**
 * Generic base class for data records.
 * Original written to demonstrate the usage of Bitcoin Cheques.
 *
 * Copyright (C) 2016 Arild Hegvik and Bitcoin Cheque Foundation.
 *
 * GNU LESSER GENERAL PUBLIC LICENSE (GNU LGPLv3)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace BCF_PayPerPage;


class DataBaseClass
{
    public $DataObjects = array();

    public function __construct()
    {
        foreach($this->MetaData as $key => $attributes)
        {
            $default_value = $attributes['default_value'];
            $db_field_name = $attributes['db_field_name'];
            $primary_key   = $attributes['db_primary_key'];

            $class_name = '\\' . __NAMESPACE__ . '\\'. $attributes['class_type'];

            if(!class_exists($class_name))
            {
                die();
            }

            $new_object                = new $class_name($default_value, $primary_key);
            $this->DataObjects[ $key ] = $new_object;
        }
    }

    public function SanitizeData()
    {
        foreach($this->MetaData as $key => $attributes)
        {
            $data_object = $this->DataObjects[ $key ];

            if(gettype($data_object) != 'object')
            {
                return false;
            }

            $class_name = __NAMESPACE__ . '\\' . $attributes['class_type'];
            if(get_class($data_object) != $class_name)
            {
                return false;
            }
            if($data_object->Sanitize() == false)
            {
                return false;
            }
        }

        return true;
    }

    public function SanitizeKey($unknown_key)
    {
        if(gettype($unknown_key) == 'string')
        {
            foreach($this->MetaData as $key => $attributes)
            {
                if($unknown_key == $key)
                {
                    return true;
                }
            }
        }
        return false;
    }

    protected function GetDataObjects($key)
    {
        if($this->SanitizeKey($key))
        {
            return $this->DataObjects[ $key ];
        }
        else
        {
            die();
        }
    }

    protected function SetDataObject($key, $data_object)
    {
        if($this->SanitizeKey($key))
        {
            if(gettype($data_object) == 'object')
            {
                $this->DataObjects[ $key ] = $data_object;
                return true;
            }
        }
        die();
    }

    public function SetDataFromDbRecord($record)
    {
        $result = true;
        foreach($record as $key => $value)
        {

            if( ! array_key_exists($key, $this->MetaData))
            {
                error_log("Error SetDataFromDdRecord. No such key '" . $key . "' in MetaData for this class '" . get_class($this) . "'");

                return false;
            }

            $meta_attributes = $this->MetaData[ $key ];

            if( ! empty($meta_attributes))
            {
                $data_object = $this->DataObjects[ $key ];

                if($data_object->SetDataFromString($value) == false)
                {
                    error_log("Error SetDataFromDdRecord. " . $key . "=[" . $value . "]");
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    public function GetDbTableName()
    {
        $name = $this::DB_TABLE_NAME;
        return $name;
    }

    public function GetPrimaryKeyArray()
    {
        $arr = array();
        $control_primary_key_count = 0;

        foreach($this->MetaData as $key => $attributes)
        {
            $is_primary_key = $attributes['db_primary_key'];
            if ($is_primary_key==true)
            {
                $control_primary_key_count += 1;
                if($control_primary_key_count > 1)
                {
                    die();
                }

                $key_data_obj = $this->DataObjects[$key];
                $arr[$key] = $key_data_obj->GetInt();
            }
        }

        return $arr;
    }

    public function GetDataArray($public_data_only=false)
    {
        $data_array = array();

        foreach($this->MetaData as $key => $attributes)
        {
            $data_object = $this->DataObjects[$key];
            if($data_object->HasValidData())
            {
                if ($public_data_only)
                {
                    if($attributes['public_data'] == true)
                    {
                        $data_array[ $key ] = $data_object->GetString();
                    }
                }
                else
                {
                    $data_array[ $key ] = $data_object->GetString();
                }
            }
        }

        return $data_array;
    }

    public function GetCreateMysqlTableText()
    {
        $line_break = "\r\n";
        global $wpdb;
        $table_name = $wpdb->prefix . $this::DB_TABLE_NAME;

        $sql        = "CREATE TABLE `" . $table_name . "` ("  . $line_break;

        $sql_columns = "";
        foreach($this->MetaData as $key => $attributes)
        {
            $class = '\\' . __NAMESPACE__ . '\\' . $attributes['class_type'];
            $container = new $class(null);
            $type = $container->GetDataType();
            $mysql_type = $container->GetDataMySqlType();
            $minsize = $container->GetDataTypeMin();
            $maxsize = $container->GetDataTypeMax();

            if($sql_columns != "")
            {
                $sql_columns .= "," . $line_break;
            }

            $sql_columns .= "\t";

            if($type == 'integer')
            {
                $sql_columns .= "`" . $key . "` " . $mysql_type;

                if($minsize >= 0)
                {
                    $sql_columns .= " UNSIGNED";
                }

                $sql_columns .= " NOT NULL";
            }
            elseif($type == 'string')
            {
                $sql_columns .= "`". $key ."` " . $mysql_type;

                if($mysql_type == 'VARCHAR')
                {
                    $sql_columns .= "(". $maxsize .")";
                }
                $sql_columns .= " NULL DEFAULT NULL";
            }
            else
            {
                die();
            }

            if($attributes['db_primary_key'])
            {
                $sql_columns .= " AUTO_INCREMENT";
                $primary_key = $key;
            }

        }

        $sql .= $sql_columns;
        $sql .= "," . $line_break . "\tPRIMARY KEY (`" . $primary_key . "`)";
        $sql .= $line_break . ")"  . $line_break;
        $sql .= "COLLATE='latin1_swedish_ci'" . $line_break;
        $sql .= "ENGINE=InnoDB" . $line_break;
        $sql .= "AUTO_INCREMENT=3" . $line_break;
        $sql .= ";" . $line_break;

        return $sql;
    }
}

<?php
/**
 * Generic data collection base-class for holding database records.
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


class DataCollectionClass extends DatabaseInterfaceClass
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

    public function SetDataString($key, $str)
    {
        $result = false;

        if($this->SanitizeKey($key))
        {
            $meta_data = $this->MetaData[$key];

            $class_name = __NAMESPACE__ . '\\' . $meta_data['class_type'];

            $data_object = new $class_name(null);

            if($data_object)
            {
                if($data_object->SetDataFromString($str))
                {
                    $result = $this->SetData($key, $data_object);
                }
            }
        }

        return $result;
    }

    public function GetDataString($key)
    {
        $result = null;

        if($this->SanitizeKey($key))
        {
            $data_obj = $this->GetDataObjects($key);

            $result = $data_obj->GetString();
        }

        return $result;
    }

    public function SetDataInt($key, $value)
    {
        $result = false;

        if($this->SanitizeKey($key))
        {
            $meta_data = $this->MetaData[$key];

            $class_name = __NAMESPACE__ . '\\' . $meta_data['class_type'];

            $data_object = new $class_name(null);

            if($data_object)
            {
                if($data_object->SetDataFromInt($value))
                {
                    $result = $this->SetData($key, $data_object);
                }
            }
        }

        return $result;
    }

    public function AddDataInt($key, $factor)
    {
        $result = false;

        if($this->SanitizeKey($key))
        {
            $data_object = $this->GetDataObjects($key);

            if($data_object)
            {
                $value = $data_object->GetInt();

                $value = $value + $factor;

                if($data_object->SetDataFromInt($value))
                {
                    $result = $this->SetData($key, $data_object);
                }
            }
        }

        return $result;
    }

    public function GetDataInt($key)
    {
        $result = null;

        if($this->SanitizeKey($key))
        {
            $data_obj = $this->GetDataObjects($key);

            $result = $data_obj->GetInt();
        }

        return $result;
    }

    public function SetData($key, $data_object)
    {
        $result = false;

        if($this->SanitizeKey($key))
        {
            $meta_data = $this->MetaData[$key];

            if(gettype($data_object) == 'object')
            {
                if(get_class($data_object) == __NAMESPACE__ . '\\' . $meta_data['class_type'])
                {
                    if($data_object->Sanitize())
                    {
                        $result = $this->SetDataObject($key, $data_object);
                    }
                }
            }
        }

        return $result;
    }

    public function GetData($key)
    {
        return $this->GetDataObjects($key);
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

    public function GetPrimaryKeyName()
    {
        $primary_key_name = '';

        foreach($this->MetaData as $key => $attributes)
        {
            $is_primary_key = $attributes['db_primary_key'];
            if ($is_primary_key==true)
            {
                if($primary_key_name != '')
                {
                    /* Self-fest: Can only have one primary key */
                    die();
                }

                $primary_key_name = $key;
            }
        }

        return $primary_key_name;
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

    public function SaveData()
    {
        $id = -1;

        $table_name =  $this::DB_TABLE_NAME;
        $data_array = $this->GetDataArray();

        $primary_key_name = $this->GetPrimaryKeyName();
        $primary_key_obj = $this->GetData($primary_key_name);
        if($primary_key_obj->HasValidData())
        {
            /* Can only update a record if it exist in database. */
            $id = $this->UpdateRecord($table_name, $data_array, $primary_key_name);
        }
        else
        {
            /* Can only create a record if it has no primary key. */
            $id = $this->WriteRecord($table_name, $data_array);

            if($id > 0)
            {
                $this->SetDataInt($primary_key_name, $id);
            }

        }

        return $id;
    }

    public function LoadData($reg_id)
    {
        $result = false;
        $table_name =  $this::DB_TABLE_NAME;

        $primary_key = $this->GetPrimaryKeyName();

        $record_list = $this->DB_GetRecordListByFieldValue($table_name, $primary_key, $reg_id);

        if(count($record_list) == 1)
        {
            $result = $this->SetDataFromDbRecord($record_list[0]);
        }

        return $result;
    }

    public function CreateDatabaseTable()
    {
        $this->CreateOrUpdateDatabaseTable();
    }
}

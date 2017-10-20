<?php
/**
 * Generic data object relational manager class.
 *
 * Copyright (C) 2016 Arild Hegvik
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
 * along with this program.  if (not, see <http://www.gnu.org/licenses/>.
 */

namespace BCF_PayPerPage;

require_once ('db_interface.php');
require_once ('db_types.php');


class DataTableAbsClass extends DatabaseInterfaceClass
{
    const PRIMARY_KEY = 'id';

    private $DataObjects = array();
    private $TouchedData = array();

    public function __construct()
    {
        $this->ClearAllData();
        parent::__construct();
    }

    protected function GetMetaData($key)
    {
        if(array_key_exists($key, static::$MetaData)){
            return static::$MetaData[$key];
        } else {
            throw new \Exception('Error reading meta data type ' . $key);
        }
    }

    public function GetMetaDataList()
    {
        return static::$MetaData;
    }

    public function FieldNameExist($key)
    {
        if($key == static::PRIMARY_KEY){
            return true;
        }
        $metadata_list = $this->GetMetaDataList();
        return array_key_exists($key, $metadata_list);
    }

    private function CreateDataObject($metadata, $value=null)
    {
        $data_type = 'BCF_PayPerPage\\' . $metadata['data_type'];
        $data_object = new $data_type($metadata, $value);
        return $data_object;
    }

    protected function InitRecord($record, $metadata_list=null)
    {
        if($metadata_list == null){
            $metadata_list = $this->GetMetaDataList();
            $record[self::PRIMARY_KEY] = null;
        }

        foreach ($metadata_list as $meta_name => $metadata)
        {
            if($meta_name !== static::PRIMARY_KEY) {
                if(isset($metadata['default_value'])){
                    $default_value = $metadata['default_value'];
                } else {
                    $default_value = null;
                }
                $record[$meta_name] = $default_value;
            }else{
                throw new \Exception("Error. Field name '".self::PRIMARY_KEY."' is reserved.");
            }
        }
        return $record;
    }

    private function InitDataIfEmpty($index)
    {
        if (count($this->DataObjects) == 0)
        {
            $this->AddDataRecord();
        }
    }

    public function ClearData()
    {
        if (count($this->DataObjects) > 0)
        {
            array_shift($this->DataObjects);
            array_shift($this->TouchedData);
        }
    }

    public function ClearAllData()
    {
        $this->DataObjects = array();
        $this->TouchedData = array();
    }

    public function GetDataType($key)
    {
        if($key==static::PRIMARY_KEY){
            return 'integer';
        }

        $metadata = $this->GetMetaData($key);
        $data_type_class = 'BCF_PayPerPage\\'.$metadata['data_type'];

        $data_type = call_user_func(array($data_type_class, 'GetType'));
        if($data_type !== null){
            return $data_type;
        } else {
            throw new \Exception('Error reading meta data type.');
        }
    }

    public function CheckDataType($key, $value)
    {
        if ($this->FieldNameExist($key)) {
            $value_data_type = gettype($value);
            if($value_data_type === 'NULL'){
                // TODO Should implement check if null is allowed data type.
                return true;
            }
            $meta_data_type = $this->GetDataType($key);
            if(($meta_data_type == 'double') and ($value_data_type == 'integer')) {
                return true;
            }
            if ($value_data_type == $meta_data_type) {
                return True;
            } else {
                return false;
            }
        }
        else
        {
            throw new \Exception('Invalid key ' . $key);
        }
    }

    public function TouchData()
    {
        $this->TouchedData[0] = True;

    }

    public function TouchAllData()
    {
        for($index=0; $index< count($this->DataObjects); $index++) {
            $this->TouchedData[$index] = true;
        }
    }

    protected function AddDataRecord($data_list=null)
    {
        $new_data_record = array();
        $new_data_record = $this->InitRecord($new_data_record);

        $new_data_record = array($new_data_record);
        $this->DataObjects = array_merge($this->DataObjects, $new_data_record);
        $this->TouchedData[] = false;

        $index = count($this->DataObjects) - 1;

        if($data_list != null) {
            $this->SetDataRecord($index, $data_list);
        }

        return $index;
    }

    protected function SetDataRecord($index, $data_record)
    {
        foreach($data_record as $key => $value){
            $this->SetDataIndex($index, $key, $value);
        }
    }

    public function SetDataIndex($index, $key, $value)
    {
        $this->InitDataIfEmpty($index);
        if ($this->CheckDataType($key, $value))
        {
            $this->DataObjects[$index][$key] = $value;
            $this->TouchedData[$index] = true;
        }
        else
        {
            /* Create a data object and try set value using that object. Maybe it has conversation function */
            $metadata = $this->GetMetaData($key);
            $data_object = $this->CreateDataObject($metadata, $value);
            $data_object->SetValue($value);
            $normalized_value = $data_object->GetValue();
            $this->DataObjects[$index][$key] = $normalized_value;
            $this->TouchedData[$index] = true;
        }
    }

    public function SetData($key, $value)
    {
        $this->SetDataIndex(0, $key, $value);

    }

    public function SetAllData($key, $value)
    {
        for($index=0; $index< count($this->DataObjects); $index++) {
            $this->SetDataIndex(0, $key, $value);
        }
    }

    public function GetDataIndex($index, $key)
    {
        if ($this->FieldNameExist($key)) {
            return $this->DataObjects[$index][$key];
        }
        else
        {
            throw new \Exception( 'Invalid key ' . $key);
        }
    }

    public function GetData($key)
    {
        return $this->GetDataIndex(0, $key);
    }

    public function GetDataObjectIndex($index, $key)
    {
        if ($this->FieldNameExist($key)) {
            $value =  $this->DataObjects[$index][$key];
            $metadata = $this->GetMetaData($key);
            $data_object = $this->CreateDataObject($metadata, $value);
            return $data_object;
        }
        else
        {
            throw new \Exception( 'Invalid key ' . $key);
        }
    }

    public function SortData($key, $sort_order, $sort_flag)
    {
        if($this->GetDataCount() > 1) {
            if ($this->FieldNameExist($key)) {
                foreach ($this->DataObjects as $i => $row) {
                    $orders[$i] = $row[$key];
                }
            }

            array_multisort($orders, $sort_order, $sort_flag, $this->DataObjects);
        }
    }

    public function GetCopyAllData()
    {
        // TODO May have to clone this
        return $this->DataObjects;
    }

    public function GetDataCount()
    {
        return count($this->DataObjects);
    }

    public function FetchData()
    {
        if (count($this->DataObjects)) {
            array_shift($this->DataObjects[0]);
        }
    }
    public function LoadData($field, $value)
    {
        $conditions = array();
        $conditions[] = array(
            'field' => $field,
            'value' => $value
        );

        $this->ClearAllData();
        return $this->LoadMoreData($conditions);
    }

    public function LoadDataWithCondition($conditions)
    {
        $this->ClearAllData();
        return $this->LoadMoreData($conditions);
    }

    public function LoadMoreData($conditions)
    {
        foreach($conditions as $condition) {
            $where_field = $condition['field'];
            $where_value = $condition['value'];
            if (!$this->CheckDataType($where_field, $where_value)){
                throw new \Exception("Wrong data type for key='".$where_field."' value='".$where_value."'");
            }
        }

        $db_data_list = $this->Read(static::TABLE_NAME, $conditions);

        for ($i=0; $i < count($db_data_list); $i++)
        {
            $data_list = array();
            foreach ($db_data_list[$i] as $key => $value)
            {
                $data_list[$key] = $value;

                $normal_type = $this->GetDataType($key);
                $data_type = gettype($value);
                if($normal_type != $data_type)
                {
                    switch ($data_type)
                    {
                        case 'string':
                            switch ($normal_type)
                            {
                                case 'integer':
                                    $normal_value = intval($value);
                                    $data_list[$key] = $normal_value;
                                    break;

                                case 'double':
                                    $normal_value = floatval($value);
                                    $data_list[$key] = $normal_value;
                                    break;

                                default:
                                    throw new \Exception('Unhandled data type ' . $normal_type);
                            }
                            break;

                        case 'NULL':
                            $data_list[$key] = null;
                            break;

                        default:
                            throw new \Exception('Unhandled data type ' . $data_type);
                    }
                }
            }
            $this->AddDataRecord($data_list);
        }

        if(count($db_data_list) == 0){
            return null;
        } else {
            return count($this->DataObjects);
        }
    }

    public function LoadAllData()
    {
        $this->ClearAllData();
        $condition = null;
        $this->DataObjects = $this->Read(static::TABLE_NAME, $condition);

        for($index=0; $index< count($this->DataObjects); $index++) {
            $this->TouchedData[] = false;
        }

        return count($this->DataObjects);

    }

    public function LoadColumn($field_name_list)
    {
        $this->ClearAllData();
        $this->DataObjects = $this->ReadColumns(static::TABLE_NAME, $field_name_list);

        for($index=0; $index< count($this->DataObjects); $index++) {
            $this->TouchedData[] = false;
        }

        return count($this->DataObjects);
    }

    public function SaveDataIndex($index)
    {
        if ($this->TouchedData[$index]) {
            if ($this->DataObjects[$index][static::PRIMARY_KEY] == null) {
                $id = $this->Write(static::TABLE_NAME, $this->DataObjects[$index]);
                if ($id != null) {
                    $this->DataObjects[$index][static::PRIMARY_KEY] = $id;
                    return true;
                } else {
                    return false;
                }
            } else {
                $condition = [static::PRIMARY_KEY => strval($this->DataObjects[$index][static::PRIMARY_KEY])];
                return $this->Update(static::TABLE_NAME, $condition, $this->DataObjects[$index]);
            }
        }
    }

    public function SaveData()
    {
        return $this->SaveDataIndex(0);

    }

    public function SaveAllData()
    {
        $err = false;
        for($index=0; $index< count($this->DataObjects); $index++) {
            if ($this->SaveDataIndex($index) == false) {
                $err = true;
            }
        }

        if ($err) {
            return false;
        }
        else {
            return true;
        }
    }

    public function Delete()
    {
        if(count($this->DataObjects)) {
            if ($this->DataObjects[0][static::PRIMARY_KEY] != null) {
                $condition = [static::PRIMARY_KEY => $this->DataObjects[0][static::PRIMARY_KEY]];
                $data = $this->DeleteTableRecord(static::TABLE_NAME, $condition);
                $this->ClearData();
            }
        }
    }

    public function ChangeFieldNameIndex($index, $field_name, $new_field_name)
    {
        $this->DataObjects[$index][$new_field_name] = $this->DataObjects[$index][$field_name];
        unset($this->DataObjects[$index][$field_name]);
        $this->TouchedData[$index] = true;

    }

    public function ChangeFieldName($field_name, $new_field_name)
    {
        $this->ChangeFieldNameIndex(0, $field_name, $new_field_name);
    }

    public function ChangeAllFieldName($field_name, $new_field_name)
    {
        for($index=0; $index< count($this->DataObjects); $index++) {
            $this->ChangeFieldNameIndex($index, $field_name, $new_field_name);
        }
    }

    public function InitTable()
    {
        if ($this->TableExist(static::TABLE_NAME))
        {
            $description_list = $this->GetTableDescription(static::TABLE_NAME);
            $previous_meta_field_name = null;
            $metadata_list = $this->GetMetaDataList();
            foreach($metadata_list as $meta_field_name => $metadata) {
                $data_object = $this->CreateDataObject($metadata);
                $meta_field_type = $data_object->GetDatabaseType();
                $field_found = false;

                foreach ($description_list as $description) {
                    $description_field_name = $description['Field'];
                    $description_field_type = $description['Type'];

                    if ($meta_field_name == $description_field_name) {
                        $field_found = true;
                        if (strtolower($meta_field_type) != strtolower($description_field_type)) {
                            error_log('Warning: Changing table "' . static::TABLE_NAME . '" field "' . $meta_field_name . '" type from "' . strtoupper($description_field_type) . '" to "' . strtoupper($meta_field_type) . '"');

                            $now = current_time('timestamp', true);
                            $now_str = date('YmdHis', $now);

                            $temp_field_name = $meta_field_name . '_' . $now_str;

                            error_log('Create new temporary field "' . $temp_field_name . '"');
                            $this->CreateTableField(static::TABLE_NAME, $temp_field_name, $meta_field_type, $metadata['default_value'], $meta_field_name);

                            error_log('Convert old data from "' . $meta_field_name . '" to "' . $temp_field_name . '"...');
                            $this->LoadColumn([static::PRIMARY_KEY , $meta_field_name]);
                            $old_data = $this->GetCopyAllData();
                            $this->ChangeAllFieldName($meta_field_name, $temp_field_name);
                            $this->SaveAllData();

                            error_log('Compare data...');
                            $this->LoadColumn([static::PRIMARY_KEY , $temp_field_name]);
                            $converted_data = $this->GetCopyAllData();
                            $convert_error = false;
                            for ($i=0; $i < count($old_data); $i++) {
                                $old_data_str = strval($old_data[$i][$meta_field_name]);
                                $converted_data_str = strval($converted_data[$i][$temp_field_name]);
                                if ($old_data_str != $converted_data_str) {
                                    error_log('Error converting data index=' . strval($i) . ' Old value "' . $old_data_str . " new value '" . $converted_data_str . "'");
                                    $convert_error = true;
                                }
                            }

                            if ($convert_error) {
                                $now = current_time('timestamp', true);
                                $now_str = date('YmdHis', $now);
                                $old_data_field_name = $meta_field_name . '_old_' . $now_str;
                                $this->ChangeTableFieldName(static::TABLE_NAME, $meta_field_name, $old_data_field_name);
                                $this->ChangeTableFieldName(static::TABLE_NAME, $temp_field_name, $meta_field_name);

                                error_log('Error converting data in table "' . static::TABLE_NAME . '"');
                                error_log('Old data stored in field "' . $old_data_field_name . '"');

                                throw new \Exception('Error converting data. Please check table manually or restart.');
                            } else {
                                error_log('Data converted successfully.');
                                error_log('Delete old field "' . $meta_field_name . '"');
                                $this->DeleteTableField(static::TABLE_NAME, $meta_field_name);
                                error_log('Rename temporary field "' . $temp_field_name . '" to "' . $meta_field_name . '"');
                                $this->ChangeTableRenameField(static::TABLE_NAME, $temp_field_name, $meta_field_name, $meta_field_type);
                            }
                        }
                    }
                }

                if ($field_found == false) {
                    error_log('Warning: Changing table table table "' . static::TABLE_NAME . '" add field "' . $meta_field_name . '" with type "' . strtoupper($meta_field_type) . '"');
                    $default_value = $data_object->GetDefaultValue();
                    $this->CreateTableField(static::TABLE_NAME, $meta_field_name, $meta_field_type, $default_value, $previous_meta_field_name);
                }

                $previous_meta_field_name = $meta_field_name;

            }
        }
        else {
            $data_object = new DbTypeId;

            $db_metadata = array(
                static::PRIMARY_KEY => array(
                    'db_type'     => $data_object->GetDatabaseType(),
                    'default_value' => null
                )
            );

            $metadata_list = $this->GetMetaDataList();
            foreach($metadata_list as $field_name => $metadata) {
                $data_object = $this->CreateDataObject($metadata);

                $db_metadata[$field_name] = array(
                    'db_type'       => $data_object->GetDatabaseType(),
                    'default_value' => $data_object->GetDefaultValue()
                );
            }

            $this->CreateTable(static::TABLE_NAME, $db_metadata,self::PRIMARY_KEY);
        }
    }
}

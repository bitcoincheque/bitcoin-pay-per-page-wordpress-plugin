<?php

namespace BCF_PayPerPage;

class DbTypesBaseClass
{
    protected $size = null;
    protected $default_value = null;
    protected $db_field_type = null;
    protected $value = null;

    function __construct($metadata, $value=null)
    {
        if(isset($metadata)){
            if(gettype($metadata) != 'array'){
                throw new \Exception( "Error:Metadata is not array, it is '".gettype($metadata)."'");
            }
        }

        if(isset($metadata['db_field_type'])) {
            $this->db_field_type = $metadata['db_field_type'];
        } else {
            switch (static::$data_type)
            {
                case 'integer':
                    if(!isset($metadata['data_size'])) {
                        $metadata['data_size'] = 8;
                    }
                    $this->db_field_type = 'INT('.strval($metadata['data_size']).')';
                    break;

                case 'string':
                    if(!isset($metadata['data_size'])) {
                        $metadata['data_size'] = 16;
                    }
                    $this->db_field_type = 'VARCHAR('.strval($metadata['data_size']).')';
                    break;

                case 'double':
                    $this->db_field_type = 'DOUBLE';
                    break;

                default:
                    throw new \Exception( "Error: Missing metadata 'db_field_type' data type");
                    break;
            }
        }

        if(isset($metadata['data_size'])) {
            $this->size = $metadata['data_size'];
        }

        if(isset($metadata['default_value'])) {
            $this->default_value = $metadata['default_value'];
        }

        if(isset($value)){
            $this->value = $value;
        } else {
            $this->value = $this->default_value;
        }
    }

    static function GetType()
    {
        return static::$data_type;
    }

    public function GetDefaultValue()
    {
        return $this->default_value;
    }

    public function GetDatabaseType()
    {
        return $this->db_field_type;
    }

    public function GetValue()
    {
        return $this->value;
    }

    public function GetString()
    {
        return strval($this->value);
    }

    function GetFormatedText()
    {
        return $this->GetString();
    }
}

class DbTypeInteger extends DbTypesBaseClass
{
    static $data_type = 'integer' ;

    function __construct($metadata, $value=null)
    {
        parent::__construct($metadata, $value);
    }

    function GetFormatedText()
    {
        $s = number_format($this->value, 0, '', ',');
        return $s;
    }
}

class DbTypeUnsignedInteger extends DbTypesBaseClass
{
    static $data_type = 'integer';

    function __construct($metadata, $value=null)
    {
        if(!isset($metadata['data_size'])) {
            $metadata['data_size'] = 8;
        }

        if(!isset($metadata['db_field_type'])) {
            $metadata['db_field_type'] = 'INT('. $metadata['data_size'] . ') UNSIGNED';
        }

        parent::__construct($metadata, $value);
    }
}


class DbTypeId extends DbTypesBaseClass
{
    static $data_type = 'integer';

    function __construct($metadata=null, $value=null)
    {
        $metadata = array();
        $metadata['data_type'] = 'DbTypeId';
        $metadata['data_size'] = 8;
        $metadata['default_value'] = null;
        $metadata['db_field_type'] = 'INT(8) UNSIGNED';

        parent::__construct($metadata, $value);
    }
}

class DbTypeString extends DbTypesBaseClass
{
    static $data_type = 'string';

    function __construct($metadata, $value=null)
    {
        if(!isset($metadata['data_size'])) {
            $metadata['data_size'] = 64;
        }

        if(!isset($metadata['db_field_type'])) {
            $metadata['db_field_type'] = 'VARCHAR('. $metadata['data_size'] . ')';
        }

        parent::__construct($metadata, $value);
    }
}

class DbTypeDouble extends DbTypesBaseClass
{
    static $data_type = 'double';
}

class DbTypeCurrency extends DbTypesBaseClass
{
    static $data_type = 'double';

    function __construct($metadata, $value=null)
    {
        if(!isset($metadata['data_size'])) {
            $metadata['data_size'] = 12;
        }
        if(!isset($metadata['db_field_type'])) {
            $metadata['db_field_type'] = 'DECIMAL(10,2)';
        }

        parent::__construct($metadata, $value);
    }

    function GetFormatedText()
    {
        $s = number_format($this->value, 2, '.', ',');
        return $s;
    }
}

class DbTypePercent extends DbTypesBaseClass
{
    static $data_type = 'double';

    function GetFormatedText($decimals=1, $decimal_point='.')
    {
        $s = number_format($this->value * 100.0, $decimals, $decimal_point, '') . '%';
        return $s;
    }
}

class DbTypeTimeStamp extends DbTypesBaseClass
{
    static $data_type = 'string';

    function __construct($metadata=null, $value=null)
    {
        if(!isset($metadata['db_field_type'])) {
            $metadata['db_field_type'] = 'TIMESTAMP';
        }

        if(isset($metadata['default_value']))
        {
            switch ($metadata['default_value'])
            {
                case 'integer':
                    if ($this->default_value >= 0)
                    {
                        $metadata['default_value'] = date('Y-m-d H:i:s', $this->default_value);
                    }
                    else
                    {
                        throw new \Exception('Invalid default value ' . $value);
                    }
                    break;

                default:
                    throw new \Exception('Unsupported data type for default value ' . gettype($value));
                    break;
            }
        }

        parent::__construct($metadata, $value);
    }

    function SetValue($value)
    {
        switch (gettype($value))
        {
            case 'integer':
                $this->value = date('Y-m-d H:i:s', $value);
                break;

            default:
                throw new \Exception( 'Unsupported data type ' . gettype($value));
                break;
        }
    }
}
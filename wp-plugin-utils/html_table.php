<?php


class HtmlElementClass
{
    private $value;
    private $id = null;
    private $class = null;
    private $style = null;

    function __construct($value=null, $id=null, $class=null, $style=null)
    {
        $this->value = $value;
        $this->SetId($id);
        $this->SetClass($class);
        $this->SetStyle($style);
    }

    public function SetValue($value)
    {
        $this->value = $value;
    }

    public function GetValue()
    {
        return $this->value;
    }

    public function GetFormatedText()
    {
        $s = '';
        $t = gettype($this->value);
        switch($t){
            case 'string':
                $s = $this->value;
                break;
            case 'integer':
                $s = strval($this->value);
                break;
            case 'double':
                $s = strval($this->value);
                break;
            case 'object':
                $s = $this->value->GetFormatedText();
                break;
            case 'NULL':
                break;
            default:
                throw new \Exception( 'Unhandled data type ' . gettype($t));
                break;
        }
        return $s;
    }

    public function SetId($id)
    {
        $this->id = $id;
    }

    public function GetId()
    {
        return $this->id;
    }

    public function SetClass($class)
    {
        if($class) {
            $this->class = array();
            $this->AddClass($class);
        }
    }

    public function AddClass($class)
    {
        if($this->class === null){
            $this->class = array();
        }
        $t = gettype($class);
        switch($t){
            case 'string':
                $this->class[] = $class;
                break;

            case 'array':
                $this->class = array_merge($this->class, $class);
                break;

            default:
                throw new \Exception( 'Unhandled data type ' . gettype($t));
                break;
        }
    }

    public function GetClass()
    {
        return $this->class;
    }

    public function SetStyle($class)
    {
        if($class) {
            $this->style = array();
            $this->AddClass($this->style);
        }
    }

    public function AddStyle($style)
    {
        if($this->style === null){
            $this->style = array();
        }
        $this->style[] = $style;
    }

    public function GetStyle()
    {
        return $this->style;
    }

    public function GetElement($tag_name, $add_content=false, $add_end_tag=false)
    {
        $id = $this->GetId();
        $class = $this->GetClass();
        $style = $this->GetStyle();

        $s = '<' . $tag_name;
        if($id){
            $s .= ' id="' . $id . '"';
        }
        if($class){
            $s .= ' class="' . implode(' ', $class) . '"';
        }
        if($style){
            $s .= ' style="' . implode(' ', $style) . '"';
        }
        $s .= '>';

        if($add_content) {
            $s .= $this->GetFormatedText();
        }

        if($add_end_tag) {
            $s .= '</' . $tag_name . '>';
        }

        return $s;
    }
}

class CurrencyElement extends HtmlElementClass
{
    function __construct($value, $id=null, $class=null, $style=null)
    {
        parent::__construct($value);
        $this->AddClass('currency');
    }
}



Class TableRowClass
{
    private $CellItems;
    private $RowLink;

    public function __construct($cell_items)
    {
        $this->CellItems = $cell_items;
    }

    public function GetCellItems()
    {
        return $this->CellItems;
    }

    public function GetRowLink()
    {
        return $this->RowLink;
    }
}

Class TableCellClass
{
    private $Text;
    private $Link;
    private $Style;
    private $Class;

    public function __construct($text, $link, $style, $class)
    {
        $this->Text = $text;
        $this->Link = $link;
        $this->Style = $style;
        $this->Class = $class;
    }

    public function GetText()
    {
        return $this->Text;
    }

    public function GetLink()
    {
        return $this->Link;
    }

    public function GetStyle()
    {
        return $this->Style;
    }

    public function GetClass()
    {
        return $this->Class;
    }
}

class HtmlTableClass extends HtmlElementClass
{
    private $table_rows = array();
    private $current_line = array();
    private $has_header = false;

    public function EnableHeader(){
        $this->has_header = true;
    }

    public function GetHtmlTable()
    {
        $html = $this->GetElement('table');

        $table_array = $this->GetValue();
        for ($y=0; $y < count($table_array); $y++) {
            $table_row = $table_array[$y];

            if($y==0) {
                if ($this->has_header) {
                    $html .= '<thead>';
                } else {
                    $html .= '<tbody>';
                }
            }

            $html .= '<tr>';
            for ($x=0; $x < count($table_row); $x++) {
                $table_cell = $table_row[$x];

                if(gettype($table_cell) != 'object') {
                    $table_cell = new HtmlElementClass($table_cell);
                }

                if($y==0 and $this->has_header){
                    $html .= $table_cell->GetElement('th', true, true);
                }else{
                    $html .= $table_cell->GetElement('td', true, true);
                }
            }

            if($y==0) {
                if ($this->has_header) {
                    $html .= '</thead>';
                }
            }

            $html .= '</tr>';

            if($y==0) {
                if ($this->has_header) {
                    $html .= '<tbody>';
                }
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
}
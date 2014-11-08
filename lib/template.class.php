<?php
/**
 * Class Template v1
 * support mysql
 */
# TODO : PHP template convert
# TODO : cache create

class Template {

    private $Template;
    private $Import;
    private $Block;
    private $Vars;
    private $outputVars;
    private $TemplateList;
    private $left_delimiter;
    private $right_delimiter;
    private $html_Encoding;
    private $system_Encoding;
    private $default_modifiers;
    private $base_filename;

    static private $Functions = array();

    function  __construct()
    {
        $this->resetTemplateData();
        $this->clear_all_assign();
        $this->html_Encoding = mb_internal_encoding();
        $this->system_Encoding = mb_internal_encoding();
        $this->default_modifiers = "htmlentities";
        $this->setDelimiter("{","}");
    }

    /**
     * @param $path
     * @return base_path
     */
    public function setBasePath($path){
        return ($this->base_filename = $path);
    }

    /**
     * @param $start
     * @param $end
     */
    public function setDelimiter($start,$end){
        $this->left_delimiter = $start;
        $this->right_delimiter = $end;
    }

    /**
     * set html encoding
     * @param string $encode encodetype(default internal encoding)
     * @return encodetype
     */
    public function setHtmlEncoding($encode)
    {
        return ($this->html_Encoding = $encode);
    }

    /**
     * set php encoding
     * @param string $encode encodetype(default internal encoding)
     * @return encodetype
     */
    public function setSystemEncoding($encode)
    {
        return ($this->system_Encoding = $encode);
    }

    /**
     * get set template variable
     * set $name is namespace for set variable
     * @param object $name
     * @return value
     */
    public function get_template_var($name = null)
    {
        if (isset($name)) {
            return $this->Vars[$name];
        }
        return $this->Vars;
    }

    /**
     *
     */
    private function resetTemplateData(){
        $this->Template = "";
        $this->TemplateList = array();
        $this->Import = array();
        $this->Block = array();
        $this->base_filename = "";
    }
    /**
     * set load html file
     * @param bool $filename (path)
     * @param bool $path_set
     * @return bool
     */
    public function load($filename,$path_set=true)
    {
        $this->resetTemplateData();
        // load tempate file
        if (file_exists($filename) && $fp = @fopen($filename, 'rb')) {
            $this->Template = "";
            while (!feof($fp)) {
                $this->Template .= fread($fp, 1024);
            }
            fclose($fp);

            // set path
            if($path_set){
                $this->base_filename = dirname($filename);
                $this->base_filename .= ($this->base_filename != "") ? "/" : "";
            }
            return true;
        }else{
            throw new Exception("Template : Load Template Error.(".$filename.")");
        }
        return false;
    }

    /**
     * @param $str
     * @return $str
     */
    public function setTemplateStr($str){
        return ($this->Template = $str);
    }

    /**
     * set template and get html for string
     * @param bool $set
     * @return string
     */
    public function get_display_template($set = true)
    {
        $this->clearOutputVars();
        $template = $this->Template;
        if ($set) {
            // import setting
            $template = $this->setImportTemplate($template);
            // strip setting
            $template = $this->setStripTemplate($template);
            // set template vars
            $template = $this->setTemplatesVars($template);
        }
        return $template;
    }

    /**
     *
     */
    private function clearOutputVars(){
        $this->outputVars = array();
    }

    /**
     * set template and get html for string and print
     * @see get_display_template
     * @param bool $set
     * @return null
     */
    public function display($set = true)
    {
        print $this->get_display_template($set);
    }

    /**
     * set template include file
     * @param  string $filename
     * @return $template
     */
    private function loadTemplatePartsFile($filename)
    {
        $file = $this->base_filename.$filename;
        if (file_exists($file) && $fp = @fopen($file, 'rb')) {
            $template = "";
            while (!feof($fp)) {
                $template .= fread($fp, 1024);
            }
            fclose($fp);

            return $template;
        }else{
            throw new Exception('file not load '.$file);
        }
        return false;
    }

    /**
     * set template variable
     * @param string $id (namespace)
     * @return null
     */
    public function assign($id, $val)
    {
        $this->Vars[$id] = $val;
    }

    /**
     * set template variable
     * @see assign
     * @param array $value
     * @return null
     */
    public function assign_vars($value)
    {
        foreach ($value as $key => $val) {
            $this->assign($key, $val);
        }
    }

    /**
     * clear template variable
     * @return null
     */
    public function clear_all_assign()
    {
        $this->Vars = array();
    }

    /**
     * @param $id
     * @param $val
     */
    static public function filter($id, $val)
    {
        self::$Functions[$id] = $val;
    }

    /**
     *
     */
    static public function clear_all_filter(){
        self::$Functions = array();
    }

    /**
     * get attribe
     * @return srting
     * @access private
     */
    public function getAttr(&$attr, $str)
    {
        $attr = array();
        // key=value convert
        $preg_str = "/(\S+)=(\S+)/";
        if (preg_match_all($preg_str, $str, $matchs)) {
            $cnt = count($matchs[0]);
            for ($key = 0; $key < $cnt; $key++) {
                $name = strtoupper($matchs[1][$key]);
                $val = $matchs[2][$key];
                $val = $this->convertString($val);
                $attr[$name] = $val;
            }
        }
        return $attr;
    }

    /**
     * set template conditional expression
     * @return srting
     * @access private
     */
    public function evaString($str)
    {
        $str = trim($str);
        // 条件式を取得
        $preg_str = "/^(\S+?)\s*?([\!\<\>\+\-\*\/%=]+)\s*?(\S+)$/";
        if (preg_match($preg_str, $str, $tp)) {
            $i1 = $this->convertString($tp[1]);
            $is = $tp[2];
            $i2 = $this->convertString($tp[3]);
            //if(!is_null($i1) && !is_null($i2)){
            switch ((string)$is) {
                case '===':
                    $item = ($i1 === $i2);
                    break;
                case '==':
                    $item = ($i1 == $i2);
                    break;
                case '<=':
                    $item = ($i1 <= $i2);
                    break;
                case '>=':
                    $item = ($i1 >= $i2);
                    break;
                case '<':
                    $item = ($i1 < $i2);
                    break;
                case '>':
                    $item = ($i1 > $i2);
                    break;
                case '!=':
                    $item = ($i1 != $i2);
                    break;
                case '+':
                    $item = ($i1 + $i2);
                    break;
                case '-':
                    $item = ($i1 - $i2);
                    break;
                case '*':
                    $item = ($i1 * $i2);
                    break;
                case '/':
                    $item = ($i1 / $i2);
                    break;
                case '%':
                    $item = ($i1 % $i2);
                    break;
            }
            //}
        } else {
            $item = $this->convertString($str);
        }
        return $item;
    }

    /**
     * @param $str
     */
    private function convertStringParam($str,&$output=null)
    {
        if(!$str || !is_string($str)){
            return array($str);
        }
        $exparam = explode(",",$str);
        $param = array();
        foreach($exparam as $k => $v){
            if(count($param) > 0){
                if(preg_match('/\(/',$param[count($param)-1]) && !preg_match('/\)$/',$param[count($param)-1])){
                    $param[count($param)-1] .= ",".$v;
                }else{
                    $param[] = $v;
                }
            }else{
                $param[] = $v;
            }
        }
        $oparam = array();
        foreach($param as $k => $v){
            $out = false;
            $param[$k] = $this->convertString($v, false, false, $out);
            $oparam[$k] = $out;
        }
        if($output !== null){
            $output = $oparam;
        }
        return $param;
    }

    /**
     * @param $func
     * @param $val
     * @return bool
     * @throws Exception
     */
    private function convertStringFunction($func,&$val)
    {
        $oparam = false;
        $param = $this->convertStringParam($val,$oparam);
        $check = false;
        if(isset(self::$Functions[$func])){
            if ( is_callable( self::$Functions[$func] ) ) {
                try{
                    $val = call_user_func_array(self::$Functions[$func],$param);
                }catch (Exception $e){
                    throw new Exception('Template : Error Functions '.$func.'('.$val.') ');
                }
            }else{
                throw new Exception('Template : Error Functions '.$func.'('.$val.') ');
            }
        }else{
            switch ($func) {
                case 'is_array':
                    $val = is_array($param[0]);
                    break;
                case 'is_numeric':
                    $val = is_numeric($param[0]);
                    break;
                case 'is_string':
                    $val = is_string($param[0]);
                    break;
                case 'upper':
                    $val = strtoupper($param[0]);
                    break;
                case 'lower':
                    $val = strtolower($param[0]);
                    break;
                case 'ucfirst':
                    $val = ucfirst($param[0]);
                    break;
                case 'lcfirst':
                    $val = lcfirst($param[0]);
                    break;
                // escape
                case 'escape':
                    if(is_string($param[0])){
                        $val = htmlspecialchars($param[0], ENT_QUOTES, $this->system_Encoding);
                    }
                    break;
                case 'htmlentities':
                    if(is_string($param[0])){
                        $val = htmlentities($param[0], ENT_COMPAT, $this->system_Encoding);
                    }
                    break;
                case 'htmlspecialchars':
                    if(is_string($param[0])){
                        $val = htmlspecialchars($param[0], ENT_COMPAT, $this->system_Encoding);
                    }
                    break;
                case 'escape_br':
                case 'nl2br':
                    if(is_string($param[0])){
                        $val = htmlentities($param[0], ENT_COMPAT, $this->system_Encoding);
                        $val = nl2br($val);
                    }
                    break;
                case 'print_r':
                    $val = print_r($param[0], true);
                    break;
                case 'dump':
                    ob_start();
                    var_dump($param[0]);
                    $val=ob_get_contents();
                    ob_end_clean();
                    break;
                case 'quotes':
                    if(is_string($param[0])){
                        $val = preg_replace("/\"/", "\\\"", $param[0]);
                    }
                    break;
                case 'urlencode':
                    if(is_string($param[0])){
                        $val = urlencode($param[0]);
                    }
                    break;
                // format
                case 'number_format':
                    $val = number_format($param[0]);
                    break;
                case 'count':
                    $val = count($param[0]);
                    break;
                case 'set':
                    list($k,$v) = explode(",",$param[0]);
                    $this->assign($k,$v);
                    $val = "";
                    break;
                case 'e':
                    $val = __($param[0]);
                    break;
                case 'nofilter':
                    $val = $param[0];
                    break;
                case 'rest':
                    $tmp = $param[0];
                    $output = $oparam[0];
                    $val = "";
                    if(is_array($tmp)){
                        foreach($tmp as $k => $v){
                            if(!(isset($output[$k]) && ($output[$k]))){
                                if(!is_array($v)){
                                    $val .= $v;
                                }
                            }
                        }
                    }else{
                        if(!$output){
                            $val = $tmp;
                        }
                    }
                    break;
                default:
                    $val = $param[0];
                    throw new Exception('Template : Error Functions '.$func.'('.$val.') ');
                    $check = false;
                    break;
            }
        }
        return $check;
    }

    /**
     * @param $str
     * @return bool
     */
    private function issetConvertString($str){
        // string
        if (preg_match("/^\"([\s\S]*)\"$/", $str, $matchs)) {
            return true;
        } elseif (preg_match("/^'([\s\S]*)'$/", $str, $matchs)) {
            return true;
            // array
        } elseif (preg_match("/^\\\$([\[\]_a-zA-Z0-9\.\\\$]+)$/", $str, $matchs)) {
            // , explode
            $vlist = explode(".", $matchs[1]);
            foreach ($vlist as $v) {
                // array inside
                if (preg_match("/^([_a-zA-Z0-9]+)(\[([\\\$_a-zA-Z0-9]+)\])?$/", $v, $m)) {
                    $key = $m[1];
                    if (isset($value)) {
                        if (!isset($value[$key])) {
                            return false;
                        } else {
                            $value = $value[$key];
                        }
                    } else {
                        if(!isset($this->Vars[$key])){
                            return false;
                        }
                        $value = $this->Vars[$key];
                    }
                    if (isset($m[3])) {
                        $key = $this->convertString($m[3]);
                        if(!isset($value[$key])){
                            return false;
                        }
                        $value = $value[$key];
                    }
                }
            }
            return true;
        } elseif (is_numeric($str)) {
            return true;
        } elseif (strtoupper($str) == "TRUE") {
            return true;
        } elseif (strtoupper($str) == "FALSE") {
            return true;
        } elseif (strtoupper($str) == "NULL") {
            return false;
        }
        return true;
    }

    /**
     * set template function
     * @return srting
     * @access private
     */
    private function convertString($str, $encode = true, $filter = true, &$output=null)
    {
        // function
        $check = true;
        if (preg_match("/^([a-zA-Z0-9_]+)\((.*)\)$/", $str, $matchs)) {
            $out = false;
            $func = $matchs[1];
            $arg = $matchs[2];
            if($func == "isset"){
                $check = false;
                $str = $this->issetConvertString($arg);
            }else{
                $val = $arg;
                //$val = $this->convertString($arg, false, false , $out);
                $check = $this->convertStringFunction($func,$val);
                $str = $val;
            }
        }
        $result = $str;
        $result_check = false;
        if ($check) {
            // string
            if (preg_match("/^\"([\s\S]*)\"$/", $str, $matchs)) {
                $result = (string)$matchs[1];
                $result_check = true;
            } elseif (preg_match("/^'([\s\S]*)'$/", $str, $matchs)) {
                $result = (string)$matchs[1];
                $result_check = true;
                // array
            } elseif (preg_match("/^\\\$([\[\]_a-zA-Z0-9\.\\\$]+)$/", $str, $matchs)) {
                // , explode
                $vlist = explode(".", $matchs[1]);
                $tmp_output = null;
                foreach ($vlist as $v) {
                    // array inside
                    if (preg_match("/^([_a-zA-Z0-9]+)(\[([\\\$_a-zA-Z0-9]+)\])?$/", $v, $m)) {
                        $key = $m[1];
                        if (isset($value)) {
                            if (!isset($value[$key])) {
                                if (!array_key_exists($key, $value)) {
                                    trigger_error("template : not found [" . $matchs[1] . "] value;", E_USER_WARNING);
                                }
                                $value = NULL;
                            } else {
                                //$tmp_output[$key] = (is_array($value[$key])) ? array() : true;
                                if(!isset($tmp_output[$key])){
                                    $tmp_output[$key] = (is_array($value[$key])) ? array() : true;
                                }
                                if($output !== null){
                                    $output = $tmp_output[$key];
                                }
                                $value = $value[$key];
                                $tmp_output = &$tmp_output[$key];
                            }
                        } else {
                            if (isset($this->Vars[$key])) {
                                $value = $this->Vars[$key];
                            }else{
                                trigger_error("template : not found value ".$str." in [" . $key . "] value;", E_USER_WARNING);
                            }
                            if(isset($this->Vars[$key])){
                                if(!isset($this->outputVars[$key])){
                                    $this->outputVars[$key] = (is_array($this->Vars[$key])) ? array() : true;
                                }
                                $tmp_output = &$this->outputVars[$key];
                                if($output !== null){
                                    $output = $this->outputVars[$key];
                                }
                            }
                        }
                        if (isset($m[3])) {
                            $key = $this->convertString($m[3]);
                            $value = $value[$key];
                        }
                    }
                }
                if($filter && $this->default_modifiers != ""){
                    $this->convertStringFunction($this->default_modifiers,$value);
                }
                $result = $value;
                $result_check = true;
            } elseif (is_numeric($str)) {
                $result = intval($str);
                $result_check = true;
            } elseif (strtoupper($str) == "TRUE") {
                $result = true;
                $result_check = true;
            } elseif (strtoupper($str) == "FALSE") {
                $result = false;
                $result_check = true;
            } elseif (strtoupper($str) == "NULL") {
                $result = NULL;
                $result_check = true;
            }
        }
        if($result_check){
            if (is_string($result) && $encode && ($this->html_Encoding != $this->system_Encoding)) {
                $result = mb_convert_encoding($result, $this->html_Encoding, $this->system_Encoding);
            }
        }else if($check){
            //$result = $this->left_delimiter.$result.$this->right_delimiter;
        }
        return $result;
        //return $this->left_delimiter.$str.$this->right_delimiter;
    }

    /**
     * set template include
     * @return srting $template
     * @access private
     */
    public function setImportTemplate($template)
    {
        // load extend file
        $template = $this->_setExtendTemplate($template);
        // replace block
        $template = $this->_setBlockTemplate($template);
        // load include file
        $preg_str = "/" . preg_quote($this->left_delimiter, "/") . "IMPORT\s+(.+?)" . preg_quote($this->right_delimiter, "/") . "/i";
        $template = preg_replace_callback($preg_str, array($this, '_setImportCallback'), $template);

        return $template;
    }

    /**
     * @param $template
     * @return mixed
     */
    private function _setExtendTemplate($template)
    {
        $preg_str = "/^([\s\S]*?" . preg_quote($this->left_delimiter, "/") . "EXTEND\s+(.+?)" . preg_quote($this->right_delimiter, "/") . "[\s\S]*?)$/i";
        $tmp = preg_replace_callback($preg_str, array($this, '_setExtendCallback'), $template);

        // load block file
        $tmp2 = $this->_setBlockData($template);
        if(!preg_match($preg_str,$template)){
            $tmp = $tmp2;
        }
        return $tmp;
    }

    /**
     * @param $args
     */
    private function throwException($args){
    }

    /**
     * @param $args
     * @return string
     */
    private function _setExtendCallback($args)
    {
        $tmp_base = $args[1];

        $var = $args[2];
        // 属性値を取得
        $attr = $this->getAttr($attr, $var);
        // 属性から値設定
        $tmp = "";
        foreach ($attr as $name => $val) {
            switch ($name) {
                case "FILE":
                    if(!$tmp = $this->loadTemplatePartsFile($val)){
                        $this->throwException($args);
                    }
                    // extend
                    $tmp = $this->_setExtendTemplate($tmp);
                    break;
            }
        }

        return $tmp;
    }

    /**
     * @param $template
     */
    private function _setBlockData($template){

        $preg_str = "/" . preg_quote($this->left_delimiter, "/") . "(\/?block([\s]+[^\s\/]+)?)" . preg_quote($this->right_delimiter, "/") . "/i";
        // explode
        $matchs = preg_split($preg_str, $template, 0, PREG_SPLIT_DELIM_CAPTURE);
        //$this->TemplateList = $matchs;
        $cnt = count($matchs);
        // テンプレートを評価
        $tmp = $matchs[0];

        $t = "";
        $level = 0;
        for ($key = 1; $key < $cnt;) {
            $tmp .= $this->_setBlockTags(false, $t, $key, $matchs, $level);
        }
        if($level != 0){
            throw new Exception("Template Error Block");
        }
        return $tmp;
    }

    /**
     * @param $template
     * @return mixed
     */
    private function setStripTemplate($template){

        $tag_id = "strip";
        $preg_str = "/" . preg_quote($this->left_delimiter, "/") . "(\/?" . $tag_id . ")" . preg_quote($this->right_delimiter, "/") . "/";
        // 文字列の分割
        $matchs = preg_split($preg_str, $template, 0, PREG_SPLIT_DELIM_CAPTURE);

        $tpl = "";
        $cnt = count($matchs);
        $lebel = 0;
        for($i=0;$i<$cnt;$i+=2){
            if($lebel > 0){
                $t = preg_replace_callback(
                    "/(" . preg_quote($this->left_delimiter, "/") . "\/?[^" . preg_quote($this->right_delimiter, "/") . "]+" . preg_quote($this->right_delimiter, "/") . ")".
                    "|".
                    "([^" . preg_quote($this->left_delimiter, "/") . "]+)/",
                    function($mt){
                        if(isset($mt[2])){
                            $t = $mt[2];
                            $t = preg_replace("/[\r\n\t]/","",$t);
                            $t = preg_replace("/(\s){2,}/","$1",$t);
                            return $t;
                        }
                        return $mt[1];
                    },
                    $matchs[$i]);
                $tpl .= $t;
            }else{
                $tpl .= $matchs[$i];
            }
            if(($i + 1) >= $cnt){
                continue;
            }
            if($matchs[$i + 1] == $tag_id){
                $lebel ++;
            }else if($matchs[$i + 1] == "/".$tag_id){
                $lebel = max($lebel - 1,0);
            }
        }
        return $tpl;
    }

    /**
     * @param $block_key
     * @param $tmp
     * @param $key
     * @param $list
     * @param $level
     * @return string
     */
    private function _setBlockTags($block_key, $tmp, &$key, &$list, &$level)
    {
        if(count($list) <= $key){
            return "";
        }
        $type = $list[$key];
        $template = "";
        if(preg_match("/^block\s+(.+)$/i",$type,$matchs)){
            $block_key = trim($matchs[1]);
            $block_tmp = $list[$key + 2];
            $c_level = $level + 1;
            $key += 3;

            $template .= $this->left_delimiter.$type.$this->right_delimiter;
            while($key < count($list)){
                if($c_level == $level){
                    break;
                }
                $block_tmp .= $this->_setBlockTags($block_key,$block_tmp,$key,$list,$c_level);
            }
        }else if($type == "/block"){
            if($block_key != ""){
                $this->Block[$block_key] = $tmp;
            }
            $level --;
            $key ++;
        }else{
            $template .= $type;
            $key ++;
        }
        return $template;
    }
    /**
     * @param $template
     * @return string
     */
    private function _setBlockTemplate($template){
        $preg_str = "/" .preg_quote($this->left_delimiter, "/")."BLOCK[\s]+(.+?)" . preg_quote($this->right_delimiter, "/")."/i";
        return preg_replace_callback($preg_str, array($this, '_setBlockCallback'), $template);
    }

    /**
     * @param $args
     * @return string
     */
    private function _setBlockCallback($args)
    {
        $var = $args[1];
        if(isset($this->Block[$var])){
            return $this->_setBlockTemplate($this->Block[$var]);
        }
        return "";
    }

    /**
     * @param $args
     * @return string
     */
    private function _setImportCallback($args)
    {
        $var = $args[1];
        // 属性値を取得
        $attr = $this->getAttr($attr, $var);
        // 属性から値設定
        $tmp = "";
        foreach ($attr as $name => $val) {
            switch ($name) {
                case "FILE":
                    if(!$tmp = $this->loadTemplatePartsFile($val)){
                        $this->throwException($args);
                    }
                    break;
            }
        }
        return $tmp;
    }

    /**
     * set template value
     * @return srting $template
     * @access private
     */
    public function setTemplatesVars($template)
    {
        $preg_str = "/" . preg_quote($this->left_delimiter, "/") . "(.+?)" . preg_quote($this->right_delimiter, "/") . "/";
        // 文字列の分割
        $matchs = preg_split($preg_str, $template, 0, PREG_SPLIT_DELIM_CAPTURE);
        $this->TemplateList = $matchs;
        $cnt = count($matchs);
        // テンプレートを評価
        $tmp = $matchs[0];
        $level = 0;
        for ($key = 1; $key < $cnt; $key += 2) {
            $this->_setTemplateTags($tmp, $key, $matchs, $level);
        }
        return $tmp;
    }

    private function _setTemplateTags(&$tmp, &$key, &$list, &$level, $check = false, $skip = false)
    {
        // 式を取得
        $ptn = $list[$key];
        // comment
        if (preg_match("/^#(.*)#$/i", $ptn, $m)) {
            $tmp .= $list[$key + 1];
        // literal
        }else if (preg_match("/^LITERAL$/i", $ptn, $m)) {
            $tmp .= $list[$key + 1];
            // tag skip
            $this->_skipLiteralTags($tmp, $key, $list, $level);
        // foreach
        }else if (preg_match("/^FOREACH\s+(.+)$/i", $ptn, $m)) {
            $level++;
            if ($skip == false) {
                // loop
                $this->_setForeachLoop($m[1], $tmp, $key, $list, $level);
            } else {
                // tag skip
                $this->_skipIfTags($tmp, $key, $list, $level);
            }
        } else if (preg_match("/^\/FOREACH$/i", $ptn, $m)) {
            $level--;
            // for文
        } else if (preg_match("/^FOR\s+(.+)$/i", $ptn, $m)) {
            $level++;
            if ($skip == false) {
                // loop
                $this->_setForLoop($m[1], $tmp, $key, $list, $level);
            } else {
                // tag skip
                $this->_skipIfTags($tmp, $key, $list, $level);
            }
        } else if (preg_match("/^\/FOR$/i", $ptn, $m)) {
            $level--;
            // if文
        } else if (preg_match("/^IF\s*\(\s*(.+)\s*\)\s*$/i", $ptn, $m)) {
            $level++;
            if ($skip == false) {
                // IF処理
                $this->_setIf($m[1], $tmp, $key, $list, $level);
            } else {
                // tag skip
                $this->_skipIfTags($tmp, $key, $list, $level);
            }
            // if end
        } else if (preg_match("/^\/IF$/i", $ptn, $m)) {
            $level--;
            // 無効な値は無視
        } else {
            if ($skip == false) {
                $var = $this->evaString($ptn);
                $tmp .= $var;
                $tmp .= $list[$key + 1];
            }
        }
        return $check;
    }

    /**
     * @param $tmp
     * @param $key
     * @param $list
     * @param $level
     */
    private function _skipLiteralTags(&$tmp, &$key, &$list, &$level)
    {
        $cnt = count($list);
        for ($key += 2; $key < $cnt; $key += 2) {
            $ptn = $list[$key];
            if(preg_match("/^\/LITERAL$/i", $ptn, $m)){
                $tmp .= $list[$key + 1];
                break;
            }
            $tmp .= $this->left_delimiter.$ptn.$this->right_delimiter;
            $tmp .= $list[$key + 1];
        }
    }

    /**
     * @param $tmp
     * @param $key
     * @param $list
     * @param $level
     */
    private function _skipIfTags(&$tmp, &$key, &$list, &$level)
    {
        $cnt = count($list);
        $start_level = $level - 1;
        for ($key += 2; $key < $cnt; $key += 2) {
            // tag
            $this->_setTemplateTags($tmp, $key, $list, $level, true, true);
            // level check
            if ($level <= $start_level) {
                break;
            }
        }
    }

    /**
     * set template FOR
     * @access private
     */
    private function _setForLoop($str, &$tmp, &$key, &$list, &$level)
    {
        // 要素を抽出
        preg_match("/^\s*\\\$([\S]+)=([\S]+)\s+TO\s+(\S+)\s*((\S+)\s*)?$/i", $str, $m);
        $name = $m[1];
        $start = $this->convertString($m[2]);
        $loop = $this->convertString($m[3]);
        $step = 1;
        if (isset($m[5])) {
            $at = $m[5];
            // 属性値を取得
            $attr = $this->getAttr($attr, $at);
            if (isset($attr["STEP"])) {
                $step = intval($attr["STEP"]);
            }
        }
        $start_key = $key;
        $start_level = $level - 1;
        $cnt = count($list);
        if ($start <= $loop) {
            for ($i = $start; $i <= $loop; $i += $step) {
                $this->assign($name, $i);
                $tmp .= $list[$key + 1];
                $key += 2;
                while ($key < $cnt) {
                    // タグの実装
                    $this->_setTemplateTags($tmp, $key, $list, $level);
                    // レベルチェック
                    if ($level <= $start_level) {
                        if (($i + $step) <= $loop) {
                            // キー値を戻す
                            $key = $start_key;
                            $level++;
                        }
                        break;
                    }
                    $key += 2;
                };
            }
            $tmp .= $list[$key + 1];
        } else {
            // tag skip
            $this->_skipIfTags($tmp, $key, $list, $level);
            $tmp .= $list[$key + 1];
        }
    }

    /**
     * set template FOREACH
     * @access private
     */
    private function _setForeachLoop($str, &$tmp, &$key, &$list, &$level)
    {
        // 要素を抽出
        preg_match("/^\s*([\S]+)\s+AS\s+(.+?)\s*$/i", $str, $m);
        $item_list = $this->convertString($m[1]);
        $loop_key = "";
        $name = "";
        $s = $m[2];
        if (preg_match("/^\\\$([\S]+)\s*=>\s*\\\$([\S]+)$/", $s, $m)) {
            $loop_key = $m[1];
            $name = $m[2];
        } elseif (preg_match("/^\\\$([\S]+)$/", $s, $m)) {
            $name = $m[1];
        }
        // section
        $start_key = $key;
        $start_level = $level - 1;
        $cnt = count($list);
        $item_cnt = count($item_list);
        if (!is_array($item_list)){
            trigger_error("template : foreach value ".$str." is not array;", E_USER_WARNING);
        } else if (0 < $item_cnt) {
            $i = 0;
            foreach ($item_list as $item_key => $item) {
                if ($loop_key != "") {
                    $this->assign($loop_key, $item_key);
                }
                $this->assign($name, $item);

                $tmp .= $list[$key + 1];
                $key += 2;
                while ($key < $cnt) {
                    // タグの実装
                    $this->_setTemplateTags($tmp, $key, $list, $level);
                    // level check
                    if ($level <= $start_level) {
                        if (($i + 1) < $item_cnt) {
                            // キー値を戻す
                            $key = $start_key;
                            $level++;
                        }
                        break;
                    }
                    $key += 2;
                };
                $i++;
            }
            $tmp .= $list[$key + 1];
        } else {
            // tag skip
            $this->_skipIfTags($tmp, $key, $list, $level);
            $tmp .= $list[$key + 1];
        }
    }

    /**
     * set template IF
     * @access private
     */
    private function _setIf($str, &$tmp, &$key, &$list, &$level)
    {
        // if処理
        $check = $this->_setIfExecute($str, $tmp, $key, $list, $level);

        $start_level = $level - 1;
        $cnt = count($list);
        $loop = 0;
        for (; $key < $cnt; $key += 2) {
            //echo str_repeat("　",$level).htmlspecialchars($list[$key])."A<br/>";
            // 式を取得
            $ptn = $list[$key];
            // elseif文
            if (preg_match("/^(\/?)ELSE\s*IF\s*\(\s*(.+?)\)$/i", $ptn, $m)) {
                //echo "skip";
                if ($check == false) {
                    // if文法処理
                    if ($check = $this->_setIfExecute($m[2], $tmp, $key, $list, $level, $check)) {
                    }
                    $key -= 2;
                    continue;
                } else {
                    // tag skip
                    $this->_skipIfTags($tmp, $key, $list, $level);
                }
                // else文
            } else if (preg_match("/^(\/?)ELSE\s*$/i", $ptn, $m)) {
                if ($check == false) {
                    // if文法処理
                    if ($check = $this->_setIfExecute(NULL, $tmp, $key, $list, $level, $check)) {
                    }
                    $key -= 2;
                    continue;
                } else {
                    // tag skip
                    $this->_skipIfTags($tmp, $key, $list, $level);
                }
            } else {
                $this->_setTemplateTags($tmp, $key, $list, $level, $check, ($check == false));
            }
            // leevl check
            if ($level <= $start_level) {
                $tmp .= $list[$key + 1];
                break;
            }
            $loop++;
        }
    }

    /**
     * set template IF/ELSEIF/ELSE
     * @access private
     */
    private function _setIfExecute($str, &$tmp, &$key, &$list, &$level, $checked = false)
    {
        // チェック
        $check = true;
        if ($str != NULL) {
            $check = $this->_setIfCheck($str);
        }
        if ($check && $checked == false) {
            $tmp .= $list[$key + 1];
        }
        $key += 2;
        return ($check | $checked);
    }

    /**
     * @param $str
     * @return bool
     */
    private function _setIfCheck($str)
    {
        $check = false;
        if ($this->evaString($str)) {
            $check = true;
        }
        return $check;
    }

} 
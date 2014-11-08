<?php

/**
 * Class WPP_Form
 */
class WPP_Form{

    protected $namespace;
    protected $forms;
    protected $vars;
    protected $errors;

    function __construct($namespace) {
        $this->namespace = $namespace;
        $this->forms = array();
        $this->vars = array();
        $this->errors = array();
    }

    /**
     * @param $key
     * @param $val
     * @return $this
     */
    public function bindVar($key,$val){
        $this->vars[$key] = $val;
        return $this;
    }

    /**
     * @param $post
     * @return $this
     */
    public function bind($post){
        foreach($post as $k => $v){
            $this->bindVar($k,$v);
        }
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return null
     */
    public function getVar($key,$default=null){
        if(isset($this->vars[$key])){
            return $this->vars[$key];
        }
        return $default;
    }

    /**
     * @param $post
     * @return array
     */
    public function getDBVar($post){
        $values = array();
        foreach($post as $key => $v){
            if(isset($this->vars[$key])){
                $values[$key] = $this->vars[$key];
            }
        }
        return $values;
    }

    /**
     * @param $name
     * @param $type
     * @param array $options
     * @return $this
     */
    public function add($name,$type,$options=array()){
        $this->forms[$name] = array(
            'name' => $name,
            'type' => $type,
            'options' => $options
        );
        return $this;
    }

    /**
     * @return array
     */
    public function getView(){
        $f = array();
        foreach($this->forms as $k => $data){
            $f[$k] = $this->getTag($data['name'],$data['type'],$data['options']);
        }
        $f['_errors'] = $this->errors;
        $f['_id'] = $this->namespace;
        return $f;
    }

    /**
     * @param $name
     * @return $this
     */
    public function delete($name){
        if(isset($this->forms[$name])){
            unset($this->forms[$name]);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function deleteAll(){
        $this->forms = array();
        return $this;
    }

    /**
     * @return bool
     */
    public function validation(){
        $this->errors = array();

        foreach($this->forms as $key => $form){
            $value = $this->getVar($key);
            $check = array();
            if(isset($form['options']['required'])){
                $check[] = 'required';
            }
            if($form['type'] == 'number'){
                $check[] = 'number';
            }else if($form['type'] == 'url'){
                $check[] = 'url';
            }else if($form['type'] == 'email'){
                $check[] = 'email';
            }
            foreach($check as $check_key){
                $ck = false;
                switch($check_key){
                    case 'required':
                        $ck = ($value === null);
                        break;
                    case 'number':
                        $ck = !(is_numeric($value));
                        break;
                    case 'url':
                        $ck = !(preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $value));
                        break;
                    case 'email':
                        $ck = !(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $value));
                        break;
                }
                if($ck){
                    $this->errors[] = $this->getErrorMessage($check_key);
                }
            }
        }
        if(count($this->errors)){
            return false;
        }
        return true;
    }

    /**
     * @param $key
     * @return null|string
     * @throws Exception
     */
    protected function getErrorMessage($key){
        switch($key){
            case 'required':
                return '必須項目が入力されていません';
                break;
            case 'number':
                return '数値ではありません';
                break;
            case 'url':
                return 'URLが正しくありません';
                break;
            case 'email':
                return 'メールアドレスが正しくありません';
                break;
        }
        throw new Exception('UnSupport form error key ('.$key.')');
        return null;
    }

    /**
     * @param $name
     * @param $type
     * @param $options
     * @return string
     * @throws Exception
     */
    public function getTag($name,$type,$options){
        $n = ($this->namespace ? $this->namespace.'['.$name.']' : $name);
        $value = $this->getVar($name);
        switch(strtolower($type)){
            case 'text':
            case 'number':
            case 'url':
            case 'email':
                $s = array_merge($options,array('type'=>'text','name'=>$n,'value'=>$value));
                return '<input '.$this->getAttrString($s).'>';
                break;
            case 'hidden':
                $s = array_merge($options,array('type'=>'hidden','name'=>$n,'value'=>$value));
                return '<input '.$this->getAttrString($s).'>';
                break;
            case 'checkbox':
                $s = array_merge($options,array('type'=>'checkbox','name'=>$n,'value'=>1));
                if($value == 1){
                    $s['checked'] = 'checked';
                }
                return '<input '.$this->getAttrString($s).'>';
                break;
            case 'radio':
                $choices = $this->get_safe_array('choices',$options,null,true);
                $tag = '';
                foreach($choices as $k => $v){
                    $s = array_merge($options,array('type'=>'radio','name'=>$n,'value'=>$k));
                    if($value == $k){
                        $s['checked'] = 'checked';
                    }
                    $tag .= '<label>';
                    $tag .= '<input '.$this->getAttrString($s).'>';
                    $tag .= $v;
                    $tag .= '</label>';
                }
                return $tag;
                break;
            case 'select':
                $choices = $this->get_safe_array('choices',$options,null,true);
                $s = array_merge($options,array('name'=>$n));
                $tag = '<select '.$this->getAttrString($s).'>';
                foreach($choices as $k => $v){
                    $s = array('value'=>$k);
                    if($value == $k){
                        $s['selected'] = 'selected';
                    }
                    $tag .= '<option '.$this->getAttrString($s).'>'.$v.'</option>';
                    $tag .= $v;
                }
                $tag .= '</select>';
                return $tag;
                break;
            default:
                throw new Exception('UnSupport form type ('.$type.')');
                break;
        }
    }

    /**
     * @param $key
     * @param $arr
     * @param null $default
     * @param bool $unset
     * @return null
     */
    protected function get_safe_array($key,&$arr,$default=null,$unset=false){
        if(isset($arr[$key])){
            $res = $arr[$key];
        }else{
            $res = $default;
        }
        if($unset){
            $this->unset_array($key,$arr);
        }
        return $res;
    }

    /**
     * @param $key
     * @param $arr
     */
    protected function unset_array($key,&$arr){
        if(isset($arr[$key])){
            unset($arr[$key]);
        }
    }

    /**
     * @param array $attr
     * @return string
     */
    protected function getAttrString($attr=array()){
        $str = array();
        foreach($attr as $k => $v){
            $str[] = $k.'="'.$v.'"';
        }
        return implode($str," ");
    }
}
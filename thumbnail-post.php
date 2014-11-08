<?php
/*
Plugin Name: thumbnail-post
Plugin URI: http://www.ateliee.com/
Description: 記事中の画像をサムネイル表示させます。
Author: ateliee
Author URI: http://www.ateliee.com/
Version: 1.0
*/

include_once(dirname(__FILE__).'/include.php');

class ThumbnailPost {

    /**
     * @var
     */
    protected $PLUGIN_DIR;
    protected $PLUGIN_FILENAME;
    protected $PLUGIN_URL;

    protected $OPTIONID;
    protected $OPTIONS_DEFAULT;

    static protected $LIST_PRECEDENCE = array(
        0 => "サムネイルを優先",
        1 => "記事文中の画像を優先",
    );
    static protected $LIST_NUMBER = array(
        0 => "はじめの画像",
        1 => "最後の画像",
    );
    /**
     * 初期設定
     */
    function __construct() {
        $this->PLUGIN_DIR = dirname(__FILE__);
        $this->PLUGIN_FILENAME = basename($this->PLUGIN_DIR);
        $this->PLUGIN_URL = plugins_url().'/'.$this->PLUGIN_FILENAME;
        $this->OPTIONID = str_replace('/'.basename( __FILE__),"",plugin_basename(__FILE__));
        $this->OPTIONS_DEFAULT = array(
            'minWidth' => 100,
            'minHeight' => 100,
            'precedence' => 0,
            'number' => 0,
        );

        $this->activate();
        $this->init();
    }

    /**
     * @return SimpleTemplate
     */
    protected function createTemplate($filename){
        $template = new Template();
        $template->load(dirname(__FILE__).'/templates/'.$filename);
        $template->assign('PLUGIN_URL',$this->PLUGIN_URL);
        return $template;
    }

    /**
     * アクティブ化
     */
    protected function activate(){

    }

    /**
     *
     */
    protected function init(){
        // add menu
        add_action('admin_menu', array($this, 'add_pages'));
        // ショートコード作成
        add_shortcode('thumbnailPost', array($this, 'getThumnailPostShortCode'));
    }

    /**
     *
     */
    public function add_pages() {
        add_menu_page('サムネイル設定','サムネイル設定',  'level_8', __FILE__, array($this,'add_menu_page_action'), '', 26);
    }

    /**
     * @return mixed|void
     */
    public function getOption($key=null,$default=null){
        $opts = get_option($this->OPTIONID);
        if($key){
            if(isset($opts[$key])){
                return $opts[$key];
            }
            return $default;
        }
        return $opts;
    }

    /**
     * @param $value
     */
    public function setOption($value){
        update_option($this->OPTIONID,$value);
    }

    /**
     * admin page add menu
     */
    public function add_menu_page_action() {
        $options = $this->OPTIONS_DEFAULT;
        // 前のデータを格納
        if($opts = $this->getOption()){
            $options = array_merge($options,$opts);
        }

        $messages = array();

        $form = new WPP_Form($this->OPTIONID);
        $form->bind($options);
        $form
            ->add('minWidth','number',array('class' => 'input-s'))
            ->add('minHeight','number',array('class' => 'input-s'))
            ->add('precedence','radio',array('choices' => self::$LIST_PRECEDENCE))
            ->add('number','radio',array('choices' => self::$LIST_NUMBER))
        ;
        if ( isset($_POST[$this->OPTIONID])) {
            if(check_admin_referer($this->OPTIONID)){
                $opts = $_POST[$this->OPTIONID];
                $form->bind($opts);

                if($form->validation()){
                    $this->setOption($opts);
                    $messages[] = '更新致しました';
                }
            }
        }

        // template
        $template = $this->createTemplate('admin.tpl');
        $template->assign_vars(array(
            'form' => $form->getView(),
            'messages' => $messages,
            'form_NONCE' => wp_nonce_field($this->OPTIONID),
        ));
        $template->display();
        return false;
    }

    /**
     * @param $postID
     */
    public function getThumnailPostShortCode($atts, $content = null){
        extract(shortcode_atts(array(
            "postid" => get_the_ID(),
        ), $atts));
        return $this->getThumnailPostImageTag($postid);
    }

    /**
     * @param $postID
     * @return bool|string
     */
    public function getThumnailPostImageTag($postID){
        // サムネイルがあれば返す
        if($this->getOption('precedence') != 1){
            $thumbnail = get_the_post_thumbnail($postID);
            if($thumbnail){
                return $thumbnail;
            }
        }
        $filename = $this->getThumnailPostURL($postID);
        if($filename){
            $tag = '<img src="'.$filename.'">';
            return $tag;
        }
        return false;
    }

    /**
     * @param int $post_id
     * @param null $more_link_text
     * @param bool $stripteaser
     * @return null|string
     */
    function get_the_content_by_id( $post_id=0, $more_link_text = null, $stripteaser = false ){
        $content_post = get_post($post_id);
        $content = $content_post->post_content;
        //$content = apply_filters('the_content', $content);
        //$content = str_replace(']]>', ']]&gt;', $content);
        return $content;
    }
    /**
     * @param $postID
     * @return string
     */
    public function getThumnailPostURL($postID){
        $content = $this->get_the_content_by_id($postID);
        if(!$content){
            return null;
        }

        $result = null;
        if(preg_match_all('/<img[ ]+[^>]*src\=["|\']([^"|^\']+)["|\'][^>]*>/',$content,$matchs)){
            $img_array = $matchs[1];
            if($this->getOption('number') != 0){
                $img_array = array_reverse($img_array);
            }
            $minw = $this->getOption('minWidth');
            $minh = $this->getOption('minHeight');
            foreach($img_array as $filename){
                list($w,$h,$type,$attr) = @getimagesize($filename);
                if($minw > 0 && $w < $minw){
                    continue;
                }
                if($minh > 0 && $h < $minh){
                    continue;
                }
                return $filename;
            }
        }else{
            // 直接imgタグにてアクセスされていない場合は紐づけられた画像を取得
            $files = get_children(array('post_parent' => $postID, 'post_type' => 'attachment', 'post_mime_type' => 'image'));
            if(is_array($files) && count($files) != 0){
                $files=array_reverse($files);
                $file=array_shift($files);
                return wp_get_attachment_url($file->ID);
            }
        }
        return null;
    }
}

$thumbnailpost = new ThumbnailPost;
/**
 * @param $postid
 */
function thumbnailPost($postid=null){
global $thumbnailpost;
    if(!$postid){
        $postid = get_the_ID();
    }
    print $thumbnailpost->getThumnailPostImageTag($postid);
}


<?php

/**
 * Class WPP_DB
 */
class WPP_DB{

    protected $namescape;
    protected $version;

    function __construct($namespace,$version) {
        $this->namespace = $namespace;
        $this->version = $version;
    }

    /**
     * @return mixed|void
     */
    public function getVersion(){
        // 現在のDBバージョン取得
        return get_option($this->namespace);
    }

    /**
     *
     */
    public function updateVersion(){
        update_option($this->namespace, $this->version);
    }

    /**
     * @param $sql
     * @param bool $update_version
     * @return bool
     */
    public function upDataDB($sql,$update_version=true){
        // DBバージョンが違ったら作成
        if( $this->getVersion() != $this->version ) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            // RSS
            dbDelta($sql);
            // オプションにDBバージョン保存
            if($update_version){
                $this->updateVersion();
            }
            return true;
        }
        return false;
    }

    /**
     * @param $table
     * @param $value
     * @param $where
     * @param $attr
     * @return mixed
     */
    public function update($table,$value,$where,$attr){
        global $wpdb;
        return $wpdb->update($table,$value,$where,$attr);
    }

    /**
     * @param $table
     * @param $value
     * @param $attr
     * @return mixed
     */
    public function insert($table,$value,$attr){
        global $wpdb;
        return $wpdb->insert($table,$value,$attr);
    }

    /**
     * @param $table
     * @param $where
     * @return mixed
     */
    public function delete($table,$where){
        $query = "DELETE FROM `".$table."` ";
        if(is_array($where)){
            $q = array();
            foreach($where as $k => $v){
                $q[] = $k.'='.$v;
            }
            $query .= "WHERE ".implode($q," AND ");
        }else if($where != ""){
            $query .= "WHERE ".$where;
        }
        return $this->query($query);
    }

    /**
     * @param $sql
     */
    public function query($sql){
        global $wpdb;
        return $wpdb->query($sql);
    }

    /**
     * @param $sql
     * @return mixed
     */
    public function get_results($sql){
        global $wpdb;
        $args = func_get_args();
        if(count($args) > 1){
            $query = call_user_func_array(array($wpdb, "prepare"), $args);
        }else{
            $query = $sql;
        }
        return $wpdb->get_results($query,ARRAY_A);
    }
}
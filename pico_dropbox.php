<?php

class Pico_Dropbox{

  private $dropbox;

  private $files;
  
  private $pico;

  function __construct($owner){
    $this->pico = $owner;
    define("USER_AGENT", "Pico Updater");
    define("FILE_CURSOR", LOG_DIR . "dropbox-cursor.conf");
    define("DB_CONFIG_DIR", "config");
    define("DB_CONTENT_DIR", "content");
  }

  public function precheck(){
    $ret = "";
    if(isset($_GET['challenge'])){
      $ret = htmlspecialchars($_GET['challenge']);
    }elseif(strpos($_SERVER["HTTP_USER_AGENT"], "DropboxWebhooks") !== false){
      // DropboxWebhooksがアクセスしたときには値を返さない(値を返すとエラーとみなされる)
      $ret = NULL;
    }
    return $ret;
  }

  public function run(){
    require_once(__DIR__ . '/vendor/autoload.php');
    $pico = $this->pico;
    $success = FALSE;
    $message = "";
    $fp = fopen(FILE_CURSOR, "c+");
    if(flock($fp, LOCK_EX)){
      $fp = fopen(FILE_CURSOR, "c+");
      $token = $pico->getConfig("dropbox")["access_token"];
      try{
        $this->dropbox = new \Dropbox\Client($token, USER_AGENT);
        $c = null;
        $cursor = array();
        if(filesize(FILE_CURSOR) > 0){
          $cursor = json_decode(fread($fp, filesize(FILE_CURSOR)), TRUE);
          if(!empty($cursor["cursor"])){
            $c = $cursor["cursor"];
          }
        }
          
        list($c, $files) = $this->loadOfDelta($c);
        $cursor["cursor"] = $c;
        // 書き込み
        ftruncate($fp, 0);
        fseek($fp, 0);
        fwrite($fp, json_encode($cursor));
        if(!empty($files)){
          $message = "Update Success\n";
        }
        $this->files = $files;
        $success = TRUE;
        fclose($fp);
      }catch(Exception $e){
        $message = $e->getMessage();
      }
    }
    return array("success" => $success, "message" => $message);
  }

  public function get_update_files(){
    return $this->files;
  }

  private function loadOfDelta($cursor){
    $pico = $this->pico;
    $cn = get_class();
    $rootdir = ROOT_DIR;
    $content_dir = $pico->getConfig("content_dir");
    $dbuploadconfig = $pico->getConfig("dropbox")["uploadconfig"];
    $dbupdir = strtolower($pico->getConfig('dropbox')['rootdir']);
    // TODO: result仕様の再検討
    $filelist = array();
    // Delta 読み込み
    $deltaPage = $this->dropbox->getDelta($cursor);
    // entries以外の処理
    // TODO: リセット処理の実装
    // entries処理
    foreach ($deltaPage["entries"] as $entry) {
      $dirtype = "";
      list($lcPath, $metadata) = $entry;
      // ルートフォルダチェック
      if($dbupdir == "" || $this->startsWith(strtolower($lcPath), $dbupdir)){
        $lcPath = substr($lcPath, strlen($dbupdir));
        if(!$this->startsWith($lcPath, "/"))
        {
          $lcPath = "/" . $lcPath;
        }
      }else{
        // ルートフォルダにはない
        continue;
      }
      
      // フォルダ切り分け
      if($this->startsWith($lcPath, "/" . DB_CONTENT_DIR)){
        // コンテントファイル
        $ppath = str_replace("/" . DB_CONTENT_DIR . "/", $content_dir, $lcPath);
        $dirtype = DB_CONTENT_DIR;
      }elseif($this->startsWith($lcPath, "/" . DB_CONFIG_DIR) && $dbuploadconfig){
        // コンフィグファイル
        $cfg = DB_CONFIG_DIR;
        $ppath = "$rootdir/$cfg/$cfg.php";
        $dirtype = DB_CONFIG_DIR;
      }else{
        // 未定義フォルダのファイルは無視
        continue;
      }
      if ($metadata === null && $dirtype != DB_CONFIG_DIR) {
        // ファイル及びフォルダは削除された
        array_push($filelist, array($ppath, FALSE)); // result配列に項目を追加
        if(file_exists($ppath)){
          $pico->sendWebhook("- $ppath", $cn, ":small_red_triangle_down:");
          if(is_dir($ppath)){
            $this->remove_dirs($ppath);
          }else {
            unlink($ppath);
          }
        }
      } elseif($metadata !== null) {
        // ファイル及びフォルダは追加or更新された
        array_push($filelist, array($ppath, TRUE)); // result配列に項目を追加
        $pico->sendWebhook("+ $ppath", $cn, ":small_red_triangle:");
        if($metadata["is_dir"]){
          if(!file_exists($ppath)){
            mkdir($ppath);
          }
        }else{
          $fp = fopen($ppath, "wb");
          try{
            $res = $this->dropbox->getFile($metadata["path"], $fp);
          }catch(Exception $e){
            throw new Exception("File Loading Error Please Download This ${metadata['path']}", 0, $e);
          }
          fclose($fp);
        }
      }
    }
    if($deltaPage["has_more"]){
      list($cursor, $files) = $this->loadOfDelta($deltaPage["cursor"]);
      $filelist = array_push($filelist, $files);
    }else{
      $cursor = $deltaPage["cursor"];
    }
    return array($cursor, $filelist);
  }

  // utils

  /**
   * ディレクトリを再帰的に削除する
   * http://d.hatena.ne.jp/kusakari/20070727/1185467927
   * @param string $dir ディレクトリ
   */
  private function remove_dirs($dir){
    if (!file_exists($dir)) {
      return;
    }
    $dhandle = opendir($dir);
    if ($dhandle) {
      while (false !== ($fname = readdir($dhandle))) {
        if (is_dir( "{$dir}/{$fname}" )) {
            if (($fname != '.') && ($fname != '..')) {
              $this->remove_dirs("$dir/$fname");
            }
        } else {
            unlink("{$dir}/{$fname}");
        }
      }
      closedir($dhandle);
    }
    rmdir($dir);
  }

  /**
  * 前方一致
  * http://d.hatena.ne.jp/nobuchiru/20100726/p2
  * $haystackが$needleから始まるか否かを判定します。
  * @param string $haystack
  * @param string $needle
  * @return TRUE = needleで始まる / FALSE = needleで始まらない
  */
  private function startsWith($haystack, $needle){
    return strpos($haystack, $needle, 0) === 0;
  }

}

?>

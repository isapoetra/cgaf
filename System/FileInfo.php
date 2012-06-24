<?php
final class FileInfo extends \BaseObject {
  private $_file;
  private $_mime = null;
  private static $_Mimecache;
  private $_infos;
  function __construct($file) {
    parent::__construct ();
    $this->_file = $file;
    $this->_infos();
  }
  function __get($var) {
    $infos = $this->_infos ();
    switch (strtolower($var)) {
      case 'mtime':
        return $infos['size']['time']['mtime'];
      case 'modified':
        return $infos['size']['time']['modified'];
      case 'mime':
        return $this->getMime();
      case 'filesize' :
        return $infos['size']['size'];
      case 'is_writable' :
      case 'is_readable' :
      case 'is_dir' :
      case 'is_link' :
      case 'is_file' :
        return isset ( $infos ['filetype'] [$var] ) ? $infos ['filetype'] [$var] : null;
      case 'owner_user' :
      case 'owner_group' :
      case 'owner_user_posix' :
      case 'owner_group_posix' :
        $sub = Strings::FromPos ( $var, '_' );
        return isset ( $infos ['owner'] [$sub] ) ? $infos ['owner'] [$sub] : null;
      case 'owner_usergroup' :
        return ($this->owner_user_posix ? $this->owner_user_posix ['name'] : 'nobody') . ':' . ($this->owner_group_posix ? $this->owner_group_posix ['name'] : 'nobody');
      case 'perm_human' :
        $sub = Strings::FromPos ( $var, '_' );
        return isset ( $infos ['perms'] [$sub] ) ? $infos ['perms'] [$sub] : null;

    }
    return isset ( $infos [$var] ) ? $infos [$var] : null;
  }

  private function _infos($force = false) {
    if ($this->_infos && ! $force) {
      return $this->_infos;
    }
    clearstatcache (true,$this->_file);
    if (!is_readable($this->_file)) {
      $this->_infos = array();
      return null;
    }
    $this->_infos = array ();
    $this->_infos ['is_exist'] = false;
    $ss = stat ( $this->_file );
    $ss ['type'] = isset ( $ss ['type'] ) ? $ss ['type'] : ($ss ['nlink'] > 1 ? 'link' : 'node');
    if (! $ss)
      return $this->_infos; // Couldnt stat file
    $ts = array (
        0140000 => 'ssocket',
        0120000 => 'llink',
        0100000 => '-file',
        0060000 => 'bblock',
        0040000 => 'ddir',
        0020000 => 'cchar',
        0010000 => 'pfifo'
    );
    $file = $this->_file;

    $p = $ss ['mode'];
    $t = decoct ( $ss ['mode'] & 0170000 ); // File Encoding Bit

    $str = (array_key_exists ( octdec ( $t ), $ts )) ? $ts [octdec ( $t )] {
      0} : 'u';
      $str .= (($p & 0x0100) ? 'r' : '-') . (($p & 0x0080) ? 'w' : '-');
      $str .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x') : (($p & 0x0800) ? 'S' : '-'));
      $str .= (($p & 0x0020) ? 'r' : '-') . (($p & 0x0010) ? 'w' : '-');
      $str .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x') : (($p & 0x0400) ? 'S' : '-'));
      $str .= (($p & 0x0004) ? 'r' : '-') . (($p & 0x0002) ? 'w' : '-');
      $str .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x') : (($p & 0x0200) ? 'T' : '-'));

      $this->_infos = array (
          'is_exist' => true,
          'perms' => array (
              'umask' => sprintf ( "%04o", @umask () ),
              'human' => $str,
              'octal1' => sprintf ( "%o", ($ss ['mode'] & 000777) ),
              'octal2' => sprintf ( "0%o", 0777 & $p ),
              'decimal' => sprintf ( "%04o", $p ),
              'fileperms' => @fileperms ( $file ),
              'mode1' => $p,
              'mode2' => $ss ['mode']
          ),
          'owner' => array (
              'user' => $ss ['uid'],
              'group' => $ss ['gid'],
              'user_posix' => (function_exists ( 'posix_getpwuid' )) ? @posix_getpwuid ( $ss ['uid'] ) : '',
              'group_posix' => (function_exists ( 'posix_getgrgid' )) ? @posix_getgrgid ( $ss ['gid'] ) : ''
          ),
          'file' => array (
              'filename' => $file,
              'realpath' => (@realpath ( $file ) != $file) ? @realpath ( $file ) : '',
              'dirname' => @dirname ( $file ),
              'basename' => @basename ( $file )
          ),
          'filetype' => array (
              'type' => substr ( $ts [octdec ( $t )], 1 ),
              'type_octal' => sprintf ( "%07o", octdec ( $t ) ),
              'is_file' => @is_file ( $file ),
              'is_dir' => @is_dir ( $file ),
              'is_link' => @is_link ( $file ),
              'is_readable' => @is_readable ( $file ),
              'is_writable' => @is_writable ( $file )
          ),
          'device' => array (
              'device' => $ss ['dev'],  // Device
              'device_number' => $ss ['rdev'],  // Device number, if device.
              'inode' => $ss ['ino'],  // File serial number
              'link_count' => $ss ['nlink'],  // link count
              'link_to' => ($ss ['type'] == 'link') ? @readlink ( $file ) : ''
          ),
          'size' => array (
              'size' => $ss ['size'],  // Size of file, in bytes.
              'blocks' => $ss ['blocks'],  // Number 512-byte blocks
              // allocated
              'block_size' => $ss ['blksize'],  // Optimal block size for I/O.
              'time' => array (
                  'mtime' => $ss ['mtime'],  // Time of last modification
                  'atime' => $ss ['atime'],  // Time of last access.
                  'ctime' => $ss ['ctime'],  // Time of last status change
                  'accessed' => @date ( 'Y M D H:i:s', $ss ['atime'] ),
                  'modified' => @date ( 'Y M D H:i:s', $ss ['mtime'] ),
                  'created' => @date ( 'Y M D H:i:s', $ss ['ctime'] )
              )
          )
      );
      return $this->_infos;
  }
  public function permEqual($p,$checkOther =true) {
    if ($checkOther) {
      return $this->perms['octal2'] !== $p;
    }else{
      if (strlen($p) === 3) {
        $p = substr($p,2);
      }
      $rp = substr($this->perms['octal2'],0,2);
      return $p===$rp;
    }
  }
  public static function getMimeFromExt($fileext) {

    if (! isset ( self::$_Mimecache [$fileext] )) {
      $regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
      $lines = file ( CGAF::getInternalStorage ( null, false ) . "mime.types" );
      foreach ( $lines as $line ) {
        if (substr ( $line, 0, 1 ) == '#')
          continue;
        // skip comments
        $line = rtrim ( $line ) . " ";
        if (! preg_match ( $regex, $line, $matches ))
          continue;
        // no match to the extension
        self::$_Mimecache [$fileext] = $matches [1];
      }
    }
    return isset ( self::$_Mimecache [$fileext] ) ? self::$_Mimecache [$fileext] : null;
  }
  public static function getFileMimeType($file) {
    $fileext = substr ( strrchr ( $file, '.' ), 1 );
    return self::getMimeFromExt ( $fileext );
  }
  function getMime() {
    if (! $this->_mime) {
      $this->_mime = self::getFileMimeType ( $this->_file );
    }
    return $this->_mime;
  }
}

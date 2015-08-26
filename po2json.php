<?php

/**
 * @file
 * A simplified version of the po2json script that ships with Datawrapper.
 */

/**
 * String object
 */
class PoeditString {
  public $key;
  public $value;
  public $fuzzy;
  public $comments;

  function __construct($key, $value = '', $fuzzy = false, $comments = array()) {
    $this->key = $key;
    $this->value = $value;
    $this->fuzzy = $fuzzy;
    $this->comments = (array)$comments;
  }

  public function __toString() {
    $str ='';
    foreach ($this->comments as $c) {
      $str .= "#: $c\n";
    }
    if ($this->fuzzy) $str .= "#, fuzzy\n";
    $str .= 'msgid "'.str_replace('"', '\\"', $this->key).'"' . "\n";
    $str .= 'msgstr "'.str_replace('"', '\\"', $this->value).'"' . "\n";
    $str .= "\n";
    return $str;
  }
}

/**
 * Parser object
 */
class PoeditParser {

  protected $file;
  protected $header = '';
  protected $strings = array();

  public function __construct($file) {
    $this->file = $file;
  }

  protected function _fixQuotes($str) {
    return stripslashes(str_replace('\n','', $str));
  }

  public function parse() {
    $contents = file_get_contents($this->file);
    $parts = preg_split('/(\r\n|\n){2}/', $contents, -1);
    $this->header = $parts[0];

    foreach ($parts as $part) {

      // parse comments
      $comments = array();
      preg_match_all('#^\\#: (.*?)$#m', $part, $matches, PREG_SET_ORDER);
      foreach ($matches as $m) $comments[] = $m[1];

      $isFuzzy = preg_match('#^\\#, fuzzy$#im', $part) ? true : false;

      preg_match_all('# *(msgid|msgstr) *(".*"(?:\\n".*")*)#', $part, $matches2, PREG_SET_ORDER);

      $lines = explode("\n", $this->_fixQuotes($matches2[0][2]));
      $msgid = "";
      foreach ($lines as $l) {
        $l = preg_replace('/"(.*)"/', '\1', $l);
        $msgid .= $l;
      }
      $msgid = trim($msgid);

      $lines = explode("\n", $this->_fixQuotes($matches2[1][2]));
      $msgstr = "";
      foreach ($lines as $l) {
        $l = preg_replace('/"(.*)"/', '\1', $l);
        $msgstr .= $l;
      }
      $msgstr = trim($msgstr);

      if ('' != $msgid) {
        $this->strings[$msgid] = new PoeditString($msgid, $msgstr, $isFuzzy, $comments);
      }
    }
  }

  public function getStrings() {
    return $this->strings;
  }

  public function getJSON() {
    $str = array();
    foreach ($this->strings as $s) {
      if ($s->value /*&& strlen($s->value) > 0*/) {
        $str[$s->key] = $s->value;
      }
      else {
        $str[$s->key] = $s->key;
      }
    }
    return json_encode($str, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  public function toJSON($outputFilename) {
    $str = $this->getJSON();
    return file_put_contents($outputFilename, $str) !== false;
  }
}

$in = $argv[1];
$out = $argv[2];

$poeditParser = new PoeditParser($in);
$poeditParser->parse();

if (!$poeditParser->toJSON($out)) {
  echo "Cannot write to file '$out'.\n";
}

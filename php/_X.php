<?
require_once("underscore.php");
__::mixin([
  'prepend'  => function($string, $to){ return (!empty($to) && isset($to))? $string.$to : $string; },
  'contains' => function($collection,$value){ return __::includ($collection,$value); }
]);
?>

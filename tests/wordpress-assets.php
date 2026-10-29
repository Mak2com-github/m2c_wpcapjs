<?php
/**
 * Vérifie l'ordonnancement avec les vraies classes et fonctions WP_Scripts.
 * php tests/wordpress-assets.php D:/Dev/ecovance
 * Ne charge ni wp-load.php, ni wp-config.php, ni une base de données.
 * Seuls les paramètres du site, CF7 et la sérialisation HTML sont simulés.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$wp_root=rtrim($argv[1]??'', '/\\');
if (!is_file($wp_root.'/wp-includes/class-wp-scripts.php')) {fwrite(STDERR,"Chemin WordPress requis.\n");exit(1);}
define('ABSPATH',$wp_root.'/');
define('WP_PLUGIN_DIR',dirname(__DIR__,2));
define('WPMU_PLUGIN_DIR',dirname(__DIR__,2).'/mu-plugins');
$wp_plugin_paths=[];
require ABSPATH.'wp-includes/plugin.php';
foreach(['class-wp-dependency.php','class-wp-dependencies.php','class-wp-scripts.php','functions.wp-scripts.php'] as $file) require ABSPATH.'wp-includes/'.$file;
function get_option($name,$default=false) {return ['m2c_capjs_version'=>'1.4.1','m2c_capjs_server_url'=>'https://captcha.example.test','m2c_capjs_site_key'=>'fixture'][$name]??$default;}
define('DAY_IN_SECONDS',86400);
function update_option($name,$value,$autoload=null) {return true;}
function home_url() {return 'https://site.example.test';}
function wp_parse_url($url,$component=-1) {return parse_url($url,$component);}
function determine_locale() {return 'es_ES';}
function plugins_url(...$args) {return 'https://site.example.test/plugin/';}
function wp_normalize_path($value) {return str_replace('\\','/',$value);}
function esc_url_raw($value) {return $value;}
function esc_attr($value) {return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
function trailingslashit($value) {return rtrim($value,'/').'/';}
function __($value,...$args) {return $value;}
function wp_json_encode($value) {return json_encode($value);}
function get_bloginfo($name) {return $name==='charset'?'UTF-8':'';}
function _doing_it_wrong($name,$message,$version) {throw new Exception($name.': '.$message);}
function wp_get_script_tag($attrs) {$html='<script';foreach($attrs as $name=>$value)$html.=' '.$name.'="'.esc_attr($value).'"';return $html.'></script>';}
function wp_get_inline_script_tag($data,$attrs=[]) {return str_replace('></script>','>'.$data.'</script>',wp_get_script_tag($attrs));}
function wp_print_inline_script_tag($data,$attrs=[]) {echo wp_get_inline_script_tag($data,$attrs);}
class WPCF7 {}
class WPCF7_Service {}
add_action('wp_default_scripts',function($scripts){$scripts->base_url='https://site.example.test/';$scripts->default_version='fixture';$scripts->add('jquery','https://site.example.test/jquery.js');});
require dirname(__DIR__).'/m2c-wpcapjs.php';
do_action('plugins_loaded');do_action('init');
$count=0;
function check($condition,$message) {global $count;if(!$condition)throw new Exception($message);$count++;}
foreach(['before-head','after-head'] as $timing) {
 $wp_scripts=new WP_Scripts();
 ob_start();$wp_scripts->do_head_items();$empty=ob_get_clean();check($empty==='','aucun script sans champ');
 $tag=(object)['options'=>[]];
 m2c_capjs_form_tag_handler($tag);m2c_capjs_form_tag_handler($tag);
 $head='';if($timing==='before-head'){ob_start();$wp_scripts->do_head_items();$head=ob_get_clean();}
 check(!str_contains($head,'capjs-cf7.js'),'CF7 reste dans le footer');
 check(!str_contains($head,'/assets/widget.js'),'widget reste dans le footer');
 ob_start();$wp_scripts->do_footer_items();$footer=ob_get_clean();$output=$head.$footer;
 foreach(['capjs-widget-js','capjs-custom-js','capjs-cf7-js'] as $id) check(substr_count($output,'id="'.$id.'"')===1,$id.' imprimé une fois');
 check(strpos($output,'capjs-widget-js"')<strpos($output,'capjs-cf7-js"'),'widget imprimé avant CF7');
 check(strpos($output,'jquery-js"')<strpos($output,'capjs-cf7-js"'),'jQuery imprimé avant CF7');
 check(strpos($output,'var capjsCustomConfig')<strpos($output,'capjs-custom-js"'),'traductions imprimées avant leur lecteur');
 check(strpos($output,'var capjsCF7Config')<strpos($output,'capjs-cf7-js"'),'configuration CF7 imprimée avant son lecteur');
 check(str_contains($output,'"language":"es"'),'langue conservée dans la sortie WordPress');
}
echo "PASS : $count contrôles avec WP_Scripts ; aucune base ni réseau.\n";

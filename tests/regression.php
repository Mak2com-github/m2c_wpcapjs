<?php
/**
 * Tests locaux des intégrations, sans base, réseau ni extension tierce active.
 * Exécution : php tests/regression.php
 * Les API WordPress/formulaires sont simulées ; le code du plugin est réel.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ABSPATH', __DIR__ . '/');
$hooks = $scripts = $styles = $registered_strings = $option_writes = [];
$options = ['m2c_capjs_version'=>'1.4.1', 'm2c_capjs_server_url'=>'https://captcha.example.test', 'm2c_capjs_site_key'=>'public-fixture', 'm2c_capjs_secret_key'=>'factice'];
$locale = 'fr_FR'; $wpml_language = null; $remote_calls = 0; $remote_success = false; $count = 0;
function add_action($name, $callback, $priority=10, $accepted=1) { global $hooks; $hooks[$name][$priority][] = [$callback,$accepted]; }
function add_filter(...$args) { add_action(...$args); }
function do_action($name, ...$args) { global $hooks; $list=$hooks[$name]??[]; ksort($list); foreach($list as $items) foreach($items as [$cb,$n]) $cb(...array_slice($args,0,$n)); }
function apply_filters($name, $value, ...$args) { global $hooks; $list=$hooks[$name]??[]; ksort($list); foreach($list as $items) foreach($items as [$cb,$n]) $value=$cb(...array_slice([$value,...$args],0,$n)); return $value; }
function get_option($name,$default=false) { global $options; return $options[$name]??$default; }
function update_option($name,$value) { global $options,$option_writes; $option_writes[]=$name; $options[$name]=$value; }
function determine_locale() { global $locale; return $locale; }
function plugin_dir_path($file) { return dirname($file).'/'; }
function plugin_dir_url($file) { return 'https://site.example.test/plugin/'; }
function register_activation_hook(...$args) {}
function esc_url_raw($value) { return $value; }
function esc_attr($value) { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
function esc_html($value) { return esc_attr($value); }
function esc_url($value) { return esc_attr($value); }
function __($value,...$args) { return $value; }
function esc_html__($value,...$args) { return esc_html($value); }
function trailingslashit($value) { return rtrim($value,'/').'/'; }
function sanitize_text_field($value) { return strip_tags($value); }
function wp_json_encode($value) { return json_encode($value); }
function wp_script_is($handle,$status='enqueued') { global $scripts; return isset($scripts[$handle]); }
function wp_enqueue_script($handle,$src='',$deps=[],$ver=false,$footer=false) { global $scripts; $scripts[$handle]=compact('src','deps','ver','footer'); }
function wp_localize_script($handle,$name,$data) { global $scripts; if (!isset($scripts[$handle])) throw new Exception('Configuration avant enregistrement : '.$handle); $scripts[$handle]['data'][$name]=$data; }
function wp_register_style($handle,$src,$deps=[],$ver=false) { global $styles; $styles[$handle]=compact('src','deps','ver'); }
function wp_enqueue_style($handle) { global $styles; $styles[$handle]['enqueued']=true; }
function wp_add_inline_style($handle,$css) { global $styles; $styles[$handle]['css']=$css; }
function wp_remote_post($url,$request) { global $remote_calls,$remote_success; $remote_calls++; return ['response'=>['code'=>200],'body'=>json_encode(['success'=>$remote_success])]; }
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code($value) { return $value['response']['code']; }
function wp_remote_retrieve_body($value) { return $value['body']; }
function is_user_logged_in() { return false; }
function check($condition,$label) { global $count; if (!$condition) throw new Exception('ÉCHEC : '.$label); $count++; }
function reset_assets() { global $scripts,$styles; $scripts=$styles=[]; }
function capture($callback) { ob_start(); $callback(); return ob_get_clean(); }
class WP_Error { public $errors=[]; function add($code,$message) {$this->errors[$code][]=$message;} }
class WPCF7 {}
class WPCF7_Service {}
class WPCF7_Submission { static function get_instance() {return false;} }
class WPCF7_ContactForm { static $current; static function get_current() {return self::$current;} }
class FixtureForm { function __construct(public $content) {} function prop($name) {return $this->content;} }
class FixtureSubmission { public $logs=[]; function __construct(public $form) {} function get_contact_form() {return $this->form;} function add_spam_log($log) {$this->logs[]=$log;} }
class Ninja_Forms {}
class NF_Abstracts_Field { function __construct() {} }
class WooCommerce {}

add_action('wpml_register_single_string',function($context,$name,$value) use (&$registered_strings) {$registered_strings[$name]=[$context,$value];},10,3);
require dirname(__DIR__).'/m2c-wpcapjs.php';
do_action('plugins_loaded');
do_action('init');
check(count($registered_strings)===3,'trois chaînes enregistrées sans visiter de formulaire');
check(array_keys($registered_strings)===['widget_human_label','verification_complete','verification_failed'],'identifiants WPML historiques conservés');
check($registered_strings['widget_human_label'][0]==='capjs-integration','contexte WPML conservé');
check($option_writes===[],'aucun réglage existant modifié à la mise à jour');
check(m2c_capjs_get_widget_strings()['humanLabel']==='Je suis un humain','libellé historique sans WPML');
foreach(['fr_FR'=>'fr','es_ES'=>'es','en-US'=>'en','pt_BR'=>'pt',''=>'fr'] as $value=>$expected) {$locale=$value; check(m2c_capjs_get_widget_language()===$expected,'repli de locale '.$value);}
$locale='fr_FR';
add_filter('wpml_current_language',function($default) use (&$wpml_language){return $wpml_language;});
$translations=['widget_human_label'=>'Soy humano','verification_complete'=>'Verificación completada','verification_failed'=>'Error de verificación'];
add_filter('wpml_translate_single_string',function($default,$context,$name) use (&$translations){return $translations[$name]??$default;},10,3);
$wpml_language='es';
check(m2c_capjs_get_widget_language()==='es','langue WPML prioritaire sur la locale');
check(m2c_capjs_get_widget_strings()['humanLabel']==='Soy humano','traduction espagnole récupérée');

do_action('wp_enqueue_scripts');
check($scripts===[],'aucun script sans rendu, même avec les trois extensions actives');
check(str_contains($styles['m2c-capjs-accessibility']['css'],'#767676'),'correction de contraste préservée');
$tag=(object)['options'=>[]];
$html=m2c_capjs_form_tag_handler($tag);
check(str_contains($html,'class="capjs-widget"'),'rendu CF7 conservé');
check(array_keys($scripts)===['capjs-widget','capjs-custom','capjs-cf7'],'scripts CF7 chargés lors du rendu');
check($scripts['capjs-cf7']['deps']===['jquery','capjs-widget'],'ordre CF7/widget explicite');
check($scripts['capjs-custom']['deps']===['capjs-widget'],'ordre traduction/widget explicite');
check($scripts['capjs-cf7']['data']['capjsCF7Config']['language']==='es','langue transmise au JS CF7');
check($scripts['capjs-custom']['data']['capjsCustomConfig']['humanLabel']==='Soy humano','traduction transmise au script de secours');
check($scripts['capjs-cf7']['data']['capjsCF7Config']['verificationFailed']==='Error de verificación','état erreur traduit');
foreach($scripts as $handle=>$script) check($script['footer']===true,$handle.' reste en pied de page pour FSE');
$first_scripts=$scripts; m2c_capjs_form_tag_handler($tag);
check($scripts===$first_scripts,'deux formulaires sans double chargement');

$wc=M2C_WooCommerce_CapJS::get_instance();
foreach(['login'=>true,'register'=>true,'checkout'=>false,'review'=>false] as $kind=>$enabled) {
 reset_assets(); $html=capture([$wc,'display_capjs_'.$kind]);
 check(str_contains($html,'<cap-widget')===$enabled,'option WooCommerce par défaut : '.$kind);
 check(isset($scripts['capjs-woocommerce'])===$enabled,'chargement conditionnel WooCommerce : '.$kind);
}
foreach(['checkout'=>'m2c_capjs_woocommerce_checkout','review'=>'m2c_capjs_woocommerce_reviews','login'=>'m2c_capjs_woocommerce_login','register'=>'m2c_capjs_woocommerce_register'] as $kind=>$option) {
 foreach([false,true] as $enabled) {
  reset_assets(); $options[$option]=$enabled; $html=capture([$wc,'display_capjs_'.$kind]);
  check(str_contains($html,'<cap-widget')===$enabled,'rendu selon réglage '.$kind.' '.(int)$enabled);
  check(isset($scripts['capjs-woocommerce'])===$enabled,'scripts selon réglage '.$kind.' '.(int)$enabled);
  if($enabled) {check(str_contains($html,'data-cap-lang="es"'),'langue du widget '.$kind);check(str_contains($html,'data-cap-i18n-initial-state="Soy humano"'),'texte du widget '.$kind);}
 }
}
check($scripts['capjs-woocommerce']['deps']===['jquery','capjs-widget'],'ordre WooCommerce/widget explicite');

reset_assets();$field=new NF_Fields_CapJS();
$settings=$field->localize_settings(['label_visibility'=>'invisible','label'=>'Test'],[]);
check(array_keys($scripts)===['capjs-widget','capjs-custom'],'Ninja Forms charge le widget lors de la localisation du champ');
check($settings['label']===''&&$settings['site_key']==='public-fixture','réglages Ninja Forms conservés');
$template=capture(fn()=>do_action('nf_display_enqueue_scripts'));
check(str_contains($template,'data-cap-lang="es"'),'langue du template Ninja Forms');
check(str_contains($template,'data-cap-i18n-solved-label="Verificación completada"'),'état validé traduit dans Ninja Forms');
$translations['widget_human_label']='Soy "humano" <seguro>';
$template=capture(fn()=>do_action('nf_display_enqueue_scripts'));
check(str_contains($template,'Soy &quot;humano&quot; &lt;seguro&gt;'),'échappement des traductions dans Ninja Forms');
$html=capture([$wc,'display_capjs_login']);
check(str_contains($html,'Soy &quot;humano&quot; &lt;seguro&gt;'),'échappement des traductions dans WooCommerce');

$configured=$options; unset($options['m2c_capjs_site_key']);reset_assets();
check(m2c_capjs_form_tag_handler($tag)==='','CF7 non configuré sans rendu');
check(capture([$wc,'display_capjs_login'])==='','WooCommerce non configuré sans rendu');
$field->localize_settings(['label_visibility'=>'visible','label'=>'Test'],[]);
check($scripts===[],'Ninja Forms non configuré sans scripts');
check($field->validate(['value'=>''],[])===false,'comportement Ninja Forms non configuré conservé');
$options=$configured;

// Régressions critiques : contrôles serveur conservés, réponses réseau simulées.
$_POST=[];$submission=new FixtureSubmission(new FixtureForm('[text name] [capjs]'));
check(m2c_capjs_verify_response(false,$submission)===true,'CF7 rejette un token absent');
check($remote_calls===0,'aucun appel pour un token absent');
$_POST=['cap-token'=>'fixture-token'];$remote_success=false;
check(m2c_capjs_verify_response(false,$submission)===true,'CF7 rejette un token invalide');
$remote_success=true;check(m2c_capjs_verify_response(false,$submission)===false,'CF7 accepte un token validé');
$_POST=['_wpcf7_capjs_token'=>'fixture-token'];check(m2c_capjs_verify_response(false,$submission)===false,'fallback token CF7 préservé');
check(m2c_capjs_verify_response(true,$submission)===true,'CF7 ne retire pas un spam préexistant');
$_POST=[];check(m2c_capjs_verify_response(false,new FixtureSubmission(new FixtureForm('[text name]')))===false,'formulaire sans CapJS inchangé');
check(is_string($field->validate(['value'=>''],[])),'Ninja Forms rejette un token absent');
$remote_success=false;check(is_string($field->validate(['value'=>'fixture'],[])),'Ninja Forms rejette un token invalide');
$remote_success=true;check($field->validate(['value'=>'fixture'],[])===false,'Ninja Forms accepte un token validé');
$_POST=[];$errors=$wc->validate_capjs_registration(new WP_Error(),'','');check(isset($errors->errors['capjs_error']),'WooCommerce rejette un token absent');
$_POST=['capjs_wc_token'=>'fixture'];$remote_success=false;$errors=$wc->validate_capjs_registration(new WP_Error(),'','');check(isset($errors->errors['capjs_error']),'WooCommerce rejette un token invalide');
$remote_success=true;$errors=$wc->validate_capjs_registration(new WP_Error(),'','');check($errors->errors===[],'WooCommerce accepte un token validé');
echo "PASS : $count contrôles PHP ; aucun accès réseau/base.\n";

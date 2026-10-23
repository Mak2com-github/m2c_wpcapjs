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
// Mises à jour (includes/updater.php) : transients, appels HTTP et système de fichiers simulés.
define('HOUR_IN_SECONDS',3600);
$transients=$transient_ttls=$remote_get_calls=[]; $remote_get_response=null; $plugin_folder='m2c_wpcapjs'; $wp_filesystem=null;
function wp_parse_url($url,$component=-1) { return parse_url($url,$component); }
function home_url() { return 'https://www.Site.Example.test/blog'; }
function get_site_transient($name) { global $transients; return $transients[$name]??false; }
function set_site_transient($name,$value,$expiration=0) { global $transients,$transient_ttls; $transients[$name]=$value; $transient_ttls[$name]=$expiration; return true; }
function delete_site_transient($name) { global $transients; unset($transients[$name]); return true; }
function add_query_arg($args,$url) { return $url.(str_contains($url,'?')?'&':'?').http_build_query($args); }
function wp_remote_get($url,$request=[]) { global $remote_get_calls,$remote_get_response; $remote_get_calls[]=$url; return $remote_get_response; }
function get_bloginfo($show) { return '6.8.1'; }
function plugin_basename($file) { global $plugin_folder; return $plugin_folder.'/'.basename($file); }
function wp_kses_post($value) { return strip_tags($value,'<h4><ul><li><p>'); }
class FixtureFilesystem { public $moves=[]; public $ok=true; function move($from,$to,$overwrite=false) { $this->moves[]=[$from,$to,$overwrite]; return $this->ok; } }

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

// Mises à jour depuis M2C Licences : sans clé de licence, réponses simulées, aucun accès réseau.
$main=file_get_contents(dirname(__DIR__).'/m2c-wpcapjs.php'); $readme=file_get_contents(dirname(__DIR__).'/readme.txt');
preg_match('/^Version:\s*(\S+)/m',$main,$header_version); preg_match('/^Stable tag:\s*(\S+)/m',$readme,$stable_tag);
check($header_version[1]===M2C_CAPJS_VERSION&&$stable_tag[1]===M2C_CAPJS_VERSION,'version identique : en-tête, constante et Stable tag du readme.txt (M2C Licences refuse sinon l’archive)');
check(str_contains($readme,"== Changelog ==\n\n= ".M2C_CAPJS_VERSION.' ='),'journal des modifications du readme.txt à jour');
foreach(['pre_set_site_transient_update_plugins','plugins_api','upgrader_source_selection','upgrader_process_complete','load-update-core.php'] as $hook) check(!empty($hooks[$hook]),'crochet de mise à jour : '.$hook);
check(m2c_capjs_update_url()==='https://licences.mak2com.fr/updates/','serveur de mises à jour par défaut');
check(m2c_capjs_update_host()==='site.example.test','hôte transmis : minuscules, sans www ni chemin');

$metadata=['name'=>'CapJS Integration by Mak2com','slug'=>'m2c_wpcapjs','version'=>'1.6.0','download_url'=>'https://licences.mak2com.fr/updates/?action=download&slug=m2c_wpcapjs&site=site.example.test','homepage'=>'https://mak2com.fr','requires'=>'6.0','tested'=>'6.8','requires_php'=>'8.0','last_updated'=>'2026-10-23 10:00:00','sections'=>['description'=>'<p>Captcha</p><script>x()</script>','changelog'=>'<h4>1.6.0</h4><ul><li>Test</li></ul>']];
$answer=fn($data)=>['response'=>['code'=>200],'body'=>json_encode($data)];
$remote_get_response=$answer($metadata);
$remote=m2c_capjs_update_remote();
check($remote['version']==='1.6.0','métadonnées lues'); check(count($remote_get_calls)===1,'un seul appel');
parse_str(parse_url($remote_get_calls[0],PHP_URL_QUERY),$sent);
check($sent==['action'=>'get_metadata','slug'=>'m2c_wpcapjs','site'=>'site.example.test','version'=>M2C_CAPJS_VERSION,'wp'=>'6.8.1','php'=>PHP_VERSION],'requête sans clé : produit, site, versions seulement');
check(!str_contains($remote_get_calls[0],'license')&&!str_contains($remote_get_calls[0],'site_key')&&!str_contains($remote_get_calls[0],'secret'),'ni licence ni réglage CapJS transmis');
check($transient_ttls[M2C_CAPJS_UPDATE_TRANSIENT]===6*HOUR_IN_SECONDS,'cache de 6 h');
m2c_capjs_update_remote(); check(count($remote_get_calls)===1,'réponse en cache : pas de nouvel appel');
m2c_capjs_update_remote(true); check(count($remote_get_calls)===2,'vérification forcée : cache ignoré');

// Proposition de la mise à jour à WordPress.
$plugin='m2c_wpcapjs/m2c-wpcapjs.php';
$offered=m2c_capjs_update_inject((object)['response'=>[],'no_update'=>[]]);
check($offered->response[$plugin]->new_version==='1.6.0'&&$offered->response[$plugin]->package===$metadata['download_url'],'mise à jour proposée avec son archive');
check($offered->response[$plugin]->plugin===$plugin&&$offered->response[$plugin]->slug==='m2c_wpcapjs'&&$offered->response[$plugin]->requires_php==='8.0','élément conforme à WordPress');
check(!isset($offered->no_update[$plugin]),'proposée : absente de no_update');
check(isset(m2c_capjs_update_inject((object)[])->response[$plugin]),'transient sans tableaux : initialisés');
check(m2c_capjs_update_inject('autre')==='autre','valeur inattendue inchangée');
foreach(['1.5.0'=>'version identique','1.4.0'=>'version antérieure'] as $version=>$label) {
 m2c_capjs_update_clear_cache(); $remote_get_response=$answer(['version'=>$version]+$metadata);
 $current=m2c_capjs_update_inject((object)['response'=>[$plugin=>(object)[]],'no_update'=>[]]);
 check(!isset($current->response[$plugin])&&isset($current->no_update[$plugin]),'aucune mise à jour proposée : '.$label);
}
m2c_capjs_update_clear_cache(); $remote_get_response=$answer($metadata);

// Fenêtre « Voir les détails ».
check(m2c_capjs_update_details('x','plugin_information',(object)['slug'=>'autre'])==='x','détails d’un autre produit ignorés');
check(m2c_capjs_update_details('x','query_plugins',(object)['slug'=>'m2c_wpcapjs'])==='x','autre action ignorée');
$details=m2c_capjs_update_details(false,'plugin_information',(object)['slug'=>'m2c_wpcapjs']);
check($details->version==='1.6.0'&&$details->download_link===$metadata['download_url']&&str_contains($details->sections['changelog'],'<h4>1.6.0</h4>'),'détails de la version');
check(!str_contains($details->sections['description'],'<script'),'sections assainies');

// Réponses refusées : mauvaise version, archive ailleurs que sur le serveur, champs absents, erreur réseau ou HTTP.
foreach(['version douteuse'=>['version'=>'1.6<script>']+$metadata,'archive hors serveur'=>['download_url'=>'https://exemple.test/m2c.zip']+$metadata,'schéma inattendu'=>['download_url'=>'ftp://licences.mak2com.fr/m2c.zip']+$metadata,'sans archive'=>['download_url'=>'']+$metadata,'sans version'=>['version'=>'']+$metadata] as $label=>$bad) {
 m2c_capjs_update_clear_cache(); $remote_get_response=$answer($bad); check(m2c_capjs_update_remote()===null,'réponse refusée : '.$label);
}
foreach(['erreur réseau'=>new WP_Error(),'HTTP 403'=>['response'=>['code'=>403],'body'=>'{"error":"x"}'],'HTTP 404'=>['response'=>['code'=>404],'body'=>'{}'],'JSON invalide'=>['response'=>['code'=>200],'body'=>'<html>']] as $label=>$bad) {
 m2c_capjs_update_clear_cache(); $remote_get_response=$bad; check(m2c_capjs_update_remote()===null,'sans mise à jour : '.$label);
 check($transient_ttls[M2C_CAPJS_UPDATE_TRANSIENT]===HOUR_IN_SECONDS,'nouvel essai dans 1 h : '.$label);
 $untouched=(object)['response'=>[],'no_update'=>[]]; check(m2c_capjs_update_inject($untouched)==(object)['response'=>[],'no_update'=>[]],'site inchangé : '.$label);
}
// Autre serveur (essais) : l'archive doit venir du même hôte.
$update_url_override='http://127.0.0.1:8090/updates/'; add_filter('m2c_capjs_update_url',function($url) use (&$update_url_override){return $update_url_override;});
m2c_capjs_update_clear_cache(); $remote_get_response=$answer(['download_url'=>'http://127.0.0.1:8090/updates/?action=download&slug=m2c_wpcapjs']+$metadata);
check(m2c_capjs_update_remote()['version']==='1.6.0'&&str_starts_with($remote_get_calls[count($remote_get_calls)-1],'http://127.0.0.1:8090/updates/?'),'serveur d’essai accepté');
m2c_capjs_update_clear_cache(); $remote_get_response=$answer($metadata); check(m2c_capjs_update_remote()===null,'archive d’un autre hôte que le serveur d’essai refusée');
$update_url_override='https://licences.mak2com.fr/updates/'; m2c_capjs_update_clear_cache();

// « Vérifier à nouveau » de WordPress et fin de mise à jour : cache vidé.
$remote_get_response=$answer($metadata); m2c_capjs_update_remote(); check(get_site_transient(M2C_CAPJS_UPDATE_TRANSIENT)!==false,'cache rempli');
do_action('load-update-core.php'); check(get_site_transient(M2C_CAPJS_UPDATE_TRANSIENT)!==false,'sans force-check : cache conservé');
$_GET['force-check']='1'; do_action('load-update-core.php'); check(get_site_transient(M2C_CAPJS_UPDATE_TRANSIENT)===false,'force-check : cache vidé'); unset($_GET['force-check']);
m2c_capjs_update_remote(); do_action('upgrader_process_complete'); check(get_site_transient(M2C_CAPJS_UPDATE_TRANSIENT)===false,'mise à jour terminée : cache vidé');

// Dossier de l'archive (m2c_wpcapjs/) renommé comme le dossier réellement installé.
$wp_filesystem=new FixtureFilesystem(); $upgrade='/wp/upgrade/m2c-abc';
check(m2c_capjs_update_fix_source($upgrade.'/m2c_wpcapjs/',$upgrade,null,['plugin'=>$plugin])===$upgrade.'/m2c_wpcapjs/'&&$wp_filesystem->moves===[],'dossier déjà conforme : rien à faire');
check(m2c_capjs_update_fix_source($upgrade.'/autre/',$upgrade,null,['plugin'=>'autre/autre.php'])===$upgrade.'/autre/'&&$wp_filesystem->moves===[],'autre extension ignorée');
check(m2c_capjs_update_fix_source($upgrade.'/m2c_wpcapjs/',$upgrade,null,[])===$upgrade.'/m2c_wpcapjs/','installation sans extension ciblée ignorée');
$plugin_folder='m2c_wpcapjs-main';
check(m2c_capjs_update_fix_source($upgrade.'/m2c_wpcapjs/',$upgrade,null,['plugin'=>'m2c_wpcapjs-main/m2c-wpcapjs.php'])===$upgrade.'/m2c_wpcapjs-main/'&&$wp_filesystem->moves===[[$upgrade.'/m2c_wpcapjs/',$upgrade.'/m2c_wpcapjs-main/',true]],'dossier « -main » : archive renommée comme le dossier installé');
$wp_filesystem->ok=false; check(m2c_capjs_update_fix_source($upgrade.'/m2c_wpcapjs/',$upgrade,null,['plugin'=>'m2c_wpcapjs-main/m2c-wpcapjs.php']) instanceof WP_Error,'renommage impossible : mise à jour interrompue (pas de seconde copie)');
$wp_filesystem=null; $plugin_folder='m2c_wpcapjs';

// Coupure complète des mises à jour par wp-config.php.
define('M2C_CAPJS_DISABLE_UPDATES',true); $calls=count($remote_get_calls); m2c_capjs_update_clear_cache();
check(m2c_capjs_updates_disabled()&&m2c_capjs_update_remote()===null&&count($remote_get_calls)===$calls,'M2C_CAPJS_DISABLE_UPDATES : aucun appel au serveur');
check(m2c_capjs_update_inject((object)['response'=>[],'no_update'=>[]])==(object)['response'=>[],'no_update'=>[]],'mises à jour coupées : transient inchangé');
echo "PASS : $count contrôles PHP ; aucun accès réseau/base.\n";

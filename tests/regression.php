<?php
/**
 * Tests locaux des intégrations, sans base, réseau ni extension tierce active.
 * Exécution : php -d extension=sodium tests/regression.php (sodium sert à signer les licences de test)
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
function wp_remote_post($url,$request) { global $remote_calls,$remote_success,$post_handler,$post_calls; if ($post_handler) { $post_calls[]=[$url,$request]; return ($post_handler)($url,$request); } $remote_calls++; return ['response'=>['code'=>200],'body'=>json_encode(['success'=>$remote_success])]; }
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
define('HOUR_IN_SECONDS',3600); define('DAY_IN_SECONDS',86400); define('WEEK_IN_SECONDS',604800); define('MINUTE_IN_SECONDS',60);
$post_handler=null; $post_calls=[]; $home_url='https://www.Site.Example.test/blog'; $scheduled=[]; $transients_extra=[];
$transients=$transient_ttls=$remote_get_calls=[]; $remote_get_response=null; $plugin_folder='m2c_wpcapjs'; $wp_filesystem=null;
function wp_parse_url($url,$component=-1) { return parse_url($url,$component); }
function home_url() { global $home_url; return $home_url; }
function wp_date($format,$timestamp=null) { return date($format,$timestamp??time()); }
function current_time($type) { return date('Y-m-d H:i:s'); }
function untrailingslashit($value) { return rtrim($value,'/\\'); }
function register_deactivation_hook(...$args) {}
function wp_next_scheduled($hook) { global $scheduled; return $scheduled[$hook]??false; }
function wp_schedule_event($timestamp,$recurrence,$hook) { global $scheduled; $scheduled[$hook]=$timestamp; return true; }
function wp_clear_scheduled_hook($hook) { global $scheduled; unset($scheduled[$hook]); return 1; }
function get_site_transient($name) { global $transients; return $transients[$name]??false; }
function set_site_transient($name,$value,$expiration=0) { global $transients,$transient_ttls; $transients[$name]=$value; $transient_ttls[$name]=$expiration; return true; }
function delete_site_transient($name) { global $transients; unset($transients[$name]); return true; }
function add_query_arg($args,$url) { return $url.(str_contains($url,'?')?'&':'?').http_build_query($args); }
function wp_remote_get($url,$request=[]) { global $remote_get_calls,$remote_get_response; $remote_get_calls[]=$url; return $remote_get_response; }
function get_bloginfo($show) { return '6.8.1'; }
function plugin_basename($file) { global $plugin_folder; return $plugin_folder.'/'.basename($file); }
function wp_kses_post($value) { return strip_tags($value,'<h4><ul><li><p>'); }
// Administration (admin/license-section.php) : fonctions WordPress simulées.
function admin_url($path='') { return 'https://site.example.test/wp-admin/'.$path; }
function wp_nonce_field($action) { echo '<input type="hidden" name="_wpnonce" value="nonce">'; }
function submit_button($text='',$type='',$name='',$wrap=true) { echo '<button type="submit" class="'.$type.'">'.htmlspecialchars($text).'</button>'; }
function get_current_user_id() { return 1; }
function get_transient($name) { return false; }
function delete_transient($name) { return true; }
function set_transient($name,$value,$expiration=0) { return true; }
function sanitize_key($key) { return preg_replace('/[^a-z0-9_\\-]/','',strtolower($key)); }
function wp_unslash($value) { return $value; }
function current_user_can($capability) { return true; }
class FixtureFilesystem { public $moves=[]; public $ok=true; function move($from,$to,$overwrite=false) { $this->moves[]=[$from,$to,$overwrite]; return $this->ok; } }

add_action('wpml_register_single_string',function($context,$name,$value) use (&$registered_strings) {$registered_strings[$name]=[$context,$value];},10,3);
if (!function_exists('sodium_crypto_sign_keypair')) { fwrite(STDERR,"Extension sodium absente : lancez « php -d extension=sodium tests/regression.php ».\n"); exit(1); }
$license_pair=sodium_crypto_sign_keypair(); $license_secret=sodium_crypto_sign_secretkey($license_pair); $license_public=base64_encode(sodium_crypto_sign_publickey($license_pair));
$plugin_copy=sys_get_temp_dir().DIRECTORY_SEPARATOR.'m2c_wpcapjs_test_'.getmypid();
function copy_tree($from,$to) { @mkdir($to,0777,true); foreach (scandir($from) as $item) { if ($item==='.'||$item==='..') continue; is_dir("$from/$item") ? copy_tree("$from/$item","$to/$item") : copy("$from/$item","$to/$item"); } }
function remove_tree($dir) { if (!is_dir($dir)) return; foreach (scandir($dir) as $item) { if ($item==='.'||$item==='..') continue; is_dir("$dir/$item") ? remove_tree("$dir/$item") : @unlink("$dir/$item"); } @rmdir($dir); }
foreach(['includes','admin'] as $folder) copy_tree(dirname(__DIR__)."/$folder","$plugin_copy/$folder");
copy(dirname(__DIR__).'/m2c-wpcapjs.php',"$plugin_copy/m2c-wpcapjs.php");
register_shutdown_function('remove_tree',$plugin_copy);
$real_license=file_get_contents(dirname(__DIR__).'/includes/license.php');
preg_match("/function m2c_capjs_license_public_key\(\) \{\s*return '([^']*)';/",$real_license,$real_key);
$patched=preg_replace("/(function m2c_capjs_license_public_key\(\) \{\s*return ')[^']*(';)/",'${1}'.$license_public.'${2}',$real_license,1,$replaced);
if ($replaced!==1) { fwrite(STDERR,"Clé publique de licence introuvable dans includes/license.php.\n"); exit(1); }
file_put_contents("$plugin_copy/includes/license.php",$patched);
function b64u($value) { return rtrim(strtr(base64_encode($value),'+/','-_'),'='); }
function sign_payload($payload) { global $license_secret; $json=json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); return b64u($json).'.'.b64u(sodium_crypto_sign_detached($json,$license_secret)); }
function license_key($overrides=[]) { return 'M2CCJ-'.sign_payload(array_merge(['v'=>1,'id'=>'CJ-2026-0001','product'=>'m2c_wpcapjs','client'=>'Client test','domains'=>['client.fr'],'plan'=>'site','expires'=>null,'issued'=>'2026-10-30'],$overrides)); }
function license_statement($status='active',$overrides=[]) { return sign_payload(array_merge(['v'=>1,'type'=>'status','product'=>'m2c_wpcapjs','id'=>'CJ-2026-0001','site'=>'client.fr','status'=>$status,'expires'=>null,'checked_at'=>time(),'message'=>'Licence '.$status.'.'],$overrides)); }
require "$plugin_copy/m2c-wpcapjs.php";
do_action('plugins_loaded');
do_action('init');
check(count($registered_strings)===3,'trois chaînes enregistrées sans visiter de formulaire');
check(array_keys($registered_strings)===['widget_human_label','verification_complete','verification_failed'],'identifiants WPML historiques conservés');
check($registered_strings['widget_human_label'][0]==='capjs-integration','contexte WPML conservé');
check(array_values(array_unique($option_writes))==['m2c_capjs_license','m2c_capjs_version'],'à la mise à jour, seuls la licence (délai de reprise) et la version sont écrits : aucun réglage existant modifié');
check(($options['m2c_capjs_license']['grace_until']??0)>time()+29*86400&&$options['m2c_capjs_license']['grace_until']<=time()+30*86400&&$options['m2c_capjs_version']==='2.0.0','installation configurée avant la licence : délai de reprise de 30 jours');
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
$options['m2c_capjs_license']['key']='M2CCJ-fixture'; m2c_capjs_license_reset_cache();

$metadata=['name'=>'CapJS Integration by Mak2com','slug'=>'m2c_wpcapjs','version'=>'2.1.0','download_url'=>'https://licences.mak2com.fr/updates/?action=download&slug=m2c_wpcapjs&site=site.example.test','homepage'=>'https://mak2com.fr','requires'=>'6.0','tested'=>'6.8','requires_php'=>'8.0','last_updated'=>'2026-10-23 10:00:00','sections'=>['description'=>'<p>Captcha</p><script>x()</script>','changelog'=>'<h4>2.1.0</h4><ul><li>Test</li></ul>']];
$answer=fn($data)=>['response'=>['code'=>200],'body'=>json_encode($data)];
$remote_get_response=$answer($metadata);
$remote=m2c_capjs_update_remote();
check($remote['version']==='2.1.0','métadonnées lues'); check(count($remote_get_calls)===1,'un seul appel');
parse_str(parse_url($remote_get_calls[0],PHP_URL_QUERY),$sent);
check($sent==['action'=>'get_metadata','slug'=>'m2c_wpcapjs','license'=>'M2CCJ-fixture','site'=>'site.example.test','version'=>M2C_CAPJS_VERSION,'wp'=>'6.8.1','php'=>PHP_VERSION],'requête : produit, licence, site, versions seulement');
check(!str_contains($remote_get_calls[0],'site_key')&&!str_contains($remote_get_calls[0],'secret')&&!str_contains($remote_get_calls[0],'public-fixture'),'aucun réglage CapJS transmis');
check($transient_ttls[M2C_CAPJS_UPDATE_TRANSIENT]===6*HOUR_IN_SECONDS,'cache de 6 h');
m2c_capjs_update_remote(); check(count($remote_get_calls)===1,'réponse en cache : pas de nouvel appel');
m2c_capjs_update_remote(true); check(count($remote_get_calls)===2,'vérification forcée : cache ignoré');

// Proposition de la mise à jour à WordPress.
$plugin='m2c_wpcapjs/m2c-wpcapjs.php';
$offered=m2c_capjs_update_inject((object)['response'=>[],'no_update'=>[]]);
check($offered->response[$plugin]->new_version==='2.1.0'&&$offered->response[$plugin]->package===$metadata['download_url'],'mise à jour proposée avec son archive');
check($offered->response[$plugin]->plugin===$plugin&&$offered->response[$plugin]->slug==='m2c_wpcapjs'&&$offered->response[$plugin]->requires_php==='8.0','élément conforme à WordPress');
check(!isset($offered->no_update[$plugin]),'proposée : absente de no_update');
check(isset(m2c_capjs_update_inject((object)[])->response[$plugin]),'transient sans tableaux : initialisés');
check(m2c_capjs_update_inject('autre')==='autre','valeur inattendue inchangée');
foreach(['2.0.0'=>'version identique','1.9.0'=>'version antérieure'] as $version=>$label) {
 m2c_capjs_update_clear_cache(); $remote_get_response=$answer(['version'=>$version]+$metadata);
 $current=m2c_capjs_update_inject((object)['response'=>[$plugin=>(object)[]],'no_update'=>[]]);
 check(!isset($current->response[$plugin])&&isset($current->no_update[$plugin]),'aucune mise à jour proposée : '.$label);
}
m2c_capjs_update_clear_cache(); $remote_get_response=$answer($metadata);

// Fenêtre « Voir les détails ».
check(m2c_capjs_update_details('x','plugin_information',(object)['slug'=>'autre'])==='x','détails d’un autre produit ignorés');
check(m2c_capjs_update_details('x','query_plugins',(object)['slug'=>'m2c_wpcapjs'])==='x','autre action ignorée');
$details=m2c_capjs_update_details(false,'plugin_information',(object)['slug'=>'m2c_wpcapjs']);
check($details->version==='2.1.0'&&$details->download_link===$metadata['download_url']&&str_contains($details->sections['changelog'],'<h4>2.1.0</h4>'),'détails de la version');
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
check(m2c_capjs_update_remote()['version']==='2.1.0'&&str_starts_with($remote_get_calls[count($remote_get_calls)-1],'http://127.0.0.1:8090/updates/?'),'serveur d’essai accepté');
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

// ---- Licence mak2com (includes/license.php) : clés signées avec une paire de test, serveur simulé ----
$real_key=$real_key[1]; $placeholder='REMPLACER_PAR_LA_CLE_PUBLIQUE_DU_PRODUIT';
check($real_key===$placeholder||(base64_decode($real_key,true)!==false&&strlen(base64_decode($real_key,true))===32),'clé publique de licence : valeur d’attente ou clé Ed25519 valide (32 octets)');
if ($real_key===$placeholder) echo "AVERTISSEMENT : clé publique du produit non renseignée dans includes/license.php (l'action GitHub refuse la publication tant qu'elle ne l'est pas).\n";
check(m2c_capjs_license_public_key()===$license_public,'les tests utilisent une copie du plugin avec une clé publique de test');

$good=license_key(); $state=function() { m2c_capjs_license_reset_cache(); $s=m2c_capjs_license_status(); return $s['state'].($s['reason']?':'.$s['reason']:''); };
$decoded=m2c_capjs_license_decode($good);
check($decoded['id']==='CJ-2026-0001'&&$decoded['domains']===['client.fr']&&$decoded['expires']===null&&$decoded['plan']==='site','clé valide décodée');
check(m2c_capjs_license_decode("  ".chunk_split($good,20,"\n  "))!==null,'espaces et retours à la ligne ignorés');
check(m2c_capjs_license_decode('M2CCB-'.substr($good,6))===null,'mauvais préfixe (autre produit)');
check(m2c_capjs_license_decode(license_key(['product'=>'m2c-cookies-banner']))===null,'clé d’un autre produit refusée');
check(m2c_capjs_license_decode(license_key(['domains'=>[]]))===null&&m2c_capjs_license_decode(license_key(['id'=>'']))===null,'charge utile incomplète refusée');
[$payload_part,$signature_part]=explode('.',substr($good,6));
$forged=json_decode(m2c_capjs_license_b64url_decode($payload_part),true); $forged['domains']=['pirate.fr'];
check(m2c_capjs_license_decode('M2CCJ-'.b64u(json_encode($forged)).'.'.$signature_part)===null,'charge utile modifiée : signature refusée');
check(m2c_capjs_license_decode('M2CCJ-'.$payload_part.'.'.b64u(str_repeat('a',64)))===null&&m2c_capjs_license_decode('M2CCJ-'.$payload_part)===null&&m2c_capjs_license_decode('M2CCJ-abc.def')===null&&m2c_capjs_license_decode('')===null,'signatures et formats invalides refusés');
$other_pair=sodium_crypto_sign_keypair(); $foreign_json='{"id":"X","domains":["client.fr"]}';
check(m2c_capjs_license_decode('M2CCJ-'.b64u($foreign_json).'.'.b64u(sodium_crypto_sign_detached($foreign_json,sodium_crypto_sign_secretkey($other_pair))))===null,'clé signée par une autre paire refusée');

foreach([['client.fr',['client.fr'],true],['shop.client.fr',['client.fr'],true],['www.client.fr',['client.fr'],true],['client.fr',['*.client.fr'],true],['autre.fr',['client.fr'],false],['monclient.fr',['client.fr'],false],['client.fr.pirate.com',['client.fr'],false],['',['client.fr'],false]] as [$h,$d,$e]) check(m2c_capjs_license_domain_matches($h,$d)===$e,'domaine couvert : '.$h.' / '.$d[0]);
foreach(['localhost','127.0.0.1','monsite.local','x.test','dev.client.fr','staging.client.fr','preprod.client.fr','recette.client.fr','test.client.fr','demo.client.fr','a.ddev.site'] as $h) check(m2c_capjs_license_is_dev_host($h),'développement ou recette : '.$h);
foreach(['client.fr','www.client.fr','device.client.fr','testing.fr'] as $h) check(!m2c_capjs_license_is_dev_host($h),'production : '.$h);

// Nouveau site en production sans clé : aucun captcha, formulaires utilisables.
$home_url='https://www.Client.fr/'; $options['m2c_capjs_license']=[]; $options['m2c_capjs_version']='2.0.0'; $post_calls=[];
check(m2c_capjs_license_host()==='client.fr','hôte de licence : minuscules, sans www');
check($state()==='missing'&&!m2c_capjs_license_runs()&&!m2c_capjs_license_premium(),'sans clé : captcha et mises à jour inactifs');
check(str_contains(m2c_capjs_license_status()['message'],'captcha est désactivé'),'message explicite : captcha désactivé sans clé');
$cf7=M2C_WPCF7_CAPJS::get_instance(); reset_assets();
check(!$cf7->is_active()&&!$wc->is_active(),'Contact Form 7 et WooCommerce inactifs sans licence');
check(m2c_capjs_form_tag_handler($tag)===''&&capture([$wc,'display_capjs_login'])===''&&$scripts===[],'aucun widget ni script CapJS sans licence');
m2c_capjs_enqueue_widget(); do_action('wp_enqueue_scripts'); check($scripts===[]&&$styles===[],'ni script ni style de contraste sans licence');
check($field->validate(['value'=>''],[])===false,'Ninja Forms : formulaire non bloqué sans licence');
$_POST=[]; check(m2c_capjs_verify_response(false,new FixtureSubmission(new FixtureForm('[text name] [capjs]')))===false,'Contact Form 7 : formulaire non bloqué sans licence');
check($post_calls===[],'aucune requête vers le serveur de licences sans clé');
$notice=capture(fn()=>do_action('admin_notices'));
check(str_contains($notice,'notice-error')&&str_contains($notice,'captcha est désactivé')&&str_contains($notice,'m2c-capjs#m2c-capjs-licence'),'avertissement dans l’administration : captcha désactivé');
$page=capture('m2c_capjs_license_section');
check(str_contains($page,'<textarea id="m2c-capjs-key"')&&str_contains($page,'name="op" value="activate"')&&!str_contains($page,'Retirer la clé'),'écran sans clé : saisie de la clé');

// Licence valide, puis fin de licence : le captcha continue, les mises à jour s'arrêtent.
$options['m2c_capjs_license']=['key'=>$good];
check($state()==='valid'&&m2c_capjs_license_runs()&&m2c_capjs_license_premium(),'clé valide pour le site : captcha et mises à jour');
check($options['m2c_capjs_license']['ever_activated']===true&&$options['m2c_capjs_license']['verified']['data']['id']==='CJ-2026-0001','vérification mémorisée, site marqué comme activé');
reset_assets(); check($cf7->is_active()&&$wc->is_active()&&str_contains(m2c_capjs_form_tag_handler($tag),'capjs-widget'),'licence valide : widget affiché');
check($post_calls===[]&&count($remote_get_calls)>0,'affichage sans appel au serveur de licences');
$notice=capture(fn()=>do_action('admin_notices')); check($notice==='','aucun avertissement avec une licence valide');
$options['m2c_capjs_license']=['key'=>license_key(['client'=>'<b>Client</b>','expires'=>date('Y-m-d',strtotime('+30 days'))])];
m2c_capjs_license_reset_cache(); $page=capture('m2c_capjs_license_section'); m2c_capjs_license_reset_cache();
check(str_contains($page,'CJ-2026-0001')&&str_contains($page,'&lt;b&gt;Client&lt;/b&gt;')&&!str_contains($page,'<b>Client')&&str_contains($page,'Vérifier maintenant')&&str_contains($page,'Retirer la clé de ce site'),'écran licence : détails échappés, vérification et retrait');
$options['m2c_capjs_license']=['key'=>license_key(['expires'=>date('Y-m-d',strtotime('-1 day'))])];
check($state()==='ended:expired'&&!m2c_capjs_license_runs()&&!m2c_capjs_license_premium(),'licence expirée : captcha désactivé, mises à jour arrêtées');
reset_assets(); check(!$cf7->is_active()&&!$wc->is_active()&&m2c_capjs_form_tag_handler($tag)===''&&capture([$wc,'display_capjs_login'])===''&&$scripts===[],'licence expirée : aucun widget ni script');
check($field->validate(['value'=>''],[])===false&&m2c_capjs_verify_response(false,new FixtureSubmission(new FixtureForm('[text name] [capjs]')))===false,'licence expirée : Ninja Forms et Contact Form 7 ne bloquent pas les formulaires');
$notice=capture(fn()=>do_action('admin_notices')); check(str_contains($notice,'notice-error')&&str_contains($notice,'Licence expirée'),'licence expirée : avertissement dans l’administration');
$options['m2c_capjs_license']=['key'=>$good]; check($state()==='valid'&&m2c_capjs_license_runs()&&$cf7->is_active(),'licence renouvelée (nouvelle clé) : captcha rétabli');
$home_url='https://autre.fr/'; $options['m2c_capjs_license']=['key'=>$good]; check($state()==='ended:domain_mismatch'&&!m2c_capjs_license_runs(),'autre domaine : captcha désactivé');
$home_url='https://boutique.client.fr/'; check($state()==='valid','sous-domaine couvert'); $home_url='https://www.Client.fr/';
$remote=fn($status,$id='CJ-2026-0001')=>['key'=>$good,'remote'=>['id'=>$id,'status'=>$status,'message'=>'x','checked_at'=>time()]];
foreach(['revoked','suspended','expired','domain_mismatch'] as $remote_status) { $options['m2c_capjs_license']=$remote($remote_status); check($state()==='ended:'.$remote_status&&!m2c_capjs_license_runs()&&!m2c_capjs_license_premium(),'décision du serveur : '.$remote_status); }
$options['m2c_capjs_license']=$remote('active'); check($state()==='valid','réponse « active » du serveur');
$options['m2c_capjs_license']=$remote('revoked','AUTRE-ID'); check($state()==='valid','réponse du serveur concernant une autre licence ignorée');
$options['m2c_capjs_license']=['key'=>'M2CCJ-illisible']; check($state()==='invalid'&&!m2c_capjs_license_runs(),'clé illisible sur un nouveau site : inactif');
$options['m2c_capjs_license']=['ever_activated'=>true]; check($state()==='ended:removed'&&!m2c_capjs_license_runs()&&!m2c_capjs_license_premium(),'clé retirée après activation : captcha désactivé');
$options['m2c_capjs_license']=['ever_activated'=>true,'key'=>'M2CCJ-illisible']; check($state()==='ended:removed'&&!m2c_capjs_license_runs(),'clé illisible après activation : captcha désactivé');
$home_url='https://monsite.local/'; $options['m2c_capjs_license']=[]; check($state()==='dev'&&m2c_capjs_license_runs()&&m2c_capjs_license_premium(),'développement sans clé : tout fonctionne');
$options['m2c_capjs_license']=['key'=>'M2CCJ-illisible']; check($state()==='dev','développement avec une clé illisible : tout fonctionne'); $home_url='https://www.Client.fr/';

// Passage à la 2.0.0 : un site déjà configuré garde son captcha 30 jours pour saisir sa clé, une installation neuve non.
$configured_options=$options;
$options['m2c_capjs_license']=[]; $options['m2c_capjs_version']='1.4.2'; $option_writes=[]; m2c_capjs_license_upgrade();
check($state()==='grace'&&m2c_capjs_license_runs()&&!m2c_capjs_license_premium()&&$options['m2c_capjs_version']==='2.0.0','site déjà configuré : captcha conservé pendant le délai de reprise, sans mises à jour');
check(str_contains(m2c_capjs_license_status()['message'],'avant l’arrivée de la licence')&&m2c_capjs_license_status()['grace_until']===$options['m2c_capjs_license']['grace_until'],'message du délai de reprise et date de fin');
$notice=capture(fn()=>do_action('admin_notices')); check(str_contains($notice,'notice-warning')&&str_contains($notice,'sinon il sera désactivé'),'délai de reprise : avertissement jusqu’à la saisie de la clé');
$options['m2c_capjs_license']['key']='M2CCJ-illisible'; check($state()==='grace'&&m2c_capjs_license_runs(),'clé illisible pendant le délai de reprise : captcha conservé'); unset($options['m2c_capjs_license']['key']);
$options['m2c_capjs_license']['grace_until']=time()-10; check($state()==='ended:grace_ended'&&!m2c_capjs_license_runs()&&!$cf7->is_active(),'délai de reprise terminé sans clé : captcha désactivé');
$options['m2c_capjs_license']['grace_until']=time()+86400; check($state()==='grace','délai de reprise en cours');
reset_assets(); check($cf7->is_active()&&$wc->is_active(),'Contact Form 7 et WooCommerce restent actifs pendant le délai de reprise');
$option_writes=[]; m2c_capjs_license_upgrade(); check($option_writes===[],'passage exécuté une seule fois');
$options['m2c_capjs_license']['key']=$good; check($state()==='valid','la clé saisie ensuite active la licence');
$options['m2c_capjs_license']=[]; $options['m2c_capjs_version']='0'; unset($options['m2c_capjs_server_url'],$options['m2c_capjs_site_key']); m2c_capjs_license_upgrade();
check($state()==='missing'&&empty($options['m2c_capjs_license']['grace_until']),'installation neuve : aucune tolérance, clé nécessaire');
$options=$configured_options; $options['m2c_capjs_license']=[]; $options['m2c_capjs_version']='2.0.0'; $options['m2c_capjs_license']['key']=$good;

// Vérification en ligne : réponses signées, rejeu, panne du serveur.
$ok_body=fn($statement)=>['response'=>['code'=>200],'body'=>json_encode(['status'=>'active','message'=>'ok','statement'=>$statement])];
$options['m2c_capjs_license']=['key'=>$good]; m2c_capjs_license_reset_cache();
$post_handler=fn($url,$request)=>$ok_body(license_statement('active')); $post_calls=[];
$result=m2c_capjs_license_check(true);
check($result['ok']===true&&$post_calls[0][0]==='https://licences.mak2com.fr/api/v1/check','vérification en ligne acceptée');
check($post_calls[0][1]['body']==['product'=>'m2c_wpcapjs','key'=>$good,'site'=>'client.fr','version'=>M2C_CAPJS_VERSION,'wp'=>'6.8.1','php'=>PHP_VERSION],'requête : produit, clé, site, versions');
check($options['m2c_capjs_license']['remote']['status']==='active'&&$options['m2c_capjs_license']['last_attempt']>0,'réponse et date de tentative mémorisées');
$post_handler=fn($url,$request)=>$ok_body(license_statement('revoked',['checked_at'=>time()+10]));
m2c_capjs_license_check(true); check($state()==='ended:revoked'&&!m2c_capjs_license_runs(),'révocation signée appliquée : captcha désactivé');
$stored_remote=$options['m2c_capjs_license']['remote'];
$other_signed=fn($payload)=>(function($json) use ($other_pair) { return b64u($json).'.'.b64u(sodium_crypto_sign_detached($json,sodium_crypto_sign_secretkey($other_pair))); })(json_encode($payload));
$bad_statements=[
 'autre site'=>license_statement('active',['site'=>'autre.fr','checked_at'=>time()+20]),
 'autre licence'=>license_statement('active',['id'=>'AUTRE-ID','checked_at'=>time()+20]),
 'autre produit'=>license_statement('active',['product'=>'m2c-cookies-banner','checked_at'=>time()+20]),
 'mauvais type'=>license_statement('active',['type'=>'agent','checked_at'=>time()+20]),
 'état inconnu'=>license_statement('inventé',['checked_at'=>time()+20]),
 'rejeu d’une ancienne réponse'=>license_statement('active',['checked_at'=>time()-1000]),
 'signature d’une autre paire'=>$other_signed(['v'=>1,'type'=>'status','product'=>'m2c_wpcapjs','id'=>'CJ-2026-0001','site'=>'client.fr','status'=>'active','checked_at'=>time()+20,'message'=>'x']),
 'absence de réponse signée'=>'',
];
foreach($bad_statements as $label=>$statement) {
 $post_handler=fn($url,$request)=>$ok_body($statement); $result=m2c_capjs_license_check(true);
 check($result['ok']===false&&$options['m2c_capjs_license']['remote']===$stored_remote&&$state()==='ended:revoked','réponse refusée sans effet : '.$label);
}
foreach(['serveur injoignable'=>new WP_Error(),'HTTP 500'=>['response'=>['code'=>500],'body'=>'erreur'],'JSON invalide'=>['response'=>['code'=>200],'body'=>'<html>']] as $label=>$answer_bad) {
 $post_handler=fn($url,$request)=>$answer_bad; $result=m2c_capjs_license_check(true);
 check($result['ok']===false&&$options['m2c_capjs_license']['remote']===$stored_remote,'panne sans effet sur l’état : '.$label);
}
$post_handler=fn($url,$request)=>$ok_body(license_statement('active',['checked_at'=>time()+30])); m2c_capjs_license_check(true); check($state()==='valid','licence rétablie par une réponse plus récente');

// Fréquence : une semaine si tout va bien, un jour si la licence pose problème.
$status_remote=fn($status,$ago)=>['key'=>$good,'last_attempt'=>time()-$ago,'remote'=>['id'=>'CJ-2026-0001','status'=>$status,'message'=>'x','checked_at'=>time()-$ago]];
$post_handler=fn($url,$request)=>$ok_body(license_statement('active',['checked_at'=>time()+40]));
foreach([['active',3600,0],['active',8*86400,1],['suspended',3600,0],['suspended',2*86400,1]] as [$remote_state,$ago,$expected]) {
 $options['m2c_capjs_license']=$status_remote($remote_state,$ago); $post_calls=[]; m2c_capjs_license_reset_cache(); m2c_capjs_license_maybe_check();
 check(count($post_calls)===$expected,'vérification planifiée : '.$remote_state.' il y a '.round($ago/3600).' h → '.$expected.' appel');
}

// Activation et retrait de la clé.
$options['m2c_capjs_license']=[]; set_site_transient(M2C_CAPJS_UPDATE_TRANSIENT,['data'=>null],3600); $post_calls=[];
$post_handler=fn($url,$request)=>$ok_body(license_statement('active',['checked_at'=>time()+50]));
$activated=m2c_capjs_license_activate("  ".$good." \n");
check($activated['state']==='valid'&&$options['m2c_capjs_license']['key']===$good,'activation : clé nettoyée, licence valide');
check(count($post_calls)===1&&str_ends_with($post_calls[0][0],'/api/v1/check')&&get_site_transient(M2C_CAPJS_UPDATE_TRANSIENT)===false,'activation : vérification en ligne et cache des mises à jour vidé');
$post_calls=[]; $replaced=m2c_capjs_license_activate('M2CCJ-illisible');
check($replaced['state']==='ended'&&$post_calls===[]&&!m2c_capjs_license_premium()&&!m2c_capjs_license_runs(),'clé illisible après activation : aucun appel, captcha désactivé');
m2c_capjs_license_activate($good); $post_calls=[]; set_site_transient(M2C_CAPJS_UPDATE_TRANSIENT,['data'=>null],3600);
$removed=m2c_capjs_license_deactivate();
check($removed['state']==='ended'&&$removed['reason']==='removed'&&!isset($options['m2c_capjs_license']['key'])&&!m2c_capjs_license_runs(),'retrait de la clé : captcha désactivé');
check(count($post_calls)===1&&str_ends_with($post_calls[0][0],'/api/v1/deactivate')&&$post_calls[0][1]['blocking']===false&&$post_calls[0][1]['body']==['product'=>'m2c_wpcapjs','key'=>$good,'site'=>'client.fr']&&get_site_transient(M2C_CAPJS_UPDATE_TRANSIENT)===false,'retrait : serveur prévenu sans attendre, cache vidé');
$post_handler=null;

// Mises à jour : seulement avec une licence valide, clé et site transmis.
$remote_get_response=$answer($metadata); $options['m2c_capjs_license']=[]; m2c_capjs_update_clear_cache(); $calls=count($remote_get_calls);
check(m2c_capjs_update_remote()===null&&count($remote_get_calls)===$calls,'sans licence : aucun appel de mise à jour');
$options['m2c_capjs_license']=['key'=>license_key(['expires'=>date('Y-m-d',strtotime('-1 day'))])]; m2c_capjs_license_reset_cache();
check(m2c_capjs_update_remote()===null&&count($remote_get_calls)===$calls,'licence expirée : aucun appel de mise à jour');
$options['m2c_capjs_license']=['key'=>$good]; m2c_capjs_license_reset_cache();
check(m2c_capjs_update_remote()!==null&&count($remote_get_calls)===$calls+1,'licence valide : mises à jour interrogées');
parse_str(parse_url($remote_get_calls[$calls],PHP_URL_QUERY),$sent_license); check($sent_license['license']===$good&&$sent_license['site']==='client.fr','licence et site transmis au serveur de mises à jour');
m2c_capjs_update_clear_cache();

// Planification quotidienne (jamais de requête pendant l'affichage d'une page).
$scheduled=[]; m2c_capjs_license_schedule(); $first=$scheduled['m2c_capjs_license_daily']??null; m2c_capjs_license_schedule();
check($first>time()&&$scheduled['m2c_capjs_license_daily']===$first,'tâche quotidienne planifiée une seule fois');
check($hooks['m2c_capjs_license_daily'][10][0][0]==='m2c_capjs_license_maybe_check'&&!empty($hooks['admin_init'])&&!empty($hooks['admin_post_m2c_capjs_license']),'crochets : tâche quotidienne, planification en administration, formulaires de licence');
m2c_capjs_license_unschedule(); check(!isset($scheduled['m2c_capjs_license_daily']),'tâche retirée à la désactivation');
$options['m2c_capjs_license']=[]; m2c_capjs_license_reset_cache(); check(m2c_capjs_license_public_status()['key_hint']==='','aucune clé : pas d’aperçu'); $options['m2c_capjs_license']=['key'=>$good];
check(m2c_capjs_license_public_status()['key_hint']===substr($good,0,10).'…'.substr($good,-6),'aperçu de la clé : début et fin seulement');

// Clé définie dans wp-config.php.
$home_url='https://www.Client.fr/'; define('M2CCJ_LICENSE_KEY',$good); $options['m2c_capjs_license']=[];
check(m2c_capjs_license_from_constant()&&m2c_capjs_license_key()===$good&&$state()==='valid','clé de wp-config.php prise en compte');
m2c_capjs_license_reset_cache(); $page=capture('m2c_capjs_license_section'); m2c_capjs_license_reset_cache();
check(str_contains($page,'M2CCJ_LICENSE_KEY')&&!str_contains($page,'<textarea')&&!str_contains($page,'value="deactivate"'),'écran : clé de wp-config.php, ni saisie ni retrait');
$options['m2c_capjs_license']=[];

// Coupure complète des mises à jour par wp-config.php.
define('M2C_CAPJS_DISABLE_UPDATES',true); $calls=count($remote_get_calls); m2c_capjs_update_clear_cache();
check(m2c_capjs_updates_disabled()&&m2c_capjs_update_remote()===null&&count($remote_get_calls)===$calls,'M2C_CAPJS_DISABLE_UPDATES : aucun appel au serveur');
check(m2c_capjs_update_inject((object)['response'=>[],'no_update'=>[]])==(object)['response'=>[],'no_update'=>[]],'mises à jour coupées : transient inchangé');
echo "PASS : $count contrôles PHP ; aucun accès réseau/base.\n";

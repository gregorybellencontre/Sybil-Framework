<?php
namespace Sybil;

require_once('Config.php');

define('FRAMEWORK_NAME','Sybil Framework');
define('ROOT_DIRECTORY',Config::$root_directory == '' ? Config::$root_directory : Config::$root_directory . '/');

define('BASE_URL',isset($_SERVER['SERVER_NAME']) ? ('http://'.$_SERVER['SERVER_NAME'].'/'.(ROOT_DIRECTORY != '' ? ROOT_DIRECTORY . '/' : '')) : '');
define('WEB_ROOT',ROOT_DIRECTORY != '' ? '/' . ROOT_DIRECTORY . '/' : '/');
define('FILE_ROOT',$_SERVER['DOCUMENT_ROOT'].'/'.(ROOT_DIRECTORY != '' ? ROOT_DIRECTORY . '/' : ''));

define('APP',FILE_ROOT.'app/');
define('BUNDLE',FILE_ROOT.'bundle/');
define('VENDOR',FILE_ROOT.'vendor/');
define('WEB',FILE_ROOT.'public/');

define('CACHE',APP.'cache/');

define('CORE',VENDOR.'sybil/framework/');
define('CORE_SRC',CORE.'src/Sybil/');
define('PLUGIN',VENDOR.'sybil/plugin/');

define('RESOURCE',WEB.'resource/');
define('THEME',APP.'theme/');
define('UPLOAD',WEB.'upload/');
define('UPLOAD_ROOT',WEB_ROOT.'web/upload/');
<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();

/***********************/
//Configurations
/***********************/
$app['debug'] = true;
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../views',
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app['jsondb.config'] = array(
    'database_path'     => '../files/',
    'file_extension'    => '.json',
    'database_files'    => array(
        'deployements' => 'Entities\Deployement'
    ),
);

/***********************/
// Helpers
/***********************/
$app['jsondb.getFilePath'] = $app->protect(function ($file) use ($app) {
    if (!array_key_exists($file, $app['jsondb.config']['database_files'])) {
        $app->abort(404, "File $file is not configured.");
        return false;
    }

    $file_path = $app['jsondb.config']['database_path'].$file.$app['jsondb.config']['file_extension'];
    if (!file_exists($file_path)) {
        $creation_link = $app["url_generator"]->generate("file_create", array("file" => $file));
        $app['session']->getFlashBag()->add('warning', 'The file '.$file_path.' doesn\'t exist. <a href="'.$creation_link.'">Create it now</a>.');
        return false;
    }

    return $file_path;
});

$app['jsondb.getPropertiesAttributes'] = $app->protect(function ($class) use ($app) {
    return $class::getPropertiesAttributes();
});

$app['jsondb.getEntitiesFromFile'] = $app->protect(function ($class_name, $file_path) use ($app) {
    $entities = array();

    $file_data = file_get_contents($file_path);
    $json_data = json_decode($file_data, true);
    if (is_array($json_data)) {
        foreach ($json_data as $data) {
            $entity = new $class_name($data);
            $entities[] = $entity;
        }
    }

    return $entities;
});

$app['jsondb.backupFile'] = $app->protect(function ($file, $force = false) use ($app) {
    $keep_backup        = 10;
    $backup_path        = $app['jsondb.config']['database_path'].'backup/';
    $file_path          = $app['jsondb.config']['database_path'].$file.$app['jsondb.config']['file_extension'];
    $file_path_backup   = $backup_path.$file.$app['jsondb.config']['file_extension'].'.'.(date("Y-m-d_H:i:s")).'.backup';

    if (!file_exists($file_path)) {
        return false;
    }
    if (!is_dir($backup_path)) {
        mkdir($backup_path);
    }

    #Backup file
    $backups_today_mask = $backup_path.$file.$app['jsondb.config']['file_extension'].'.'.(date("Y-m-d")).'*.backup';
    //Create maximum 1 backup per day, except when force is true
    if ($force || count(glob($backups_today_mask)) == 0) {
        copy($file_path, $file_path_backup);
    }

    #Rotate backups older than 1 week
    $backups_mask   = $backup_path.$file.$app['jsondb.config']['file_extension'].'*.backup';
    $backups        = glob($backups_mask);
    $offset         = count($backups) - $keep_backup;
    if ($offset > 0) {
        $backups_to_delete = array_slice($backups, 0, $offset);
        foreach ($backups_to_delete as $backup_to_delete) {
            unlink($backup_to_delete);
        }
    }

    return true;
});

/***********************/
//Routing
/***********************/
$app->get('/', function() use($app) {
    return $app['twig']->render('files_list.html', array(
        'database_files' => array_keys($app['jsondb.config']['database_files']),
    ));
});

$app->get('/{file}/view', function($file) use($app) {
    $entities   = array();

    $class_name = $app['jsondb.config']['database_files'][$file];

    if ($file_path = $app['jsondb.getFilePath']($file)) {
        $entities = $app['jsondb.getEntitiesFromFile']($class_name, $file_path);
    }

    $attributes = $app['jsondb.getPropertiesAttributes']($class_name);

    return $app['twig']->render('file_view.html', array(
        'current_file'  => $file,
        'attributes'    => $attributes,
        'entities'      => $entities,
    ));
})->bind('file_view');

$app->get('/{file}/download', function($file) use($app) {
    $class_name = $app['jsondb.config']['database_files'][$file];
    $file_path  = $app['jsondb.getFilePath']($file);

    $stream = function () use ($file_path) {
        readfile($file_path);
    };
 
    return $app->stream($stream, 200, array(
        'Content-Type' => 'text/json',
        'Content-length' => filesize($file_path),
        'Content-Disposition' => 'attachment; filename="'.$file.'.json"' 
        ));
})->bind('file_download');

$app->get('/{file}/edit', function($file) use($app) {
    $entities = array();

    $class_name = $app['jsondb.config']['database_files'][$file];

    if ($file_path = $app['jsondb.getFilePath']($file)) {
        $entities = $app['jsondb.getEntitiesFromFile']($class_name, $file_path);
    }

    $attributes = $app['jsondb.getPropertiesAttributes']($class_name);

    return $app['twig']->render('file_edit.html', array(
        'current_file'  => $file,
        'attributes'    => $attributes,
        'entities'      => $entities,
    ));
})->bind('file_edit');

$app->get('/{file}/create', function(Request $request, $file) use($app) {
    $file_path = $app['jsondb.config']['database_path'].$file.$app['jsondb.config']['file_extension'];

    if (!file_exists($file_path)) {
        touch($file_path);
    } else {
        $app['session']->getFlashBag()->add('danger', 'The file ' . $file_path . ' already exist.');
    }

    return $app->redirect($app["url_generator"]->generate("file_view", array("file" => $file)));
})->bind('file_create');

$app->post('/{file}/edit', function(Request $request, $file) use($app) {
    $entities   = array();
    $class_name = $app['jsondb.config']['database_files'][$file];
    $file_path  = $app['jsondb.getFilePath']($file);
    $attributes = $app['jsondb.getPropertiesAttributes']($class_name);

    if (isset($_POST['id'])) {
        foreach ($_POST['id'] as $id) {
            $entity = new $class_name();
            $data = array();
            foreach ($attributes as $attribute) {
                if (isset($attribute['type']) && $attribute['type'] == 'boolean') {
                    $data[$attribute['name']] = (isset($_POST[$attribute['name']][$id]) && $_POST[$attribute['name']][$id])? true : false;
                } else {
                    if (isset($_POST[$attribute['name']][$id])) {
                        $value = $_POST[$attribute['name']][$id];
                    } else {
                        $value = null;
                    }
                    $data[$attribute['name']] = $value;
                }
            }
            $entity->set($data);
            $entities[] = $entity;
        }
        file_put_contents($file_path, json_encode($entities), LOCK_EX);

        //Backup file
        $app['jsondb.backupFile']($file);
    }

    return $app->redirect($app["url_generator"]->generate("file_view", array("file" => $file)));
});

$app->get('/{file}/api/add', function(Request $request, $file) use($app) {
    $class_name = $app['jsondb.config']['database_files'][$file];

    if ($file_path = $app['jsondb.getFilePath']($file)) {
        $entities = $app['jsondb.getEntitiesFromFile']($class_name, $file_path);
    }

    $attributes = $app['jsondb.getPropertiesAttributes']($class_name);
    if (isset($_GET['entities'])) {
        $new_entities = json_decode($_GET['entities'], true);
        foreach ($new_entities as $new_entity) {
            $entity = new $class_name($new_entity);
            $entities[] = $entity;
        }

        file_put_contents($file_path, json_encode($entities), LOCK_EX);

        //Backup file
        $app['jsondb.backupFile']($file);

        $app['session']->getFlashBag()->add('success', 'New data have been saved.');
    } else {
        $app['session']->getFlashBag()->add('danger', 'Missing <strong>entities</strong> parameter.');
    }

    return $app->redirect($app["url_generator"]->generate("file_view", array("file" => $file)));
});

$app->get('/{file}/backup', function($file) use($app) {
    $entities   = array();

    $class_name = $app['jsondb.config']['database_files'][$file];
    $file_path  = $app['jsondb.getFilePath']($file);

    if ($app['jsondb.backupFile']($file)) {
        $app['session']->getFlashBag()->add('success', 'Backup of '.$file.' succeed.');
    } else {
        $app['session']->getFlashBag()->add('danger', 'Backup of '.$file.' failed.');
    }

    return $app->redirect($app["url_generator"]->generate("file_view", array("file" => $file)));
})->bind('file_backup');

//Create database path if not existing
if (!is_dir($app['jsondb.config']['database_path'])) {
    mkdir($app['jsondb.config']['database_path']);
}

//Start the app
$app->run();

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
// Closures
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
$app['jsondb.getClassAttributes'] = $app->protect(function ($class) use ($app) {
    $attributes = array();

    $reflect = new ReflectionClass($class);
    $properties = $reflect->getProperties();
    foreach ($properties as $property) {
        $attribute = array();
        $attribute['name'] = $property->getName();

        $doc_comment = $property->getDocComment();
        preg_match_all('/@JsonDB\((\w+)="(\w+)"\)/', $doc_comment, $matches);
        if ($matches) {
            foreach ($matches[1] as $i => $match) {
                $attribute[$matches[1][$i]] = $matches[2][$i];
            }
        }

        $attributes[] = $attribute;
    }

    return $attributes;
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
    $json_data  = array();

    if ($file_path = $app['jsondb.getFilePath']($file)) {
        $file_data = file_get_contents($file_path);
        $json_data = json_decode($file_data, true);
    }

    $attributes = $app['jsondb.getClassAttributes']($app['jsondb.config']['database_files'][$file]);

    return $app['twig']->render('file_view.html', array(
        'current_file'  => $file,
        'attributes'    => $attributes,
        'json_data'     => $json_data,
    ));
})->bind('file_view');

$app->get('/{file}/edit', function($file) use($app) {
    $json_data = array();

    if ($file_path = $app['jsondb.getFilePath']($file)) {
        $file_data = file_get_contents($file_path);
        $json_data = json_decode($file_data, true);
    }

    $attributes = $app['jsondb.getClassAttributes']($app['jsondb.config']['database_files'][$file]);

    return $app['twig']->render('file_edit.html', array(
        'current_file'  => $file,
        'attributes'    => $attributes,
        'json_data'  => $json_data,
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
    $file_path  = $app['jsondb.getFilePath']($file);
    $attributes = $app['jsondb.getClassAttributes']($app['jsondb.config']['database_files'][$file]);

    $rows = array();
    if (isset($_POST['id'])) {
        foreach ($_POST['id'] as $id) {
            $row = array();
            foreach ($attributes as $attribute) {
                if (isset($attribute['type']) && $attribute['type'] == 'boolean') {
                    $row[$attribute['name']] = (isset($_POST[$attribute['name']][$id]) && $_POST[$attribute['name']][$id])? true : false;
                } else {
                    if (isset($_POST[$attribute['name']][$id])) {
                        $value = $_POST[$attribute['name']][$id];
                    } else {
                        $value = null;
                    }
                    $row[$attribute['name']] = $value;
                }
            }
            $rows[] = $row;
        }

        $new_database = json_encode($rows);
        file_put_contents($file_path, $new_database, LOCK_EX);
    }

    return $app->redirect($app["url_generator"]->generate("file_view", array("file" => $file)));
});

$app->get('/{file}/api/add', function(Request $request, $file) use($app) {
    $file_path  = $app['jsondb.getFilePath']($file);
    $attributes = $app['jsondb.getClassAttributes']($app['jsondb.config']['database_files'][$file]);

    if ($file_path) {
        $file_data = file_get_contents($file_path);
        $json_data = json_decode($file_data, true);
    }

    $row = array();
    foreach ($attributes as $attribute) {
        if (isset($attribute['type']) && $attribute['type'] == 'boolean') {
            $row[$attribute['name']] = (isset($_GET[$attribute['name']]) && $_GET[$attribute['name']])? true : false;
        } else {
            if (isset($_GET[$attribute['name']])) {
                $value = $_GET[$attribute['name']];
            } else {
                $value = null;
            }
            $row[$attribute['name']] = $value;
        }
    }
    $json_data[] = $row;

    $new_database = json_encode($json_data);
    file_put_contents($file_path, $new_database, LOCK_EX);

    return $app->redirect($app["url_generator"]->generate("file_view", array("file" => $file)));
});

//Create database path if not existing
if (!is_dir($app['jsondb.config']['database_path'])) {
    mkdir($app['jsondb.config']['database_path']);
}

//Start the app
$app->run();

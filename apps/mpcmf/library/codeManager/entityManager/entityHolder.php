<?php

namespace mpcmf\apps\mpcmf\library\codeManager\entityManager;

use mpcmf\loader;
use mpcmf\modules\moduleBase\moduleBase;
use mpcmf\system\configuration\config;
use mpcmf\system\view\smartyDriver;

/**
 * Entity holder
 *
 * @author Gregory Ostrovsky <greevex@gmail.com>
 */
class entityHolder
{
    private $staticConfig = [
        'dirs' => [
            'actions' => 'actions',
            'controller' => 'controllers',
            'entity' => 'entities',
            'mapper' => 'mappers',
            'model' => 'models',
        ]
    ];

    private $entityData = [
        'name' => '',
        'description' => '',
        'date' => null, //auto-generate
        'authors' => [

        ],
        'module' => '',
        'moduleNamespace' => '',
        'baseDirectory' => '', //auto-generate
        'struct' => [
            'actions' => [
                'suffix' => 'Actions',
                'filepath' => '', //auto-generate
                'className' => '', //auto-generate
                'fullClassName' => '', //auto-generate
            ],
            'controller' => [
                'suffix' => 'Controller',
                'filepath' => '', //auto-generate
                'className' => '', //auto-generate
                'fullClassName' => '', //auto-generate
            ],
            'entity' => [
                'suffix' => '',
                'filepath' => '', //auto-generate
                'className' => '', //auto-generate
                'fullClassName' => '', //auto-generate
            ],
            'mapper' => [
                'suffix' => 'Mapper',
                'publicName' => '',
                'dbConfig' => [
                    'configSection' => '',
                    'dbName' => '',
                    'collectionName' => '',
                ],
                'map' => [
                    'field_name' => [
                        'roles' => [],
                        'name' => '',
                        'description' => '',
                        'type' => '',
                        'formType' => '',
                        'validator' => [],
                        'relations' => [],
                        'options' => [],
                        'mixedCase' => 'fieldName',
                    ]
                ],
                'filepath' => '', //auto-generate
                'className' => '', //auto-generate
                'fullClassName' => '', //auto-generate
            ],
            'model' => [
                'suffix' => 'Model',
                'filepath' => '', //auto-generate
                'className' => '', //auto-generate
                'fullClassName' => '', //auto-generate
            ],
        ]
    ];

    public function __construct($moduleClass, $entityName)
    {
        /** @var moduleBase $moduleClass */
        $moduleInstance = $moduleClass::getInstance();

        $this->entityData['module'] = $moduleClass;
        $this->entityData['moduleNamespace'] = $moduleInstance->getModuleNamespace();
        $this->entityData['name'] = $entityName;
        $this->entityData['date'] = time();

        $basedir = $moduleInstance->getModuleDirectory();
        foreach($this->entityData['struct'] as $key => &$data) {
            $shortClassName = $this->entityData['name'] . $this->entityData['struct'][$key]['suffix'];
            $filepath = "{$basedir}/{$this->staticConfig['dirs'][$key]}/{$shortClassName}.php";
            $data['className'] = $shortClassName;
            $data['fullClassName'] = $this->entityData['moduleNamespace'] . '\\' . $this->staticConfig['dirs'][$key] . '\\' . $shortClassName;
            $data['filepath'] = $filepath;
        }
    }

    protected function toMixedCase($string)
    {
        //exceptions
        if($string == '_id') {
            $string = 'mongo_id';
        }

        $result = '';
        foreach(preg_split('/[^a-z0-9]+/ui', $string) as $word) {
            $result .= ucfirst($word);
        }

        return lcfirst($result);
    }

    public function setDescription($description)
    {
        //@todo add validation
        $this->entityData['description'] = $description;
    }

    public function setPublicName($publicName)
    {
        //@todo add validation
        $this->entityData['struct']['mapper']['publicName'] = $publicName;
    }

    public function setDbConfig(array $dbConfig)
    {
        //@todo add validation
        $this->entityData['struct']['mapper']['dbConfig'] = $dbConfig;
    }

    public function getDbConfig()
    {
        if(empty($this->entityData['struct']['mapper']['dbConfig']['configSection'])
            || empty($this->entityData['struct']['mapper']['dbConfig']['dbName'])
            || empty($this->entityData['struct']['mapper']['dbConfig']['collectionName'])
        ) {
            return null;
        }
        return $this->entityData['struct']['mapper']['dbConfig'];
    }

    public function setAuthors($authors)
    {
        //@todo add validation
        $this->entityData['authors'] = $authors;
    }

    public function setFieldsMap($fieldsMap)
    {
        //@todo add validation
        foreach($fieldsMap as $fieldKey => &$fieldData) {
            $fieldData['mixedCase'] = $this->toMixedCase($fieldKey);
        }
        $this->entityData['struct']['mapper']['map'] = $fieldsMap;
    }

    protected function getBaseDirectory()
    {
        if(empty($this->entityData['baseDirectory'])) {
            $this->entityData['baseDirectory'] = loader::getLoader()->findFile($this->entityData['module']);
        }

        return $this->entityData['baseDirectory'];
    }

    public function saveAll()
    {
        $this->checkDirectories();
        $this->saveActions();
        $this->saveController();
        $this->saveEntity();
        $this->saveMapper();
        $this->saveModel();
    }

    public function checkDirectories()
    {
        foreach($this->entityData['struct'] as $key => $data) {
            $this->checkDirectory($key, $data);
        }
    }

    public function checkDirectory($key, $data = null)
    {
        $filedir = dirname($data === null ? $this->entityData['struct'][$key]['filepath'] : $data['filepath']);
        if(!file_exists($filedir)) {
            @mkdir($filedir, 0775, true);
        }
    }

    protected function saveCodeByType($key)
    {
        $smarty = $this->smarty();
        $smarty->appendData([
            'entityData' => $this->entityData,
            'entityKey' => $key,
        ]);

        $this->checkDirectory($key);

        file_put_contents($this->entityData['struct'][$key]['filepath'], $smarty->render("{$key}.tpl", true));
    }

    public function saveMapperConfig()
    {
        $smarty = $this->smarty();
        $smarty->appendData([
            'entityData' => $this->entityData,
        ]);

        $filepath = config::getConfigFilepath($this->entityData['struct']['mapper']['fullClassName']);
        file_put_contents($filepath, $smarty->render('mapperConfig.tpl', true));
    }

    public function saveActions()
    {
        $key = 'actions';

        $this->saveCodeByType($key);
    }

    public function saveController()
    {
        $key = 'controller';

        $this->saveCodeByType($key);

    }

    public function saveEntity()
    {
        $key = 'entity';

        $this->saveCodeByType($key);

    }

    public function saveMapper()
    {
        $key = 'mapper';

        $this->saveCodeByType($key);
        $this->saveMapperConfig();
    }

    public function saveModel()
    {
        $key = 'model';

        $this->saveCodeByType($key);

    }

    protected function smarty()
    {
        $smarty = new smartyDriver();
        $smarty->setTemplatesDirectory(__DIR__ . '/templates');

        return $smarty;
    }
}
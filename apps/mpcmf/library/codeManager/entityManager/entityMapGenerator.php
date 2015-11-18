<?php

namespace mpcmf\apps\mpcmf\library\codeManager\entityManager;

use mpcmf\system\storage\mongoInstance;

/**
 * Entity holder
 *
 * @author Gregory Ostrovsky <greevex@gmail.com>
 */
class entityMapGenerator
{

    /**
     * @var mongoInstance
     */
    private $mongoInstance;
    private $config = [
        'configSection' => null,
        'dbName' => null,
        'collectionName' => null,
        'limit' => 1000
    ];

    protected $map = [];

    public function __construct($configSection, $dbName, $collectionName)
    {
        $this->config['configSection'] = $configSection;
        $this->config['dbName'] = $dbName;
        $this->config['collectionName'] = $collectionName;
    }

    public function setLimit($limit)
    {
        $this->config['limit'] = (int)$limit;
    }

    public function process()
    {
        $stats = [
            '_id' => [
                'types' => [],
                'options' => [
                    'unique' => true
                ]
            ]
        ];
        $collection = $this->getCollectionInstance();

        foreach($collection->getIndexInfo() as $indexInfo) {
            if(count($indexInfo['key']) > 1) {
                continue;
            }
            $fieldName = array_keys($indexInfo['key'])[0];
            if(!isset($stats[$fieldName])) {
                $stats[$fieldName] = [
                    'types' => [],
                    'options' => [],
                ];
            }
            if(isset($indexInfo['unique']) && $indexInfo['unique']) {
                $stats[$fieldName]['options']['unique'] = true;
            }
        }

        $cursor = $collection->find();
        $cursor->limit($this->config['limit']);
        foreach($cursor as $item) {
            foreach($item as $field => $value) {
                $valueType = gettype($value);
                if($valueType == 'NULL') {
                    if(!isset($stats[$field])) {
                        $stats[$field] = [
                            'types' => [
                                'mixed' => 1
                            ],
                            'options' => [

                            ],
                        ];
                    } else {
                        if(!isset($stats[$field]['types']['mixed'])) {
                            $stats[$field]['types']['mixed'] = 1;
                        } else {
                            $stats[$field]['types']['mixed']++;
                        }
                    }
                    continue;
                } elseif($field == '_id') {
                    if(!isset($stats[$field])) {
                        $stats[$field] = [
                            'types' => [
                                'string' => 1
                            ],
                            'options' => [
                                'unique' => true
                            ],
                            'name' => 'Mongo ID'
                        ];
                    } else {
                        if(!isset($stats[$field]['types']['string'])) {
                            $stats[$field]['types']['string'] = 1;
                        } else {
                            $stats[$field]['types']['string']++;
                        }
                        $stats[$field]['options']['unique'] = true;
                        $stats[$field]['name'] = 'Mongo ID';
                    }
                    continue;
                }
                if($valueType == 'double') {
                    $valueType = 'float';
                } elseif($valueType == 'integer') {
                    $valueType = 'int';
                }
                if(!isset($stats[$field])) {
                    $stats[$field] = [
                        'types' => [],
                        'options' => [],
                    ];
                }
                if(!isset($stats[$field]['types'][$valueType])) {
                    $stats[$field]['types'][$valueType] = 1;
                } else {
                    $stats[$field]['types'][$valueType]++;
                }
            }
        }

        foreach($stats as $field => &$fieldData) {
            if(count($fieldData['types']) > 1) {
                $totalFieldPoints = array_sum($fieldData['types']);
                foreach ($fieldData['types'] as $typeName => $typeValue) {
                    $fieldData['types'][$typeName] = $typeValue / $totalFieldPoints;
                    if ($fieldData['types'][$typeName] < 0.02) {
                        unset($fieldData['types'][$typeName]);
                    }
                }
            }
            $fieldData['type'] = count($fieldData['types']) !== 1 ? 'mixed' : array_keys($fieldData['types'])[0];
            unset($fieldData['types']);

            if(!isset($fieldData['name'])) {
                $fieldData['name'] = ucfirst(trim(implode(' ', preg_split('/[_\s\-]+/', $field))));
            }
        }

        ksort($stats);

        return $stats;
    }

    /**
     * @return \MongoCollection
     */
    protected function getCollectionInstance()
    {
        return $this->storage()->getCollection($this->config['dbName'], $this->config['collectionName']);
    }

    /**
     * @return mongoInstance
     */
    protected function storage()
    {
        if($this->mongoInstance === null) {
            $this->mongoInstance = mongoInstance::factory($this->config['configSection']);
        }

        return $this->mongoInstance;
    }
}
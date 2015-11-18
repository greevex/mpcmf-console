<?php

namespace mpcmf\apps\mpcmf\commands\create;

use mpcmf\apps\mpcmf\library\codeManager\entityManager\entityHolder;
use mpcmf\apps\mpcmf\library\codeManager\entityManager\entityMapGenerator;
use mpcmf\system\application\applicationInstance;
use mpcmf\system\application\consoleCommandBase;
use mpcmf\system\application\webApplicationBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use cli;

/**
 * Create entity command
 *
 * @author Gregory Ostrovsky <greevex@gmail.com>
 */
class entity
    extends consoleCommandBase
{

    /**
     * Define arguments
     *
     * @return mixed
     */
    protected function defineArguments()
    {
        // TODO: Implement defineArguments() method.
    }

    /**
     * Executes the current command.
     *
     * This method is not because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this method is not implemented
     *
     * @see setCode()
     */
    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $config = [
            'module' => null,
            'entityName' => null,
            'params' => [
                'public-name' => '-',
                'description' => '-',
                'authors' => '-',
                'mapGenerate' => '-',
                'map' => '-',
                'save-all' => '-',
                'save-config' => '-',
            ]
        ];

        $pattern = '/\\\\([^\\\\]+)\\\\console$/ui';
        $replace = '\\\\$1\\\\$1';
        /** @var webApplicationBase $webAppClass */
        $webAppClass = preg_replace($pattern, $replace, get_class(applicationInstance::getInstance()->getCurrentApplication()));
        $output->writeln('<info>Please, select application:</info>');
        $applicationMenu = [];
        foreach (scandir(APP_ROOT . '/apps') as $appName) {
            $appNamespace = APP_NAME . "\\apps\\" . $appName . "\\";

            $appConsoleClass = $appNamespace . 'console';
            $appWebApplication = $appNamespace . $appName;

            if(!class_exists($appConsoleClass) && !class_exists($appWebApplication)) {
                continue;
            }
            $applicationMenu[$appNamespace] = $appName;
        }

        /** @var webApplicationBase $baseAppClass */
        $baseAppClass = cli\menu($applicationMenu, array_search($webAppClass, $applicationMenu, false), 'Select an application');
        $baseApp = $baseAppClass::getInstance();
        $output->writeln("<info>Selected application: {$baseAppClass}</info>");
        $output->writeln('<info>Please, select module:</info>');
        $modules = [];
        foreach($baseApp->getModules() as $module) {
            $name = $module->getModuleName();
            $modules[$name] = $name;
        }
        $selectedModule = cli\menu($modules, null, 'Select module');
        if(!isset($modules[$selectedModule])) {
            $output->writeln("<error>Module not found: {$selectedModule}</error>");
        }

        $module = $baseApp->getModules()[$selectedModule];
        $moduleClass = get_class($module);
        $output->writeln("<info>Selected module: {$moduleClass}</info>");
        $config['module'] = $module;

        $config['entityName'] = trim(readline('Entity class name: '));

        $entityHandler = new entityHolder($moduleClass, $config['entityName']);

        for(;;) {
            $menu = [];
            foreach ($config['params'] as $key => $data) {
                $menu[$key] = ucfirst($key) . ' ' . json_encode($data);
            }
            $menu['exit'] = 'Exit';
            $selected = cli\menu($menu);
            switch($selected) {
                case 'public-name':
                    $publicName = trim(readline('Public human readable name: '));
                    $config['params']['public-name'] = '+';
                    $entityHandler->setPublicName($publicName);
                    break;
                case 'description':
                    $output->writeln('Empty line means end of description');
                    $description = '';
                    do {
                        $read = trim(readline('> '));
                        $description .= $read . "\n";
                    } while(!empty($read));

                    $config['params']['description'] = '+';
                    $entityHandler->setDescription(trim($description));
                    break;
                case 'authors':
                    $output->writeln('Add authors. Empty name means end of add');
                    $authors = [];
                    do {
                        $name = trim(readline('Name: '));
                        if(empty($name)) {
                            break;
                        }
                        $email = trim(readline('Email: '));
                        $authors[] = [
                            'name' => $name,
                            'email' => $email,
                        ];
                    } while(!empty($name));

                    $config['params']['authors'] = '+';
                    $entityHandler->setAuthors($authors);
                    break;
                case 'mapGenerate':
                    $output->writeln('Generating map...');
                    $dbConfig = $entityHandler->getDbConfig();
                    if(!$dbConfig) {
                        $dbConfig = [];
                        $dbConfig['configSection'] = trim(readline('Server: '));
                        $dbConfig['dbName'] = trim(readline('Database: '));
                        $dbConfig['collectionName'] = trim(readline('Collection: '));
                        $entityHandler->setDbConfig($dbConfig);
                    }
                    $count = (int)trim(readline('Limit items {example: 50}: '));

                    $generator = new entityMapGenerator($dbConfig['configSection'], $dbConfig['dbName'], $dbConfig['collectionName']);
                    $count > 0 && $generator->setLimit($count);
                    $dbFields = $generator->process();
                    $totalFields = count($dbFields);
                    error_log("Found fields in database: {$totalFields}");

                    $map = [];
                    $fieldCounter = 0;

                    foreach($dbFields as $fieldName => $fieldData) {
                        $fieldCounter++;
                        $output->writeln("Processing field {$fieldName} ({$fieldCounter}/{$totalFields})");
                        $map[$fieldName] = [
                            'roles' => [],
                            'relations' => [],
                            'validator' => [],
                            'options' => $fieldData['options'],
                            'name' => $fieldData['name'],
                            'description' => $fieldData['name'],
                            'type' => $fieldData['type'],
                        ];
                        $askNameMenu = [
                            'auto' => $fieldData['name'],
                            'manual' => 'Type manually'
                        ];
                        $askNameChoose = cli\menu($askNameMenu, 'auto', "Name for field: {$fieldName}");
                        if($askNameChoose === 'manual') {
                            $map[$fieldName]['name'] = trim(readline('Type name: '));
                        }
                        $askDescriptionMenu = [
                            'auto' => $fieldData['name'],
                            'manual' => 'Type manually'
                        ];
                        $askDescriptionChoose = cli\menu($askDescriptionMenu, 'auto', "Description for field: {$fieldName}");
                        if($askDescriptionChoose === 'manual') {
                            $map[$fieldName]['description'] = trim(readline('Type description: '));
                        }
                        $typeMenu = [
                            'int' => 'Integer',
                            'int[]' => 'Array of integer values',
                            'float' => 'Float',
                            'float[]' => 'Array of float values',
                            'string' => 'String',
                            'string[]' => 'Array of string values',
                            'boolean' => 'Boolean',
                            'boolean[]' => 'Array of boolean values',
                            'array' => 'Array',
                        ];
                        $map[$fieldName]['type'] = cli\menu($typeMenu, $fieldData['type'], "Select type of {$fieldName}");

                        $formTypeMenu = [
                            'default' => [
                                'text' => 'text',
                                'json' => 'json',
                                'checkbox' => 'checkbox',
                                'datetimepicker' => 'datetimepicker',
                                'geojson' => 'geojson',
                                'multitext' => 'multitext',
                                'multiselect' => 'multiselect',
                                'radio' => 'radio',
                                'operationValueSelect' => 'operationValueSelect',
                                'select' => 'select',
                                'password' => 'password',
                                'textarea' => 'textarea',
                                'timepicker' => 'timepicker',
                            ],
                            'int' => [
                                'datetimepicker' => 'datetimepicker',
                                'timepicker' => 'timepicker',
                                'text' => 'text',
                                'json' => 'json',
                                'select' => 'select',
                                'radio' => 'radio',
                            ],
                            'string' => [
                                'text' => 'text',
                                'select' => 'select',
                                'textarea' => 'textarea',
                                'password' => 'password',
                                'json' => 'json',
                                'radio' => 'radio',
                            ],
                            'boolean' => [
                                'checkbox' => 'checkbox',
                                'json' => 'json',
                                'radio' => 'radio',
                                'select' => 'select',
                            ],
                            'array' => [
                                'multiselect' => 'multiselect',
                                'multitext' => 'multitext',
                                'json' => 'json',
                                'geojson' => 'geojson',
                                'textarea' => 'textarea',
                            ]
                        ];
                        $formTypeMenuSelected = isset($formTypeMenu[$fieldData['type']]) ? $formTypeMenu[$fieldData['type']] : $formTypeMenu['default'];
                        $map[$fieldName]['formType'] = cli\menu($formTypeMenuSelected, array_keys($formTypeMenuSelected)[0], "Select form type of {$fieldName}");

                        $output->writeln('Add roles:');
                        $roles = [];
                        do {
                            $roleMenu = [
                                'key' => 'ROLE__PRIMARY_KEY',
                                'generate-key' => 'ROLE__GENERATE_KEY',
                                'title' => 'ROLE__TITLE',
                                'searchable' => 'ROLE__SEARCHABLE',
                                'sortable' => 'ROLE__SORTABLE',
                                'query-field' => 'ROLE__QUERY_FIELD',
                                'geo-area' => 'ROLE__GEO_AREA',
                                'geo-point' => 'ROLE__GEO_POINT',
                            ];
                            foreach($roleMenu as $roleKey => &$roleText) {
                                if(isset($roles[$roleKey]) && $roles[$roleKey]) {
                                    $roleText = "+ {$roleText}";
                                } else {
                                    $roleText = "- {$roleText}";
                                }
                            }
                            unset($roleText);
                            $roleMenu['done'] = 'Save and exit...';
                            $selectedRole = cli\menu($roleMenu, 'done', 'Select roles:');
                            if($selectedRole === 'done') {
                                break;
                            }
                            if(!isset($roles[$selectedRole])) {
                                $roles[$selectedRole] = false;
                            }
                            $roles[$selectedRole] = !$roles[$selectedRole];
                        } while($selectedRole !== 'done');
                        foreach($roles as $roleKey => $roleValue) {
                            if($roleValue === false) {
                                unset($roles[$roleKey]);
                            }
                        }
                        $map[$fieldName]['roles'] = $roles;

                        $output->writeln('Add options:');
                        $options = [
                            'required' => false,
                            'unique' => false,
                        ];
                        do {
                            $optionsMenu = [
                                'required' => 'Required',
                                'unique' => 'Unique',
                                'done' => 'Done'
                            ];
                            foreach($optionsMenu as $optionKey => &$optionText) {
                                if(isset($options[$optionKey]) && $options[$optionKey]) {
                                    $optionText = "+ {$optionText}";
                                } else {
                                    $optionText = "- {$optionText}";
                                }
                            }
                            unset($optionText);
                            $optionsMenu['done'] = 'Save and exit...';
                            $option = cli\menu($optionsMenu, 'done', 'Select options:');
                            if($option === 'done') {
                                break;
                            }
                            $options[$option] = !$options[$option];
                        } while($option !== 'done');
                        $map[$fieldName]['options'] = $options;
                    }

                    $config['params']['mapGenerate'] = '+';
                    $entityHandler->setFieldsMap($map);

                    break;
                case 'map':
                    $output->writeln('Add map. Empty field name means end of add');
                    $map = [];
                    do {
                        $fieldName = trim(readline('Field name: '));
                        if(empty($fieldName)) {
                            break;
                        }
                        $map[$fieldName] = [
                            'roles' => [],
                            'relations' => [],
                            'validator' => [],
                        ];
                        $map[$fieldName]['name'] = trim(readline('Name: '));
                        $map[$fieldName]['description'] = trim(readline('Description: '));
                        $typeMenu = [
                            'int' => 'Integer',
                            'int[]' => 'Array of integer values',
                            'float' => 'Float',
                            'float[]' => 'Array of float values',
                            'string' => 'String',
                            'string[]' => 'Array of string values',
                            'boolean' => 'Boolean',
                            'boolean[]' => 'Array of boolean values',
                            'array' => 'Array',
                        ];
                        $map[$fieldName]['type'] = cli\menu($typeMenu);

                        $formTypeMenu = [
                            'checkbox' => 'checkbox',
                            'datetimepicker' => 'datetimepicker',
                            'geojson' => 'geojson',
                            'json' => 'json',
                            'multitext' => 'multitext',
                            'multiselect' => 'multiselect',
                            'radio' => 'radio',
                            'operationValueSelect' => 'operationValueSelect',
                            'select' => 'select',
                            'text' => 'text',
                            'password' => 'password',
                            'textarea' => 'textarea',
                            'timepicker' => 'timepicker',
                        ];
                        $map[$fieldName]['formType'] = cli\menu($formTypeMenu);

                        $output->writeln('Add roles:');
                        $roleMenu = [
                            'key' => 'ROLE__PRIMARY_KEY',
                            'generate-key' => 'ROLE__GENERATE_KEY',
                            'title' => 'ROLE__TITLE',
                            'searchable' => 'ROLE__SEARCHABLE',
                            'sortable' => 'ROLE__SORTABLE',
                            'query-field' => 'ROLE__QUERY_FIELD',
                            'geo-area' => 'ROLE__GEO_AREA',
                            'geo-point' => 'ROLE__GEO_POINT',
                            '' => 'Done (Exit)'
                        ];
                        do {
                            $role = cli\menu($roleMenu);
                            if(empty($role)) {
                                break;
                            }
                            $map[$fieldName]['roles'][$role] = true;
                        } while(!empty($role));

                        $output->writeln('Add options:');
                        $options = [
                            'required' => false,
                            'unique' => false,
                        ];
                        do {
                            $optionsMenu = [
                                'required' => 'Required: ' . json_encode($options['required']),
                                'unique' => 'Unique: ' . json_encode($options['unique']),
                                'done' => 'Done'
                            ];
                            $option = cli\menu($optionsMenu);
                            if($option === 'done') {
                                break;
                            }
                            $options[$option] = !$options[$option];
                        } while($option !== 'done');
                        $map[$fieldName]['options'] = $options;
                    } while(!empty($fieldName));

                    $config['params']['map'] = '+';
                    $entityHandler->setFieldsMap($map);
                    break;
                case 'save-all':
                    $config['params']['save-all'] = '+';
                    $entityHandler->saveAll();
                    break;
                case 'save-config':
                    $dbConfig = $entityHandler->getDbConfig();
                    if(!$dbConfig) {
                        $dbConfig = [];
                        $dbConfig['configSection'] = trim(readline('Server: '));
                        $dbConfig['dbName'] = trim(readline('Database: '));
                        $dbConfig['collectionName'] = trim(readline('Collection: '));
                        $entityHandler->setDbConfig($dbConfig);
                    }

                    $config['params']['save-config'] = '+';
                    $entityHandler->saveMapperConfig();
                    break;
                case 'exit':
                    break 2;
                default:
                    $output->writeln("Unknown command: {$selected}");
                    break;
            }
        }
    }

}
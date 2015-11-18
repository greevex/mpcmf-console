<?php
{assign var="data" value=$entityData['struct']['mapper']}

/**
{include file="generator.tpl"}
 *
 * @package {$entityData['module']}\mapper\{$data['className']}
 * @date {"Y-m-d H:i:s"|date:$entityData['date']}
 *
{foreach from=$entityData['authors'] item='author'}
 * @author {$author['name']} <{$author['email']}>
{/foreach}
 */
\mpcmf\system\configuration\config::setConfig(__FILE__, [
    'storage' => [
        'configSection' => '{$data['dbConfig']['configSection']}',
        'db' => '{$data['dbConfig']['dbName']}',
        'collection' => '{$data['dbConfig']['collectionName']}'
    ]
]);
<?php
{assign var="data" value=$entityData['struct'][$entityKey]}

namespace {$entityData['moduleNamespace']}\entities;

use mpcmf\modules\moduleBase\entities\entityBase;
use mpcmf\system\pattern\singleton;

/**
 * Class {$data['className']}
 *
{if !empty($entityData['description'])}
 * {$entityData['description']|replace:"\n":"\n * "}
 *
{/if}
 *
 {include file="generator.tpl"}
 *
 * @package {$entityData['moduleNamespace']}\entities
 * @date {"Y-m-d H:i:s"|date:$entityData['date']}
 *
{foreach from=$entityData['authors'] item='author'}
 * @author {$author['name']} <{$author['email']}>
{/foreach}
 */
class {$data['className']}
    extends entityBase
{

    use singleton;
}
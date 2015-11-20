<?php
{assign var="data" value=$entityData['struct'][$entityKey]}

namespace {$entityData['moduleNamespace']}\models;

use mpcmf\modules\moduleBase\models\modelBase;
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
 * @package {$entityData['moduleNamespace']}\models
 * @date {"Y-m-d H:i:s"|date:$entityData['date']}
 *
{foreach from=$entityData['authors'] item='author'}
 * @author {$author['name']} <{$author['email']}>
{/foreach}
 *
{foreach from=$entityData['struct']['mapper']['map'] key="fieldName" item="fieldData"}
 * @method {$fieldData['type']} get{$fieldData['mixedCase']|ucfirst}() {if empty($fieldData['description'])}{$fieldData['name']}{else}{$fieldData['description']}{/if}

 * @method $this set{$fieldData['mixedCase']|ucfirst}({if substr($fieldData['type'], -2) == '[]'}array {elseif $fieldData['type'] == 'mixed'}{else}{$fieldData['type']} {/if}$value) {if empty($fieldData['description'])}{$fieldData['name']}{else}{$fieldData['description']}{/if}

{/foreach}
 */
class {$data['className']}
    extends modelBase
{

    use singleton;
}
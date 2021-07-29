<?php

namespace Crumbls\LaravelDivi\Components;

use Crumbls\LaravelDivi\Css\Generator;
use Crumbls\LaravelDivi\Css\Rule;
use Illuminate\View\Component;

class Row extends AbstractElement
{
    public $attributesExtended = [
        '_module_preset' => 'default',
        'global_module' => null
    ];


    // TODO: FINISH RULES
    public function generateStylesText()
    {
        $generator = $this->getStyleGenerator();

        $id = $this->generateUniqueId();
        $rule = array_filter($generator->rules, function ($e) use ($id) {
            if ($e->getRuleType() != Rule::RULE_TYPE_PARENT) {
                return false;
            };
            if ($e->getRuleIdentifierType() != '#') {
                return false;
            }
            if ($e->getRuleIdentifier() != $id) {
                return false;
            }
            return true;
        });
        $rid = false;

        /**
         * Generate a new rule if needed.
         */
        if (!$rule) {
            $rule = new Rule($this->generateUniqueId(), Generator::RULE_TYPE_ID);
            $generator->extendRule($rule, $id, Generator::RULE_TYPE_ID);
            $rule = array_filter($generator->rules, function ($e) use ($id) {
                if ($e->getRuleType() != Rule::RULE_TYPE_PARENT) {
                    return false;
                };
                if ($e->getRuleIdentifierType() != '#') {
                    return false;
                }
                if ($e->getRuleIdentifier() != $id) {
                    return false;
                }
                return true;
            });
        }

        $rid = array_keys($rule);
        $rid = end($rid);

        return;
        $temp = $this->attributes->has('custom_padding') ? $this->attributes->get('custom_padding') : false;
        if ($temp) {
            // '0.715em|0.715em|0.715em|0.715em|false|false',
            $temp = explode('|', $temp);
            $x = count($temp);
            if ($x == 6) {
                $temp = array_slice($temp, 0, 4);
                if ($temp[0]) {
                    $rule->set('padding-top', $temp[0]);
                }
                if ($temp[1]) {
                    $rule->set('padding-right', $temp[0]);
                }
                if ($temp[2]) {
                    $rule->set('padding-bottom', $temp[0]);
                }
                if ($temp[3]) {
                    $rule->set('padding-left', $temp[0]);
                }
                $x = count($temp);
            }
            if ($temp == 4) {

            }
            dd($temp);
            if ( '' !== $custom_padding && 4 === count( explode( '|', $custom_padding ) ) ) {
                $attributes['padding'] = $custom_padding;
            }
//            $generator->rules[$rid]->set('font-size', $temp);
        }
        echo $id;
    }

}

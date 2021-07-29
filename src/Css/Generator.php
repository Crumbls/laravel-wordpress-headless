<?php

namespace Crumbls\LaravelWordpress\Css;

use RuntimeException;

class Generator
{
    const RULE_TYPE_CLASS   = '.';
    const RULE_TYPE_ID      = '#';

    /** @var Rule[] */
    public $rules = [];

    /**
     * Defines new rule which identifier is ID
     *
     * @param string $className
     * @return Rule
     */
    public function defineClassRule($class)
    {
        return $this->defineRule($class, self::RULE_TYPE_CLASS);
    }


    /**
     *
     * Defines new rule which identifier is ID
     *
     * @param string $id
     * @return Rule
     */
    public function defineIdRule($id)
    {
        return $this->defineRule($id, self::RULE_TYPE_ID);
    }

    /**
     *
     * Defines new rule
     *
     * @param string $name
     * @param string $type
     * @return Rule
     */
    public function defineRule($name, $type = 'id')
    {
        $rule = new Rule($name, $type);

        $this->rules[] = $rule;

        return $rule;
    }

    /**
     *
     * Creates new rule with new identifier and identifier type but with same attributes as one that is extended
     *
     * @param Rule $Rule
     * @param string $ruleIdentifier
     * @param string $ruleIdentifierType
     * @return Rule
     */
    public function extendRule(Rule $Rule, $ruleIdentifier, $ruleIdentifierType = Generator::RULE_TYPE_ID)
    {
        $attributes = $Rule->getAttributes();

        $newRule = $this->defineRule($ruleIdentifier, $ruleIdentifierType);

        foreach ($attributes as $attribute => $value) {
            $newRule->set($attribute, $value);
        }

        return $newRule;
    }

    /**
     *
     * Generates CSS
     *
     * @return string
     */
    public function generate()
    {
        $css = '';

        foreach ($this->rules as $rule) {
            $css .= $rule->generate();
        }

        return $css;
    }


    /**
     *
     * Generates CSS and serves it
     *
     */
    public function serve()
    {
        header('Content-Type: text/css');

        echo $this->generate();

        exit;
    }
}
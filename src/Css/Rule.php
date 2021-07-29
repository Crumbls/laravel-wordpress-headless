<?php

namespace Crumbls\LaravelWordpress\Css;

use RuntimeException;

class Rule
{
    /*
     * RULE_TYPE_* defines relation between current rule and its parent
     *
     * RULE_TYPE_PARENT - this rule is parent
     * RULE_TYPE_CHILD - this rule is child of parent rule e.g #parent > .child
     * RULE_TYPE_SUBRULE - this rule is subrule of parent e.g #parent .child
     * RULE_TYPE_ADDITIONAL - this rule is part of parent e.g #parent.child
     */
    const RULE_TYPE_PARENT       = 'parent';
    const RULE_TYPE_CHILD        = 'child';
    const RULE_TYPE_SUBRULE      = 'subrule';
    const RULE_TYPE_ADDITIONAL   = 'additional';

    /** @var array */
    private static $_cssAttributes = ['color','opacity','background','background-attachment','background-blend-mode','background-color','background-image','background-position','background-repeat','background-clip','background-origin','background-size','border','border-bottom','border-bottom-color','border-bottom-left-radius','border-bottom-right-radius','border-bottom-style','border-bottom-width','border-color','border-image','border-image-outset','border-image-repeat','border-image-slice','border-image-source','border-image-width','border-left','border-left-color','border-left-style','border-left-width','border-radius','border-right','border-right-color','border-right-style','border-right-width','border-style','border-top','border-top-color','border-top-left-radius','border-top-right-radius','border-top-style','border-top-width','border-width','box-decoration-break','box-shadow','bottom','clear','clip','display','float','height','left','margin','margin-bottom','margin-left','margin-right','margin-top','max-height','max-width','min-height','min-width','overflow','overflow-x','overflow-y','padding','padding-bottom','padding-left','padding-right','padding-top','position','right','top','visibility','width','vertical-align','z-index','align-content','align-items','align-self','flex','flex-basis','flex-direction','flex-flow','flex-grow','flex-shrink','flex-wrap','justify-content','order','hanging-punctuation','hyphens','letter-spacing','line-break','line-height','overflow-wrap','tab-size','text-align','text-align-last','text-combine-upright','text-indent','text-justify','text-transform','white-space','word-break','word-spacing','word-wrap','text-decoration','text-decoration-color','text-decoration-line','text-decoration-style','text-shadow','text-underline-position','@font-face','@font-feature-values','font','font-family','font-feature-settings','font-kerning','font-language-override','font-size','font-size-adjust','font-stretch','font-style','font-synthesis','font-variant','font-variant-alternates','font-variant-caps','font-variant-east-asian','font-variant-ligatures','font-variant-numeric','font-variant-position','font-weight','direction','text-orientation','text-combine-upright','unicode-bidi','writing-mode','border-collapse','border-spacing','caption-side','empty-cells','table-layout','counter-increment','counter-reset','list-style','list-style-image','list-style-position','list-style-type','@keyframes','animation','animation-delay','animation-direction','animation-duration','animation-fill-mode','animation-iteration-count','animation-name','animation-play-state','animation-timing-function','backface-visibility','perspective','perspective-origin','transform','transform-origin','transform-style','transition','transition-property','transition-duration','transition-timing-function','transition-delay','box-sizing','content','cursor','ime-mode','nav-down','nav-index','nav-left','nav-right','nav-up','outline','outline-color','outline-offset','outline-style','outline-width','resize','text-overflow','break-after','break-before','break-inside','column-count','column-fill','column-gap','column-rule','column-rule-color','column-rule-style','column-rule-width','column-span','column-width','columns','widows','orphans','page-break-after','page-break-before','page-break-inside','marks','quotes','filter','image-orientation','image-rendering','image-resolution','object-fit','object-position','mask','mask-type','mark','mark-after','mark-before','phonemes','rest','rest-after','rest-before','voice-balance','voice-duration','voice-pitch','voice-pitch-range','voice-rate','voice-stress','voice-volume','marquee-direction','marquee-play-count','marquee-speed','marquee-style'];

    /** @var string */
    private $ruleType = Rule::RULE_TYPE_PARENT;

    /** @var string */
    private $ruleIdentifierType;

    /** @var string */
    private $ruleIdentifier;

    /** @var array */
    private $ruleAttributes = [];

    /** @var Rule[] */
    private $ruleChildren = [];

    /** @var Rule[] */
    private $ruleSubRules = [];

    /** @var Rule[] */
    private $additionalRules = [];

    /** @var Rule */
    private $parentRule = null;

    /**
     * Rule constructor.
     *
     * @param $ruleIdentifier
     * @param $ruleType
     * @param int $cssVersion
     *
     * @throws RuntimeException
     */
    public function __construct($ruleIdentifier, $ruleType, $cssVersion = 3)
    {
        $this->setRuleIdentifierType($ruleType);

        $this->setRuleIdentifier($ruleIdentifier);
    }

    /**
     *
     * Sets attribute for current rule
     *
     * @param string $attribute
     * @param string $value
     *
     * @return Rule
     *
     * @throws RuntimeException
     */
    public function set($attribute, $value)
    {
        $attribute = strtolower($attribute);

        if (false === in_array($attribute, self::$_cssAttributes)) {
            throw new RuntimeException("Unknown CSS property {$attribute}");
        }

        $this->ruleAttributes[$attribute] = $value;

        return $this;
    }

    /**
     *
     * Returns certain attribute from current rule
     *
     * @param string $attribute
     * @param bool $failOnNone
     */
    public function get($attribute, $failOnNone = false)
    {
        if (empty($this->ruleAttributes[$attribute]) && $failOnNone) {
            throw new RuntimeException("Attribute {$attribute} not defined.");
        }

        return empty($this->ruleAttributes[$attribute]) ? null : $this->ruleAttributes[$attribute];
    }

    /**
     *
     * Returns list that contains all attributes for current rule
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->ruleAttributes;
    }

    /**
     *
     * Returns current rule identifier
     *
     * @return string
     */
    public function getRuleIdentifier()
    {
        return $this->ruleIdentifier;
    }

    /**
     *
     * Returns current rule identifier type (# or .)
     *
     * @return string
     */
    public function getRuleIdentifierType()
    {
        return $this->ruleIdentifierType;
    }

    /**
     *
     * Returns full identifier with parent identifiers for current rule
     *
     * @return string
     */
    public function getRuleFullIdentifier()
    {
        $identifier = "{$this->ruleIdentifierType}{$this->ruleIdentifier}";

        if ($this->parentRule instanceof Rule) {

            $glue = '';

            switch ($this->ruleType) {
                case Rule::RULE_TYPE_SUBRULE:
                    $glue = ' ';
                    break;
                case Rule::RULE_TYPE_ADDITIONAL:
                    $glue = '';
                    break;
                case Rule::RULE_TYPE_CHILD:
                    $glue = ' > ';
                    break;
            }

            $identifier = "{$this->parentRule->getRuleFullIdentifier()}{$glue}{$identifier}";
        }

        return $identifier;
    }

    /**
     * @return string
     */
    public function getRuleType()
    {
        return $this->ruleType;
    }

    /**
     *
     * Change rule type that is relation between current rule and its parent
     *
     * @param $type
     * @return $this
     */
    public function setRuleType($type)
    {
        switch ($type) {
            case Rule::RULE_TYPE_CHILD:
            case Rule::RULE_TYPE_PARENT:
            case Rule::RULE_TYPE_SUBRULE:
            case Rule::RULE_TYPE_ADDITIONAL:
                $this->ruleType = $type;

                return $this;
            default:
                throw new RuntimeException("Invalid rule type {$type}");
        }
    }

    /**
     *
     * Defines child rule of current rule that will use # (id) as identifier type
     *
     * e.g #parent > #child
     *
     * @param string $ruleIdentifier
     * @return Rule
     */
    public function defineIdChildRule($ruleIdentifier)
    {
        return $this->defineChildRule(Generator::RULE_TYPE_ID, $ruleIdentifier);
    }

    /**
     *
     * Defines child rule of current rule that will use . (class) as identifier type
     *
     * e.g #parent > .child
     *
     * @param string $ruleIdentifier
     * @return Rule
     */
    public function defineClassChildRule($ruleIdentifier)
    {
        return $this->defineChildRule(Generator::RULE_TYPE_CLASS, $ruleIdentifier);
    }

    /**
     *
     * Defines child rule of current rule
     *
     * e.g #parent > (# or .)child
     *
     * @param string $ruleIdentifierType
     * @param string $ruleIdentifier
     * @return Rule
     */
    public function defineChildRule($ruleIdentifierType, $ruleIdentifier)
    {
        $childRule = new Rule($ruleIdentifier, $ruleIdentifierType);
        $childRule
            ->setRuleType(Rule::RULE_TYPE_CHILD)
            ->setParent($this);

        $this->ruleChildren[] = $childRule;

        return $childRule;
    }

    /**
     *
     * Defines sub rule of current rule that will use # (id) as identifier type
     *
     * e.g #parent #child
     *
     * @param string $ruleIdentifier
     * @return Rule
     */
    public function defineIdSubRule($ruleIdentifier)
    {
        return $this->defineSubRule(Generator::RULE_TYPE_ID, $ruleIdentifier);
    }

    /**
     *
     * Defines sub rule of current rule that will use . (class) as identifier type
     *
     * e.g #parent .child
     *
     * @param string $ruleIdentifier
     * @return Rule
     */
    public function defineClassSubRule($ruleIdentifier)
    {
        return $this->defineSubRule(Generator::RULE_TYPE_CLASS, $ruleIdentifier);
    }

    /**
     *
     * Defines sub rule of current rule
     *
     * e.g #parent (# or .)child
     *
     * @param string $ruleIdentifierType
     * @param string $ruleIdentifier
     * @return Rule
     */
    public function defineSubRule($ruleIdentifierType, $ruleIdentifier)
    {
        $subRule = new Rule($ruleIdentifier, $ruleIdentifierType);

        $subRule
            ->setRuleType(Rule::RULE_TYPE_SUBRULE)
            ->setParent($this);

        $this->ruleSubRules[] = $subRule;

        return $subRule;
    }

    /**
     *
     * Defines additional rule to current rule that will use # (id) as identifier
     *
     * e.g .parent#child
     *
     * @param string $ruleIdentifier
     * @return Rule
     */
    public function defineIdRule($ruleIdentifier)
    {
        return $this->defineRule(Generator::RULE_TYPE_ID, $ruleIdentifier);
    }

    /**
     *
     * Defines additional rule to current rule that will use . (class) as identifier
     *
     * e.g .parent.child
     *
     * @param string $ruleIdentifier
     * @return Rule
     */
    public function defineClassRule($ruleIdentifier)
    {
        return $this->defineRule(Generator::RULE_TYPE_CLASS, $ruleIdentifier);
    }

    /**
     *
     * Defines additional rule tp current rule
     *
     * e.g #parent.child
     *
     * @param string $ruleIdentifierType
     * @param string $ruleIdentifier
     * @return Rule
     */
    public function defineRule($ruleIdentifierType, $ruleIdentifier)
    {
        $additionalRule = new Rule($ruleIdentifier, $ruleIdentifierType);

        $additionalRule
            ->setRuleType(Rule::RULE_TYPE_ADDITIONAL)
            ->setParent($this);

        $this->additionalRules[] = $additionalRule;

        return $additionalRule;
    }

    /**
     *
     * Get all child rules from current rule
     *
     * @return Rule[]
     */
    public function getRuleChildren()
    {
        return $this->ruleChildren;
    }

    /**
     *
     * Set current rule parent
     *
     * @param Rule $Rule
     * @return Rule|$this
     */
    public function setParent(Rule $Rule)
    {
        if ($Rule === $this) {
            throw new RuntimeException('Element cannot be parent to itself.');
        }

        $rule = $Rule->getParent();

        while ($rule) {
            if ($rule === $this) {
                throw new RuntimeException('Rule parent is child of current rule cannot be parent.');
            }

            $rule = $rule->getParent();
        }

        /** @var Rule[] $children */
        $children = array_merge($this->additionalRules, $this->ruleChildren, $this->ruleSubRules);

        foreach ($children as $child) {
            if ($child === $Rule) {
                throw new RuntimeException('Child cannot be parent.');
            }
        }

        $this->parentRule = $Rule;

        return $this;
    }

    /**
     * @param Rule $Rule
     * @return $this
     */
    public function addAdditionalRule(Rule $Rule)
    {
        $this->additionalRules[] = $Rule;

        return $this;
    }

    /**
     * @param Rule $Rule
     * @return $this
     */
    public function addChildRule(Rule $Rule)
    {
        $this->ruleChildren[] = $Rule;

        return $this;
    }

    /**
     * @param Rule $Rule
     * @return $this
     */
    public function addSubRule(Rule $Rule)
    {
        $this->ruleSubRules[] = $Rule;

        return $this;
    }

    /**
     * @param string $parentIdentifier
     * @param string $parentIdentifierType
     * @return Rule
     */
    public function getParent($parentIdentifier = null, $parentIdentifierType = Generator::RULE_TYPE_ID)
    {
        $parent = $this->parentRule;

        if (is_string($parentIdentifier) && false === empty($parentIdentifier)) {
            while (
                false === is_null($parent) &&
                false === $this->isRightParent($parent, $parentIdentifier, $parentIdentifierType)
            ) {
                $parent = $parent->getParent($parentIdentifier, $parentIdentifierType);
            }

            if (is_null($parent)) {
                throw new RuntimeException("Parent {$parentIdentifierType}{$parentIdentifier} not found.");
            }
        }

        return $parent;
    }

    /**
     * @param Rule $parent
     * @param string $parentIdentifier
     * @param string $parentIdentifierType
     * @return bool
     */
    private function isRightParent(Rule $parent = null, $parentIdentifier, $parentIdentifierType)
    {
        return
            false === is_null($parent) &&
            $parent->getRuleIdentifier() === $parentIdentifier &&
            $parent->getRuleIdentifierType() === $parentIdentifierType
        ;
    }

    /**
     *
     * Set current rule identifier type (# or .)
     *
     * @param string $ruleIdentifierType
     * @return Rule|$this
     */
    public function setRuleIdentifierType($ruleIdentifierType)
    {
        switch ($ruleIdentifierType) {
            case Generator::RULE_TYPE_CLASS:
            case Generator::RULE_TYPE_ID:
                $this->ruleIdentifierType = $ruleIdentifierType;
                break;
            default:
                throw new RuntimeException("Invalid rule type {$ruleIdentifierType}");
        }

        return $this;
    }

    /**
     *
     * Set current rule identifier
     *
     * @param string $ruleIdentifier
     * @return Rule|$this
     */
    public function setRuleIdentifier($ruleIdentifier)
    {
        if (0 === strlen($ruleIdentifier)) {
            throw new RuntimeException('Rule is missing identifier.');
        }

        $this->ruleIdentifier = $ruleIdentifier;

        return $this;
    }

    /**
     *
     * Generates CSS for current rule and its children
     *
     * @return string
     */
    public function generate()
    {
        $ruleString = '';

        $ruleIdentifier = "{$this->getRuleFullIdentifier()}";

        $definition = [];

        $attributes = $this->getAttributes();

        array_walk($attributes, function ($value, $property) use (&$definition) {
            $definition[] = "{$property}: {$value}";
        });

        if (false === empty($definition)) {
            $ruleString = "\n{$ruleIdentifier} {" . implode('; ', $definition) . ";}";
        }

        foreach ($this->additionalRules as $additionalRule) {
            $ruleString .= "\n{$additionalRule->generate()}";
        }

        foreach ($this->ruleChildren as $ruleChild) {
            $ruleString .= "\n{$ruleChild->generate()}";
        }

        foreach ($this->ruleSubRules as $subRule) {
            $ruleString .= "\n{$subRule->generate()}";
        }

        return $ruleString;
    }
}
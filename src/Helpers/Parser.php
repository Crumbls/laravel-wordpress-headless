<?php

namespace Crumbls\LaravelWordpress\Helpers;

use Crumbls\LaravelWordpress\Helpers\Beautifier;
use \Illuminate\View\ComponentAttributeBag as BaseAttributeBag;

class Parser {
    protected static $instance;

    /**
     * Instance handler.
     * @return mixed
     */
    public static function getInstance() {
        if (!static::$instance) {
            $class = get_called_class();
            static::$instance = new $class;
        }
        return static::$instance;
    }

    public function process(string $content) : string {
        $shortcodes = $this->getShortcodes($content);
        $content = $this->stripInvalidParagaphTags($content, $shortcodes);
        $content = $this->convertShortcodesToComponents($content, $shortcodes);
        $content = $this->render($content);
        return $content;
    }

    private function render(string $content) : string {
        $data = [];

        $data['__env'] = app(\Illuminate\View\Factory::class);

        $php = \Blade::compileString($content);

        $obLevel = ob_get_level();
        ob_start();
        extract($data, EXTR_SKIP);
        try {
            eval('?' . '>' . $php);
        } catch (\Exception $e) {
            while (ob_get_level() > $obLevel) ob_end_clean();
            throw $e;
        } catch (\Throwable $e) {
            while (ob_get_level() > $obLevel) ob_end_clean();
            throw $e;
//            throw new \Throwable($e);
        }

        return ob_get_clean();
    }

    /**
     * WordPress adds paragraphs ( via wpautop ) to content that may not always be necessary.
     * We strip out paragraph tags that wrap shortcodes.
     * It's also kind of resource intensive, so we provide an option to not do this.
     * @param string $content
     * @return string
     */
    protected function stripInvalidParagaphTags(string $content, array $shortcodes = null) : string {
        if (!config('wordpress.remove_invalid_paragraphs', true)) {
            return $content;
        }

        if ($shortcodes === null) {
            $shortcodes = $this->getShortcodes($content);
        }

        if (!$shortcodes) {
            return $content;
        }

        $tagregexp = implode( '|', array_map( 'preg_quote',  $shortcodes ) );
        $spaces    = '[\r\n\t ]|\xC2\xA0|&nbsp;';

        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound,WordPress.WhiteSpace.PrecisionAlignment.Found -- don't remove regex indentation
        $pattern = '/'
            . '<p>'                              // Opening paragraph.
            . '(?:' . $spaces . ')*+'            // Optional leading whitespace.
            . '('                                // 1: The shortcode.
            .     '\\[\/?'                          // Opening bracket.
            .     "($tagregexp)"                 // 2: Shortcode name.
            .     '(?![\\w-])'                   // Not followed by word character or hyphen.
            // Unroll the loop: Inside the opening shortcode tag.
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash.
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket.
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash.
            .     ')*?'
            .     '(?:'
            .         '\\/\\]'                   // Self closing tag and closing bracket.
            .     '|'
            .         '\\]'                      // Closing bracket.
            .'))/'
        ;

        do {
            $content = preg_replace( $pattern, '$1', $content, 100, $count);
        } while ($count);

// phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound,WordPress.WhiteSpace.PrecisionAlignment.Found -- don't remove regex indentation
        $pattern = '/'
            . '('                                // 1: The shortcode.
            .     '\\[\/?'                          // Opening bracket.
            .     "($tagregexp)"                 // 2: Shortcode name.
            .     '(?![\\w-])'                   // Not followed by word character or hyphen.
            // Unroll the loop: Inside the opening shortcode tag.
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash.
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket.
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash.
            .     ')*?'
            .     '(?:'
            .         '\\/\\]'                   // Self closing tag and closing bracket.
            .     '|'
            .         '\\]'                      // Closing bracket.
            .'))'
            . '(?:' . $spaces . ')*+'            // Optional leading whitespace.
            . '<\/p>'                              // Opening paragraph.
            .'/'

        ;

        do {
            $content = preg_replace( $pattern, '$1', $content, 100, $count);
        } while ($count);

        return $content;
    }

    protected function convertShortcodesToComponents(string $content, array $shortcodes) : string {
        if (!config('wordpress.shortcodes_convert', true)) {
            return $content;
        }

        if ($shortcodes === null) {
            $shortcodes = $this->getShortcodes($content);
        }

        if (!$shortcodes) {
            return $content;
        }

        // Map shortcodes.
        $shortcodes = array_map(function($e) {
            return 'x-'.strtolower(str_replace('_', '-', $e));
        }, array_combine($shortcodes, $shortcodes));

        // Convert closing tags.
        $content = str_replace(array_map(function($shortcode) {
            return '[/'.$shortcode.']';
        }, array_keys($shortcodes)),
            array_map(function($shortcode) {
                return '</'.$shortcode.'>';
            }, $shortcodes),
            $content);

        // Build our regex to handle this all.
        $tagregexp = implode( '|', array_map( 'preg_quote',  array_keys($shortcodes) ) );
        $spaces    = '[\r\n\t ]|\xC2\xA0|&nbsp;';

        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound,WordPress.WhiteSpace.PrecisionAlignment.Found -- don't remove regex indentation
        $pattern = '/'
            . '(?:' . $spaces . ')*+'            // Optional leading whitespace.
            . '('                                // 1: The shortcode.
            .     '\\[\/?'                          // Opening bracket.
            .     "($tagregexp)"                 // 2: Shortcode name.
            .     '(?![\\w-])'                   // Not followed by word character or hyphen.
            // Unroll the loop: Inside the opening shortcode tag.
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash.
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket.
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash.
            .     ')*?'
            .     '(?:'
            .         '\\/\\]'                   // Self closing tag and closing bracket.
            .     '|'
            .         '\\]'                      // Closing bracket.
            .'))/'
        ;

        // Convert open tags.
        while (preg_match($pattern, $content, $inner)) {
            $shortcode = array_key_exists($inner[2], $shortcodes) ? $shortcodes[$inner[2]] : false;
            if (!$shortcode) {
                continue;
            }
            $replacement = trim(substr($inner[0], strlen($inner[2])+1, -1));
                $temp = $this->parseAttributes($replacement);
                $replacement = '';
                foreach($temp as $k => $v) {
                    $replacement .= ' '.$k.'="'.$v.'"';
                }
                $content = str_replace($inner[0], '<'.$shortcode.$replacement.'>', $content);
        }

        if (false) {
            return $content;
        }

        /**
         * Make sure html is valid.
         */
        preg_match_all('#<(.*?)[\s|>]#', $content, $tags);
        $tags = array_unique(array_merge($tags[1], $shortcodes));
        $tags = array_filter($tags, function($e) { return $e[0] != '/'; });

        $content = $this->restructure($content, $tags);

        return $content;


//echo __FILE__;exit;
//echo $content;
  //      exit;
//        dd($content);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
//        $dom->loadHTML($content);
        $dom->loadXML($content);
echo $dom->saveXML();exit;
        if (true) {
            return $dom->saveXML();
        }

//        libxml_clear_errors();
        //$body = $dom->documentElement->firstChild->firstChild;
        $body = null;

        $beautify = new Beautifier([
            'indent_inner_html' => true,
            'indent_char' => "\t"
        ]);

        return $beautify->beautify($dom->saveXML($body));
    }

    /**
     * Get all shortcodes in this string.
     * @param string $content
     * @return array|string
     */
    private function getShortcodes(string $content) : array {
        /**
         * Extract shortcodes.
         */
        if (!preg_match_all('#\[([a-z].*?)[\s|\]]#i', $content, $shortcodes)) {
            return [];
        }

        /**
         * Get all shortcodes.
         */
        $shortcodes = array_values(array_unique($shortcodes[1]));

        /**
         * Intersect with registered components.
         * You could just override.  Sadly, we are running short on time, as per usual.
         */
        if (true) {
                $intersect = array_map(function($e) {
                    return strtolower(str_replace('-', '_', $e));
                }, array_keys(\Blade::getClassComponentAliases()));
                $shortcodes = array_intersect($intersect, $shortcodes);
        }

        return $shortcodes;
    }

    private function getShortcodeRegex( $tagnames = [] ) {
        $tagregexp = implode( '|', array_map( 'preg_quote', $tagnames ) );

        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag().
        // Also, see shortcode_unautop() and shortcode.js.

        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
        return '\\['                             // Opening bracket.
            . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]].
            . "($tagregexp)"                     // 2: Shortcode name.
            . '(?![\\w-])'                       // Not followed by word character or hyphen.
            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag.
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash.
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket.
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash.
            .     ')*?'
            . ')'
            . '(?:'
            .     '(\\/)'                        // 4: Self closing tag...
            .     '\\]'                          // ...and closing bracket.
            . '|'
            .     '\\]'                          // Closing bracket.
            .     '(?:'
            .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags.
            .             '[^\\[]*+'             // Not an opening bracket.
            .             '(?:'
            .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag.
            .                 '[^\\[]*+'         // Not an opening bracket.
            .             ')*+'
            .         ')'
            .         '\\[\\/\\2\\]'             // Closing shortcode tag.
            .     ')?'
            . ')'
            . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]].
        // phpcs:enable
    }


    private function parseAttributes(string $input) : array {
        if ( '' === $input ) {
            return [];
        }

        $input = trim($input);
        $input = str_replace('&#8221; ', '&#8243; ', $input);

        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
        $regex =
            '(?:'
            .     '[_a-zA-Z][-_a-zA-Z0-9:.]*' // Attribute name.
            . '|'
            .     '\[\[?[^\[\]]+\]\]?'        // Shortcode in the name position implies unfiltered_html.
            . ')'
            . '(?:'               // Attribute value.
            .     '\s*=\s*'       // All values begin with '='.
            .     '(?:'
            .         '"[^"]*"'   // Double-quoted.
            .     '|'
            .         "'[^']*'"   // Single-quoted.
            .     '|'
            .         '[^\s"\']+' // Non-quoted.
            .         '(?:\s|$)'  // Must have a space.
            .     ')'
            . '|'
            .     '(?:\s|$)'      // If attribute has no value, space is required.
            . ')'
            . '\s*';              // Trailing space is optional except as mentioned above.
        // phpcs:enable

        // Although it is possible to reduce this procedure to a single regexp,
        // we must run that regexp twice to get exactly the expected result.

        $validation = "%^($regex)+$%";
        $extraction = "%$regex%";

        if ( 1 === preg_match( $validation, $input ) ) {
            preg_match_all( $extraction, $input, $attrarr );
            $attrarr = $attrarr[0];

            $attrarr = array_map(function($e) {
                $e = explode('=', $e, 2);
                $e[1] = preg_replace('#^&\#8\d+;(.*)&\#8\d+;\s?$#', '$1', $e[1]);
                $e[1] = htmlspecialchars($e[1]);
                return $e;
            }, $attrarr);

            $attrarr = array_column($attrarr, 1, 0);

            return $attrarr;
        } else {
            /**
             * A very ugly and error prone way to try and extract shortcodes.
             */
            $pattern = '/(\\w+)\s*=\\s*("[^"]*"|\'[^\']*\'|[^"\'\\s>]*)/';
            preg_match_all($pattern, $input, $matches, PREG_SET_ORDER);
            $attrarr = array();
            foreach ($matches as $match) {
                if (($match[2][0] == '"' || $match[2][0] == "'") && $match[2][0] == $match[2][strlen($match[2])-1]) {
                    $match[2] = substr($match[2], 1, -1);
                }
                $name = strtolower($match[1]);

                // More ugly cleanup.

                $match[2] = preg_replace('#^&\#82\d{2};#', '', $match[2]);
                $match[2] = preg_replace('#&\#82\d{2};$#', '', $match[2]);

                $value = html_entity_decode($match[2]);

                switch ($name) {
                    case 'class':
                        $attrarr[$name] = preg_split('/\s+/', trim($value));
                        break;
                    case 'style':
                        // parse CSS property declarations
                        break;
                    default:
                        $attrarr[$name] = $value;
                }
            }
            return $attrarr;
        }
        return [];
    }


    /**
     * extract_tags()
     * Extract specific HTML tags and their attributes from a string.
     *
     * You can either specify one tag, an array of tag names, or a regular expression that matches the tag name(s).
     * If multiple tags are specified you must also set the $selfclosing parameter and it must be the same for
     * all specified tags (so you can't extract both normal and self-closing tags in one go).
     *
     * The function returns a numerically indexed array of extracted tags. Each entry is an associative array
     * with these keys :
     *  tag_name    - the name of the extracted tag, e.g. "a" or "img".
     *  offset      - the numberic offset of the first character of the tag within the HTML source.
     *  contents    - the inner HTML of the tag. This is always empty for self-closing tags.
     *  attributes  - a name -> value array of the tag's attributes, or an empty array if the tag has none.
     *  full_tag    - the entire matched tag, e.g. '<a href="http://example.com">example.com</a>'. This key
     *                will only be present if you set $return_the_entire_tag to true.
     *
     * @param string $html The HTML code to search for tags.
     * @param string|array $tag The tag(s) to extract.
     * @param bool $selfclosing Whether the tag is self-closing or not. Setting it to null will force the script to try and make an educated guess.
     * @param bool $return_the_entire_tag Return the entire matched tag in 'full_tag' key of the results array.
     * @param string $charset The character set of the HTML code. Defaults to ISO-8859-1.
     *
     * @return array An array of extracted tags, or an empty array if no matching tags were found.
     */
    function extract_tags( $html, $tag, $selfclosing = null, $return_the_entire_tag = false, $charset = 'ISO-8859-1' ){

        if ( is_array($tag) ){
            $tag = implode('|', $tag);
        }

        //If the user didn't specify if $tag is a self-closing tag we try to auto-detect it
        //by checking against a list of known self-closing tags.
        $selfclosing_tags = array( 'area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta', 'col', 'param' );
        if ( is_null($selfclosing) ){
            $selfclosing = in_array( $tag, $selfclosing_tags );
        }

        //The regexp is different for normal and self-closing tags because I can't figure out
        //how to make a sufficiently robust unified one.
        if ( $selfclosing ){
            $tag_pattern =
                '@<(?P<tag>'.$tag.')           # <tag
            (?P<attributes>\s[^>]+)?       # attributes, if any
            \s*/?>                   # /> or just >, being lenient here 
            @xsi';
        } else {
            $tag_pattern =
                '@<(?P<tag>'.$tag.')           # <tag
            (?P<attributes>\s[^>]+)?       # attributes, if any
            \s*>                 # >
            (?P<contents>.*?)         # tag contents
            </(?P=tag)>               # the closing </tag>
            @xsi';
        }

        $attribute_pattern =
            '@
        (?P<name>\w+)                         # attribute name
        \s*=\s*
        (
            (?P<quote>[\"\'])(?P<value_quoted>.*?)(?P=quote)    # a quoted value
            |                           # or
            (?P<value_unquoted>[^\s"\']+?)(?:\s+|$)           # an unquoted value (terminated by whitespace or EOF) 
        )
        @xsi';

        //Find all tags
        if ( !preg_match_all($tag_pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ){
            //Return an empty array if we didn't find anything
            return array();
        }

        $tags = array();
        foreach ($matches as $match){

            //Parse tag attributes, if any
            $attributes = array();
            if ( !empty($match['attributes'][0]) ){

                if ( preg_match_all( $attribute_pattern, $match['attributes'][0], $attribute_data, PREG_SET_ORDER ) ){
                    //Turn the attribute data into a name->value array
                    foreach($attribute_data as $attr){
                        if( !empty($attr['value_quoted']) ){
                            $value = $attr['value_quoted'];
                        } else if( !empty($attr['value_unquoted']) ){
                            $value = $attr['value_unquoted'];
                        } else {
                            $value = '';
                        }

                        //Passing the value through html_entity_decode is handy when you want
                        //to extract link URLs or something like that. You might want to remove
                        //or modify this call if it doesn't fit your situation.
                        $value = html_entity_decode( $value, ENT_QUOTES, $charset );

                        $attributes[$attr['name']] = $value;
                    }
                }

            }

            $tag = array(
                'tag_name' => $match['tag'][0],
                'offset' => $match[0][1],
                'contents' => !empty($match['contents'])?$match['contents'][0]:'', //empty for self-closing tags
                'attributes' => $attributes,
            );
            if ( $return_the_entire_tag ){
                $tag['full_tag'] = $match[0][0];
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    protected function restructure(string $content, array $tags = []) : string {
        /**
         * Convert the input to an array.
         * @param $input
         * @return array
         */
        $recursive = function(&$input) use (&$recursive, $tags) : array {
            if (is_string($input)) {
                $input = $this->extract_tags($input, $tags);
            }

            if (!is_array($input)) {
                echo 'error123';
                exit;
            }

            $keys = array_keys($input);
            $isSequential = count($keys) == count(array_filter($keys, 'is_numeric'));

            if ($isSequential) {
                foreach($input as $idx => $row) {
                    $input[$idx]['children'] = $recursive($row['contents'], $tags);

                    /**
                     * This is an  ugly fix for DIVI where it sends columns inside of sections sometimes.
                     * They should be wrapped by rows, IMHO.  Yeah, we shouldn't do this, but we need something
                     * short term and fast.
                     */
                    if (true && $row['tag_name'] == 'x-et-pb-section' && $input[$idx]['children']) {
                        $tagNames = array_column($input[$idx]['children'], 'tag_name');
                        $x = count($tagNames);
                        $y = count(array_filter($tagNames, function($e) {
                            return $e == 'x-et-pb-column';
                        }));
                        if ($y && $y == $x) {
                            $input[$idx]['children'] = [
                                [
                                'tag_name' => 'x-et-pb-row',
                                'offset' => null,
                                'attributes' => [
                                    'autogenerated' => true
                                ],
                                'children' => $input[$idx]['children']
                                ]
                            ];
                        }
                    }

                    if ($input[$idx]['children']) {
                        unset($input[$idx]['contents']);
                    }
                }
            } else {
                echo __LINE__;
                print_r($input);
            }

            return $input;
        };

        $content = $recursive($content, $tags);

        /**
         * Poor programming practice in effort to save time for now.  Sorry!
         * @param $input
         * @return string
         */
        $recursive = function(&$input, int $indent = 0) use (&$recursive) : string {
            $return = [];
            foreach($input as $idx => $row) {
                if (!array_key_exists('children', $row)) {
                    echo 'hjmmm';
                    continue;
                }

                $attributes = str_replace("=", '="', http_build_query($row['attributes'], null, '" ', PHP_QUERY_RFC3986)).'"';

                if (!$row['children'] && !$row['contents']) {
                    $return[] = str_repeat("\t", $indent).sprintf('<%s %s />', $row['tag_name'], $attributes);
                    continue;
                }

                $return[] = str_repeat("\t", $indent).sprintf('<%s %s>', $row['tag_name'], $attributes);
                if (array_key_exists('contents', $row) && $row['contents']) {
//                    $return[] = 'inner guts';
                    $return[] = $row['contents'];
//                  print_r($row['contents']);
                } else if ($row['children']) {
                    $return[] = $recursive($row['children'], ($indent + 1));
                }
                $return[] = str_repeat("\t", $indent).sprintf('</%s>', $row['tag_name']);
            }
            $return = implode("\r\n", $return);
            return $return;
        };

        $content = $recursive($content);

        return $content;
    }
}


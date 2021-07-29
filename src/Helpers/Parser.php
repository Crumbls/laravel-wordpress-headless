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
            throw new \FatalThrowableError($e);
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

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadHTML($content);
        libxml_clear_errors();
        $body = $dom->documentElement->firstChild->firstChild;

        $beautify = new Beautifier([
            'indent_inner_html' => true,
            'indent_char' => "\t"
        ]);

        return $beautify->beautify($dom->saveHTML($body));
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
}


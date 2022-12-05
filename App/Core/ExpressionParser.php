<?php

declare(strict_types=1);

namespace App\Core;

class ExpressionParser
{
    // Operators listed with evaluation precedence/associativity in mind
    private const OPERATORS = [
        ['right', '**'],
        ['left', ['++', '--', '~']],
        ['left', '!'],
        ['left', ['*', '/', '%']],
        ['left', ['+', '-']],
        ['left', ['<<', '>>']],
        ['left', ['<=', '>=', '<',  '>']],
        ['left', ['===', '!==', ',', '==', '!=', '<>']],
        ['left', '&'],
        ['left', '^'],
        ['left', '|'],
        ['left', '&&'],
        ['left', '||'],
        ['right', '??'],
        ['right', ['**=', '<<=', '>>=', '??=', '+=', '-=', '*=', '/=', '.=', '%=', '&=', '|=', '^=', '=']],
        ['left', 'and'],
        ['left', 'xor'],
        ['left', 'or']
    ];

    // Find operands, [opers] is just a placeholder which will be replaced as needed
    private const LEFT_ASSOC_REG = '((?:-|\+)?\d+(?:\.\d+(?:E[+-]\d+)?)?|true|false|null|\'.*?\'|".*?")\s*?([opers])\s*?((?:-|\+)?\d+(?:\.\d+(?:E[+-]\d+)?)?|true|false|null|\'.*?\'|".*?")';
    // Right associativity operators, we need to find and evaluate them from right to left i.e. 2**3**4 must be evaluated as 2**(3**4)
    private const RIGHT_ASSOC_REG = '[^+-]*\K' . self::LEFT_ASSOC_REG;

    // Escape operators inside sing/double quotes so related operands won't be parsed
    private static function escapeOperators($exp, $opers): string
    {
        $exp = preg_replace_callback('#([\'"]).*?\1#', function($match)use($opers){
            $escaped = preg_replace("#($opers)#", "[:$1:]", $match[0]);
            return $escaped;
        }, $exp);

        return $exp;
    }

    private static function unescapeOperators($exp): string
    {
       return preg_replace('#\[:(.*?):\]#', '$1', $exp);
    }

    private static function valueOf($val)
    {
        // Un-escape operators
        $val = self::unescapeOperators($val);

        if ($val == 'true') {
            $val = true;
        } elseif ($val == 'false') {
            $val = false;
        } elseif ($val == 'null') {
            $val = null;
        } elseif (is_numeric($val)) {
            $val = +$val;
        }

        return $val;
    }

    private static function stringOf($val)
    {
        if (is_bool($val) && $val == true) {
            $val = 'true';
        } elseif (is_bool($val) && $val == false) {
            $val = 'false';
        } elseif (is_null($val)) {
            $val = 'null';
        } elseif (is_numeric($val)) {
            $val = (string)$val;
        }

        return $val;
    }

    // Handling plus/minus multiplication (i.e. +-+-5 => +5)
    private static function solveSignMultiplication($exp)
    {
        // Handling plus/minus multiplication (i.e. +-+-5 => +5)
        $exp = preg_replace_callback('#[\+\-]{3,}#', function ($matches) {
            // count minus signs
            $minuses = substr_count($matches[0], '-');
            // Even minuses make plus, odd minuses make minus
            $sign = $minuses % 2 ? '-' : '+';

            return $sign;
        }, $exp);

        return $exp;
    }

    public static function parse($exp)
    {
        // Find parenthese (if any) and parse them recursively
        while (preg_match("#\((\s*(?>[^()]+|(?R))*\s*)\)#", $exp, $match)) {
            $res = self::parse($match[1]);
            $exp = str_replace($match[0], $res, $exp);
        }

        $exp = self::solveSignMultiplication($exp);

        foreach (self::OPERATORS as [$assoc, $opers]) {
            if (is_array($opers)) {
                $opers = array_map(function ($oper) {
                    return preg_quote($oper);
                }, $opers);

                $opers = implode('|', $opers);
            } else {
                $opers = preg_quote($opers);
            }

            $reg = '#' . str_replace('[opers]', $opers, $assoc == 'left' ? self::LEFT_ASSOC_REG : self::RIGHT_ASSOC_REG) . '#';

            $exp = self::escapeOperators($exp, $opers);

            while (preg_match($reg, $exp, $match)) {
                $res = self::evaluate($match[1], $match[3], $match[2]);
                $exp = str_replace($match[0], self::stringOf($res), $exp);
            }
        }

        return self::valueOf($exp);
    }

    private static function evaluate($a, $b, $oper)
    {
        $res = null;

        // Get correct type value
        $a = self::valueOf($a);
        $b = self::valueOf($b);

        try {
            switch ($oper) {
                case '**':
                    $res = $a ** $b;
                    break;
                case '+':
                    $res = $a + $b;
                    break;
                case '-':
                    $res = $a - $b;
                    break;
                case '++':
                    $res = $a++;
                    break;
                case '--':
                    $res = $a--;
                    break;
                case '~':
                    $res = ~$a;
                    break;
                case '!':
                    $res = !$a;
                    break;
                case '*':
                    $res = $a * $b;
                    break;
                case '/':
                    $res = $a / $b;
                    break;
                case '%':
                    $res = $a % $b;
                    break;
                case '<<':
                    $res = $a << $b;
                    break;
                case '>>':
                    $res = $a >> $b;
                    break;
                case '<':
                    $res = $a < $b;
                    break;
                case '<=':
                    $res = $a <= $b;
                    break;
                case '>':
                    $res = $a > $b;
                    break;
                case '>=':
                    $res = $a >= $b;
                    break;
                case '==':
                    $res = $a == $b;
                    break;
                case '!=':
                    $res = $a != $b;
                    break;
                case '===':
                    $res = $a === $b;
                    break;
                case '!==':
                    $res = $a !== $b;
                    break;
                case '<>':
                    $res = $a <> $b;
                    break;
                case '<=>':
                    $res = $a <=> $b;
                    break;
                case '&':
                    $res = $a & $b;
                    break;
                case '^':
                    $res = $a ^ $b;
                    break;
                case '|':
                    $res = $a | $b;
                    break;
                case '&&':
                    $res = $a && $b;
                    break;
                case '||':
                    $res = $a || $b;
                    break;
                case '??':
                    $res = $a ?? $b;
                    break;
                case '=':
                    $res = ($a = $b);
                    break;
                case '+=':
                    $res = ($a += $b);
                    break;
                case '-=':
                    $res = ($a -= $b);
                    break;
                case '*=':
                    $res = ($a *= $b);
                    break;
                case '**=':
                    $res = ($a **= $b);
                    break;
                case '/=':
                    $res = ($a /= $b);
                    break;
                case '.=':
                    $res = ($a .= $b);
                    break;
                case '%=':
                    $res = ($a %= $b);
                    break;
                case '&=':
                    $res = ($a &= $b);
                    break;
                case '|=':
                    $res = ($a |= $b);
                    break;
                case '^=':
                    $res = ($a ^= $b);
                    break;
                case '<<=':
                    $res = ($a <<= $b);
                    break;
                case '>>=':
                    $res = ($a >>= $b);
                    break;
                case '??=':
                    $res = ($a ??= $b);
                    break;
                case 'and':
                    $res = $a and $b;
                    break;
                case 'xor':
                    $res = $a xor $b;
                    break;
                case 'or':
                    $res = $a or $b;
                    break;
                default:
                    $res = "$a $oper $b";
            }
        } catch (\Throwable $e) {
            // Logger::log("Can't parse expression: " . implode('', [$a, $oper, $b]));
            return null;
        }

        if (is_numeric($res) && $res >= 0) {
            $res = "+$res";
        }

        return $res;
    }
}

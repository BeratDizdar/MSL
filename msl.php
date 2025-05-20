<?php
$stack = [];
$lang = '/help|f(quit|avg|var)|:((((\+|-|\*)(>))|(<|>|r|\.\*))\/)|(pop|dup|peek|view|drop|swap|clear|depth)|-?\d+(?:\.\d+)?|\(([^()]+)\)|(\*\*|\+|-|\*|\/|%|v-|\||~)/';

function dup() {$c=pop(); push($c); push($c);}
function average() {$a = pop(); push(array_sum($a)/count($a));}
function variance() {
    $a = pop();
    if (!is_array($a)) {
        echo "Error: variance expects an array\n";
        return;
    }
    $n = count($a);
    if ($n < 2) {
        echo "Error: variance requires at least two data points\n";
        return;
    }
    $mean = array_sum($a) / $n;
    $sum_sq_diff = 0;
    foreach ($a as $v) {
        $sum_sq_diff += ($v - $mean) ** 2;
    }
    push($sum_sq_diff / ($n - 1));
}

function foldr_sum() {
    $array = pop();
    $total = 0;
    foreach($array as $e) {
        $total += $e;
    }
    push($total);
}
function foldr_sub() {
    $array = pop();
    if (empty($array)) {
        push(0);
        return;
    }
    $total = array_shift($array); // İlk elemanla başla
    foreach($array as $e) {
        $total -= $e;
    }
    push($total);
}
function foldr_mul() {push(array_product(pop()));}
function sort_asc() {
    $array = pop();
    sort($array);
    push($array);
}
function sort_dsc() {
    $array = pop();
    rsort($array);
    push($array);
}
function push($x) {global $stack; array_push($stack, $x);}
function pop() {global $stack; return array_pop($stack);}
function peek() {
    global $stack;
    if (empty($stack)) {
        echo "[empty stack]";
        return null;
    }
    
    $top = end($stack);
    
    if (is_array($top)) {
        echo "[" . implode(", ", $top) . "]";
    } else {
        echo $top;
    }
    return $top;
}
function view() {
    global $stack;
    foreach ($stack as $i => $item) {
        echo "\n#$i: ";
        if (is_array($item)) {
            echo "[" . implode(", ", $item) . "]";
        } else {
            echo $item;
        }
    }
}
function drop() {
    pop();
}
function swap() {
    $a = pop();
    $b = pop();
    push($a);
    push($b);
}
function clear() {global $stack; $stack = array_diff($stack, $stack);}
function depth() {
    global $stack;
    push(count($stack));
}

function dot_product() {
    $a = pop();
    $b = pop();

    if (!is_array($a) || !is_array($b)) {
        echo "Error: dot_product expects two arrays\n";
        return;
    }

    if (count($a) !== count($b)) {
        echo "Error: Vector length mismatch for dot_product\n";
        return;
    }

    $result = 0;
    for ($i = 0; $i < count($a); $i++) {
        $result += $a[$i] * $b[$i];
    }

    push($result);
}

function unary_op(callable $f) {
    $a = pop();
    
    if (is_array($a)) {
        $res = array_map($f, $a);
        push($res);
    } elseif (is_numeric($a)) {
        push($f($a));
    } else {
        echo "Error: Unsupported type for unary operation\n";
    }
}

function negate() { unary_op(fn($x) => -$x); }
function sqrt_op() { unary_op(fn($x) => sqrt($x)); }
function abs_op() { unary_op(fn($x) => abs($x)); }
function reverse() {
    $a = pop();
    if (is_array($a)) {
        push(array_reverse($a));
    } else {
        echo "Error: reverse expects an array\n";
    }
}

function binary_op(callable $f) {
    $a = pop();
    $b = pop();

    if (is_array($a) && is_array($b)) {
        if (count($a) !== count($b)) {
            echo "Error: Vector length mismatch\n";
            return;
        }
        $res = [];
        for ($i = 0; $i < count($a); $i++) {
            $res[] = $f($b[$i], $a[$i]);
        }
        push($res);
    } elseif (is_array($a) && is_numeric($b)) {
        push(array_map(fn($x) => $f($b, $x), $a));
    } elseif (is_array($b) && is_numeric($a)) {
        push(array_map(fn($x) => $f($x, $a), $b));
    } else {
        push($f($b, $a));
    }
}

function add() { binary_op(fn($x, $y) => $x + $y); }
function sub() { binary_op(fn($x, $y) => $x - $y); }
function mul() { binary_op(fn($x, $y) => $x * $y); }
function div() { binary_op(fn($x, $y) => $x / $y); }
function power() { binary_op(fn($x, $y) => $x ** $y); }
function rem()  { binary_op(fn($x, $y) => $x % $y); }

function init_fn_list() {
    $dispatch = [
        'help' => fn() => print_help(),
        'fquit' => fn() => exit(0),
        'favg' => fn() => average(),
        'fvar' => fn() => variance(),

        ':+>/' => fn() => foldr_sum(),
        ':->/' => fn() => foldr_sub(),
        ':*>/' => fn() => foldr_mul(),
        ':</' => fn() => sort_asc(),
        ':>/' => fn() => sort_dsc(),
        ':r/' => fn() => reverse(),
        ':.*/' => fn() => dot_product(),
        
        'pop' => fn() => pop(),
        'dup' => fn() => dup(),
        'peek' => fn() => peek(),
        'view' => fn() => view(),
        'drop' => fn() => drop(),
        'swap' => fn() => swap(),
        'clear' => fn() => clear(),
        'depth' => fn() => depth(),

        '+' => fn() => add(),
        '-' => fn() => sub(),
        '*' => fn() => mul(),
        '**' => fn() => power(),
        '/' => fn() => div(),
        '%' => fn() => rem(),

        '~' => fn() => negate(),
        'v-' => fn() => sqrt_op(),
        '|' => fn() => abs_op(),
    ];
    return $dispatch;
}

function print_help() {
    echo "MSL (Mathematical Stack Language) Help:\n\n";
    
    echo "MATHEMATICAL OPERATIONS:\n";
    echo "  +       Addition (a b + → a+b)\n";
    echo "  -       Subtraction (a b - → a-b)\n";
    echo "  *       Multiplication (a b * → a*b)\n";
    echo "  /       Division (a b / → a/b)\n";
    echo "  %       Modulo (a b % → a%b)\n";
    echo "  **      Exponentiation (a b ** → a^b)\n";
    echo "  ~       Negation (a ~ → -a)\n";
    echo "  v-      Square root (a v- → sqrt(a))\n";
    echo "  |       Absolute value (a | → abs(a))\n\n";
    
    echo "STATISTICAL FUNCTIONS:\n";
    echo "  favg    Calculate average of array\n";
    echo "  fvar    Calculate variance of array\n\n";
    
    echo "STACK OPERATIONS:\n";
    echo "  pop     Remove top element\n";
    echo "  dup     Duplicate top element\n";
    echo "  peek    Show top element\n";
    echo "  view    Show entire stack\n";
    echo "  drop    Remove top element (without returning)\n";
    echo "  swap    Swap top two elements\n";
    echo "  clear   Clear entire stack\n";
    echo "  depth   Push stack depth (count) onto stack\n\n";
    
    echo "ARRAY OPERATIONS:\n";
    echo "  :+>/    Fold right with addition (sum array)\n";
    echo "  :->/    Fold right with subtraction\n";
    echo "  :*>/    Fold right with multiplication\n";
    echo "  :</     Sort array ascending\n";
    echo "  :>/     Sort array descending\n";
    echo "  :r/     Reverse array\n";
    echo "  :.*/    Dot product of two arrays\n\n";
    
    echo "SYSTEM:\n";
    echo "  fquit   Exit the interpreter\n";
    echo "  help    Show this help message\n";
    
    echo "\nEXAMPLES:\n";
    echo "  (1 2 3) (4 5 6) :.*/ → 32 (dot product)\n";
    echo "  (5 1 3) :>/ → [5, 3, 1] (sort descending)\n";
    echo "  9 v- → 3 (square root)\n";
    echo "  1 2 3 depth → 1 2 3 3\n";
    echo "  10 20 swap → 20 10\n";
}


function lexer($input) {
    global $lang;
    preg_match_all($lang, $input, $tokens, PREG_PATTERN_ORDER);
    return $tokens[0];
}

function read() {
    $input = trim(fgets(STDIN));
    return lexer($input);
}

function eval_tokens($fn_list, $tokens) {

    foreach($tokens as $token) {
        if (isset($fn_list[$token])) {
            $fn_list[$token]();
        } elseif (is_numeric($token)) {
            push((int)$token);
        } elseif (preg_match('/^\(([^()]+)\)$/', $token, $matches)) {
            $elements = preg_split('/\s+/', trim($matches[1]));
            $vector = array_map('intval', $elements);
            push($vector);
        } else {
            echo "Unknown token: $token\n";
        }
    }
    
}

function main() {
    $fn_list = init_fn_list();
    echo "MSL - REPL\n";
    while(1) {
        echo "\n<< ";
        $tokens = read();
        #print_r($tokens);
        echo ">> ";
        eval_tokens($fn_list, $tokens);
        peek();
    }
    
}
main();

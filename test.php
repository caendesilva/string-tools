<?php

// ANSI color codes
const RESET = "\033[0m";
const RED = "\033[31m";
const GREEN = "\033[32m";
const YELLOW = "\033[33m";
const BLUE = "\033[34m";
const MAGENTA = "\033[35m";
const CYAN = "\033[36m";

function test(string $command, string $input, string $expected, bool $strict = true): bool
{
    echo MAGENTA."Testing command: $command".RESET."\n";
    echo CYAN.'Input: '.RESET."$input\n";

    $start_time = microtime(true);
    $output = shell_exec('php '.__DIR__."/string-tools.php $command $input");
    $end_time = microtime(true);

    $execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
    $output = trim($output);

    if ($strict) {
        $passed = $output === $expected;
    } else {
        $passed = str_contains($output, $expected);
    }

    echo BLUE.'Expected: '.RESET."$expected\n";
    echo YELLOW.'Got:      '.RESET."$output\n";

    if ($passed) {
        echo GREEN.'✓ PASS'.RESET;
    } else {
        echo RED.'✗ FAIL'.RESET;
    }

    echo " (Execution time: {$execution_time}ms)\n\n";

    return $passed;
}

function runTests(): int
{
    $total_tests = 0;
    $passed_tests = 0;
    $start_time = microtime(true);

    $tests = [
        ['help', '', 'String Tools CLI', false],
        ['kebab', 'Hello World', 'hello-world'],
        ['snake', 'Hello World', 'hello_world'],
        ['camel', 'hello world', 'helloWorld'],
        ['studly', 'hello world', 'HelloWorld'],
        ['lower', 'Hello World', 'hello world'],
        ['upper', 'Hello World', 'HELLO WORLD'],
        ['title', 'hello world', 'Hello World'],
        ['headline', 'hello world', 'Hello World'],
        ['slug', 'hello world', 'hello-world'],
        ['sentence', 'hello world', 'Hello world'],
    ];

    foreach ($tests as $test) {
        $total_tests++;
        if (test(...$test)) {
            $passed_tests++;
        }
    }

    $end_time = microtime(true);
    $total_time = round(($end_time - $start_time) * 1000, 2);

    echo str_repeat('-', 40)."\n";
    echo BLUE."Test Summary:\n".RESET;
    echo "Total tests:  $total_tests\n";
    echo GREEN."Passed tests: $passed_tests\n".RESET;
    echo RED.'Failed tests: '.($total_tests - $passed_tests)."\n".RESET;
    echo YELLOW."Total time:   {$total_time}ms\n".RESET;

    if ($passed_tests === $total_tests) {
        echo GREEN."\n✨ All tests passed! ✨\n".RESET;

        return 0; // Success exit code
    } else {
        echo RED."\n❌ Some tests failed. Please review the output above.\n".RESET;

        return 1; // Failure exit code
    }
}

exit(runTests());

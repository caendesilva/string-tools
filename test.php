<?php

// The declared test cases
$tests = [];

test('help', '', 'String Tools CLI', false);
test('kebab', 'Hello World', 'hello-world');
test('snake', 'Hello World', 'hello_world');
test('camel', 'hello world', 'helloWorld');
test('studly', 'hello world', 'HelloWorld');
test('lower', 'Hello World', 'hello world');
test('upper', 'Hello World', 'HELLO WORLD');
test('title', 'hello world', 'Hello World');
test('headline', 'hello world', 'Hello World');
test('slug', 'hello world', 'hello-world');
test('sentence', 'hello world', 'Hello world');
test('count', 'hello world', '11');
test('words', 'hello world', '2');

// ANSI color codes
const RESET = "\033[0m";
const RED = "\033[31m";
const GREEN = "\033[32m";
const YELLOW = "\033[33m";
const BLUE = "\033[34m";
const MAGENTA = "\033[35m";
const CYAN = "\033[36m";

/** Declare a test case */
function test(string $command, string $input, string $expected, bool $strict = true): void
{
    global $tests;

    $tests[] = [$command, $input, $expected, $strict];
}

/** Run a test case and print the result */
function runTest(string $command, string $input, string $expected, bool $strict = true): bool
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

/** Run all test cases and print the summary */
function runTests(): int
{
    $totalTests = 0;
    $passedTests = 0;
    $startTime = microtime(true);

    global $tests;

    foreach ($tests as $test) {
        $totalTests++;
        if (runTest(...$test)) {
            $passedTests++;
        }
    }

    $endTime = microtime(true);
    $totalTime = round(($endTime - $startTime) * 1000, 2);

    echo str_repeat('-', 40)."\n";
    echo BLUE."Test Summary:\n".RESET;
    echo "Total tests:  $totalTests\n";
    echo GREEN."Passed tests: $passedTests\n".RESET;
    echo RED.'Failed tests: '.($totalTests - $passedTests)."\n".RESET;
    echo YELLOW."Total time:   {$totalTime}ms\n".RESET;

    if ($passedTests === $totalTests) {
        echo GREEN."\n✨ All tests passed! ✨\n".RESET;

        return 0; // Success exit code
    } else {
        echo RED."\n❌ Some tests failed. Please review the output above.\n".RESET;

        return 1; // Failure exit code
    }
}

exit(runTests());

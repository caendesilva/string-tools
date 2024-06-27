<?php

function test(string $command, string $input, string $expected, bool $strict = true): void
{
    $output = shell_exec("php ".__DIR__."/string-tools.php $command $input");
    $output = trim($output);

    if ($strict) {
        $passed = $output === $expected;
    } else {
        $passed = str_contains($output, $expected);
    }

    if (! $passed) {
        echo "Test failed for command: $command\n";
        echo "Expected: $expected\n";
        echo "Got: $output\n";
    } else {
        echo "Test passed for command: $command\n";
    }
}

test('help', '', 'String Tools CLI', false);

test('kebab', 'Hello World', 'hello-world');

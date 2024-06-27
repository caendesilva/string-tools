<?php

function test(string $command, string $input, string $expected): void
{
    $output = shell_exec("php ".__DIR__."/string-tools.php $command $input");
    $output = trim($output);

    if ($output !== $expected) {
        echo "Test failed for command: $command\n";
        echo "Expected: $expected\n";
        echo "Got: $output\n";
    }
}

test('kebab', 'Hello World', 'hello-world');

#!/usr/bin/env php
<?php

// Bundled vendor dependencies

/**
 * This file is part of MinimaPHP.
 *
 * @license MIT License
 * @author Caen De Silva
 * @copyright Copyright (c) 2023 Caen De Silva
 *
 * @link https://github.com/caendesilva/MinimaPHP
 *
 * @version Minima::VERSION
 */
interface Minima
{
    const VERSION = 'v0.1.0-dev';
}

interface Console
{
    const INPUT = STDIN;
    const OUTPUT = STDOUT;
}

interface ANSI_EXT
{
    const BRIGHT_RED = "\033[91m";
    const BRIGHT_GREEN = "\033[92m";
    const BRIGHT_YELLOW = "\033[93m";
    const BRIGHT_BLUE = "\033[94m";
    const BRIGHT_MAGENTA = "\033[95m";
    const BRIGHT_CYAN = "\033[96m";
    const BRIGHT_WHITE = "\033[97m";
}

interface XML_ANSI
{
    const INFO = ANSI::GREEN;
    const WARNING = ANSI::YELLOW;
    const ERROR = ANSI::RED;
    const COMMENT = ANSI::GRAY;
    const RESET = ANSI::RESET;
}

interface ANSI extends ANSI_EXT, XML_ANSI
{
    const BLACK = "\033[30m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const GRAY = "\033[90m"; // (Bright Black)
    const RESET = "\033[0m";
}

trait InteractsWithIO
{
    public function write(string $string): void
    {
        Output::write($string);
    }

    public function line(string $message = ''): void
    {
        Output::write($message."\n");
    }

    public function info(string $message): void
    {
        $this->line(XML_ANSI::INFO.$message.ANSI::RESET);
    }

    public function warning(string $message): void
    {
        $this->line(XML_ANSI::WARNING.$message.ANSI::RESET);
    }

    public function error(string $message): void
    {
        $this->line(XML_ANSI::ERROR.$message.ANSI::RESET);
    }

    public function formatted(string $message, bool $newLine = true): void
    {
        $startTags = [
            '<info>' => XML_ANSI::INFO,
            '<warning>' => XML_ANSI::WARNING,
            '<error>' => XML_ANSI::ERROR,
            '<comment>' => XML_ANSI::COMMENT,
            '<reset>' => XML_ANSI::RESET,

            '<red>' => ANSI::RED,
            '<green>' => ANSI::GREEN,
            '<blue>' => ANSI::BLUE,
            '<yellow>' => ANSI::YELLOW,
            '<magenta>' => ANSI::MAGENTA,
            '<cyan>' => ANSI::CYAN,
        ];

        $endTags = [
            '</info>' => XML_ANSI::RESET,
            '</warning>' => XML_ANSI::RESET,
            '</error>' => XML_ANSI::RESET,
            '</comment>' => XML_ANSI::RESET,
            '</reset>' => XML_ANSI::RESET,
            '</>' => XML_ANSI::RESET,

            '</red>' => ANSI::RESET,
            '</green>' => ANSI::RESET,
            '</blue>' => ANSI::RESET,
            '</yellow>' => ANSI::RESET,
            '</magenta>' => ANSI::RESET,
            '</cyan>' => ANSI::RESET,
        ];

        $formatted = str_replace(array_keys($startTags), array_values($startTags), $message);
        $formatted = str_replace(array_keys($endTags), array_values($endTags), $formatted);

        if ($newLine) {
            $this->line($formatted);
        } else {
            $this->write($formatted);
        }
    }

    /** @example $this->line('Hello ' . $this->ask('Name')); */
    public function ask(string $question, string $default = ''): string
    {
        return Input::readline(ANSI::YELLOW."$question: ".ANSI::RESET) ?: $default;
    }
}

trait AccessesArguments
{
    protected function options(): array
    {
        return $this->options;
    }

    protected function arguments(): array
    {
        return $this->arguments;
    }

    protected function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    protected function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]) || isset(array_flip(array_values($this->arguments))[$name]);
    }

    protected function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    protected function getArgument(string $name, mixed $default = null): mixed
    {
        return $this->arguments[$name] ?? $this->getArgumentByValue($name) ?? $default;
    }

    private function getArgumentByValue(string $value): ?string
    {
        $index = array_flip(array_values($this->arguments))[$value] ?? null;

        return $this->arguments[$index] ?? null;
    }

    private static function parseOptions(array $options): array
    {
        $formatted = [];
        foreach ($options as $index => $option) {
            $option = ltrim($option, '-');
            if (str_contains($option, '=')) {
                $parts = explode('=', $option);
                $formatted[$parts[0]] = $parts[1];
            } else {
                $formatted[$option] = true;
            }
        }

        return $formatted;
    }

    private static function parseArguments(array $arguments): array
    {
        $formatted = [];
        foreach ($arguments as $index => $argument) {
            if (str_contains($argument, '=')) {
                $parts = explode('=', $argument);
                $formatted[$parts[0]] = $parts[1];
            } else {
                $formatted[$index] = $argument;
            }
        }

        return $formatted;
    }

    private static function parseCommandArguments(): array
    {
        global $argc;
        global $argv;

        $options = [];
        $arguments = [];

        for ($i = 1; $i < $argc; $i++) {
            if (str_starts_with($argv[$i], '-')) {
                $options[] = $argv[$i];
            } else {
                $arguments[] = $argv[$i];
            }
        }

        return [self::parseOptions($options), self::parseArguments($arguments)];
    }
}

class Output
{
    public static function write(string $string): void
    {
        file_put_contents('php://output', $string);
    }
}

class Input
{
    public static function readline(?string $prompt = null): string
    {
        return readline($prompt);
    }

    public static function getline(): string
    {
        return trim(fgets(Console::INPUT));
    }
}

class Command
{
    use AccessesArguments;
    use InteractsWithIO;

    protected Output $output;

    protected array $options;
    protected array $arguments;

    protected function __construct()
    {
        $this->output = new Output();

        [$this->options, $this->arguments] = $this->parseCommandArguments();
    }

    public static function main(Closure $logic): int
    {
        $command = new static();

        $logic = $logic->bindTo($command, static::class);

        return $logic() ?? 0;
    }
}

class Dumper
{
    public static int $arrayBreakLevel = 2;

    const INDENT = '  ';
    const ARRAY_OPEN = ANSI::WHITE.'['.ANSI::RESET;
    const ARRAY_CLOSE = ANSI::WHITE.']'.ANSI::RESET;
    const STRING_OPEN = ANSI::BLUE."'".ANSI::GREEN;
    const STRING_CLOSE = ANSI::BLUE."'".ANSI::RESET;
    const INTEGER_OPEN = ANSI::YELLOW;
    const INTEGER_CLOSE = ANSI::RESET;
    const BOOLEAN_OPEN = ANSI::RED;
    const BOOLEAN_CLOSE = ANSI::RESET;
    const OBJECT_OPEN = ANSI::YELLOW;
    const OBJECT_CLOSE = ANSI::RESET;
    const NULL = ANSI::RED.'null'.ANSI::RESET;

    protected int $indentationLevel = 0;
    protected bool $inOpenArray = false;

    public static function highlight(mixed $data): string
    {
        return (new static())->runHighlighter($data);
    }

    protected function runHighlighter(mixed $data): string
    {
        if (is_null($data)) {
            return $this->null($data);
        }
        if (is_string($data)) {
            return $this->string($data);
        }
        if (is_int($data)) {
            return $this->int($data);
        }
        if (is_bool($data)) {
            return $this->bool($data);
        }
        if (is_array($data)) {
            return $this->array($data);
        }
        if (is_object($data)) {
            return static::OBJECT_OPEN.$data::class.static::OBJECT_CLOSE;
        }

        return (string) $data;
    }

    protected function null(?string $value): string
    {
        return static::NULL;
    }

    protected function string(string $value): string
    {
        return static::STRING_OPEN.$value.static::STRING_CLOSE;
    }

    protected function int(int $value): string
    {
        return static::INTEGER_OPEN.$value.static::INTEGER_CLOSE;
    }

    protected function bool(bool $value): string
    {
        return static::BOOLEAN_OPEN.($value ? 'true' : 'false').static::BOOLEAN_CLOSE;
    }

    protected function array(array $array): string
    {
        $this->indentationLevel++;
        if ($this->indentationLevel >= static::$arrayBreakLevel - 1) {
            $this->inOpenArray = true;
        }
        $parts = [];
        foreach ($array as $key => $value) {
            if ($this->inOpenArray) {
                $indent = str_repeat(self::INDENT, $this->indentationLevel);
            } else {
                $indent = '';
            }
            if (is_int($key)) {
                $parts[] = $indent.$this->runHighlighter($value);
            } else {
                $parts[] = $indent.$this->string($key).' => '.$this->runHighlighter($value);
            }
        }
        if ($this->inOpenArray) {
            $this->indentationLevel--;
            $indent = str_repeat(self::INDENT, $this->indentationLevel);

            return static::ARRAY_OPEN."\n".implode(",\n", $parts)."\n$indent".static::ARRAY_CLOSE;
        } else {
            return static::ARRAY_OPEN.''.implode(', ', $parts).''.static::ARRAY_CLOSE;
        }
    }
}

if (! function_exists('main')) {
    function main(Closure $logic): int
    {
        return Command::main($logic);
    }
}

if (! function_exists('dump')) {
    function dump(mixed $value, bool $highlight = false): void
    {
        if ($highlight) {
            echo Dumper::highlight($value)."\n";
        } else {
            var_dump($value);
        }
    }
}

if (! function_exists('dd')) {
    function dd(mixed $value, bool $highlight = false): never
    {
        dump($value, $highlight);
        exit(1);
    }
}

if (! function_exists('task')) {
    /**
     * Create a self-contained task that does something, then reports the execution time.
     * You can bypass all tasks by setting the environment variable SKIP_TASKS to true.
     * This is great for skipping long tasks when testing your script during coding.
     *
     * // Todo add buffer parameter to disable buffering, in case live output is needed?
     */
    function task(string $name, callable $task): void
    {
        $timeStart = microtime(true);

        global $argv;
        if (in_array('--skip-tasks', $argv)) {
            putenv('SKIP_TASKS=true');
            $setLocation = 'option';
        }

        Output::write(ANSI::GREEN.'Running task '.ANSI::YELLOW."$name".ANSI::GREEN.'...'.ANSI::RESET.' ');
        if (! getenv('SKIP_TASKS')) {
            ob_start();
            $task();
            $buffer = ob_get_clean();
            $time = round((microtime(true) - $timeStart) * 1000, 2);
            Output::write(ANSI::GREEN.'Done! '.ANSI::GRAY."(took {$time}ms)"." \n".ANSI::RESET);
        } else {
            $setLocation = $setLocation ?? 'environment variable';
            Output::write(ANSI::YELLOW.'Skipped '.ANSI::GRAY."(as set in $setLocation)\n".ANSI::RESET);
        }
        if (! empty($buffer)) {
            foreach (explode("\n", trim($buffer)) as $line) {
                Output::write("  $line\n");
            }
        }
    }
}

/**
 * Cherry-picked from the Laravel Str class.
 *
 * @link https://github.com/laravel/framework/blob/11.x/src/Illuminate/Support/Str.php
 *
 * @license MIT License
 */
class Str
{
    /**
     * The cache of snake-cased words.
     */
    protected static array $snakeCache = [];

    /**
     * The cache of camel-cased words.
     */
    protected static array $camelCache = [];

    /**
     * The cache of studly-cased words.
     */
    protected static array $studlyCache = [];

    /**
     * Convert a string to kebab case.
     *
     * @param  string  $value
     */
    public static function kebab($value): string
    {
        return static::snake($value, '-');
    }

    /**
     * Convert a string to snake case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * Convert the given string to lower-case.
     *
     * @param  string  $value
     */
    public static function lower($value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Convert a value to camel case.
     */
    public static function camel(string $value): string
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }
    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     */
    public static function studly($value): string
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $words = explode(' ', static::replace(['-', '_'], ' ', $value));

        $studlyWords = array_map(fn ($word) => static::ucfirst($word), $words);

        return static::$studlyCache[$key] = implode($studlyWords);
    }

    /**
     * Returns the portion of the string specified by the start and length parameters.
     */
    public static function substr(string $string, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
    {
        return mb_substr($string, $start, $length, $encoding);
    }

    /**
     * Replace the given value in the given string.
     *
     * @param  string|iterable<string>  $search
     * @param  string|iterable<string>  $replace
     * @param  string|iterable<string>  $subject
     * @return string|string[]
     */
    public static function replace(array|string $search, array|string $replace, array|string $subject, bool $caseSensitive = true): array|string
    {
        return $caseSensitive
            ? str_replace($search, $replace, $subject)
            : str_ireplace($search, $replace, $subject);
    }

    /**
     * Make a string's first character uppercase.
     */
    public static function ucfirst(string $string): string
    {
        return static::upper(static::substr($string, 0, 1)).static::substr($string, 1);
    }

    /**
     * Split a string into pieces by uppercase characters.
     *
     * @return string[]
     */
    public static function ucsplit(string $string): array
    {
        return preg_split('/(?=\p{Lu})/u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Convert the given string to upper-case.
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Convert the given string to proper case.
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert the given string to proper case for each word.
     */
    public static function headline(string $value): string
    {
        $parts = explode(' ', $value);

        $parts = count($parts) > 1
            ? array_map([static::class, 'title'], $parts)
            : array_map([static::class, 'title'], static::ucsplit(implode('_', $parts)));

        $collapsed = static::replace(['-', '_', ' '], '_', implode('_', $parts));

        return implode(' ', array_filter(explode('_', $collapsed)));
    }
}

// Main logic

class Commands
{
    use InteractsWithIO;

    /**
     * Display the help screen.
     */
    public function help(): void
    {
        $this->formatted('<info>String Tools CLI</info> <comment>--</comment> <warning>Usage:</warning> string-tools <command> [args]');

        foreach (Commands::list() as $command => $description) {
            $this->formatted("  <warning>$command</warning> <comment>- $description</comment>");
        }
    }

    /** Convert a string to kebab-case. */
    public function kebab(string $string): string
    {
        return Str::kebab($string);
    }

    /** Convert a string to snake_case. */
    public function snake(string $string): string
    {
        return Str::snake($string);
    }

    /** Convert a string to camelCase. */
    public function camel(string $string): string
    {
        return Str::camel($string);
    }

    // #[Alias: 'pascal']
    /** Convert a string to StudlyCaps/PascalCase. */
    public function studly(string $string): string
    {
        return Str::studly($string);
    }

    /** Convert a string to lowercase. */
    public function lower(string $string): string
    {
        return Str::lower($string);
    }

    /** Convert a string to UPPERCASE. */
    public function upper(string $string): string
    {
        return Str::upper($string);
    }

    /** Convert a string to Title Case. */
    public function title(string $string): string
    {
        return Str::title($string);
    }

    /** Convert a string to Headline Case. */
    public function headline(string $string): string
    {
        return Str::headline($string);
    }

    /** @return array<string, string> Command name over description */
    public static function list(): array
    {
        // Get all public methods in this class that are not inherited or static

        $reflector = new ReflectionClass(__CLASS__);
        $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);

        // Get file name and start line of the class itself
        $classFileName = $reflector->getFileName();
        $classStartLine = $reflector->getStartLine();
        $classEndLine = $reflector->getEndLine();

        $commands = [];
        foreach ($methods as $method) {
            // Check if method is declared in the same file as the class and within the class definition lines
            if ($method->class === __CLASS__
                && ! $method->isStatic()
                && $method->getFileName() === $classFileName
                && $method->getStartLine() >= $classStartLine
                && $method->getStartLine() <= $classEndLine) {

                $commands[$method->name] = static::parseDocComment($method->getDocComment() ?: 'No description provided.');
            }
        }

        return $commands;
    }

    protected static function parseDocComment(string $docComment): string
    {
        $lines = explode("\n", $docComment);
        $description = '';
        foreach ($lines as $line) {
            $line = trim($line, "/* \t");
            if (str_starts_with($line, '@')) {
                break;
            }
            $description .= $line;
        }

        return $description;
    }
}

// Entry point

Command::main(function (): int {
    /** @var Command $this */
    $commands = new Commands();
    $commandList = array_keys(Commands::list());

    if ($this->hasArgument(0) && in_array($this->getArgument(0), $commandList)) {
        $command = $this->getArgument(0);
    } else {
        $command = 'help';
    }

    // Merge all arguments into a single string
    $args = $this->arguments();
    // Unset the command name
    unset($args[0]);
    $args = implode(' ', $args);

    try {
        $call = $commands->$command(...array_filter([$args]));
    } catch (ArgumentCountError $exception) {
        $message = $exception->getMessage();

        // Remove file and class name from error message
        $message = preg_replace('/passed in .* on line \d+/', 'passed', $message);
        $message = str_replace(['function Commands::', '()'], ["command '", "'"], $message);
        $message = '<error>'.str_replace("', ", "'</error><comment> - </comment><warning>", $message).'</warning>';

        $this->formatted($message);
        $this->line("Usage: string-tools $command [args]");

        return 1;
    }

    if (is_string($call)) {
        $this->line($call);

        return 0;
    }

    return $call ?? 0;
});

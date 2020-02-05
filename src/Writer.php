<?php declare(strict_types=1);

namespace NotSoSimple;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final class Writer
{
    public static bool $quiet = false;
    public static bool $noColor = false;
    public static bool $noProgressBar = false;
    public static OutputInterface $output;

    private static ?ProgressBar $pb = null;

    public static function write(string $text): void
    {
        if (static::$quiet) {
            return;
        }
        static::$output->write(static::sanitize($text));
    }

    public static function writeln(string $text): void
    {
        if (static::$quiet) {
            return;
        }
        static::$output->writeln(static::sanitize($text));
    }

    public static function pbStart(int $max = 1): void
    {
        if (static::$quiet || static::$noProgressBar) {
            return;
        }

        static::$pb = new ProgressBar(static::$output, $max);
    }

    public static function pbAdvance(int $step = 1): void
    {
        if (static::$quiet || static::$noProgressBar) {
            return;
        }

        static::$pb->advance($step);
    }

    public static function pbFinish(): void
    {
        if (static::$quiet || static::$noProgressBar) {
            return;
        }

        static::$pb->finish();

        static::$pb = null;

        static::$output->writeln('');
    }

    public static function info(string $info): void
    {
        static::$output->writeln(static::sanitize("<info>{$info}</info>"));
    }

    public static function comment(string $comment): void
    {
        static::$output->writeln(static::sanitize("<comment>{$comment}</comment>"));
    }

    public static function error(string $error): void
    {
        static::$output->writeln(static::sanitize("<error>{$error}</error>"));
    }

    public static function escape(string $text): string
    {
        $of = new OutputFormatter();
        return $of->escape($text);
    }

    private static function sanitize(string $text): string
    {
        if (! static::$noColor) {
            return $text;
        }

        $text = preg_replace('/<\/?\w+>/', '', $text);

        if (is_null($text) || is_array($text)) {
            return '';
        }

        return $text;
    }
}

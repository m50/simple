<?php

declare(strict_types=1);

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

    public static function writeln(string $text): void
    {
        if (static::$quiet) {
            return;
        }

        static::$output->writeln(static::sanitize($text));
    }

    public static function startProgressBar(int $max = 1): void
    {
        if (static::$quiet || static::$noProgressBar) {
            return;
        }

        static::$pb = new ProgressBar(static::$output, $max);
    }

    public static function advanceProgressBar(int $step = 1): void
    {
        if (static::$quiet || static::$noProgressBar) {
            return;
        }

        if (! is_null(static::$pb)) {
            static::$pb->advance($step);
        }
    }

    public static function finishProgressBar(): void
    {
        if (static::$quiet || static::$noProgressBar) {
            return;
        }
        if (! is_null(static::$pb)) {
            static::$pb->finish();
        }

        static::$pb = null;

        static::$output->writeln('');
    }

    public static function comment(string $comment): void
    {
        if (static::$quiet) {
            return;
        }

        static::$output->writeln(static::sanitize("<comment>{$comment}</comment>"));
    }

    public static function escape(string $text): string
    {
        return (new OutputFormatter())->escape($text);
    }

    /**
     * Remove all tags from string.
     *
     * @param string $text
     * @return string
     */
    private static function sanitize(string $text): string
    {
        if (! static::$noColor) {
            return $text;
        }

        $text = preg_replace('/<\/?[a-zA-Z=,]*>/', '', $text);

        if (is_null($text)) {
            return '';
        }

        return $text;
    }
}

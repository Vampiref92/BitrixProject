<?php

namespace ProjectName\Events;

class Minimize
{
    public static function minimizeHtml(&$content): void
    {
        $content = preg_replace('~>\s*\n\s*<~', '><', $content);
    }
}
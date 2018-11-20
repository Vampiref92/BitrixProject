<?php

namespace ProjectName\Common\Events;

class Optimize
{
    /**
     * @param $content
     */
    public static function minimizeHtml(&$content): void
    {
        $content = preg_replace('~>\s*\n\s*<~', '><', $content);
    }

    /**
     * @param $content
     */
    public static function cssToBottom(&$content): void
    {
        preg_match_all('|<link[^>]*type=[\'"]text/css[\'"][^>]*>|i', $content, $matches);
        if (!empty($matches) && !empty($matches[0])) {
            $cssList = [];
            foreach ($matches[0] as $match) {
                if (strpos($match, 'data-skip-moving') === false) {
                    $cssList[] = $match;
                    $content = str_replace($match, '', $content);
                }
            }
            if (!empty($cssList)) {
                $content = str_replace('</body>', implode('', $cssList) . '</body>', $content);
            }
        }
    }

    /**
     * @param $content
     */
    public static function deleteKernelCss(&$content): void
    {
        global $USER, $APPLICATION;
        if ($APPLICATION->GetProperty('save_kernel') === 'Y'
            || (\is_object($USER) && $USER->IsAuthorized())
            || strpos($APPLICATION->GetCurDir(), '/bitrix/') !== false) {
            return;
        }

        $arPatternsToRemove = [
            '|<link.*href=[\'"].*kernel_main/kernel_main[^\'"]*\.css\?\d+[\'"][^>]*>|i',
            '|<link.*href=[\'"].*bitrix/js/main/core/css/core[^\'"]+\.css[\'"][^>]*>|i',
        ];

        $content = preg_replace($arPatternsToRemove, '', $content);
        $content = preg_replace("/\n{2,}/", "\n\n", $content);
    }
}
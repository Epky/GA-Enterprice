<?php

namespace App\Helpers;

class HelpTextHelper
{
    /**
     * Get help text for a specific field
     *
     * @param string $section Section name (e.g., 'product', 'pricing')
     * @param string $field Field name (e.g., 'name', 'price')
     * @return string|null
     */
    public static function get(string $section, string $field): ?string
    {
        $helpConfig = config('help');
        
        if (!isset($helpConfig[$section])) {
            return null;
        }
        
        return $helpConfig[$section][$field] ?? null;
    }

    /**
     * Get all help text for a section
     *
     * @param string $section Section name
     * @return array
     */
    public static function getSection(string $section): array
    {
        return config("help.{$section}", []);
    }

    /**
     * Check if help text exists for a field
     *
     * @param string $section Section name
     * @param string $field Field name
     * @return bool
     */
    public static function has(string $section, string $field): bool
    {
        return self::get($section, $field) !== null;
    }

    /**
     * Render help tooltip component
     *
     * @param string $section Section name
     * @param string $field Field name
     * @param string $position Tooltip position (top, bottom, left, right)
     * @return string
     */
    public static function tooltip(string $section, string $field, string $position = 'top'): string
    {
        $content = self::get($section, $field);
        
        if (!$content) {
            return '';
        }
        
        return view('components.help-tooltip', [
            'content' => $content,
            'position' => $position
        ])->render();
    }
}

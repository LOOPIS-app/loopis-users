<?php
/**
 * Standard LOOPIS mail footer
 * 
 * @return string HTML footer
 */

if (!defined('ABSPATH')) {
    exit;
}

function get_loopis_mail_footer(string $text = ''): string {
    if ($text === '') {
        $text = 'Ett mail från LOOPIS.app';
    }

    $icon = LOOPIS_THEME_HQ_URI . '/assets/img/LOOPIS_icon.png';

    $html = '<table style="border-collapse: collapse;border-top: 1px solid">'
        . '<tbody>'
        . '<tr>'
        . '<td style="padding: 5px 5px 0 0"><img style="height: 32px" src="' . esc_url($icon) . '" alt="LOOPIS_icon" /></td>'
        . '<td style="padding: 5px 10px 0 0">'
        . '<p style="font-size: 11px;font-style: italic;margin: 0;line-height: 1.2">' . esc_html($text) . '</p>'
        . '</td>'
        . '</tr>'
        . '</tbody>'
        . '</table>';

    return $html;
}
